<?php

namespace ASM\Redis;

use ASM\AsmException;
use ASM\ConcurrentDriver;

use ASM\IdGenerator;
use ASM\Serializer;
use ASM\SessionManager;
use Predis\Client as RedisClient;


use ASM\SessionManagerInterface;

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

/**
 * @param $sessionID
 * @return string
 *
 */
function generateSessionDataKey($sessionID)
{
    return 'session:'.$sessionID;
}

function generateZombieKey($dyingSessionID)
{
    return 'zombie:'.$dyingSessionID;
}

function generateLockKey($sessionID)
{
    return 'session:'.$sessionID.':lock';
}

function generateProfileKey($sessionID)
{
    return 'session:'.$sessionID.':profile';
}

/**
 * @param $sessionID
 * @return string
 */
function generateAsyncKey($sessionID)
{
    $key = 'session:'.$sessionID.':async';

    return $key;
}



class RedisDriver implements ConcurrentDriver
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

    /**
     * A redis lua script
     *
     * Todo - upgrade to a fault tolerant distributed version of this.
     * https://github.com/ronnylt/redlock-php/blob/master/src/RedLock.php
     * http://redis.io/topics/distlock
     *
     */
    const unlockScript = <<< END
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end
END;


    /* # if the value of key is the same as arg
     * if redis.call("get",KEYS[1]) == ARGV[1]
     * then
     *     # return the result of deleting the key which is
     *     # Integer reply: The number of keys that were removed.
     *     return redis.call("del",KEYS[1])
     * else
     *     #return 0 
     *     return 0
     * 
     *
     */
    function __construct(
        RedisClient $redisClient,
        Serializer $serializer = null,
        IdGenerator $idGenerator = null)
    {
        $this->redisClient = $redisClient;

        if ($serializer) {
            $this->serializer = $serializer;
        } else {
            $this->serializer = new \ASM\Serializer\PHPSerializer();
        }

        if ($idGenerator) {
            $this->idGenerator = $idGenerator;
        } else {
            $this->idGenerator = new \ASM\IdGenerator\RandomLibIdGenerator();
        }
    }


    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionID
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
     * @return null|string
     */
    function openSession($sessionID, SessionManager $sessionManager, $userProfile = null)
    {
        $sessionID = (string)$sessionID;
        
        $dataKey = generateSessionDataKey($sessionID);
        $dataString = $this->redisClient->get($dataKey);
        if ($dataString == null) {
            return null;
        }

        $fullData = $this->serializer->unserialize($dataString);
        $currentProfiles = [];
        $data = [];

        //TODO - this should never be needed?
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
            $sessionID,
            $this,
            $sessionManager,
            $data,
            $currentProfiles
        );
    }

    /**
     * Create a new session
     * @return RedisSession
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @throws AsmException
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

        for ($count = 0; $count < 10; $count++) {
            $sessionID = $this->idGenerator->generateSessionID();
            $dataKey = generateSessionDataKey($sessionID);

            $set = $this->redisClient->set(
                $dataKey,
                $dataString,
                'EX',
                $sessionLifeTime,
                'NX'
            );

            if ($set) {
                return new RedisSession(
                    $sessionID,
                    $this,
                    $sessionManager,
                    [],
                    $profiles
                );
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
        $dataKey = generateSessionDataKey($sessionID);
        $this->redisClient->del($dataKey);
    }

//    /**
//     * @param $sessionID
//     * @return mixed
//     * @throws AsmException
//     */
//    function read($sessionID)
//    {
//        $dataKey = generateSessionDataKey($sessionID);
//        //Todo - replace with atomic script
//        
//        
////        if ($createIfNotExists) {
////            $emptyData = [];
////            $emptyData['profiles'] = [];
////            $emptyData['data'] = [];
////
////            $emptyDataString = $this->serializer->serialize($emptyData);
////            
////            $dataString = $this->redisClient->eval(
////                self::openOrCreateScript,
////                1,
////                $dataKey,
////                $emptyDataString
////            );
////        }
////        else {
//            $dataString = $this->redisClient->get($dataKey);
//        //}
//
//        if ($dataString == null) {
//            return null;
//        }
//
//        $fullData = $this->serializer->unserialize($dataString);
//        $profiles = [];
//        $data = [];
//
//        //TODO - this should never be needed?
//        if (isset($fullData['profiles'])) {
//            $profiles = $fullData['profiles'];
//        }
//
//        if (isset($fullData['data'])) {
//            $data = $fullData['data'];
//        }
//
//        return [$data, $profiles];
//    }


    /**
     * @param $sessionID
     * @param $saveData
     * @param $existingProfiles
     */
    function save($sessionID, $saveData, $existingProfiles)
    {
        $sessionLifeTime = 3600; // 1 hour
        
        $data = [];
        $data['data'] = $saveData;
        $data['profiles'] = $existingProfiles;

        $dataKey = generateSessionDataKey($sessionID);
        $dataString = $this->serializer->serialize($data);
        $this->redisClient->set(
            $dataKey,
            $dataString,
            'EX',
            $sessionLifeTime
        );
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
//     * @return bool|mixed
//     */
//    function releaseLock($sessionID) {
//        if (!$this->lockNumber) {
//            //It is already released
//            return false;
//        }
//        $lockKey = generateLockKey($sessionID);
//        
//        $result = $this->redisClient->eval(self::unlockScript, 1, $lockKey, $this->lockNumber);
//        
//        // TODO - this needs to throw an exception if lockNumber did not match
//        // the value stored in 
//
//        $this->lockNumber = null;
//
//        return $result;
//    }
//
//    function isLocked($sessionID) {
//        return (bool)($this->lockNumber);
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
//    
//    /**
//     * @param $sessionID
//     * @return array
//     */
//    function getData($sessionID) {
//        $data = $this->redisClient->hgetall(generateSessionDataKey($sessionID));
//        
//        return $data;
//    }
//
//    /**
//     * @param $sessionID
//     * @param $milliseconds
//     * @return bool
//     * @throws FailedToAcquireLockException
//     */
//    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS) {
//        $lockKey = generateLockKey($sessionID);
//        //TODO - change to actual random numbers.
//        $lockRandomNumber = "".rand(100000000, 100000000000);
//
//        $finished = false;
//
//        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;
//        
//        do {
//            $set = $this->redisClient->set(
//                $lockKey,
//                $lockRandomNumber,
//                'PX',
//                $lockTimeMS,
//                'NX'
//            );
//            
//            if ($set == "OK") {
//                $finished = true;
//            }
//            else if ($giveUpTime < ((int)(microtime(true) * 1000))) {
//                throw new FailedToAcquireLockException(
//                    "FileDriver failed to acquire lock for session $sessionID"
//                );
//            }
//        } while($finished === false);
//
//        $this->lockNumber = $lockRandomNumber;
//
//        return true;
//    }
//
//    /**
//     * @param $sessionID
//     * @return mixed|void
//     */
//    function forceReleaseLock($sessionID) {
//        $lockKey = generateLockKey($sessionID);
//        $this->redisClient->del($lockKey);
//    }
//
//    /**
//     * @param $sessionID
//     * @param $saveData
//     * @return mixed|void
//     */
//    function save($sessionID, $saveData) {
//        // TODO - check if sessionID is still valid, or whether it was zombiefied?
//
//        if (count($saveData) == 0) {
//            //Redis can't save empty hashes. Either need to delete the key or
//            //do magic.
//            $this->redisClient->del(generateSessionDataKey($sessionID));
//        }
//        else {
//            $this->redisClient->hmset(generateSessionDataKey($sessionID), $saveData);
//        }
//    }
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
//
//    /**
//     * @param $sessionID
//     * @param $sessionProfile
//     */
//    function addProfile($sessionID, $sessionProfile) {
//        $profileKey = generateProfileKey($sessionID);
//        $this->redisClient->rpush($profileKey, $sessionProfile);
//    }
//
//    /**
//     * @param $sessionID
//     * @return array
//     */
//    function getStoredProfile($sessionID) {
//        $profileKey = generateProfileKey($sessionID);
//        $profileData = $this->redisClient->lrange($profileKey, 0, -1);
//
//        return $profileData;
//    }
//
//    /**
//     * @param $sessionID
//     * @param array $sessionProfiles
//     * @return mixed|void
//     */
//    function storeSessionProfiles($sessionID, array $sessionProfiles) {
//        $profileKey = generateProfileKey($sessionID);
//        $this->redisClient->del($profileKey);
//        $this->redisClient->rpush($profileKey, $sessionProfiles);
//    }

    /**
     * @param $sessionID
     * @param $index
     * @return int
     */
    function get($sessionID, $index) {
        $key = generateAsyncKey($sessionID, $index);

        return $this->redisClient->hget($key, $index);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $value
     * @return int
     */
    function set($sessionID, $index, $value) {
        $key = generateAsyncKey($sessionID, $index);

        return $this->redisClient->hset($key, $index, $value);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $increment
     * @return int
     */
    function increment($sessionID, $index, $increment) {
        $key = generateAsyncKey($sessionID, $index);

        return $this->redisClient->hincrby($key, $index, $increment);
    }

    /**
     * @param $sessionID
     * @param $index
     * @return array
     */
    function getList($sessionID, $index) {
        $key = generateAsyncKey($sessionID, $index);

        return $this->redisClient->lrange($key, 0, -1);
    }

    /**
     * @param $sessionID
     * @param $index
     * @param $value
     * @internal param $key
     * @return int
     */
    function appendToList($sessionID, $index, $value) {

        $key = generateAsyncKey($sessionID, $index);
        
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
    function clearList($sessionID, $index) {
        $key = generateAsyncKey($sessionID, $index);
        return $this->redisClient->del($key);
    }
    
    
    
}