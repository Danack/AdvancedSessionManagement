<?php

namespace Intahwebz\ASM;

use Predis\Client as RedisClient;



class Session {

    const READ_ONLY = 'READ_ONLY';
    const WRITE_LOCK = 'WRITE_LOCK';

    const CACHE_SKIP                = 'skip';
    const CACHE_PUBLIC              = 'public';
    const CACHE_PRIVATE             = 'private';
    const CACHE_PRIVATE_NO_EXPIRE   = 'private_no_expire';
    const CACHE_NO_CACHE            = 'nocache';

    private $sessionData;

    /**
     * @var SessionConfig
     */
    private $sessionConfig;

    private $sessionID = null;

    private $lockKey;
    
    private $lockNumber;

    const unlockScript = <<< END
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end
END;

    const lockSleepTime = 1000;

    private $sessionProfile;

    private $cookieData;

    function __construct(
        SessionConfig $sessionConfig, 
        $openMode, 
        $cookieData,
        RedisClient $redisClient,
        ValidationConfig $validationConfig = null,
        SessionProfile $sessionProfile = null
    ) {
        \Intahwebz\ASM\Functions::load();
        $this->sessionConfig = $sessionConfig;
        $this->redis = $redisClient;
        $this->validationConfig = $validationConfig;
        $this->sessionProfile = $sessionProfile;
        $this->cookieData = $cookieData;
    }

    /**
     * 
     */
    function start() {
        $existingSessionOpened = false;

        if (isset($this->cookieData[$this->sessionConfig->getSessionName()])) {
            $this->sessionID = $this->cookieData[$this->sessionConfig->getSessionName()];
            //Only start the session automatically, if the user sent us a cookie.
            $existingSessionOpened = $this->openSession();
        }
        else {
            $this->sessionID = $this->makeSessionKey();
            $this->sessionData = array();
        }
        
        if ($existingSessionOpened == true) {
            $this->performProfileSecurityCheck();
        }
        else {
            //Record new ip address
            $this->addProfile();
        }
    }

    function getSessionID() {
        return $this->sessionID;
    }
    
    function makeSessionKey() {
        return rand();
    }
    
    function regenerateSessionID() {
        $newSessionID = $this->makeSessionKey();
        $zombieTime = $this->sessionConfig->getZombieTime();
        
        if ($zombieTime > 0) {
            $zombieKey = generateZombieKey($this->sessionID);

            //TODO - Need to think about possibility for session hijacking here?
            $this->redis->set(
                $zombieKey, 
                $newSessionID, 
                'EX', $this->sessionConfig->getZombieTime()
            );
        }

        //TODO - combine this operation with the setting of the zombie key to avoid 
        //any possibility for a race condition.
        
        //TODO - need to rename all the metadata keys.
        $this->redis->rename(generateRedisDataKey($this->sessionID), generateRedisDataKey($newSessionID));
        
        //TODO - do as a redis transaction
        
        $this->sessionID = $newSessionID;
    }

    /**
     * Deletes all data in the session.
     */
    function clear() {
        $this->sessionData = array();
        $this->saveAllData();
    }

    /**
     * @return array
     */
    function openSession() {
        $this->loadData();

        if ($this->sessionData) {
            return true;
        }

        //No session data was 
        $this->invalidKeyAccessed();
        $this->sessionData = array();

        return false;
    }

    /**
     * @param $data
     */
    function setData($data) {
        $this->sessionData = $data;
    }

    /**
     * @param $index
     * @param $value
     */
    function set($index, $value) {
        $this->sessionData[$index] = $value;
    }

    /**
     * @param $index
     * @param $value
     * @return mixed
     */
    function append($index, $value) {
        $this->sessionData[$index][] = $value;

        $this->saveAllData();
        $this->loadData();

        return $this->sessionData;
    }

    /**
     * @return mixed
     */
    function getData() {
        return $this->sessionData;
    }

    /**
     * @return string
     */
    function getHeader() {

        $lifetime = $this->sessionConfig->getLifetime();

        $cookieHeader = generateCookieHeader(
            time(),
            $this->sessionConfig->getSessionName(),
            $this->sessionID,
            $lifetime
        );

        return $cookieHeader;
    }


    function close($discard = false) {
        //TODO - add a compare
        $dataModified = true;

        //TODO - check any lock is closed.

        if (!$discard) {
            if ($dataModified == true) {
                $this->saveAllData();
            }
        }
    }

    function mapZombieIDToRegeneratedID() {
        $zombieKeyName = generateZombieKey($this->sessionID);
        $regeneratedSessionID = $this->redis->get($zombieKeyName);

        if ($regeneratedSessionID) {
            $this->zombieKeyDetected();

            return $regeneratedSessionID;
        }

        return null;
    }


    function loadData() {
        $newData = $this->redis->hgetall(generateRedisDataKey($this->sessionID));
        
        if ($newData == null) {
            //No session data was available. Check to see if there is a mapping
            //for a zombie key to an active session key
            $regeneratedID = $this->mapZombieIDToRegeneratedID();
            
            if ($regeneratedID) {
                $this->sessionID = $regeneratedID;
                $newData = $this->redis->hgetall(generateRedisDataKey($this->sessionID));
            }
            else {
                //Session id was not valid, and was not mapped from a zombie key to a live
                //key. Therefore it's a totally dead key.
                $this->invalidKeyAccessed();
            }
        }

        $newVersion = array();
        foreach ($newData as $key => $value) {
            $raw = unserialize($value);
            $newVersion[$key] = $raw;
        }

        $this->sessionData = $newVersion;
    }
    
    function saveAllData() {
        $saveData = array();
        foreach ($this->sessionData as $key => $value) {
            $raw = serialize($value);
            $saveData[$key] = $raw;
        }
        if (count($saveData) == 0) {
            //Redis can't save empty hashes. Either need to delete the key or
            //do magic.
            $this->redis->del(generateRedisDataKey($this->sessionID));
        }
        else {
            $this->redis->hmset(generateRedisDataKey($this->sessionID), $saveData);
        }
    }


    function acquireLock() {
        $this->lockKey = generateLockKey($this->sessionID);
        $this->lockNumber = rand();

        $totalTimeWaitedForLock = 0;

        do {
            $set = $this->redis->set(
                $this->lockKey,
                $this->lockNumber,
                'PX', $this->sessionConfig->getLockMilliSeconds(),
                'NX'
            );

            if ($set == null) {
                throw new FailedToAcquireLockException("Failed to acquire lock for session data, lockKey already exists.");
            }

            if ($set != "OK") {
                throw new FailedToAcquireLockException("Failed to acquire lock for session data with unknown reason");
            }

            if ($totalTimeWaitedForLock >= $this->sessionConfig->getMaxLockWaitTime()) {
                throw new FailedToAcquireLockException("Failed to acquire lock for session data.");
            }
            
            if (!$set) {
                usleep(self::lockSleepTime); //Wait one millisecond
            }
            
            $totalTimeWaitedForLock += self::lockSleepTime;
            
        } while(!$set);
    }
    


    function releaseLock() {

        $keysRemoved = $this->redis->eval(self::unlockScript, 1, $this->lockKey, $this->lockNumber);

        $lockReleased = true;
        
        if (!$keysRemoved) {
            //lock was force removed by a different script, or this script went over $this->sessionConfig->lockTime
            //Either way - bad things are likely to happen
            $this->processLockWasAlreadyReleased();
            $lockReleased = false;
        }

        return $lockReleased;
    }

    /**
     * @throws FailedToAcquireLockException
     */
    function renewLock() {
        $set = $this->redis->executeRaw([
            'SET',
            $this->lockKey,
            $this->lockNumber,
            'PX', $this->sessionConfig->getLockMilliSeconds(),
            'XX'
        ]);

        if (!$set) {
            throw new FailedToAcquireLockException("Failed to renew lock.");
        }
    }

    /**
     * 
     */
    function forceReleaseLock() {
        $this->lockKey = generateLockKey($this->sessionID);
        $this->redis->del($this->lockKey);
    }

    function invalidKeyAccessed() {
        if (!$this->validationConfig) {
            return;
        }

        $invalidSessionAccessed = $this->validationConfig->getInvalidSessionAccessed();

        if (!$invalidSessionAccessed) {
            return;
        }

        call_user_func($invalidSessionAccessed, $this, $this->sessionProfile);
    }

    function zombieKeyDetected() {
    }
    
    function processLockWasAlreadyReleased() {
    }

    function performSecurityCheck() {
        //get past 
    }

    function performProfileSecurityCheck() {
        if (!$this->validationConfig) {
            return;
        }

        if (!$this->sessionProfile) {
            return;
        }

        $profileChangedCallable = $this->validationConfig->getProfileChanged();
        if (!$profileChangedCallable) {
            return;
        }

        $profileKey = generateProfileKey($this->sessionID);
        $profileDataArray = $this->redis->lrange($profileKey, 0, -1);

        $profileObjectArray = array();
        
        foreach ($profileDataArray as $profileData) {
            $profileObject = unserialize($profileData);
            $profileObjectArray[] = $profileObject;
        }

        call_user_func($profileChangedCallable, $this, $this->sessionProfile, $profileObjectArray);
    }

    /**
     * Add session profile to the approved session profile list
     */
    function addProfile() {
        if ($this->sessionProfile) {
            $profileKey = generateProfileKey($this->sessionID);
            $profileData = serialize($this->sessionProfile);
            $this->redis->rpush($profileKey, $profileData);
        }
    }

    function asyncIncrement($hashKey, $increment = 1) {
        $key = generateAsyncKey($this->sessionID);

        return $this->redis->hincrby($key, $hashKey, $increment);
    }
    
    function asyncGet($hashKey) {
        $key = generateAsyncKey($this->sessionID);

        return $this->redis->hget($key, $hashKey);
    }

    function asyncSet($hashKey, $value) {
        $key = generateAsyncKey($this->sessionID);

        return $this->redis->hset($key, $hashKey, $value);
    }
}