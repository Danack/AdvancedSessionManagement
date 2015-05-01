<?php


namespace ASM;


interface Serializer
{
    /**
     * Convert a value into a string representation.
     * @param array $data
     * @return mixed
     */
    function serialize(array $data);

    /**
     * Creates a value from a stored representation
     * @param string $string
     * @return mixed
     */
    function unserialize($string);
}

