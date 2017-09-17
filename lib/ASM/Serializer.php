<?php

namespace ASM;

interface Serializer
{
    /**
     * Convert a value into a string representation.
     * @param array $data
     * @return mixed
     */
    public function serialize(array $data);

    /**
     * Creates a value from a stored representation
     * @param string $string
     * @return mixed
     */
    public function unserialize($string);
}
