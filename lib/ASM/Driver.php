<?php


namespace ASM;



interface Driver
{

    const E_SESSION_ID_CLASS = 1;

    /**
     * Open an existing session. Returns either the opened session or null if
     * the session could not be found.
     * @param $sessionID
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return Session|null The newly opened session
     */

    function openSession($sessionID, SessionManager $sessionManager, $userProfile = null);

    /**
     * Create a new session.
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return Session The newly opened session.
     */
    function createSession(SessionManager $sessionManager, $userProfile = null);


    /**
     * @return mixed
     */
    //function destroyExpiredSessions();

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSession($sessionID);

    function releaseLock($sessionID, $lockToken);
    
    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS);

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLock($sessionID);

}

