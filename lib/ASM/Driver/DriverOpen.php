<?php


namespace ASM\Driver;


interface DriverOpen {

    /**
     * @param string $saveData
     */
    function save($saveData);

    /**
     * @return mixed
     */
    function close();


    /**
     * @return mixed
     */
    function getSessionID();

    /**
     * @return mixed
     */
    function readData();
    

    /**
     * Test whether the driver thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     * @param $sessionID
     * @return boolean
     */
    //function isLocked($sessionID);

    /**
     * @param $sessionID
     * @return boolean
     */
    //function validateLock($sessionID);

    /**
     * Acquire a lock for the session
     * @param $sessionID
     * @param $milliseconds
     */
//    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS);

    /**
     * @param $sessionID
     * @param $milliseconds
     * @return mixed
     */
    //function renewLock($sessionID, $milliseconds);

    /**
     * @param $sessionID
     * @return mixed
     */
    //function releaseLock($sessionID);

    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     * @return string newSessionID
     */
    //function setupZombieID($dyingSessionID, $zombieTimeMilliseconds);

    /**
     * @param $sessionID
     * @return mixed
     */
    //function findSessionIDFromZombieID($zombieSsessionID);
}

