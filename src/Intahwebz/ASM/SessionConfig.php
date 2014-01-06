<?php


class SessionConfig {

    private $redisMaster;
    private $redisSlave;
    private $sessionExpiryTime;
    private $sessionZombieTime;
    private $sessionName;


    function __construct(
        $redisMaster, 
        $redisSlave,
        $sessionExpiryTime,
        $sessionZombieTime,
        $sessionName
    ) {
        $this->redisMaster = $redisMaster;
        $this->redisSlave, $redisSlave;
        $this->sessionExpiryTime = $sessionExpiryTime;
        $this->sessionZombieTime = $sessionZombieTime;
        $this->sessionName = $sessionName;
    }
}

 