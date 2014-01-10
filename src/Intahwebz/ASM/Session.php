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
    
    private $sessionKey = null;
    
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

    function __construct(SessionConfig $sessionConfig, $openMode, $cookieData) {
        \Intahwebz\ASM\Functions::load();
        $this->sessionConfig = $sessionConfig;
        $this->redis = new RedisClient($sessionConfig->getRedisConfig());
        
        if (isset($cookieData[$sessionConfig->getSessionName()])) {
            $this->sessionID = $cookieData[$sessionConfig->getSessionName()];
            //Only start the session automatically, if the user sent us a cookie.
            $this->openSession();
        }
        else {
            $this->sessionID = $this->generateSessionKey();
        }
    }

    function generateSessionKey() {
        return rand();
    }
    
    function regenerateSessionID() {
        $newSessionID = $this->generateSessionKey();
        $zombieTime = $this->sessionConfig->getZombieTime();
        
        if ($zombieTime > 0) {
            //TODO - Need to think about possibility for session hijacking here?
            $this->redis->set(
                $this->generateZombieKey($this->sessionID), 
                $newSessionID, 
                $this->sessionConfig->getZombieTime()
            );
        }

        //TODO - combine this operation with the setting of the zombie key to avoid 
        //any possibility for a race condition.
        $this->redis->rename($this->sessionID, $newSessionID);

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
            $this->sessionConfig->getSessionName(),
            $this->sessionID,
            $lifetime
        );

        $headers[] = $cookieHeader;

        //$this->redis->ttl($this->getRedisSessionKey());
        

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

        if ($zombieKeyName) {
            $regeneratedSessionID = $this->redis->get($this->generateZombieKey($this->sessionID));

            if ($regeneratedSessionID) {
                $this->zombieKeyDetected();
                return $regeneratedSessionID;
            }
        }
        
        return null;
    }

    function generateZombieKey($dyingSessionID) {
        return $this->sessionConfig->getRedisKeyPrefix().'zombie:'.$dyingSessionID;
    }

    function generateRedisSessionKey() {
        return $this->sessionConfig->getRedisKeyPrefix().'session:'.$this->$this->sessionID;
    }

    function generateLockKey() {
        return $this->sessionConfig->getRedisKeyPrefix().'session:'.$this->sessionID.':lock';
    }

    function generateHistoryKey() {
        return $this->sessionConfig->getRedisKeyPrefix().'session:'.$this->sessionID.':history';
    }

    function loadData() {
        $newData = $this->redis->hgetall($this->generateRedisSessionKey());
        
        if ($newData == null) {
            //No session data was available. Check to see if there is a mapping
            //for a zombie key to an active session key
            $regeneratedID = $this->mapZombieIDToRegeneratedID();
            
            if ($regeneratedID) {
                $this->invalidKeyAccessed();
                $this->sessionID = $regeneratedID;
                $newData = $this->redis->hgetall($this->sessionID);
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
            $this->redis->del($this->sessionKey);
        }
        else {
            $this->redis->hmset($this->sessionKey, $saveData);
        }
    }


    function acquireLock() {
        $this->lockKey = $this->generateLockKey();
        $this->lockNumber = rand();

        $totalTimeWaitedForLock = 0;
        
        do {
            $set = $this->redis->set(
                $this->lockKey, 
                $this->lockNumber, 
                $this->sessionConfig->getLockSeconds(),
                $this->sessionConfig->getLockMilliSeconds(),
                'NX'
            );

            if ($totalTimeWaitedForLock >= $this->sessionConfig->getMaxLockWaitTime()) {
                throw new FailedToAcquireLockException("Failed to acquire lock for session data.");
            }
            
            if (!$set) {
                usleep(self::lockSleepTime); //Wait one millisecond
            }
            
            $totalTimeWaitedForLock += self::lockSleepTime;
            
        } while(!$set);
    }
    
    function renewLock() {
        $set = $this->redis->set(
            $this->lockKey,
            $this->lockNumber,
            $this->sessionConfig->getLockSeconds(),
            $this->sessionConfig->getLockMilliSeconds(),
            'XX'
        );
        
        if (!$set) {
            throw new FailedToAcquireLockException("Failed to renew lock.");
        }
    }

    function releaseLock() {
        $keysRemoved = $this->redis->eval(self::unlockScript, 1, $this->lockKey, $this->lockNumber);

        if ($keysRemoved == 0) {
            //lock was force removed by a different script, or this script went over $this->sessionConfig->lockTime
            //Either way - bad things are likely to happen
            $this->processLockWasAlreadyReleased();
        }
    }

    function invalidKeyAccessed() {
    }

    function zombieKeyDetected() {
    }
    
    function processLockWasAlreadyReleased() {
        
    }
}


/*


Patterns
The command SET resource-name anystring NX EX max-lock-time is a simple way to implement a locking system with Redis.
A client can acquire the lock if the above command returns OK (or retry after some time if the command returns Nil), and remove the lock just using DEL.
The lock will be auto-released after the expire time is reached.
It is possible to make this system more robust modifying the unlock schema as follows:
Instead of setting a fixed string, set a non-guessable large random string, called token.
Instead of releasing the lock with DEL, send a script that only removes the key if the value matches.
This avoids that a client will try to release the lock after the expire time deleting the key created by another client that acquired the lock later.
An example of unlock script would be similar to the following:


*/
