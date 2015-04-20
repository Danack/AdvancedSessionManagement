<?php


namespace ASM\Driver;


interface Driver {

    /**
     * Acquire a lock for the session
     * @param $sessionID
     * @param $milliseconds
     */
    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS);

    /**
     * @param $sessionID
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($sessionID, $milliseconds);

    /**
     * @param $sessionID
     * @return mixed
     */
    function releaseLock($sessionID);

    /**
     * Test whether the driver thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     * @param $sessionID
     * @return boolean
     */
    function isLocked($sessionID);

    /**
     * @param $sessionID
     * @return boolean
     */
    function validateLock($sessionID);
    
    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLock($sessionID);

    /**
     * @param $sessionID
     * @return mixed
     */
    function findSessionIDFromZombieID($sessionID);

    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     */
    function setupZombieID($dyingSessionID, $newSessionID, $zombieTimeMilliseconds);

    /**
     * @param $sessionID
     * @param $saveData
     */
    function save($sessionID, $saveData);
    
    /**
     * @return mixed
     */
    function destroyExpiredSessions();
    
    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID);

    /**
     * @param $sessionID
     * @param $sessionProfile
     */
    function addProfile($sessionID, $sessionProfile);

    /**
     * @param $sessionID
     * @return mixed
     */
    function getStoredProfile($sessionID);

    /**
     * @param $sessionID
     * @param array $sessionProfiles
     */
    function storeSessionProfiles($sessionID, array $sessionProfiles);
}

