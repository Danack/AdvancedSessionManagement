<?php

namespace ASM;


class Data implements \ArrayAccess {

    private $container = array();

    public function __construct(array $data) {
        $this->container = $data;
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->container)) {
            return $this->container[$offset];
        }
        return null;
    }
    
    public function getArray()
    {
        return $this->container;
    }
}