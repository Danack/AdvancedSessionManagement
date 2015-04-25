<?php


namespace ASM;

use ASM\Driver\ConcurrentDriver;

/**
 * Class ConcurrentSession
 * @package ASM
 */
class ConcurrentSession extends SessionManager
{

    /**
     * @var ConcurrentDriver
     */
    protected $driver;

    function __construct(
        SessionConfig $sessionConfig,
        $openMode,
        $cookieData,
        ConcurrentDriver $driver,
        ValidationConfig $validationConfig = null)
    {
        parent::__construct(
            $sessionConfig,
            $openMode,
            $cookieData,
            $driver,
            $validationConfig
        );

        $this->driver = $driver;
    }

//    /**
//     * @param $index
//     * @return string
//     */
//    function get($index) {
//        return $this->driver->get($this->sessionID, $index);
//    }
//
//    /**
//     * @param $index
//     * @param $value
//     * @return int
//     */
//    function set($index, $value) {
//        return $this->driver->set($this->sessionID, $index, $value);
//    }
//
//    function increment($index, $increment = 1) {
//        return $this->driver->increment($this->sessionID, $index, $increment);
//    }
//
//    /**
//     * @param $index
//     * @return array
//     */
//    function getList($index) {
//        return $this->driver->getList($this->sessionID, $index);
//    }
//
//    /**
//     * @param $index
//     * @param $value
//     * @return int
//     */
//    function appendToList($index, $value) {
//        //$key = generateAsyncKey($this->sessionID, $index);
//        return $this->driver->appendToList($this->sessionID, $index, [$value]);
//    }
//
//    /**
//     * @param $index
//     * @return int
//     */
//    function clearList($index) {
//        return $this->driver->clearList($this->sessionID, $index);
//    }
}

