<?php

namespace Intahwebz\ASM;

use Predis\Client as RedisClient;

class Session {

    const READ_ONLY = 'READ_ONLY';
    const WRITE_LOCK = 'WRITE_LOCK';

    private $sessionData;

    private $sessionConfig;
    
    private $sessionKey = null;
    
    private $sessionID = null;

    function __construct(SessionConfig $sessionConfig, $openMode, $cookieData) {
        
        \Intahwebz\ASM\Functions::load();
        
        $this->sessionConfig = $sessionConfig;

        $this->redis = new RedisClient($sessionConfig->getRedisConfig());
        
        if (isset($cookieData[$sessionConfig->getSessionName()])) {
            $this->sessionID = $cookieData[$sessionConfig->getSessionName()];
            //Only start the session automatically, if the user sent us a cookie.
            $this->openSession();
        }
        else {
            $this->sessionID = rand();
        }
    }
    
    function clear() {
        $this->sessionData = array();
        $this->saveAllData();
    }

    function openSession() {
        $this->loadData();

        if (!$this->sessionData) {
            $this->invalidKeyAccessed();
            $this->sessionData = array();
        }

        return $this->sessionData;
    }

    function setData($data) {
        $this->sessionData = $data;
    }

    function set($index, $value) {
        $this->sessionData[$index] = $value;
    }
    
    function append($index, $value) {
        $this->sessionData[$index][] = $value;

        $this->saveAllData();        
        $this->loadData();

        return $this->sessionData;
    }
    
    function getData() {
        return $this->sessionData;
    }
    
    function getHeaders() {
        $headers = array();

        $cookieHeader = generateCookieHeader(
            $this->sessionConfig->getSessionName(),
            $this->sessionID,
            $this->sessionConfig->getSessionExpiryTime()
        );

        $headers[] = $cookieHeader;
        
        return $headers;
    }


    function close($discard = false) {
        //TODO - add a compare
        $dataModified = true;
        
        if ($dataModified == true) {
            $this->saveAllData();
        }
    }
    
    function loadData() {
        $newData = $this->redis->hgetall($this->sessionKey);
        $newVersion = array();
        foreach ($newData as $key => $value) {
            $raw = unserialize($value);
            $newVersion[$key] = $raw;
        }

        $this->sessionData = $newVersion;
    }
    
    function saveAllData() {
        $saveData = array();
        foreach ($this->sessionData as $key => $value) {
            $raw = serialize($value);
            $saveData[$key] = $raw;
        }
        if (count($saveData) == 0) {
            $this->redis->del($this->sessionKey);
        }
        else {
            $this->redis->hmset($this->sessionKey, $saveData);
        }
    }

    function acquireWriteLock() {
    }

    function releaseWriteLock() {
    }

    function invalidKeyAccessed() {
    }
}



