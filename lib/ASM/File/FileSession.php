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


//    /**
//     * @var string This is random for each lock. It allows us to detect when another
//     * process has force released the lock, and it is no longer owned by this process.
//     */
//    protected $lockContents = null;


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
        // TODO: Implement acquireLock() method.
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
        //TODO - implement
    }
}

