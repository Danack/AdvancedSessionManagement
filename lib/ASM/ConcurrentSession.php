<?php


namespace ASM;

use ASM\Driver\ConcurrentDriver;

class ConcurrentSession extends Session {


    function __construct(
        SessionConfig $sessionConfig,
        $openMode,
        $cookieData,
        ConcurrentDriver $driver,
        ValidationConfig $validationConfig = null
    ) {
        parent::__construct(
            $sessionConfig,
            $openMode,
            $cookieData,
            $driver,
        $validationConfig);
    }

    /**
     * @param $index
     * @return string
     */
    function get($index) {
        return $this->driver->get($this->sessionID, $index);
    }

    /**
     * @param $index
     * @param $value
     * @return int
     */
    function set($index, $value) {
        return $this->driver->set($this->sessionID, $index, $value);
    }

    function increment($index, $increment = 1) {
        return $this->driver->increment($this->sessionID, $index, $increment);
    }

    /**
     * @param $index
     * @return array
     */
    function getList($index) {
        return $this->driver->getList($this->sessionID, $index);
    }

    /**
     * @param $index
     * @param $value
     * @return int
     */
    function appendToList($index, $value) {
        $key = generateAsyncKey($this->sessionID, $index);
        return $this->driver->rpush($key, [$value]);
    }

    /**
     * @param $index
     * @return int
     */
    function clearList($index) {
        $key = generateAsyncKey($this->sessionID, $index);
        return $this->driver->del($key);
    }
}

