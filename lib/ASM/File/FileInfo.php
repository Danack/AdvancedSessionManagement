<?php


namespace ASM\File;

/**
 * Class FileInfo
 * @package ASM\File
 */
class FileInfo {

    public $fileHandle;

    /** 
     * @var boolean Whether the file is meant to be locked by the current process.
     * This may not always reflect reality if the file was force unlocked by 
     * another process.
     */
    public $isLocked;
    
    public $lockFileHandle;

    public function __construct($fileHandle, $lockFileHandle)
    {
        $this->fileHandle = $fileHandle;

        $this->lockFileHandle = $lockFileHandle;
    }
}

