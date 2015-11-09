<?php


namespace ASM\File;


use ASM\Session;
use ASM\SessionManager;

class FileSession implements Session
{

    protected $sessionId;


    protected $data = null;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * @var null
     */
    protected $userProfiles = null;

    /**
     * @var FileInfo
     */
    protected $fileInfo;

    /**
     * @param $sessionId
     * @param FileDriver $fileDriver
     * @param SessionManager $sessionManager
     * @param array $userProfiles
     * @param FileInfo $fileInfo
     */
    function __construct(
        $sessionId,
        $data, 
        FileDriver $fileDriver,
        SessionManager $sessionManager,
        array $userProfiles,
        FileInfo $fileInfo)
    {
        $this->sessionId = $sessionId;
        $this->data = $data;
        $this->fileDriver = $fileDriver;
        $this->sessionManager = $sessionManager;
        $this->userProfiles = $userProfiles;
        $this->fileInfo = $fileInfo;
    }


    function getHeaders($caching,
                        $lastModifiedTime = null,
                        $path = null,
                        $domain = false,
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

    /**
     *
     */
    function __destruct()
    {
        $this->releaseLock();
    }


    function save()
    {
        $this->fileDriver->save(
            $this->sessionId,
            $this->data,
            $this->userProfiles,
            $this->fileInfo
        );
    }

    function delete()
    {
        $this->fileDriver->deleteSessionByID($this->sessionId);
    }

    function setData(array $data)
    {
        $this->data = $data;
    }


    function &getData()
    {
        return $this->data;
    }

    /**
     * @param bool $saveData
     */
    function close($saveData = true)
    {
        $this->fileDriver->close($this->fileInfo);
    }

    function acquireLock($lockTimeMS, $acquireTimeoutMS)
    {
        $fileHandle = $this->fileDriver->acquireLock(
            $this->getSessionId(),
            $lockTimeMS,
            $acquireTimeoutMS
        );
        
        $this->fileInfo->lockFileHandle = $fileHandle;
    }

    function releaseLock()
    {
        $this->fileDriver->releaseLock($this->fileInfo);
    }

    /**
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($milliseconds)
    {
        $this->fileDriver->renewLock(
            $this->getSessionId(),
            $milliseconds,
            $this->fileInfo
        );
    }

    function forceReleaseLocks()
    {
        return $this->fileDriver->forceReleaseLockByID($this->getSessionId());
    }

    function validateLock()
    {
        return $this->fileDriver->validateLock(
            $this->getSessionId(),
            $this->fileInfo
        );
    }

    function isLocked()
    {
        if ($this->fileInfo->lockFileHandle == null) {
            return false;
        }

        return true;
    }
}

