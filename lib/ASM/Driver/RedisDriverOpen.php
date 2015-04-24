<?php


namespace ASM\Driver;


class RedisDriverOpen implements DriverOpen {


    protected $sessionID;


    /**
     * @var RedisDriver
     */
    protected $redisDriver;

//    /**
//     * @var string This is random for each lock. It allows us to detect when another
//     * process has force released the lock, and it is no longer owned by this process.
//     */
//    protected $lockNumber;
//    

    function __construct($sessionID, RedisDriver $redisDriver)
    {
        $this->sessionID = $sessionID;
        $this->redisDriver = $redisDriver;
    }

    /**
     * @return mixed
     */
    function getSessionID()
    {
        return $this->sessionID;
    }

    /**
     * @param $sessionID
     * @param $saveData string
     */
    function save($data) {
        //$sessionID =
        $this->redisDriver->save($this->sessionID, $data);

    }

    /**
     *
     */
    function readData()
    {
        $data = $this->redisDriver->read($this->sessionID);

        return $data;
    }

    /**
     *
     */
    function close()
    {
        //releaseLock
        //    $this->__destruct();
    }
    
//
//    /**
//     * @param $sessionID
//     * @param $index
//     * @return int
//     */
//    function get($sessionID, $index) {
//        $key = generateAsyncKey($sessionID, $index);
//
//        return $this->redisClient->hget($key, $index);
//    }
//
//
//    /**
//     * @param $sessionID
//     * @param $index
//     * @param $value
//     * @return int
//     */
//    function set($sessionID, $index, $value) {
//        $key = generateAsyncKey($sessionID, $index);
//
//        return $this->redisClient->hset($key, $index, $value);
//    }
//
//
//    /**
//     * @param $sessionID
//     * @param $index
//     * @param $increment
//     * @return int
//     */
//    function increment($sessionID, $index, $increment) {
//        $key = generateAsyncKey($sessionID, $index);
//
//        return $this->redisClient->hincrby($key, $index, $increment);
//    }
//
//    /**
//     * @param $sessionID
//     * @param $index
//     * @return array
//     */
//    function getList($sessionID, $index) {
//        $key = generateAsyncKey($sessionID, $index);
//
//        return $this->redisClient->lrange($key, 0, -1);
//    }
//
//    /**
//     * @param $sessionID
//     * @param $key
//     * @param $value
//     * @return int
//     */
//    function appendToList($sessionID, $key, $value) {
//        return $this->redisClient->rpush($key, [$value]);
//    }
//
//    /**
//     * @param $sessionID
//     * @param $index
//     * @return int
//     */
//    function clearList($sessionID, $index) {
//        $key = generateAsyncKey($sessionID, $index);
//        return $this->redisClient->del($key);
//    }



//    /**
//     * @param $sessionID
//     * @param $milliseconds
//     * @return mixed
//     */
//    function renewLock($sessionID, $milliseconds) {
//        $lockKey = generateLockKey($sessionID);
//        //TODO - this is not valid. It needs a script to check that the lockNumber
//        //is still valid.
//        $set = $this->redisClient->executeRaw([
//                'SET',
//                $lockKey,
//                $this->lockNumber,
//                'PX', $milliseconds,
//                'XX'
//            ]
//        );
//
//        return $set;
//    }

}


