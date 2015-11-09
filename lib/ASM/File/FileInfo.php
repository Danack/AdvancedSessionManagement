<?php


namespace ASM\File;

/**
 * Class FileInfo
 * @package ASM\File
 */
class FileInfo
{
    public $lockFileHandle;

    public function __construct($lockFileHandle)
    {
        $this->lockFileHandle = $lockFileHandle;
    }
}

