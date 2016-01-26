<?php


namespace ASM\File;

use ASM\AsmException;

class SessionFilePath
{
    private $path;

    public function __construct($path)
    {
        if ($path === null) {
            throw new AsmException(
                "Path cannot be null for class ".get_class($this),
                AsmException::BAD_ARGUMENT
            );
        }
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}

