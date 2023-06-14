<?php

namespace Asm\Predis;

use Asm\AsmException;
use Asm\Driver;
use Asm\IdGenerator;
use Asm\Serializer;
use Asm\SessionManager;
use Asm\LostLockException;
use Asm\Encrypter;
use Asm\FailedToAcquireLockException;
use Asm\SessionConfig;
use Asm\Session;
use Asm\RedisKeyGenerator;
use Asm\Redis\StandardRedisKeyGenerator;
use Asm\Serializer\PHPSerializer;
use Predis\Client as RedisClient;
use Asm\Profile\SimpleProfile;

class PredisDriver implements Driver
{
    /**
     * @var \Predis\Client
     */
    private $redisClient;

    /**
     * @var string The lock key, this is consistent per sessionID.
     */
    protected $lockKey;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var IdGenerator
     */
    private $idGenerator;

    private RedisKeyGenerator $keyGenerator;

    /**
     * Redis lua script for releasing a lock. Returns int(1) when the lock was
     * released correctly, otherwise returns 0
     *
     * Todo - upgrade to a fault tolerant distributed version of this.
     * https://github.com/ronnylt/redlock-php/blob/master/src/RedLock.php
     * http://redis.io/topics/distlock
     *
     */
    const UNLOCK_SCRIPT = <<< END
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end
END;

    /**
     * KEYS[1] == lock key
     * ARGV[1] == lock token
     * ARGV[2] == lock time in milliseconds.
     *
     * If the token is correct - renew the session
     * If there is no lock current - renewing the lock is fine
     * Otherwise return error message.
     */
    const RENEW_LOCK_SCRIPT = <<< END
local token = redis.call("get",KEYS[1])
if (token == ARGV[1])
then
    return redis.call("PSETEX",KEYS[1],ARGV[2],ARGV[1])
elseif not token
then
    return "Lock token not found"
else
    return "Lock token not found"
end
END;


    public function __construct(
        RedisClient $redisClient,
        Serializer $serializer = null,
        IdGenerator $idGenerator = null,
        RedisKeyGenerator $keyGenerator = null
    ) {
        $this->redisClient = $redisClient;

        if ($serializer !== null) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new PHPSerializer();
        }

        if ($idGenerator !== null) {
            $this->idGenerator = $idGenerator;
        }
        else {
            $this->idGenerator = new \Asm\IdGenerator\RandomLibIdGenerator();
        }

        if ($keyGenerator !== null) {
            $this->keyGenerator = $keyGenerator;
        }
        else {
            $this->keyGenerator = new StandardRedisKeyGenerator();
        }
    }


    /**
     * @inheritdoc
     */
    public function openSessionByID(
        string $sessionId,
        Encrypter $encrypter,
        SessionManager $sessionManager,
        ?SimpleProfile $userProfile
    ): ?Session {

        $lockToken = $this->acquireLockIfRequired($sessionId, $sessionManager);

        $dataKey = $this->keyGenerator->generateSessionDataKey($sessionId);
        $encryptedDataString = $this->redisClient->get($dataKey);
        if ($encryptedDataString === null) {
            if ($lockToken !== null) {
                $this->releaseLock($sessionId, $lockToken);
            }
            return null;
        }

        $dataString = $encrypter->decrypt($encryptedDataString);

        $fullData = $this->serializer->unserialize($dataString);
        $currentProfiles = [];
        $data = [];

        if (isset($fullData['profiles'])) {
            $currentProfiles = $fullData['profiles'];
        }

        if (isset($fullData['data'])) {
            $data = $fullData['data'];
            //Data was not found?
        }

        $currentProfiles = $sessionManager->performProfileSecurityCheck(
            $userProfile,
            $currentProfiles
        );

        return new PredisSession(
            $sessionId,
            $this,
            $sessionManager,
            $encrypter,
            $data,
            $currentProfiles,
            true,
            $lockToken
        );
    }

    private function acquireLockIfRequired(
        string $sessionId,
        SessionManager $sessionManager
    ): ?string {
        $lockToken = null;
        if ($sessionManager->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $lockToken = $this->acquireLock(
                $sessionId,
                $sessionManager->getSessionConfig()->getLockMilliSeconds(),
                $sessionManager->getSessionConfig()->getMaxLockWaitTimeMilliseconds()
            );
        }

        return $lockToken;
    }

    /**
     * Create a new session
     * @return PredisSession
     * @param SessionManager $sessionManager
     * @param ?SimpleProfile $userProfile
     * @throws AsmException
     */
    public function createSession(
        Encrypter $encrypter,
        SessionManager $sessionManager,
        SimpleProfile $userProfile = null
    ): PredisSession {
        $sessionLifeTime = $sessionManager->getSessionConfig()->getLifetime();
        $initialData = [];
        $profiles = [];
        if ($userProfile !== null) {
            $profiles = [$userProfile];
        }
        $initialData['profiles'] = $profiles;
        $initialData['data'] = [];
        $dataString = $this->serializer->serialize($initialData);

        $lockToken = null;

        for ($count = 0; $count < 10; $count++) {
            $sessionId = $this->idGenerator->generateSessionId();
            $lockToken = $this->acquireLockIfRequired($sessionId, $sessionManager);
            $dataKey = $this->keyGenerator->generateSessionDataKey($sessionId);
            $set = $this->redisClient->set(
                $dataKey,
                $dataString,
                'EX',
                $sessionLifeTime,
                'NX'
            );

            if ($set !== null) {
                return new PredisSession(
                    $sessionId,
                    $this,
                    $sessionManager,
                    $encrypter,
                    [],
                    $profiles,
                    false,
                    $lockToken
                );
            }

            if ($lockToken !== null) {
                $this->releaseLock($sessionId, $lockToken);
            }
        }

        throw new AsmException(
            "Failed to createSession.",
            AsmException::ID_CLASH
        );
    }

    public function deleteSessionByID(string $sessionID): void
    {
        $dataKey = $this->keyGenerator->generateSessionDataKey($sessionID);
        $this->redisClient->del($dataKey);
    }


    public function save(
        Session $session,
        Encrypter $encrypter,
        array $saveData,
        array $existingProfiles
    ): void {
        $sessionID = $session->getSessionId();
        $sessionLifeTime = 3600; // 1 hour

        $data = [];
        $data['data'] = $saveData;
        $data['profiles'] = $existingProfiles;

        $dataKey = $this->keyGenerator->generateSessionDataKey($sessionID);
        $dataString = $this->serializer->serialize($data);

        $encryptedDataString = $encrypter->encrypt($dataString);

        $written = $this->redisClient->set(
            $dataKey,
            $encryptedDataString,
            'EX',
            $sessionLifeTime
        );

        /** @var $written \Predis\Response\Status */

        if ($written->getPayload() !== 'OK') {
            // TODO - handle this more gracefully...
            throw new AsmException("Failed to save data", AsmException::IO_ERROR);
        }
    }

//
//    /**
//     * Destroy expired sessions.
//     */
//    function destroyExpiredSessions() {
//        // Nothing to do for redis driver as redis automatically clears dead keys
//    }


    public function validateLock(string $sessionID, string $lockToken): bool
    {
        // TODO - why was this ever possible
//        if ($lockToken !== null) {
//            return false;
//        }
        
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $storedLockNumber = $this->redisClient->get($lockKey);
        
        if ($storedLockNumber === $lockToken) {
            return true;
        }

        return false;
    }


    public function acquireLock(string $sessionID, int $lockTimeMS, int $acquireTimeoutMS): string
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $lockToken = $this->idGenerator->generateSessionId();
        $finished = false;

        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;

        do {
            $set = $this->redisClient->set(
                $lockKey,
                $lockToken,
                'PX',
                $lockTimeMS,
                'NX'
            );
            /** @var $set \Predis\Response\Status */

            if ($set == "OK") {
                $finished = true;
            }
            else if ($giveUpTime < ((int)(microtime(true) * 1000))) {
                throw new FailedToAcquireLockException(
                    "Failed to acquire lock for session $sessionID"
                );
            }
        } while ($finished === false);

        return $lockToken;
    }

    public function releaseLock(string $sessionId, string $lockToken): int
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionId);
        $result = $this->redisClient->eval(self::UNLOCK_SCRIPT, 1, $lockKey, $lockToken);
        
        // TODO - this should be handled better...
//        if ($result !== 1) {
//            throw new LostLockException(
//                "Releasing lock revealed lock had been lost."
//            );
//        }

        /** @var int $result */
        return $result;
    }


    public function forceReleaseLockByID(string $sessionID): void
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $this->redisClient->del($lockKey);
    }

    public function renewLock(string $sessionID, string $lockToken, int $lockTimeMS): void
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);

        /**
         * @phpstan-ignore-next-line
         *
         * Method Predis\ClientInterface::eval() invoked with 5 parameters, 2-4 required.
         */
        $result = $this->redisClient->eval(
            self::RENEW_LOCK_SCRIPT,
            1,
            $lockKey,
            $lockToken,
            $lockTimeMS
        );

        // TODO - why is this an exception and not an error return?
        if ($result != "OK") {
            throw new LostLockException("Failed to renew lock.");
        }

//        return $result;
    }
    

//
//    /**
//     * @param $sessionID
//     * @return string
//     */
//    function findSessionIDFromZombieID($sessionID) {
//        $zombieKeyName = generateZombieKey($sessionID);
//        $regeneratedSessionID = $this->redisClient->get($zombieKeyName);
//
//        return $regeneratedSessionID;
//    }
//
//
//    /**
//     * @param $dyingSessionID
//     * @param $newSessionID
//     * @param $zombieTimeMilliseconds
//     * @return mixed|void
//     */
//    function setupZombieID($dyingSessionID,  $zombieTimeMilliseconds) {
//        $newSessionID = $sessionID = $this->idGenerator->generateSessionID();;
//        $zombieKey = generateZombieKey($dyingSessionID);
//        $this->redisClient->set(
//            $zombieKey,
//            $newSessionID,
//            'EX',
//            $zombieTimeMilliseconds
//        );
//
//        //TODO - combine this operation with the setting of the zombie key to avoid
//        //any possibility for a race condition.
//
//        //TODO - need to rename all the metadata keys.
//        // or maybe use RENAMENX ?
//        $this->redisClient->rename(
//            generateSessionDataKey($dyingSessionID),
//            generateSessionDataKey($newSessionID)
//        );
//
//        return $newSessionID;
//    }


    public function get(string $sessionID, string $index): ?string
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->hget($key, $index);
    }


    public function set(string $sessionID, string $index, string|int $value): void
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

//        return $this->redisClient->hset($key, $index, $value);
        $this->redisClient->hset($key, $index, $value);
    }


    public function increment(string $sessionID, string $index, int $increment): int
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->hincrby($key, $index, $increment);
    }


    public function getList(string $sessionID, string $index): array
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->lrange($key, 0, -1);
    }


    public function appendToList(string $sessionID, string $index, string|int $value): int
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        // TODO - why do we care about returning the length?
        // That seems to be implementation leakage.

//        if (is_array($value)) {
//            return $this->redisClient->rpush($key, $value);
//        }
//        else {
            return $this->redisClient->rpush($key, [$value]);
//        }
    }

    public function clearList(string $sessionID, string $index): void
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);
//        return $this->redisClient->del($key);
        $this->redisClient->del($key);
    }
}
