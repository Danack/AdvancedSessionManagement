<?php

namespace Asm;

class SessionConfig
{
    const LOCK_ON_OPEN = 'LOCK_ON_OPEN';
    const LOCK_ON_WRITE = 'LOCK_ON_WRITE';
    const LOCK_MANUALLY = 'LOCK_MANUALLY';

    /**
     * @var int How long session data should persist for in seconds
     */
    private int $lifetime;

    /**
     * @var int When a session ID is changed through Session::regenerateSessionID
     * how long should the previous sessionID be allowed to access the session data.
     * This is useful when multiple requests hit the server at the same time, and you don't want
     * them to block each other.
     */
    private int $zombieTime;

    private string $sessionName;

    private string $lockMode;

    /**
     * @var int How long sessions should be locked for when they are locked. Sessions that
     * are locked for longer than this time will be automatically unlocked, as it assumed
     * that the PHP processing them has crashed.
     *
     */
    private int $lockMilliSeconds;


    private int $maxLockWaitTimeMilliseconds;

    public function __construct(
        string $sessionName,
        int $lifetime,
        int $zombieTime = 5,
        string $lockMode = self::LOCK_ON_OPEN,
        int $lockTimeInMilliseconds = 30000,
        int $maxLockWaitTimeMilliseconds = 15000
    ) {
        $this->sessionName = $sessionName;
        $this->lifetime = $lifetime;
        $this->zombieTime = $zombieTime;
        $this->sessionName = $sessionName;
        $this->lockMode = $lockMode;

        $this->lockMilliSeconds = $lockTimeInMilliseconds;

        //Time in microseconds
        $this->maxLockWaitTimeMilliseconds = $maxLockWaitTimeMilliseconds;
    }


    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    public function getSessionName(): string
    {
        return $this->sessionName;
    }

    public function getZombieTime(): int
    {
        return $this->zombieTime;
    }

    public function getLockMilliSeconds(): int
    {
        return $this->lockMilliSeconds;
    }

    public function getMaxLockWaitTimeMilliseconds(): int
    {
        return $this->maxLockWaitTimeMilliseconds;
    }

    public function getLockMode(): string
    {
        return $this->lockMode;
    }
}
