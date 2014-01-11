<?php

namespace Intahwebz\ASM;

class SessionConfig {


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
        $lockTimeInMilliseconds = 30000
        
    ) {
        $this->lifetime = $lifetime;
        $this->zombieTime = $zombieTime;
        $this->sessionName = $sessionName;
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
}

 