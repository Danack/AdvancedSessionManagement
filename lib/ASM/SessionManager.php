<?php

namespace ASM;

use Predis\Client as RedisClient;


class SessionManager {

    /**
     * @var SessionConfig
     */
    private $sessionConfig;

    /**
     * @var \Predis\Client
     */
    private $redisClient;
    
    function __construct(SessionConfig $sessionConfig, RedisClient $redisClient) {
        \ASM\Functions::load();
        $this->sessionConfig = $sessionConfig;
        $this->redisClient = $redisClient;
    }

    function destroyExpiredSessions() {
        
    }

//    function createSession(
//        SessionConfig $sessionConfig,
//        $openMode,
//        $cookieData) {
//        return new Session($sessionConfig, $openMode, $cookieData);
//    }

    function deleteSession($sessionID) {
        deleteAllRelatedRedisInfo($sessionID, $this->redisClient);
    }
}

 