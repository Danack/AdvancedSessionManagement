<?php

namespace Intahwebz\ASM;

class SessionConfig {

    private $redisConfig;
    
    private $redisKeyPrefix;
    private $lifetime;
    private $zombieTime;
    private $sessionName;
    private $lockTime;
    private $name;
    private $lockSeconds;
    private $lockMilliSeconds;
    
    //Time in microseconds
    private $maxLockWaitTime;

    function __construct(
        $sessionName,
        $redisConfig, 
        $lifetime,
        $zombieTime,
        $lockTime = 30
        
    ) {
        $this->redisConfig = $redisConfig;
        $this->lifetime = $lifetime;
        $this->zombieTime = $zombieTime;
        $this->sessionName = $sessionName;
        $this->lockTime = $lockTime;

        $this->lockSeconds = 5;
        $this->lockMilliSeconds = 0;
        
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
    public function getRedisConfig() {
        return $this->redisConfig;
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

    function getLockSeconds() {
        return $this->lockSeconds;
    }
    
    function getLockMilliSeconds() {
        return $this->lockMilliSeconds;
    }

    function getMaxLockWaitTime() {
        return $this->maxLockWaitTime;
    }

    function getRedisKeyPrefix() {
        return $this->redisKeyPrefix;
    }
}

 