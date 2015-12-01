<?php

namespace ASM;

use ASM\Driver as SessionDriver;

class SessionManager
{
    const READ_ONLY = 'READ_ONLY';
    const WRITE_LOCK = 'WRITE_LOCK';

    const CACHE_SKIP = 'skip';
    const CACHE_PUBLIC = 'public';
    const CACHE_PRIVATE = 'private';
    const CACHE_NO_CACHE = 'nocache';

    /**
     * @var SessionConfig
     */
    protected $sessionConfig;


    /**
     * @var \ASM\Driver
     */
    protected $driver;

    /**
     *
     */
    const lockSleepTime = 1000;

    /**
     * @param SessionConfig $sessionConfig
     * @param SessionDriver $driver
     * @param ValidationConfig $validationConfig
     */
    function __construct(
        SessionConfig $sessionConfig,
        SessionDriver $driver,
        ValidationConfig $validationConfig = null)
    {
        $this->sessionConfig = $sessionConfig;
        $this->driver = $driver;

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
    
    function getSessionConfig()
    {
        return $this->sessionConfig;
    }
    
    function getLockMode()
    {
        return $this->sessionConfig->getLockMode();
    }

    public function openSessionFromCookie(array $cookieData, $userProfile = null)
    {
        if (!array_key_exists($this->sessionConfig->getSessionName(), $cookieData)) {
            return null;
        }

        $sessionID = $cookieData[$this->sessionConfig->getSessionName()];

        $existingSession = $this->openSessionByID($sessionID, $userProfile);
        
        if ($existingSession == null) {
            return $this->driver->createSession($this, $userProfile);
        }
        
        return $existingSession;
    }

    /**
     * Opens an existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise returns null.
     *
     * @param array $cookieData
     * @param null $userProfile
     * @return Session|null
     */
    public function openSessionByID($sessionID, $userProfile = null)
    {
        $session = $this->driver->openSessionByID(
            $sessionID,
            $this,
            $userProfile
        );
        
        if ($session == null) {
            $this->invalidSessionAccessed();
        }

        return $session;
    }

    /**
     * Create a new session or open existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise creates a new session.
     *
     * @param array $cookieData
     * @param $userProfile
     * @return Session
     */
    function createSession(array $cookieData, $userProfile = null)
    {
        if (!array_key_exists($this->sessionConfig->getSessionName(), $cookieData)) {
            return $this->driver->createSession($this, $userProfile);
        }

        $sessionID = $cookieData[$this->sessionConfig->getSessionName()];
        $existingSession = $this->openSessionByID($sessionID, $userProfile);
        
        if ($existingSession == null) {
            return $this->driver->createSession($this, $userProfile);
        }

        return $existingSession;
    }



//    /**
//     * 
//     */
//    function regenerateSessionID() {
//        $newSessionID = $this->makeSessionKey();
//        $zombieTime = $this->sessionConfig->getZombieTime();
//        
//        if ($zombieTime > 0) {
//            $this->driver->setupZombieID(
//                $this->sessionID,
//                $newSessionID,
//                $this->sessionConfig->getZombieTime()
//            );
//        }
//
//        $this->sessionID = $newSessionID;
//    }


//    /**
//     * Load the session data from storage.
//     */
//    function loadData($sessionID) {
//        $maxLoops = 5;
//        $newData = null;
//
//        for ($i=0 ; $i<$maxLoops ; $i++) {
//            $newData = $this->driver->getData($sessionID);
//
//            if ($newData == null) {
//                //No session data was available. Check to see if there is a mapping
//                //for a zombie key to an active session key
//                $regeneratedID = $this->driver->findSessionIDFromZombieID($sessionID);
//
//                if ($regeneratedID) {
//                    //The user is trying to use a recently re-generated key.
//                    $this->zombieKeyDetected();
//                    $sessionID = $regeneratedID;
//                    $newData = $this->driver->getData($sessionID);
//                }
//                else {
//                    //Session id was not valid, and was not mapped from a zombie key to a live
//                    //key. Therefore it's a totally dead key.
//                    $this->invalidSessionAccessed();
//                    return [null, []];
//                }
//            }
//        }
//
//        return [$sessionID, $newData];
//    }

//    /**
//     * A zombie key was detected. If the user
//     */
//    private function zombieKeyDetected() { 
//        $zombieKeyAccessedCallable = $this->validationConfig->getZombieKeyAccessedCallable();
//
//        if (!$zombieKeyAccessedCallable) {
//            return;
//        }
//
//        call_user_func($zombieKeyAccessedCallable, $this);
//    }

//    /**
//     * 
//     */
//    public function saveData($saveData) {
//        $this->driver->save($this->sessionID, $saveData);
//    }

//    /**
//     * @throws FailedToAcquireLockException
//     */
//    function acquireLock() {
//        if ($this->sessionID == null) {
//            throw new AsmException("Cannot acquire lock, session is not open.");
//        }
//        
//        $totalTimeWaitedForLock = 0;
//
//        do {
//            $lockAcquired = $this->driver->acquireLock(
//                $this->sessionID,
//                $this->sessionConfig->getLockMilliSeconds(),
//                $this->sessionConfig->getMaxLockWaitTimeMilliseconds()
//            );
//
//            if ($totalTimeWaitedForLock >= $this->sessionConfig->getMaxLockWaitTimeMilliseconds()) {
//                throw new FailedToAcquireLockException("Failed to acquire lock for session data after time $totalTimeWaitedForLock ");
//            }
//            
//            if (!$lockAcquired) {
//                //Wait one millisecond to prevent hammering driver.
//                //TODO - change to random sleep time?
//                usleep(self::lockSleepTime); 
//            }
//            
//            $totalTimeWaitedForLock += self::lockSleepTime;
//            
//        } while(!$lockAcquired);
//    }


//    /**
//     * @return bool
//     * @throws LockAlreadyReleasedException
//     */
//    function releaseLock() {
//        $lockReleased = $this->driver->releaseLock($this->sessionID);
//        
//        if (!$lockReleased) {
//            // lock was force removed by a different script, or this script went over
//            // the $this->sessionConfig->lockTime Either way - bad things are likely to happen
//            $lostLockCallable = $this->validationConfig->getLockWasForceReleasedCallable();
//            $continueExecution = false;
//            if ($lostLockCallable) {
//                $continueExecution = call_user_func($lostLockCallable, $this);
//            }
//
//            if ($continueExecution === true) {
//                return false;
//            }
//
//            throw new LockAlreadyReleasedException("The lock for the session has already been released.");
//        }
//        
//        return true;
//    }

//    /**
//     * Renews a lock. This allows long running operations to keep a lock open longer
//     * than the SessionConfig::$lockMilliSeconds time. If the lock fails to be renewed
//     * an exception is thrown. This can happen when another process force releases a 
//     * lock.
//     * @throws FailedToAcquireLockException
//     */
//    function renewLock() {
//        $renewed = $this->driver->renewLock(
//            $this->sessionID,
//            $this->sessionConfig->getLockMilliSeconds()
//        );
//
//        if (!$renewed) {
//            throw new FailedToAcquireLockException("Failed to renew lock.");
//        }
//    }

//    /**
//     * 
//     */
//    function forceReleaseLock() {
//        //TODO - should this only be callable after the session is started?
//        $this->driver->forceReleaseLock($this->sessionID);
//    }

    /**
     *
     */
    private function invalidSessionAccessed()
    {
        $invalidSessionAccessed = $this->validationConfig->getInvalidSessionAccessedCallable();

        if (!$invalidSessionAccessed) {
            return;
        }

        call_user_func($invalidSessionAccessed, $this);
    }

    /**
     * @param $newProfile
     * @param $existingProfiles
     * @return mixed|null
     * @throws AsmException
     */
    function performProfileSecurityCheck($newProfile, $existingProfiles)
    {
        if ($newProfile === null) {
            return $existingProfiles;
        }

        if (is_string($newProfile) == false &&
            (!(is_object($newProfile) && method_exists($newProfile, '__toString')))) {
            throw new AsmException(
                "userProfile must be a string or an object containing a __toString method.",
                AsmException::BAD_ARGUMENT
            );
        }

        $profileChangedCallable = $this->validationConfig->getProfileChangedCallable();
        if (!$profileChangedCallable) {
            return $existingProfiles;
        }

        foreach ($existingProfiles as $sessionProfile) {
            if ($newProfile === $sessionProfile) {
                return $existingProfiles;
            }
        }

        $newProfiles = call_user_func($profileChangedCallable, $this, $newProfile, $existingProfiles);
        
        if (is_array($newProfiles) == false) {
            throw new AsmException(
                "The profileChangedCallable must return an array of the allowed session profiles, but instead a ".gettype($newProfiles)."was returned",
                AsmException::BAD_ARGUMENT
            );
        }

        return $newProfiles;
    }

//    /**
//     * Add session profile to the approved session profile list
//     */
//    function addProfile($sessionProfile) {
//        $this->driver->addProfile($this->sessionID, $sessionProfile);
//    }

    /**
     *
     */
    function destroyExpiredSessions()
    {

    }

    /**
     * @param $sessionID
     */
    function deleteSession($sessionID)
    {
        $this->driver->deleteSessionByID($sessionID);
    }

    /**
     * @param $sessionId
     * @param $privacy
     * @param $lastModifiedTime
     * @param $domain
     * @param $path
     * @param $secure
     * @param $httpOnly
     * @return array
     * @throws AsmException
     */
    function getHeaders(
        $sessionId, 
        $privacy,
        $domain,
        $path,
        $secure,
        $httpOnly
    ) {
        $time = time();

        $headers = [];
        $headers["Set-Cookie"] = ASM::generateCookieHeaderString($time,
            $this->sessionConfig->getSessionName(),
            $sessionId,
            $this->sessionConfig->getLifetime(),
            $path,
            $domain,
            $secure,
            $httpOnly);


        $cachingHeaders = ASM::getCacheControlPrivacyHeader(
            $privacy
        );

        $headers = array_merge($headers, $cachingHeaders);

        return $headers;
    }
}