<?php


namespace ASM\Serializer;
use ASM\Serializer;

class PHPSerializer implements Serializer
{
    /**
     * @param array $data
     * @return mixed
     */
    function serialize(array $data)
    {
        return \serialize($data);
    }

    /**
     * @param string $string
     * @return mixed
     */
    function unserialize($string)
    {
        return unserialize($string);
    }
}

