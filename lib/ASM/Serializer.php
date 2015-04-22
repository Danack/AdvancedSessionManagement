<?php


namespace ASM;


interface Serializer {

    /**
     * @param array $data
     * @return mixed
     */
    function serialize(array $data);

    /**
     * @param string $string
     * @return mixed
     */
    function unserialize($string);
}

