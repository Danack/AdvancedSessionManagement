<?php


namespace ASM;

use ASM\ConcurrentDriver;

/**
 * Class ConcurrentSession
 * @package ASM
 */
class ConcurrentSessionManager extends SessionManager
{
    function __construct(
        SessionConfig $sessionConfig,
        ConcurrentDriver $driver,
        ValidationConfig $validationConfig = null)
    {
        parent::__construct(
            $sessionConfig,
            $driver,
            $validationConfig
        );
    }

    /**
     * Opens an existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise returns null.
     *
     * @param null $userProfile
     * @return null|\ASM\ConcurrentSession
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    public function openSession($userProfile = null)
    {
        return parent::openSession($userProfile);
    }

    /**
     * Create a new session or open existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise creates a new session.
     *
     * @param $userProfile
     * @return \ASM\ConcurrentSession
     * @throws AsmException
     * @throws FailedToAcquireLockException
     */
    function createSession($userProfile = null)
    {
        return parent::createSession($userProfile);
    }
}

