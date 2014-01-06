<?php

use Predis\Client as RedisClient;

class Session {

    const READ_ONLY = 'READ_ONLY';
    const WRITE_LOCK = 'WRITE_LOCK';

    private $sessionData;

    private $sessionConfig;

    function __construct(SessionConfig $sessionConfig, $openMode, $cookieData) {
        $this->sessionConfig = $sessionConfig;

        if (isset($_COOKIE[$this->sessionName])) {
            //Only start the session automatically, if the user sent us a cookie.
            $this->startSession();
        }

        $redis = new RedisClient(array(
//        "scheme" => "tcp",
//        "host" => "127.0.0.1",
//        "port" => 6379));
        ));
    }
    
    function getHeaders() {
        
        $headers = array();
        
        return $headers;
    }

    function acquireWriteLock() {
    }

    function releaseWriteLock() {
    }

    function close($discard = false) {
    }

    function unsetasdsd() {

    }
    
    
}



