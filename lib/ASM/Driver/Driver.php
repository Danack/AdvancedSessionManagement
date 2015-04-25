<?php


namespace ASM\Driver;


use ASM\Session;

interface Driver
{

    const E_SESSION_ID_CLASS = 1;

    /**
     * Open an existing session. Returns either the opened session or null if
     * the session could not be found.
     * @param $sessionID
     * @return Session|null The newly opened session
     */
    function openSession($sessionID);

    /**
     * Create a new session.
     * @return Session The newly opened session.
     */
    function createSession();

    /**
     * @param $sessionID
     * @return mixed
     */
//    function forceReleaseLock($sessionID);

    /**
     * @return mixed
     */
    //function destroyExpiredSessions();

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID);

    /**
     * @param $sessionID
     * @param $sessionProfile
     */
    //function addProfile($sessionID, $sessionProfile);

    /**
     * @param $sessionID
     * @return string[]
     */
    //function getStoredProfiles($sessionID);

    /**
     * @param $sessionID
     * @param array $sessionProfiles
     */
    //function storeSessionProfiles($sessionID, array $sessionProfiles);
}

