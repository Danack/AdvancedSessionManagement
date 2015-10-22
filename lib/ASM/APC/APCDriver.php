<?php

namespace ASM\APC;

use ASM\AsmException;
use ASM\Driver;
use ASM\IdGenerator;
use ASM\Serializer;
use ASM\SessionManager;
use ASM\LostLockException;
use ASM\FailedToAcquireLockException;
use ASM\SessionConfig;


/**
 *
 *
 * SessionDataKey - if this exists the session exists
 *
 * ZombieKey - if this exists, the value it contains is the session ID that replaced
 * the session ID that was destroyed.
 *
 * LockKey - Used to hold implement a lock for the session.
 *
 * Profile key - stores a list of the allowed user-profile strings that can access
 *               this session.
 *
 */





class APCDriver implements Driver
{

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

    private $prefix;

 
    function __construct(
        Serializer $serializer = null,
        IdGenerator $idGenerator = null,
        $prefix = '')
    {

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

        $this->prefix = $prefix;
    }


    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionId
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
     * @return null|string
     */
    function openSession($sessionId, SessionManager $sessionManager, $userProfile = null)
    {
        $sessionId = (string)$sessionId;
        $lockToken = $this->acquireLockIfRequired($sessionId, $sessionManager);
        $dataKey = $this->generateSessionDataKey($sessionId);
        $dataString = apc_fetch($dataKey, $success);

        if ($success == false) {
            return null;
        }

        $fullData = $this->serializer->unserialize($dataString);
        $currentProfiles = [];
        $data = [];

        if (array_key_exists('profiles', $fullData)) {
            $currentProfiles = $fullData['profiles'];
        }

        if (array_key_exists('data', $fullData)) {
            $data = $fullData['data'];
        }

        $currentProfiles = $sessionManager->performProfileSecurityCheck(
            $userProfile,
            $currentProfiles
        );

        return new APCSession(
            $sessionId,
            $this,
            $sessionManager,
            $data,
            $currentProfiles,
            $lockToken
        );
    }

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
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return APCSession
     * @throws AsmException
     * @throws LostLockException
     */
    function createSession(SessionManager $sessionManager, $userProfile = null)
    {
        $sessionLifeTime = $sessionManager->getLifetime();
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
            $dataKey = $this->generateSessionDataKey($sessionId);
            $set = apc_store(
                $dataKey,
                $dataString,
                $sessionLifeTime
            );

            if ($set) {
                return new APCSession(
                    $sessionId,
                    $this,
                    $sessionManager,
                    [],
                    $profiles,
                    $lockToken
                );
            }

            if ($lockToken !== null) {
                $this->releaseLock($sessionId, $lockToken);
            }
        }

        throw new AsmException(
            "Failed to createSession.",
            \ASM\Driver::E_SESSION_ID_CLASS
        );
    }

    /**
     * @param $sessionID
     * @return mixed|void
     */
    function deleteSession($sessionID)
    {
        $dataKey = $this->generateSessionDataKey($sessionID);
        apc_delete($dataKey);
    }

    /**
     * @param $sessionId
     * @param $saveData
     * @param $existingProfiles
     */
    function save($sessionId, $saveData, $existingProfiles, SessionManager $sessionManager)
    {
        $data = [];
        $data['data'] = $saveData;
        $data['profiles'] = $existingProfiles;

        $dataKey = $this->generateSessionDataKey($sessionId);
        $dataString = $this->serializer->serialize($data);
        $lifetime = $sessionManager->getSessionConfig()->getLifetime();

        $stored = apc_store($dataKey, $dataString, $lifetime);
        
        if (!$stored) {
            throw new AsmException("Failed to save data.");
        }
    }

    /**
     *
     */
    function close()
    {
    }

//
//    /**
//     * Destroy expired sessions.
//     */
//    function destroyExpiredSessions() {
//        // Nothing to do for redis driver as redis automatically clears dead keys
//    }
//


//    /**
//     * @param $sessionID
//     * @return bool
//     */
//    function validateLock($sessionID) {
//        if (!$this->lockNumber) {
//            return false;
//        }
//        
//        $lockKey = generateLockKey($sessionID);
//        $storedLockNumber = $this->redisClient->get($lockKey);
//        
//        if ($storedLockNumber === $this->lockNumber) {
//            return true;
//        }
//
//        return false;
//    }


    /**
     * @param $sessionId
     * @param $lockTimeMS
     * @param $acquireTimeoutMS
     * @return string
     * @throws FailedToAcquireLockException
     */
    function acquireLock($sessionId, $lockTimeMS, $acquireTimeoutMS)
    {
        $lockKey = $this->generateLockKey($sessionId);
        $lockToken = $this->idGenerator->generateSessionID();
        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;

        $lockTimeSeconds = intval(ceil($lockTimeMS / 1000));
        
        do {
            $added = apc_add($lockKey, $lockToken, $lockTimeSeconds);
            if ($added === true) {
                return $lockToken;
            }
        } while ($giveUpTime > ((int)(microtime(true) * 1000)));

        throw new FailedToAcquireLockException(
            "Failed to acquire lock for session $sessionId"
        );
        
        
    }

    /**
     * @param $sessionId
     * @return bool|mixed
     */
    function releaseLock($sessionId, $lockToken)
    {
        $lockKey = $this->generateLockKey($sessionId);
        $success = false;

        $storedLockToken = apc_fetch($lockKey, $success);

        if ($success == false) {
            throw new LostLockException(
                "Releasing lock revealed lock had been lost."
            );
        }
        //TODO - there is a race condition here between this code
        //and force releasing a lock
        apc_delete($lockKey);

        if ($storedLockToken !== $lockToken) {
            throw new LostLockException(
                "Releasing lock revealed lock had been lost."
            );
        }
    }

    /**
     * @param $sessionID
     * @return mixed|void
     */
    function forceReleaseLock($sessionID)
    {
        $lockKey = $this->generateLockKey($sessionID);
        apc_delete($lockKey);
    }

    /**
     * @param $sessionID
     * @param $lockToken
     * @param $lockTimeMS
     * @return mixed
     * @throws AsmException
     */
    function renewLock($sessionId, $lockToken, $lockTimeMS)
    {
        $lockKey = $this->generateLockKey($sessionId);
        $storedLockToken = apc_fetch($lockKey);

        if ($storedLockToken !== $lockToken) {
            throw new LostLockException(
                "Renewing lock revealed lock had been lost."
            );
        }

        $lockTimeSeconds = intval(ceil($lockTimeMS / 1000));
        $result = apc_store($lockKey, $lockToken, $lockTimeSeconds);
        
        if (!$result) {
            throw new LostLockException(
                "Failed to renew lock"
            );
        }
    }


    /**
     * @param $sessionId
     * @return string
     *
     */
    function generateSessionDataKey($sessionId)
    {
        return $this->prefix.'session:'.$sessionId;
    }

    function generateZombieKey($dyingSessionId)
    {
        return $this->prefix.'zombie:'.$dyingSessionId;
    }

    function generateLockKey($sessionId)
    {
        return $this->prefix.'lock:'.$sessionId;
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



}