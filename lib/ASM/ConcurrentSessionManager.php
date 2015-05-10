<?php


namespace ASM;


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
     * @param array $cookieData
     * @param null $userProfile
     * @return ConcurrentSession|null
     */
    public function openSession(array $cookieData, $userProfile = null)
    {
        return parent::openSession($cookieData, $userProfile);
    }

    /**
     * Create a new session or open existing session.
     *
     * Opens and returns the data for an existing session, if and only if the
     * client sent a valid existing session ID. Otherwise creates a new session.
     *
     * @param array $cookieData
     * @param $userProfile
     * @return ConcurrentSession
     */
    function createSession(array $cookieData, $userProfile = null)
    {
        return parent::createSession($cookieData, $userProfile);
    }
}

