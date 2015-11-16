<?php

namespace {

    $mockFunctions = [];

    function resetMockFunctions()
    {
        global $mockFunctions;
        $mockFunctions = [];
    }

    function mock($functionName, callable $fn)
    {
        global $mockFunctions;
        $mockFunctions[$functionName] = $fn;
    }
}


namespace ASM\File {

    function file_put_contents($filename, $data, $flags = 0, $context = null)
    {
        global $mockFunctions;
        
        if (array_key_exists('file_put_contents', $mockFunctions) == true) {
            $fn = $mockFunctions['file_put_contents'];
            if ($context != null) {
                return $fn($filename, $data, $flags, $context);
            }
            return $fn($filename, $data, $flags);
        }

        if ($context != null) {
            return \file_put_contents($filename, $data, $flags, $context);
        }

        return \file_put_contents($filename, $data, $flags);
    }
    
    function rename($oldname, $newname, $context = null)
    {
        global $mockFunctions;

        if (array_key_exists('rename', $mockFunctions) == true) {
            $fn = $mockFunctions['rename'];
            if ($context != null) {
                return $fn($oldname, $newname, $context);
            }
            return $fn($oldname, $newname);
        }

        if ($context != null) {
            return \rename($oldname, $newname, $context);
        }

        return \rename($oldname, $newname);
    }

    function stat($filename)
    {
        global $mockFunctions;

        if (array_key_exists('stat', $mockFunctions) == true) {
            $fn = $mockFunctions['stat'];

            return $fn($filename);
        }

        return \stat($filename);
    }

    function fstat($filehandle)
    {
        global $mockFunctions;

        if (array_key_exists('fstat', $mockFunctions) == true) {
            $fn = $mockFunctions['fstat'];

            return $fn($filehandle);
        }

        return \fstat($filehandle);
    }


    function fopen($filename, $mode, $use_include_path = false, $context = null)
    {
        global $mockFunctions;

        if (array_key_exists('fopen', $mockFunctions) == true) {
            $fn = $mockFunctions['fopen'];

            return $fn(
                $filename,
                $mode,
                $use_include_path,
                $context
            );
        }

        if ($context !== null) {
            return \fopen($filename, $mode, $use_include_path, $context);
        }

        return \fopen($filename, $mode, $use_include_path);
    }
}

namespace ASM\Redis {

}




