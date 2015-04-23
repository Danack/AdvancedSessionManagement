<?php


namespace ASM\Driver;


class FileDriverOpen implements DriverOpen {


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
     * @param $fileHandle
     * @param FileDriver $fileDriver
     */
    function __construct($sessionID, FileDriver $fileDriver)
    {
        $this->sessionID = $sessionID;
        //$this->fileHandle = $fileHandle;
        $this->fileDriver = $fileDriver;
    }

    /**
     * @return mixed
     */
    function getSessionID()
    {
        return $this->sessionID;
    }


    /**
     *
     */
    function __destruct() {
//        if ($this->fileHandle != null) {
//            fclose($this->fileHandle);
//        }
//        $this->fileHandle = null;
    }
    
    /**
     * @param $sessionID
     * @param $saveData string
     */
    function save($data) {
        //$sessionID =
            $this->fileDriver->save($this->sessionID, $data);

    }

    /**
     *
     */
    function readData()
    {
        $data = $this->fileDriver->read($this->sessionID);
        
        return $data;
    }
    
    

    function close() {
        //releaseLock
        //    $this->__destruct();
    }
    
}

