<?php


namespace ASM;


class OpenSession {

    public function __construct()
    {

    }
    
    public function getSessionID()
    {
        
    }



    /**
     *
     */
    public function close() 
    {

    }


    /**
     * @return string
     */
    function getHeader() {
        $lifetime = $this->sessionConfig->getLifetime();
        $cookieHeader = generateCookieHeader(
            time(),
            $this->sessionConfig->getSessionName(),
            $this->getSessionID(),
            $lifetime
        );

        return $cookieHeader;
    }



    //    /**
//     * @return array
//     */
//    public function readSessionData($sessionID) {
//        if ($this->sessionConfig->getLockMode() == SessionConfig::LOCK_ON_OPEN) {
//            $this->acquireLock();
//        }
//
//        return  $this->loadData($sessionID);
//    }



//    /**
//     * @return null
//     */
//    public function getSessionID() {
//        return $this->sessionID;
//    }

}

