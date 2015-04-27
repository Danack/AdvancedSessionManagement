<?php


namespace ASM\Mock;

use ASM\AsmException;
use ASM\FailedToAcquireLockException;
use ASM\SessionManagerInterface;


class MockSessionManager implements SessionManagerInterface {
    /**
     * @return mixed
     */
    function getName()
    {
        return "TestSessionName";
    }

    /**
     * @return mixed
     */
    function getLifetime()
    {
        return 3600;
    }

    /**
     * Opens an existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise returns null.
     *
     * @param null $userProfile
     * @return null|\ASM\Session
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    public function openSession($userProfile = null)
    {
        throw new \Exception("Not implemented");
    }

    /**
     * Create a new session or open existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise creates a new session.
     *
     * @param $userProfile
     * @return \ASM\Session
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    function createSession($userProfile = null)
    {
        throw new \Exception("Not implemented");
    }

    /**
     *
     */
    function destroyExpiredSessions()
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @param $sessionID
     */
    function deleteSession($sessionID)
    {
        throw new \Exception("Not implemented");
    }
}

