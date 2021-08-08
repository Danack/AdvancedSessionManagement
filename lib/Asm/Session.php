<?php


namespace Asm;

interface Session
{

    public function getHeaders(
        string $privacy,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true
    ): array;

    /**
     * @return mixed
     */
    public function getSessionId();

    /**
     * Get all of the data stored in this session.
     * @return array
     */
    public function getData();

    /**
     * Set all of the data stored in this session.
     *
     * @param array $data
     * @return mixed
     */
    public function setData(array $data);

    /**
     * @return mixed
     */
    public function save();

    /**
     * Close the session
     * @param bool $saveData
     * @return mixed
     */
    public function close(bool $saveData = true);

    /**
     * Deletes the Session from memory and storage.
     * @return mixed
     */
    public function delete();
    

    /**
     * A session should attempt to release any locks when it is destructed.
     */
    public function __destruct();
    
    /**
     * Test whether the session thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     *
     * @return boolean
     */
    public function isLocked(): bool;

    public function validateLock(): bool;

    /**
     * Acquire a lock for the session, or renew it if the session already
     * has a lock.
     * @param int $lockTimeMS - the amount of time the session is locked for once
     * the lock is acquired.
     * @param int $acquireTimeoutMS - the amount of time to wait to acquire the
     * lock before giving up and throwing an exception.
     * @return mixed
     */
    public function acquireLock(int $lockTimeMS, int $acquireTimeoutMS);

    /**
     * Renew the lock the session has on the data.
     *
     * If the lock has been broken by another process, an exception
     * will be thrown, to prevent data loss through concurrent modification.
     *
     * @param int $milliseconds
     * @return mixed
     * @throws AsmException
     */
    public function renewLock(int $milliseconds);

    /**
     * Release the lock the session has on the data.
     * TODO - should this throw an exception if the lock was already lost?
     * @return mixed
     */
    public function releaseLock();

    /**
     * TODO - naming...
     * @return mixed
     */
    public function forceReleaseLocks();

    /**
     * Is the session active or not? Sessions are active either if the client
     * sent a session cookie to the server, or if some data has been written
     * to the session.
     * If sessions are not active, there is no need to send the session cookie
     * to the client.
     * @return bool
     */
    public function isActive();

    //function setupZombieID($dyingSessionID, $zombieTimeMilliseconds);

    //function findSessionIDFromZombieID($zombieSsessionID);
    
    public function set(string $name, int|bool|array|string|float $value): void;

    public function get(
        string $name,
        int|bool|array|string|float $default = null,
        bool $clear = false
    ): int|bool|array|string|float|null;
}
