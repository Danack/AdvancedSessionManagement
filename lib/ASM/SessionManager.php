<?php

namespace ASM;

use Predis\Client as RedisClient;
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

    function destroyExpiredSessions() {
        
    }

    function deleteSession($sessionID) {
        deleteAllRelatedRedisInfo($sessionID, $this->redisClient);
    }
}

 