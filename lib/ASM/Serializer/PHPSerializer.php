<?php

namespace ASM\Serializer;

use ASM\Serializer;

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
