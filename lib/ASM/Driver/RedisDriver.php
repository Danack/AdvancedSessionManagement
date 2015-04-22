<?php

namespace ASM\Driver;

use ASM\Serializer;
use ASM\IDGenerator;
use ASM\FailedToAcquireLockException;
use Predis\Client as RedisClient;


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
function generateSessionDataKey($sessionID) {
    return 'session:'.$sessionID;
}

function generateZombieKey($dyingSessionID) {
    return 'zombie:'.$dyingSessionID;
}

function generateLockKey($sessionID) {
    return 'session:'.$sessionID.':lock';
}

function generateProfileKey($sessionID) {
    return 'session:'.$sessionID.':profile';
}

/**
 * @param $sessionID
 * @internal param null $index
 * @return string
 */
function generateAsyncKey($sessionID) {
    $key = 'session:'.$sessionID.':async';

    return $key;
}



class RedisDriver implements ConcurrentDriver {

    /**
     * @var \Predis\Client
     */
    private $redisClient;

    /**
     * @var string The lock key, this is consistent per sessionID.
     */
    protected $lockKey;

    /**
     * @var string This is random for each lock. It allows us to detect when another 
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockNumber;


    /**
     * @var Serializer
     */
    private $serializer;


    /**
     * @var IDGenerator
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
        IDGenerator $idGenerator = null)
    {
        $this->redisClient = $redisClient;

        if ($serializer) {
            $this->serializer = $serializer;
        }
        else {
            $this->serializer = new \ASM\PHPSerializer();
        }

        if ($idGenerator) {
            $this->idGenerator = $idGenerator;
        }
        else {
            $this->idGenerator = new \ASM\StandardIDGenerator();
        }
    }

    

    function close() {
        //$this->__destruct();
    }

    /**
     * Open an existing session. Returns either the session data or null if
     * the session could not be found.
     * @param $sessionID
     * @return string|null
     */
    function openSession($sessionID) {
        // TODO: Implement openSession() method.
    }

    /**
     * Create a new session.
     * @return string The newly created session ID.
     */
    function createSession() {
        // TODO: Implement createSession() method.
    }


    /**
     * @param $sessionID
     * @param $index
     * @internal param $value
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
     * @internal param $key
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
     * @internal param $key
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
     * @param $key
     * @param $value
     * @internal param $index
     * @return int
     */
    function appendToList($sessionID, $key, $value) {
        return $this->redisClient->rpush($key, [$value]);
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

    /**
     * @param $sessionID
     * @param $milliseconds
     * @internal param $lockKey
     * @internal param $lockNumber
     * @return mixed true on success.
     */
    function renewLock($sessionID, $milliseconds) {
        $lockKey = generateLockKey($sessionID);
        //TODO - this is not valid. It needs a script to check that the lockNumber
        //is still valid.
        $set = $this->redisClient->executeRaw([
                'SET',
                $lockKey,
                $this->lockNumber,
                'PX', $milliseconds,
                'XX'
            ]
        );

        return $set;
    }

    /**
     * @param $sessionID
     * @return mixed|void
     */
    function deleteSession($sessionID) {
        $dataKey = generateSessionDataKey($sessionID);
        $this->redisClient->del($dataKey);
    }

    /**
     * Destroy expired sessions.
     */
    function destroyExpiredSessions() {
        // Nothing to do for redis driver as redis automatically clears dead keys
    }

    /**
     * @param $sessionID
     * @return bool|mixed
     */
    function releaseLock($sessionID) {
        if (!$this->lockNumber) {
            //It is already released
            return false;
        }
        $lockKey = generateLockKey($sessionID);
        
        $result = $this->redisClient->eval(self::unlockScript, 1, $lockKey, $this->lockNumber);
        
        // TODO - this needs to throw an exception if lockNumber did not match
        // the value stored in 

        $this->lockNumber = null;

        return $result;
    }

    function isLocked($sessionID) {
        return (bool)($this->lockNumber);
    }

    /**
     * @param $sessionID
     * @return bool
     */
    function validateLock($sessionID) {
        if (!$this->lockNumber) {
            return false;
        }
        
        $lockKey = generateLockKey($sessionID);
        $storedLockNumber = $this->redisClient->get($lockKey);
        
        if ($storedLockNumber === $this->lockNumber) {
            return true;
        }

        return false;
    }
    
    /**
     * @param $sessionID
     * @return array
     */
    function getData($sessionID) {
        $data = $this->redisClient->hgetall(generateSessionDataKey($sessionID));
        
        return $data;
    }

    /**
     * @param $sessionID
     * @param $milliseconds
     * @return bool
     * @throws FailedToAcquireLockException
     */
    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS) {
        $lockKey = generateLockKey($sessionID);
        //TODO - change to actual random numbers.
        $lockRandomNumber = "".rand(100000000, 100000000000);

        $finished = false;

        $giveUpTime = ((int)(microtime(true) * 1000)) + $acquireTimeoutMS;
        
        do {
            $set = $this->redisClient->set(
                $lockKey,
                $lockRandomNumber,
                'PX',
                $lockTimeMS,
                'NX'
            );
            
            if ($set == "OK") {
                $finished = true;
            }
            else if ($giveUpTime < ((int)(microtime(true) * 1000))) {
                throw new FailedToAcquireLockException(
                    "FileDriver failed to acquire lock for session $sessionID"
                );
            }
        } while($finished === false);

        $this->lockNumber = $lockRandomNumber;

        return true;
    }

    /**
     * @param $sessionID
     * @return mixed|void
     */
    function forceReleaseLock($sessionID) {
        $lockKey = generateLockKey($sessionID);
        $this->redisClient->del($lockKey);
    }

    /**
     * @param $sessionID
     * @param $saveData
     * @return mixed|void
     */
    function save($sessionID, $saveData) {
        // TODO - check if sessionID is still valid, or whether it was zombiefied?

        if (count($saveData) == 0) {
            //Redis can't save empty hashes. Either need to delete the key or
            //do magic.
            $this->redisClient->del(generateSessionDataKey($sessionID));
        }
        else {
            $this->redisClient->hmset(generateSessionDataKey($sessionID), $saveData);
        }
    }

    /**
     * @param $sessionID
     * @return string
     */
    function findSessionIDFromZombieID($sessionID) {
        $zombieKeyName = generateZombieKey($sessionID);
        $regeneratedSessionID = $this->redisClient->get($zombieKeyName);

        return $regeneratedSessionID;
    }


    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     * @return mixed|void
     */
    function setupZombieID($dyingSessionID,  $zombieTimeMilliseconds) {
        $newSessionID = $sessionID = $this->idGenerator->generateSessionID();;
        $zombieKey = generateZombieKey($dyingSessionID);
        $this->redisClient->set(
            $zombieKey,
            $newSessionID,
            'EX',
            $zombieTimeMilliseconds
        );

        //TODO - combine this operation with the setting of the zombie key to avoid 
        //any possibility for a race condition.

        //TODO - need to rename all the metadata keys.
        // or maybe use RENAMENX ?
        $this->redisClient->rename(
            generateSessionDataKey($dyingSessionID),
            generateSessionDataKey($newSessionID)
        );

        return $newSessionID;
    }

    /**
     * @param $sessionID
     * @param $sessionProfile
     */
    function addProfile($sessionID, $sessionProfile) {
        $profileKey = generateProfileKey($sessionID);
        $this->redisClient->rpush($profileKey, $sessionProfile);
    }

    /**
     * @param $sessionID
     * @return array
     */
    function getStoredProfile($sessionID) {
        $profileKey = generateProfileKey($sessionID);
        $profileData = $this->redisClient->lrange($profileKey, 0, -1);

        return $profileData;
    }

    /**
     * @param $sessionID
     * @param array $sessionProfiles
     * @return mixed|void
     */
    function storeSessionProfiles($sessionID, array $sessionProfiles) {
        $profileKey = generateProfileKey($sessionID);
        $this->redisClient->del($profileKey);
        $this->redisClient->rpush($profileKey, $sessionProfiles);
    }
}

