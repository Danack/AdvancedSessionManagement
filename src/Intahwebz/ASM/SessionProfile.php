<?php

namespace Intahwebz\ASM;


class SessionProfile {
    
    private $ipAddress;
    
    private $userAgent;
    
    function __construct($ipAddress, $userAgent) {
        $this->ipAddress = $ipAddress; 
        $this->userAgent = $userAgent;
    }

    /**
     * @return mixed
     */
    public function getIPAddress() {
        return $this->ipAddress;
    }

    /**
     * @return mixed
     */
    public function getUserAgent() {
        return $this->userAgent;
    }
}




 