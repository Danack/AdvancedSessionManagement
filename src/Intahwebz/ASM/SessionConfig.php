<?php

namespace Intahwebz\ASM;

class SessionConfig {

    private $redisConfig;
    private $sessionExpiryTime;
    private $sessionZombieTime;
    private $sessionName;

    private $name;

    function __construct(
        $sessionName,
        $redisConfig, 
        $sessionExpiryTime,
        $sessionZombieTime
        
    ) {
        $this->redisConfig = $redisConfig;
        $this->sessionExpiryTime = $sessionExpiryTime;
        $this->sessionZombieTime = $sessionZombieTime;
        $this->sessionName = $sessionName;
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
    public function getSessionExpiryTime() {
        return $this->sessionExpiryTime;
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
    public function getSessionZombieTime() {
        return $this->sessionZombieTime;
    }
    
    
    
    
}

 