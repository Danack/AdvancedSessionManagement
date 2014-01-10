<?php


class SessionProfile {
    
    private $ipAddress;
    
    private $userAgent;
    
    function __construct($ipAddress, $userAgent) {
        $this->ipAddress = $ipAddress; 
        $this->userAgent = $userAgent;
    }
    
}




 