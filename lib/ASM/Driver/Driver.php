<?php


namespace ASM\Driver;


interface Driver {

    /**
     * Open an existing session. Returns either the session data or null if 
     * the session could not be found.
     * @param $sessionID
     * @return string|false
     */
    function openSession($sessionID);

    /**
     * Create a new session.
     * @return string The newly created session ID.
     */
    function createSession();

    /**
     * @param $sessionID
     * @param string $saveData 
     */
    function save($sessionID, $saveData);

    function close();
    
    //function getData($sessionID);

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
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     * @return string newSessionID
     */
    function setupZombieID($dyingSessionID, $zombieTimeMilliseconds);

    /**
     * @param $sessionID
     * @return mixed
     */
    function findSessionIDFromZombieID($zombieSsessionID);
    
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

