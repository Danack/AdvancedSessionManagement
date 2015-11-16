<?php


namespace ASMTest\Stub;

use ASM\AsmException;
use ASM\Driver;
use ASM\Session;
use ASM\SessionManager;

class NullDriver implements Driver
{

    /**
     * Open an existing session. Returns either the opened session or null if
     * the session could not be found.
     * @param $sessionID
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return Session|null The newly opened session
     */
    function openSessionByID($sessionID, SessionManager $sessionManager, $userProfile = null)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Create a new session.
     * @param SessionManager $sessionManager
     * @param null $userProfile
     * @return Session The newly opened session.
     */
    function createSession(SessionManager $sessionManager, $userProfile = null)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Delete a single session that matches the $sessionID
     * @param $sessionID
     */
    function deleteSessionByID($sessionID)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @param $sessionID
     * @return mixed
     */
    function forceReleaseLockByID($sessionID)
    {
        throw new \Exception("Not implemented");
    }


}
