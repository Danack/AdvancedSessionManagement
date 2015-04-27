<?php




namespace ASM;

interface SessionManagerInterface
{
    /**
     * @return mixed
     */
    function getName();

    /**
     * @return mixed
     */
    function getLifetime();

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
    public function openSession($userProfile = null);

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
    function createSession($userProfile = null);

    /**
     *
     */
    function destroyExpiredSessions();

    /**
     * @param $sessionID
     */
    function deleteSession($sessionID);
}