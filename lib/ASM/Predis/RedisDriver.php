<?php

namespace ASM\Predis;

use ASM\AsmException;
use ASM\Driver;
use ASM\IdGenerator;
use ASM\Serializer;
use ASM\SessionManager;
use ASM\LostLockException;
use ASM\Encrypter;
use ASM\FailedToAcquireLockException;
use ASM\SessionConfig;
use ASM\Session;
use ASM\RedisKeyGenerator;
use ASM\Redis\StandardRedisKeyGenerator;
use Predis\Client as RedisClient;

class RedisDriver implements Driver
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

    /** @var StandardRedisKeyGenerator  */
    private $keyGenerator;

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

        if ($serializer) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new \ASM\Serializer\PHPSerializer();
        }

        if ($idGenerator) {
            $this->idGenerator = $idGenerator;
        }
        else {
            $this->idGenerator = new \ASM\IdGenerator\RandomLibIdGenerator();
        }

        if ($keyGenerator) {
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
        $sessionId,
        Encrypter $encrypter,
        SessionManager $sessionManager,
        $userProfile = null
    ) {
        $sessionId = (string)$sessionId;

        $lockToken = $this->acquireLockIfRequired($sessionId, $sessionManager);

        $dataKey = $this->keyGenerator->generateSessionDataKey($sessionId);
        $encryptedDataString = $this->redisClient->get($dataKey);
        if ($encryptedDataString == null) {
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

        return new RedisSession(
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

    /**
     * @param $sessionId
     * @param SessionManager $sessionManager
     * @return null|string
     */
    private function acquireLockIfRequired($sessionId, SessionManager $sessionManager)
    {
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
     * @return RedisSession
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
     */
    public function createSession(Encrypter $encrypter, SessionManager $sessionManager, $userProfile = null)
    {
        $sessionLifeTime = $sessionManager->getSessionConfig()->getLifetime();
        $initialData = [];
        $profiles = [];
        if ($userProfile) {
            $profiles = [$userProfile];
        }
        $initialData['profiles'] = $profiles;
        $initialData['data'] = [];
        $dataString = $this->serializer->serialize($initialData);

        $lockToken = null;

        for ($count = 0; $count < 10; $count++) {
            $sessionId = $this->idGenerator->generateSessionID();
            $lockToken = $this->acquireLockIfRequired($sessionId, $sessionManager);
            $dataKey = $this->keyGenerator->generateSessionDataKey($sessionId);
            $set = $this->redisClient->set(
                $dataKey,
                $dataString,
                'EX',
                $sessionLifeTime,
                'NX'
            );

            if ($set) {
                return new RedisSession(
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

    /**
     * @param $sessionID
     * @return mixed|void
     */
    public function deleteSessionByID($sessionID)
    {
        $dataKey = $this->keyGenerator->generateSessionDataKey($sessionID);
        $this->redisClient->del($dataKey);
    }


    public function save(Session $session, Encrypter $encrypter, $saveData, $existingProfiles)
    {
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
            $dataString,
            'EX',
            $sessionLifeTime
        );

        if (!$written) {
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

    /**
     * @param $sessionID
     * @return bool
     */
    public function validateLock($sessionID, $lockToken)
    {
        if (!$lockToken) {
            return false;
        }
        
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $storedLockNumber = $this->redisClient->get($lockKey);
        
        if ($storedLockNumber === $lockToken) {
            return true;
        }

        return false;
    }


    /**
     * @param $sessionID
     * @param $lockTimeMS
     * @param $acquireTimeoutMS
     * @return string LockToken
     * @throws FailedToAcquireLockException
     */
    public function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS)
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $lockToken = $this->idGenerator->generateSessionID();
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
            } else if ($giveUpTime < ((int)(microtime(true) * 1000))) {
                throw new FailedToAcquireLockException(
                    "Failed to acquire lock for session $sessionID"
                );
            }
        } while ($finished === false);

        return $lockToken;
    }

    /**
     * @param $sessionId
     * @param $lockToken
     * @return mixed
     * @throws LostLockException
     */
    public function releaseLock($sessionId, $lockToken)
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionId);
        $result = $this->redisClient->eval(self::UNLOCK_SCRIPT, 1, $lockKey, $lockToken);
        
        // TODO - should
//        if ($result !== 1) {
//            throw new LostLockException(
//                "Releasing lock revealed lock had been lost."
//            );
//        }

        return $result;
    }

    /**
     * @param $sessionID
     * @return mixed|void
     */
    public function forceReleaseLockByID($sessionID)
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $this->redisClient->del($lockKey);
    }

    /**
     * @param $sessionID
     * @param $lockToken
     * @param $lockTimeMS
     * @return mixed
     * @throws LostLockException
     */
    public function renewLock($sessionID, $lockToken, $lockTimeMS)
    {
        $lockKey = $this->keyGenerator->generateLockKey($sessionID);
        $result = $this->redisClient->eval(
            self::RENEW_LOCK_SCRIPT,
            1,
            $lockKey,
            $lockToken,
            $lockTimeMS
        );
 
        if ($result != "OK") {
            throw new LostLockException("Failed to renew lock.");
        }

        return $result;
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



    /**
     * @param $sessionID
     * @param $index
     * @return int
     */
    public function get($sessionID, $index)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->hget($key, $index);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $value
     * @return int
     */
    public function set($sessionID, $index, $value)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->hset($key, $index, $value);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $increment
     * @return int
     */
    public function increment($sessionID, $index, $increment)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->hincrby($key, $index, $increment);
    }

    /**
     * @param $sessionID
     * @param $index
     * @return array
     */
    public function getList($sessionID, $index)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);

        return $this->redisClient->lrange($key, 0, -1);
    }

    /**
     * @param $sessionID
     * @param $index
     * @param $value
     * @return int
     */
    public function appendToList($sessionID, $index, $value)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);
        
        if (is_array($value)) {
            return $this->redisClient->rpush($key, $value);
        }
        else {
            return $this->redisClient->rpush($key, [$value]);
        }
    }

    /**
     * @param $sessionID
     * @param $index
     * @return int
     */
    public function clearList($sessionID, $index)
    {
        $key = $this->keyGenerator->generateAsyncKey($sessionID, $index);
        return $this->redisClient->del($key);
    }
}