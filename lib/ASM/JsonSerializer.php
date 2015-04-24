<?php


namespace ASM;

class JsonSerializer implements Serializer {

    /**
     * @param array $data
     * @return mixed
     */
    function serialize(array $data) {
        return \json_encode($data);
    }

    /**
     * @param string $string
     * @return mixed
     */
    function unserialize($string) {
        return json_decode($string, true);
    }
}

