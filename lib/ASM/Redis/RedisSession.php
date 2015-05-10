<?php


namespace ASM\Redis;


use ASM\AsmException;
use ASM\Redis\RedisDriver;
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

    protected $sessionManager = false;

    /**
     * @var array
     */
    protected $data;

    protected $currentProfiles;

    /**
     * @var string A token for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     */
    protected $lockToken;

    function __construct(
        $sessionID,
        RedisDriver $redisDriver,
        SessionManager $sessionManager,
        array $data,
        array $currentProfiles,
        $lockToken = null)
    {
        $this->sessionID = $sessionID;
        $this->redisDriver = $redisDriver;
        $this->sessionManager = $sessionManager;
        $this->data = $data;
        $this->currentProfiles = $currentProfiles;
        $this->lockToken = $lockToken;
    }

    function __destruct()
    {
        $this->releaseLock();
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

    function &getData()
    {
        return $this->data;
    }

    function setData(array $data)
    {
        $this->data = $data;
    }

    function save()
    {
        $this->redisDriver->save(
            $this->sessionID,
            $this->data,
            $this->currentProfiles
        );
    }

    /**
     * @param bool $saveData
     * @return mixed|void
     */
    function close($saveData = true)
    {
        if ($saveData) {
            $this->save();
        }

        $this->releaseLock();
        $this->redisDriver->close();
    }

    function delete()
    {
        $this->redisDriver->deleteSession($this->sessionID);
        $this->releaseLock();
    }

    /**
     * 
     */
    protected function releaseLock()
    {
        if ($this->lockToken) {
            $lockToken = $this->lockToken;
            $this->lockToken = null;
            $this->redisDriver->releaseLock($this->sessionID, $lockToken);
        }
    }

    /**
     * @param $index
     * @return int
     */
    function get($index)
    {
        return $this->redisDriver->get($this->sessionID, $index);
    }


    /**
     * @param $index
     * @param $value
     * @return int
     */
    function set($index, $value)
    {
        return $this->redisDriver->set($this->sessionID, $index, $value);
    }


    /**
     * @param $index
     * @param $increment
     * @return int
     */
    function increment($index, $increment)
    {
        return $this->redisDriver->increment($this->sessionID, $index, $increment);
    }

    /**
     * @param $index
     * @return array
     */
    function getList($index)
    {
        return $this->redisDriver->getList($this->sessionID, $index);
    }

    /**
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
     * @param $index
     * @return int
     */
    function clearList($index)
    {
        return $this->redisDriver->clearList($this->sessionID, $index);
    }


    /**
     * @param $sessionID
     * @param $milliseconds
     * @return mixed
     */
    function renewLock($milliseconds)
    {
        $this->redisDriver->renewLock($this->sessionID, $this->lockToken, $milliseconds);
    }

}


