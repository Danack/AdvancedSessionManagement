<?php


namespace ASM\APC;


use ASM\AsmException;
use ASM\Session;
use ASM\SessionManager;


class APCSession implements Session
{

    protected $sessionId = null;

    /**
     * @var APCDriver
     */
    protected $apcDriver;

    protected $sessionManager = false;

    /**
     * @var array
     */
    protected $data;

    protected $currentProfiles;

    /**
     * @var string A token for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockToken;

    function __construct(
        $sessionID,
        APCDriver $redisDriver,
        SessionManager $sessionManager,
        array $data,
        array $currentProfiles,
        $lockToken = null)
    {
        $this->sessionId = $sessionID;
        $this->apcDriver = $redisDriver;
        $this->sessionManager = $sessionManager;
        $this->data = $data;
        $this->currentProfiles = $currentProfiles;
        $this->lockToken = $lockToken;
    }

    function __destruct()
    {
        $this->releaseLock();
    }
    
    /**
     * @param $caching
     * @param null $lastModifiedTime
     * @param bool $domain
     * @param null $path
     * @param bool $secure
     * @param bool $httpOnly
     * @return array
     * @throws AsmException
     */
    function getHeaders($caching,
                        $lastModifiedTime = null,
                        $domain = false,
                        $path = null,
                        $secure = false,
                        $httpOnly = true)
    {
        return $this->sessionManager->getHeaders(
            $this->sessionId,
            $caching,
            $lastModifiedTime,
            $domain,
            $path,
            $secure,
            $httpOnly
        );
    }


    /**
     * @return mixed
     */
    function getSessionId()
    {
        return $this->sessionId;
    }

    function &getData()
    {
        return $this->data;
    }

    function setData(array $data)
    {
        $this->data = $data;
    }

    function save()
    {
        $this->apcDriver->save(
            $this->sessionId,
            $this->data,
            $this->currentProfiles,
            $this->sessionManager
        );
    }

    /**
     * @param bool $saveData
     * @return mixed|void
     */
    function close($saveData = true)
    {
        if ($saveData) {
            $this->save();
        }

        $this->releaseLock();
        $this->apcDriver->close();
    }

    function delete()
    {
        $this->apcDriver->deleteSession($this->sessionId);
        $this->releaseLock();
    }

    /**
     * 
     */
    function releaseLock()
    {
        if ($this->lockToken) {
            $lockToken = $this->lockToken;
            $this->lockToken = null;
            $this->apcDriver->releaseLock($this->sessionId, $lockToken);
        }
    }


    /**
     * @param $sessionID
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($milliseconds)
    {
        $this->apcDriver->renewLock($this->sessionId, $this->lockToken, $milliseconds);
    }
}


