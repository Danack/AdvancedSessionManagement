<?php


namespace ASM;


interface Session
{

    /**
     * @param $privacy int one of the \ASM\SessionManager::CACHE_* constants
     * @param null $path
     * @param bool $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return mixed
     */
    function getHeaders($privacy,
                        $path = null,
                        $domain = false,
                        $secure = false,
                        $httpOnly = true);

    /**
     * @return mixed
     */
    function getSessionId();

    /**
     * @return array
     */
    function getData();

    /**
     * @param array $data
     * @return mixed
     */
    function setData(array $data);

    /**
     * @return mixed
     */
    function save();

    /**
     * Close the session
     * @param bool $saveData
     * @return mixed
     */
    function close($saveData = true);

    /**
     * Deletes the Session from memory and storage.
     * @return mixed
     */
    function delete();
    

    /**
     * A session should attempt to release any locks when it is destructed.
     */
    function __destruct();
    
    /**
     * Test whether the session thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     *
     * @return boolean
     */
    function isLocked();

    /**
     * If the driver
     * @param $sessionID
     * @return boolean
     */
    function validateLock();

    /**
     * Acquire a lock for the session, or renew it if the session already
     * has a lock.
     * @param int $lockTimeMS - the amount of time the session is locked for once
     * the lock is acquired.
     * @param int $acquireTimeoutMS - the amount of time to wait to acquire the
     * lock before giving up and throwing an exception.
     * @return mixed
     */
    function acquireLock($lockTimeMS, $acquireTimeoutMS);

    /**
     * Renew the lock the session has on the data.
     * 
     * If the lock has been broken by another process, an exception
     * will be thrown, to prevent data loss through concurrent modification. 
     * 
     * @param $milliseconds
     * @return mixed
     * @throws AsmException
     */
    function renewLock($milliseconds);

    /**
     * Release the lock the session has on the data.
     * TODO - should this throw an exception if the lock was already lost? 
     * @return mixed
     */
    function releaseLock();

    /**
     * TODO - naming...
     * @return mixed
     */
    function forceReleaseLocks();

    /**
     * Is the session active or not? Sessions are active either if the client
     * sent a session cookie to the server, or if some data has been written
     * to the session.
     * If sessions are not active, there is no need to send the session cookie
     * to the client.
     * @return bool
     */
    function isActive();

    /**
     * @param $dyingSessionID
     * @param $newSessionID
     * @param $zombieTimeMilliseconds
     * @return string newSessionID
     */
     //function setupZombieID($dyingSessionID, $zombieTimeMilliseconds);

    /**
     * @param $sessionID
     * @return mixed
     */
    //function findSessionIDFromZombieID($zombieSsessionID);
    
    function setSessionVariable($name, $value);
    function getSessionVariable($name, $default = false, $clear = false);
}

