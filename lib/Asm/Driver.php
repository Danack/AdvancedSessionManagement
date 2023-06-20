<?php

namespace Asm;

use Asm\Profile\SimpleProfile;

/**
 * Interface Driver
 *
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
     * @param string $sessionID
     * @param Encrypter $encrypter
     * @param SessionManager $sessionManager
     * @param ?SimpleProfile $userProfile
     * @return Session|null The newly opened session
     */
    public function openSessionByID(
        string $sessionID,
        Encrypter $encrypter,
        SessionManager $sessionManager,
        ?SimpleProfile $userProfile
    ): ?Session;


    public function createSession(
        Encrypter $encrypter,
        SessionManager $sessionManager,
        SimpleProfile $userProfile = null
    ): Session;


    /**
     * Destroy any sessions that are managed by this driver that have expired
     * @return mixed
     */
    //function destroyExpiredSessions();

    /**
     * Delete a single session that matches the $sessionID
     */
    public function deleteSessionByID(string $sessionID): void;


    public function forceReleaseLockByID(string $sessionID): void;

    //function findSessionIDFromZombieID($zombieSsessionID);
}
