<?php


namespace ASM\File;

use ASM\AsmException;

class SessionFilePath
{
    private $path;

    public function __construct($path)
    {
        if ($path === null || strlen($path) === 0) {
            throw new AsmException(
                sprintf(
                    "Invalid path SessionFilePath [%s]",
                    $path
                ),
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

