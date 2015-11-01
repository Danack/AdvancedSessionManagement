<?php


namespace ASM;


interface Session
{

    /**
     * @param $caching one of the \ASM\SessionManager::CACHE_SKIP constants
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
     * @param bool $saveData
     * @return mixed
     */
    function close($saveData = true);
    
    /**
     * Test whether the driver thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     * @param $sessionID
     * @return boolean
     */
    //function isLocked($sessionID);

    /**
     * @param $sessionID
     * @return boolean
     */
    //function validateLock($sessionID);

    /**
     * Acquire a lock for the session
     * @param $sessionID
     * @param $milliseconds
     */
//    function acquireLock($sessionID, $lockTimeMS, $acquireTimeoutMS);

    /**
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($milliseconds);

    //function acquireLock($lockTimeMS, $acquireTimeoutMS);

    function releaseLock();

    /**
     * @param $sessionID
     * @return mixed
     */
    //function releaseLock($sessionID);

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

