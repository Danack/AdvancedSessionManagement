<?php

namespace Asm\Serializer;

use Asm\Serializer;

use function JsonSafe\json_encode_safe;

class JsonSerializer implements Serializer
{
    /**
     * @param array $data
     * @return mixed
     */
    public function serialize(array $data)
    {
        return json_encode_safe($data);
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
