<?php


namespace ASM\File;

use ASM\File\FileDriver;
use ASM\Session;
use ASM\SessionManager;

class FileSession implements Session
{

    protected $sessionID;
    
    
    protected $data = null;

//    /**
//     * @var bool
//     */
//    protected $fileHandle = null;

    /**
     * @var FileDriver
     */
    protected $fileDriver;


    /**
     * @var string This is random for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockContents = null;


    /**
     * @var null
     */
    protected $userProfile = null;

    /**
     * @param $sessionID
     * @param FileDriver $fileDriver
     * @param SessionManager $sessionManager
     * @param $userProfile
     */
    function __construct(
        $sessionID,
        FileDriver $fileDriver,
        SessionManager $sessionManager,
        $userProfile)
    {
        $this->sessionID = $sessionID;
        //$this->fileHandle = $fileHandle;
        $this->fileDriver = $fileDriver;
        $this->sessionManager = $sessionManager;
        $this->userProfile = $userProfile;
    }

    function isPersisted()
    {
        // TODO: Implement isPersisted() method.
    }

    function getHeaders($caching,
                        $lastModifiedTime = null,
                        $path = null,
                        $domain = false,
                        $secure = false,
                        $httpOnly = true)
    {
        // TODO: Implement getHeaders() method.
    }

    /**
     * @return mixed
     */
    function getSessionId()
    {
        return $this->sessionID;
    }

    /**
     *
     */
    function __destruct()
    {
//        if ($this->fileHandle != null) {
//            fclose($this->fileHandle);
//        }
//        $this->fileHandle = null;
    }

    /**
     * @param $data
     * @internal param $sessionID
     * @internal param string $saveData
     */
    function saveData($data)
    {
        //$sessionID =
        $this->fileDriver->save($this->sessionID, $data);

    }

    function save()
    {
        $this->fileDriver->save($this->sessionID, $this->data);
    }

    function setData(array $data)
    {
        $this->data = $data;
    }


    function &getData()
    {
        throw new \Exception("Not implemented yet.");
    }

    /**
     *
     */
    function loadData()
    {
        $data = $this->fileDriver->read($this->sessionID);

        return $data;
    }

    /**
     *
     */
    function close($saveData = true)
    {
        //releaseLock
        //    $this->__destruct();
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

