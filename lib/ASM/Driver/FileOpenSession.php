<?php


namespace ASM\Driver;

use ASM\Session;
use ASM\SessionManagerInterface;

class FileOpenSession implements Session
{

    protected $sessionID;

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
     * @param $sessionID
     * @param FileDriver $fileDriver
     */
    function __construct($sessionID, FileDriver $fileDriver, SessionManagerInterface $sessionManager)
    {
        $this->sessionID = $sessionID;
        //$this->fileHandle = $fileHandle;
        $this->fileDriver = $fileDriver;
        $this->sessionManager = $sessionManager;
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
    function getSessionID()
    {
        return $this->sessionID;
    }

    function &getData()
    {
        throw new \Exception("Not implemented yet.");
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
    function close()
    {
        //releaseLock
        //    $this->__destruct();
    }
}

