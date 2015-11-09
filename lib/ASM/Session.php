<?php


namespace ASM;


interface Session
{

    /**
     * @param $caching int one of the \ASM\SessionManager::CACHE_* constants
     * @param null $lastModifiedTime
     * @param null $path
     * @param bool $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @return mixed
     */
    function getHeaders($caching,
                        $lastModifiedTime = null,
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
    function &getData();

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
     * @param $lockTimeMS
     * @param $acquireTimeoutMS
     * @return mixed
     */
    function acquireLock($lockTimeMS, $acquireTimeoutMS);

    /**
     * Renew the lock the session has on the data.
     * 
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($milliseconds);

    /**
     * Release the lock the session has on the data.
     * @return mixed
     */
    function releaseLock();

    /**
     * TODO - naming...
     * @return mixed
     */
    function forceReleaseLocks();


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
}

