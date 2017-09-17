<?php

namespace ASM;

/**
 * Interface Driver
 * This interface is called by the SessionManager. Each session implementation
 * is free to have other functionality as needed.
 * @package ASM
 */
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
    public function openSessionByID($sessionID, SessionManager $sessionManager, $userProfile = null);

    /**
     * Create a new session.
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return Session The newly opened session.
     */
    public function createSession(SessionManager $sessionManager, $userProfile = null);


    /**
     * Destroy any sessions that are managed by this driver that have expired
     * @return mixed
     */
    //function destroyExpiredSessions();

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    public function deleteSessionByID($sessionID);

    /**
     * @param $sessionID
     * @return mixed
     */
    public function forceReleaseLockByID($sessionID);

    //function findSessionIDFromZombieID($zombieSsessionID);
}
