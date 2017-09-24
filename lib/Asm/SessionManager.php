<?php

namespace Asm;

use Asm\Driver as SessionDriver;
use Asm\Encrypter\NullEncrypterFactory;
use Asm\Encrypter\OpenSslEncrypterFactory;
use Psr\Http\Message\ServerRequestInterface as ServerRequest;

class SessionManager implements CookieGenerator
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
     * @var \Asm\Driver
     */
    protected $driver;

    /**
     * @var ValidationConfig
     */
    protected $validationConfig;

    /**
     * @var EncrypterFactory
     */
    protected $encrypterFactory;

    /**
     *
     */
    const LOCK_SLEEP_TIME = 1000;

    /**
     * SessionManager constructor.
     * @param SessionConfig $sessionConfig
     * @param Driver $driver
     * @param ValidationConfig|null $validationConfig
     * @param EncrypterFactory|null $encrypterFactory
     */
    public function __construct(
        SessionConfig $sessionConfig,
        SessionDriver $driver,
        ValidationConfig $validationConfig = null,
        EncrypterFactory $encrypterFactory = null
    ) {
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

        if ($encrypterFactory) {
            $this->encrypterFactory = $encrypterFactory;
        }
        else {
            $this->encrypterFactory = new OpenSslEncrypterFactory($sessionConfig->getSessionName() . '_key');
            //$this->encrypterFactory = new NullEncrypterFactory();
        }
    }

    public function getSessionConfig()
    {
        return $this->sessionConfig;
    }

    public function getLockMode()
    {
        return $this->sessionConfig->getLockMode();
    }

    /**
     * Opens the session, if and only if there is a valid session id
     * in the cookies in the request.
     * @param ServerRequest $request
     * @param null $userProfile
     * @return Session|null
     */
    public function openSessionFromCookie(ServerRequest $request, $userProfile = null)
    {
        $cookieData = $request->getCookieParams();
        $encrypter = $this->encrypterFactory->create($cookieData);
        if (!array_key_exists($this->sessionConfig->getSessionName(), $cookieData)) {
            return null;
        }

        $sessionID = $cookieData[$this->sessionConfig->getSessionName()];
        $session = $this->driver->openSessionByID(
            $sessionID,
            $encrypter,
            $this,
            $userProfile
        );

        if ($session != null) {
            return $session;
        }
        $this->invalidSessionAccessed();

        return null;
    }

    /**
     * Call the invalidSessionAccessed callable, if one is set.
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
     * Create a new session or open existing session.
     *
     * Opens and returns the data for an existing session, if the
     * client sent a valid existing session ID. Otherwise creates a new session.
     *
     * @param ServerRequest $request
     * @param null $userProfile
     * @return
     */
    public function createSession(ServerRequest $request, $userProfile = null)
    {
        $cookieData = $request->getCookieParams();
        $encrypter = $this->encrypterFactory->create($cookieData);

        if (!array_key_exists($this->sessionConfig->getSessionName(), $cookieData)) {
            return $this->driver->createSession($encrypter, $this, $userProfile);
        }

        $sessionID = $cookieData[$this->sessionConfig->getSessionName()];
        $session = $this->driver->openSessionByID(
            $sessionID,
            $encrypter,
            $this,
            $userProfile
        );

        if ($session !== null) {
            return $session;
        }

        $this->invalidSessionAccessed();

        return $this->driver->createSession($encrypter, $this, $userProfile);
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
//                throw new FailedToAcquireLockException("Failed to acquire lock for session data after
// time $totalTimeWaitedForLock ");
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
     * @param $newProfile
     * @param $existingProfiles
     * @return mixed|null
     * @throws AsmException
     */
    public function performProfileSecurityCheck($newProfile, $existingProfiles)
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
            $message = sprintf(
                "The profileChangedCallable must return an array of the allowed" .
                " session profiles, but instead a [%s] was returned",
                gettype($newProfiles)
            );

            throw new AsmException(
                $message,
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
    public function destroyExpiredSessions()
    {
    }

    /**
     * @param $sessionID
     */
    public function deleteSession($sessionID)
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
    public function getHeaders(
        Encrypter $encrypter,
        $sessionId,
        $privacy,
        $domain,
        $path,
        $secure,
        $httpOnly
    ) {
        $time = time();

        $headers = [];
        $headers[] = ["Set-Cookie", Asm::generateCookieHeaderString(
            $time,
            $this->sessionConfig->getSessionName(),
            $sessionId,
            $this->sessionConfig->getLifetime(),
            $path,
            $domain,
            $secure,
            $httpOnly
        )];

        $cachingHeader = ASM::getCacheControlPrivacyHeader($privacy);
        $headers[] = $cachingHeader;

        $encryptionCookieHeaders = $encrypter->getCookieHeaders();

        foreach ($encryptionCookieHeaders as $name => $value) {
            $headers[] = ["Set-Cookie", Asm::generateCookieHeaderString(
                $time,
                $name,
                $value,
                $this->sessionConfig->getLifetime(),
                $path,
                $domain,
                $secure,
                $httpOnly
            )];
        }

        return $headers;
    }
}
