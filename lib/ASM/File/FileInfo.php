<?php


namespace ASM\File;


class FileInfo {

    public $fileHandle;

    public $isLocked;

    public function __construct($fileHandle, $isLocked)
    {
        $this->fileHandle = $fileHandle;
        $this->isLocked = $isLocked;
    }
}

