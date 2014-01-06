<?php


class SessionManager {

    function __construct(SessionConfig $sessionConfig) {
        $this->sessionConfig = $sessionConfig;
    }

    function destroyExpiredSessions() {
        
    }

    function createSession(
        SessionConfig $sessionConfig,
        $openMode,
        $cookieData) {
        return new Session($sessionConfig, $openMode, $cookieData);
    }

}

 