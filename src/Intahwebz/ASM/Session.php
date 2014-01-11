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
    
    //private $sessionKey = null;
    
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

    function __construct(SessionConfig $sessionConfig, $openMode, $cookieData, RedisClient $redisClient) {
        \Intahwebz\ASM\Functions::load();
        $this->sessionConfig = $sessionConfig;

        $this->redis = $redisClient;
        
        if (isset($cookieData[$sessionConfig->getSessionName()])) {
            $this->sessionID = $cookieData[$sessionConfig->getSessionName()];
            //Only start the session automatically, if the user sent us a cookie.
            $this->openSession();
        }
        else {
            $this->sessionID = $this->makeSessionKey();
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
            $zombieKey = $this->generateZombieKey($this->sessionID);

            //TODO - Need to think about possibility for session hijacking here?
            $this->redis->set(
                $this->generateZombieKey($this->sessionID), 
                $newSessionID, 
                'EX', $this->sessionConfig->getZombieTime()
            );
        }

        //TODO - combine this operation with the setting of the zombie key to avoid 
        //any possibility for a race condition.
        
        //TODO - need to rename all the metadata keys.
        $this->redis->rename($this->generateRedisDataKey(), $this->generateRedisDataKey($newSessionID));
        
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

        if (!$this->sessionData) {
            $this->invalidKeyAccessed();
            $this->sessionData = array();
        }

        return $this->sessionData;
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
     * @param string $caching
     * @return array
     * @throws \InvalidArgumentException
     */
    function getHeaders($caching = self::CACHE_SKIP) {
        $headers = array();

        $lifetime = $this->sessionConfig->getLifetime();

        $cookieHeader = generateCookieHeader(
            time(),
            $this->sessionConfig->getSessionName(),
            $this->sessionID,
            $lifetime
        );

        $headers[] = $cookieHeader;

        //$lastModifiedTime = $this->getLastWriteTime();
//        $expireTime = $lastModifiedTime + $expireLength
//        $expireDate = date("D, d-M-Y H:i:s T", $expireTime);
        $expireDate = 'Thu, 19 Nov 1981 08:52:00 GMT';
        $lastModifiedDate = 'Thu, 19 Nov 1981 08:52:00 GMT';
        
        switch($caching) {

            case(self::CACHE_SKIP): {
                //nothing to do. 
                break;
            }
            case(self::CACHE_PUBLIC): {
                $headers[] = "Expires: ".$expireDate;
                $headers[] = "Cache-Control: public, max-age=".$this->sessionConfig->getLifetime();
                $headers[] = "Last-Modified: (the timestamp of when the session was last saved)";
                break;
            }
            case(self::CACHE_PRIVATE): {
                $headers[] = "Expires: ".$expireDate;
                $headers[] = "Cache-Control: private, max-age=".$lifetime." pre-check=".$lifetime;
                $headers[] = "Last-Modified: (the timestamp of when the session was last saved)";
                break;
            }
            case(self::CACHE_PRIVATE_NO_EXPIRE): {
                $headers[] = "Cache-Control: private, max-age=".$lifetime." pre-check=".$lifetime;
                $headers[] = "Last-Modified: ".$lastModifiedDate;
                break;
            }
            case(self::CACHE_NO_CACHE): {

                $headers[] = "Expires: ".$expireDate;
                $headers[] = "Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0";
                $headers[] = "Pragma: no-cache";
                break;
            }
            default: {
                throw new \InvalidArgumentException("Unknown cache setting '$caching'.");
            }
        }

        return $headers;
    }


    function close($discard = false) {
        //TODO - add a compare
        $dataModified = true;
        
        if ($dataModified == true) {
            $this->saveAllData();
        }
    }

    function mapZombieIDToRegeneratedID() {
        $zombieKeyName = $this->generateZombieKey($this->sessionID);
        $regeneratedSessionID = $this->redis->get($zombieKeyName);

        if ($regeneratedSessionID) {
            $this->zombieKeyDetected();

            return $regeneratedSessionID;
        }

        return null;
    }

    function generateZombieKey($dyingSessionID) {
        return 'zombie:'.$dyingSessionID;
    }

    function generateRedisDataKey($newSessionID = null) {
        if ($newSessionID == null) {
            return 'session:'.$this->sessionID;
        }
        return 'session:'.$newSessionID;
    }

    function generateLockKey() {
        return 'session:'.$this->sessionID.':lock';
    }

    function generateHistoryKey() {
        return 'session:'.$this->sessionID.':history';
    }

    function loadData() {
        $newData = $this->redis->hgetall($this->generateRedisDataKey());
        
        if ($newData == null) {
            //No session data was available. Check to see if there is a mapping
            //for a zombie key to an active session key
            $regeneratedID = $this->mapZombieIDToRegeneratedID();
            
            if ($regeneratedID) {
                $this->sessionID = $regeneratedID;
                $newData = $this->redis->hgetall($this->generateRedisDataKey());
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
//        if (count($saveData) == 0) {
//            $this->redis->del($this->sessionID);
//        }
//        else {
            $this->redis->hmset($this->generateRedisDataKey(), $saveData);
        //}
    }


    function acquireLock() {
        $this->lockKey = $this->generateLockKey();
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
    
    function forceReleaseLock() {
        $this->lockKey = $this->generateLockKey();
        $this->redis->del($this->lockKey);
    }

    function invalidKeyAccessed() {
    }

    function zombieKeyDetected() {
    }
    
    function processLockWasAlreadyReleased() {
    }
}