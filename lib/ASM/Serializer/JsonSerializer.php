<?php

namespace ASM\Serializer;

use ASM\Serializer;

class JsonSerializer implements Serializer
{
    /**
     * @param array $data
     * @return mixed
     */
    public function serialize(array $data)
    {
        return \json_encode($data);
    }

    /**
     * @param string $string
     * @return mixed
     */
    public function unserialize($string)
    {
        return json_decode($string, true);
    }
}
