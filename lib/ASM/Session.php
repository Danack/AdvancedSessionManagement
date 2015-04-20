<?php

namespace ASM;

use ASM\Driver\Driver as SessionDriver;


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
    protected $sessionConfig;

    /**
     * @var null 
     */
    protected $sessionID = null;

    /**
     * @var \ASM\Driver\RedisDriver
     */
    protected $driver;

    /**
     * 
     */
    const lockSleepTime = 1000;

    /**
     * @var
     */
    private $cookieData;

    /**
     * @param SessionConfig $sessionConfig
     * @param $openMode
     * @param $cookieData
     * @param SessionDriver $driver
     * @param ValidationConfig $validationConfig
     */
    function __construct(
        SessionConfig $sessionConfig, 
        $openMode, 
        $cookieData,
        SessionDriver $driver,
        ValidationConfig $validationConfig = null
    ) {
        $this->sessionConfig = $sessionConfig;
        $this->driver = $driver;
        $this->cookieData = $cookieData;

        if ($validationConfig) {
            $this->validationConfig = $validationConfig;
        }
        else {
            $this->validationConfig = new ValidationConfig(
                null,
                null,
                null
            );
        }
    }

    /**
     * 
     */
    public function openSession($userProfile = null) {
        $existingSessionOpened = false;

        if (isset($this->cookieData[$this->sessionConfig->getSessionName()])) {
            $this->sessionID = $this->cookieData[$this->sessionConfig->getSessionName()];
            //Only start the session automatically, if the user sent us a cookie.
            $existingSessionOpened = $this->readSessionData();
        }
        else {
            $this->sessionID = $this->makeSessionKey();
            $this->sessionData = array();
        }
        
        if ($existingSessionOpened == true) {
            $this->performProfileSecurityCheck($userProfile);
        }
        else {
            //Record new ip address
            if ($userProfile != null) {
                $this->addProfile($userProfile);
            }
        }
    }


    /**
     * 
     */
    public function close() {

    }
    
    
    
    /**
     * @return array
     */
    public function readSessionData() {
        if ($this->sessionConfig->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
            $this->acquireLock();
        }

        $sessionData = $this->loadData();

        return $sessionData;
//        
//        if ($this->sessionData) {
//            return true;
//        }
//
//        //No session data was found
//        $this->invalidKeyAccessed();
//
//        return false;
    }



    /**
     * @return null
     */
    public function getSessionID() {
        return $this->sessionID;
    }

    /**
     * @return int
     */
    private function makeSessionKey() {
        //TODO - understand what is required of a real session ID generator.
        return rand(100000000, 999999999);
    }

    /**
     * 
     */
    function regenerateSessionID() {
        $newSessionID = $this->makeSessionKey();
        $zombieTime = $this->sessionConfig->getZombieTime();
        
        if ($zombieTime > 0) {
            $this->driver->setupZombieID(
                $this->sessionID,
                $newSessionID,
                $this->sessionConfig->getZombieTime()
            );
        }

        $this->sessionID = $newSessionID;
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


    /**
     * Load the session data from storage.
     */
    function loadData() {
        $maxLoops = 5;
        $newData = null;

        for ($i=0 ; $i<$maxLoops ; $i++) {
            $newData = $this->driver->getData($this->sessionID);

            if ($newData == null) {
                //No session data was available. Check to see if there is a mapping
                //for a zombie key to an active session key
                $regeneratedID = $this->driver->findSessionIDFromZombieID($this->sessionID);

                if ($regeneratedID) {
                    //The user is trying to use a recently re-generated key.
                    $this->zombieKeyDetected();
                    $this->sessionID = $regeneratedID;
                    $newData = $this->driver->getData($this->sessionID);
                }
                else {
                    //Session id was not valid, and was not mapped from a zombie key to a live
                    //key. Therefore it's a totally dead key.
                    $this->invalidKeyAccessed();
                    return null;
                }
            }
        }

        return $newData;
    }

    /**
     * A zombie key was detected. If the user
     */
    private function zombieKeyDetected() { 
        $zombieKeyAccessedCallable = $this->validationConfig->getZombieKeyAccessedCallable();

        if (!$zombieKeyAccessedCallable) {
            return;
        }

        call_user_func($zombieKeyAccessedCallable, $this);
    }

    /**
     * 
     */
    public function saveData($saveData) {
        $this->driver->save($this->sessionID, $saveData);
    }

    /**
     * @throws FailedToAcquireLockException
     */
    function acquireLock() {
        $totalTimeWaitedForLock = 0;

        do {
            $lockAcquired = $this->driver->acquireLock(
                $this->sessionID,
                $this->sessionConfig->getLockMilliSeconds(),
                $this->sessionConfig->getMaxLockWaitTimeMilliseconds()
            );

            if ($totalTimeWaitedForLock >= $this->sessionConfig->getMaxLockWaitTimeMilliseconds()) {
                throw new FailedToAcquireLockException("Failed to acquire lock for session data after time $totalTimeWaitedForLock ");
            }
            
            if (!$lockAcquired) {
                //Wait one millisecond to prevent hammering driver.
                //TODO - change to random sleep time?
                usleep(self::lockSleepTime); 
            }
            
            $totalTimeWaitedForLock += self::lockSleepTime;
            
        } while(!$lockAcquired);
    }


    /**
     * @return bool
     * @throws LockAlreadyReleasedException
     */
    function releaseLock() {
        $lockReleased = $this->driver->releaseLock($this->sessionID);
        
        if (!$lockReleased) {
            // lock was force removed by a different script, or this script went over
            // the $this->sessionConfig->lockTime Either way - bad things are likely to happen
            $lostLockCallable = $this->validationConfig->getLockWasForceReleasedCallable();
            $continueExecution = false;
            if ($lostLockCallable) {
                $continueExecution = call_user_func($lostLockCallable, $this);
            }

            if ($continueExecution === true) {
                return false;
            }

            throw new LockAlreadyReleasedException("The lock for the session has already been released.");
        }
        
        return true;
    }

    /**
     * Renews a lock. This allows long running operations to keep a lock open longer
     * than the SessionConfig::$lockMilliSeconds time. If the lock fails to be renewed
     * an exception is thrown. This can happen when another process force releases a 
     * lock.
     * @throws FailedToAcquireLockException
     */
    function renewLock() {
        $renewed = $this->driver->renewLock(
            $this->sessionID,
            $this->sessionConfig->getLockMilliSeconds()
        );

        if (!$renewed) {
            throw new FailedToAcquireLockException("Failed to renew lock.");
        }
    }

    /**
     * 
     */
    function forceReleaseLock() {
        //TODO - should this only be callable after the session is started?
        $this->driver->forceReleaseLock($this->sessionID);
    }

    /**
     * 
     */
    function invalidKeyAccessed() {
        $invalidSessionAccessed = $this->validationConfig->getInvalidSessionAccessedCallable();

        if (!$invalidSessionAccessed) {
            return;
        }

        call_user_func($invalidSessionAccessed, $this);
    }

    /**
     * 
     */
    function performProfileSecurityCheck($userProfile) {
        if ($userProfile === null) {
            return;
        }

        if (is_string($userProfile) == false &&
            (!(is_object($userProfile) && method_exists($userProfile, '__toString')))) {
            throw new AsmException("userProfile must be a string or an object containing a __toString method.");
        }

        $profileChangedCallable = $this->validationConfig->getProfileChangedCallable();
        if (!$profileChangedCallable) {
            return;
        }

        $sessionProfiles = $this->driver->getStoredProfile($this->sessionID);
        $knownProfile = false;
        
        foreach ($sessionProfiles as $sessionProfile) {
            if ($userProfile === $sessionProfile) {
                $knownProfile = true;
                break;
            }
        }
        
        if ($knownProfile == false) {
            $newProfiles = call_user_func($profileChangedCallable, $this, $userProfile, $sessionProfiles);
            
            if (is_array($newProfiles) == false) {
                throw new AsmException("The profileChangedCallable must return an array of the allowed session profiles, but instead a ".gettype($newProfiles)."was returned");
            }
            
            $this->driver->storeSessionProfiles(
                $this->sessionID,
                $newProfiles
            );
        }
    }

    /**
     * Add session profile to the approved session profile list
     */
    function addProfile($sessionProfile) {
        $this->driver->addProfile($this->sessionID, $sessionProfile);
    }
}