<?php

namespace ASM;

use ASM\Driver\Driver as SessionDriver;

class SessionManager {

    /**
     * @var SessionConfig
     */
    private $sessionConfig;

    /**
     * @var \Predis\Client
     */
    private $redisClient;
    
    function __construct(SessionDriver $driver) {
        $this->driver = $driver;
    }

    /**
     * 
     */
    function destroyExpiredSessions() {
        
    }

    /**
     * @param $sessionID
     */
    function deleteSession($sessionID) {
        deleteAllRelatedRedisInfo($sessionID, $this->redisClient);
    }
}

 