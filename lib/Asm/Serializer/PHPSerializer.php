<?php

namespace Asm\Serializer;

use Asm\Serializer;

class PHPSerializer implements Serializer
{
    /**
     * @param array $data
     * @return mixed
     */
    public function serialize(array $data)
    {
        return \serialize($data);
    }

    /**
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        return unserialize($string);
    }
}
