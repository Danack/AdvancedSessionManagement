<?php

namespace ASM;

class SessionConfig
{

    const LOCK_ON_OPEN = 'LOCK_ON_OPEN';
    const LOCK_ON_WRITE = 'LOCK_ON_WRITE';
    const LOCK_MANUALLY = 'LOCK_MANUALLY';

    /**
     * @var int How long session data should persist for in seconds
     */
    private $lifetime;

    /**
     * @var int When a session ID is changed through Session::regenerateSessionID
     * how long should the previous sessionID be allowed to access the session data.
     * This is useful when multiple requests hit the server at the same time, and you don't want
     * them to block each other.
     */
    private $zombieTime;

    private $sessionName;


    /**
     * @var int How long sessions should be locked for when they are locked. Sessions that
     * are locked for longer than this time will be automatically unlocked, as it assumed
     * that the PHP processing them has crashed.
     *
     */
    private $lockMilliSeconds;

    /**
     * @var
     */
    private $maxLockWaitTimeMilliseconds;

    function __construct(
        $sessionName,
        $lifetime,
        $zombieTime,
        $lockMode = self::LOCK_ON_OPEN,
        $lockTimeInMilliseconds = 30000,
        $maxLockWaitTimeMilliseconds = 15000
    )
    {
        $this->sessionName = $sessionName;
        $this->lifetime = $lifetime;
        $this->zombieTime = $zombieTime;
        $this->sessionName = $sessionName;
        $this->lockMode = $lockMode;

        $this->lockMilliSeconds = $lockTimeInMilliseconds;

        //Time in microseconds
        $this->maxLockWaitTimeMilliseconds = $maxLockWaitTimeMilliseconds;
    }

    /**
     * @return mixed
     */
    public function getLifetime()
    {
        return $this->lifetime;
    }

    /**
     * @return mixed
     */
    public function getSessionName()
    {
        return $this->sessionName;
    }

    /**
     * @return mixed
     */
    public function getZombieTime()
    {
        return $this->zombieTime;
    }

    function getLockMilliSeconds()
    {
        return $this->lockMilliSeconds;
    }

    function getMaxLockWaitTimeMilliseconds()
    {
        return $this->maxLockWaitTimeMilliseconds;
    }

    /**
     * @return string
     */
    function getLockMode()
    {
        return $this->lockMode;
    }
}

 