<?php


namespace ASM\Redis;


use ASM\AsmException;
use ASM\Redis\RedisDriver;
//use ASM\Session;
use ASM\ConcurrentSession;
use ASM\SessionManager;
use ASM\Data;

class RedisSession implements ConcurrentSession
{

    protected $sessionID = null;

    /**
     * @var RedisDriver
     */
    protected $redisDriver;

    /**
     * @var array
     */
    protected $data;
    
    protected $existsInStorage = false;

    protected $sessionManager = false;


//    /**
//     * @var string This is random for each lock. It allows us to detect when another
//     * process has force released the lock, and it is no longer owned by this process.
//     */
//    protected $lockNumber;


    function __construct($sessionID, RedisDriver $redisDriver, SessionManager $sessionManager)
    {
        $this->sessionID = $sessionID;
        $this->redisDriver = $redisDriver;
        $this->sessionManager = $sessionManager;
    }

    /**
     * @return bool
     */
    function isPersisted()
    {
        return (!($this->sessionID === null));
    }

    /**
     * @param $caching
     * @param null $lastModifiedTime
     * @param bool $domain
     * @param null $path
     * @param bool $secure
     * @param bool $httpOnly
     * @return array
     * @throws AsmException
     */
    function getHeaders($caching,
                        $lastModifiedTime = null,
                        $domain = false,
                        $path = null,
                        $secure = false,
                        $httpOnly = true)
    {
        if (!$this->sessionID) {
            throw new AsmException("Cannot generate headers, session has not been saved to storage.");
        }

//        var_dump($this->sessionManager->getName());
//        exit(0);
        
        $time = time();
        
        $headers = [];
        $headers[] = generateCookieHeader($time,
            $this->sessionManager->getName(),
            $this->sessionID,
            $this->sessionManager->getLifetime(),
            $path,
            $domain,
            $secure,
            $httpOnly);

        $expireTime = $time + $this->sessionManager->getLifetime();

        $cachingHeaders = getCacheHeaders(
            $caching,
            $expireTime,
            $lastModifiedTime
        );
        
        $headers = array_merge($headers, $cachingHeaders);
        
        return $headers;
    }


    /**
     * @return mixed
     */
    function getSessionId()
    {
        return $this->sessionID;
    }

//    /**
//     * @param string $data
//     * @internal param $sessionID
//     * @internal param string $saveData
//     */
//    function saveData($data)
//    {
//        $this->redisDriver->save($this->sessionID, $data);
//    }

    /**
     *
     */
    function loadData()
    {
        $this->data = $this->redisDriver->read($this->sessionID);

        return $this->data;
    }

    function &getData()
    {
        if ($this->data == null) {
            $this->loadData();
        }

        return $this->data;
    }

    function setData(array $data)
    {
        $this->data = $data;
    }
    
    
    function save()
    {
        $this->redisDriver->save($this->sessionID, $this->data);
    }
    
    /**
     *
     */
    function close()
    {
        //releaseLock
        //    $this->__destruct();
    }


    /**
     * @param $sessionID
     * @param $index
     * @return int
     */
    function get($index)
    {
        return $this->redisDriver->get($this->sessionID, $index);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $value
     * @return int
     */
    function set($index, $value)
    {
        return $this->redisDriver->set($this->sessionID, $index, $value);
    }


    /**
     * @param $sessionID
     * @param $index
     * @param $increment
     * @return int
     */
    function increment($index, $increment)
    {
        return $this->redisDriver->increment($this->sessionID, $index, $increment);
    }

    /**
     * @param $sessionID
     * @param $index
     * @return array
     */
    function getList($index)
    {
        return $this->redisDriver->getList($this->sessionID, $index);
    }

    /**
     * @param $sessionID
     * @param $key
     * @param $value
     * @return int
     */
    function appendToList($key, $value)
    {
        $result =  $this->redisDriver->appendToList($this->sessionID, $key, $value);

        return $result;
    }

    /**
     * @param $sessionID
     * @param $index
     * @return int
     */
    function clearList($index)
    {
        return $this->redisDriver->clearList($this->sessionID, $index);
    }


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


