<?php

namespace ASM;

class SessionConfig {

    const LOCK_ON_OPEN = 'LOCK_ON_OPEN';
    const LOCK_ON_WRITE = 'LOCK_ON_WRITE';
    const LOCK_MANUALLY = 'LOCK_MANUALLY';

    private $lifetime;
    private $zombieTime;
    private $sessionName;
    private $name;
    private $lockMilliSeconds;
    
    //Time in microseconds
    private $maxLockWaitTime;

    function __construct(
        $sessionName,
        $lifetime,
        $zombieTime,
        $lockMode = self::LOCK_ON_OPEN,
        $lockTimeInMilliseconds = 30000
    ) {
        $this->lifetime = $lifetime;
        $this->zombieTime = $zombieTime;
        $this->sessionName = $sessionName;
        $this->lockMode = $lockMode;
        
        $this->lockMilliSeconds = $lockTimeInMilliseconds;

        //Time in microseconds
        $this->maxLockWaitTime = 5000000;

        $this->redisKeyPrefix = 'session:';
    }

    /**
     * @return mixed
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getLifetime() {
        return $this->lifetime;
    }

    /**
     * @return mixed
     */
    public function getSessionName() {
        return $this->sessionName;
    }

    /**
     * @return mixed
     */
    public function getZombieTime() {
        return $this->zombieTime;
    }
    
    function getLockMilliSeconds() {
        return $this->lockMilliSeconds;
    }

    function getMaxLockWaitTime() {
        return $this->maxLockWaitTime;
    }

    /**
     * @return string
     */
    function getLockMode() {
        return $this->lockMode;
    }
}

 