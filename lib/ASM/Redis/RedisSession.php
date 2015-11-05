<?php


namespace ASM\Redis;

use ASM\AsmException;
use ASM\ConcurrentSession;
use ASM\SessionManager;

class RedisSession implements ConcurrentSession
{

    protected $sessionId = null;

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
        $this->sessionId = $sessionID;
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
        return $this->sessionManager->getHeaders(
            $this->sessionId,
            $caching,
            $lastModifiedTime,
            $domain,
            $path,
            $secure,
            $httpOnly
        );
    }


    /**
     * @return mixed
     */
    function getSessionId()
    {
        return $this->sessionId;
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
            $this,
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
        $this->redisDriver->deleteSessionByID($this->sessionId);
        $this->releaseLock();
    }

    function acquireLock($lockTimeMS, $acquireTimeoutMS)
    {
        throw new \Exception("Not implemented");
    }
    
    
    /**
     * 
     */
    function releaseLock()
    {
        if ($this->lockToken) {
            $lockToken = $this->lockToken;
            $this->lockToken = null;
            $this->redisDriver->releaseLock($this->sessionId, $lockToken);
        }
    }

    /**
     * @param $index
     * @return int
     */
    function get($index)
    {
        return $this->redisDriver->get($this->sessionId, $index);
    }


    /**
     * @param $index
     * @param $value
     * @return int
     */
    function set($index, $value)
    {
        return $this->redisDriver->set($this->sessionId, $index, $value);
    }


    /**
     * @param $index
     * @param $increment
     * @return int
     */
    function increment($index, $increment)
    {
        return $this->redisDriver->increment($this->sessionId, $index, $increment);
    }

    /**
     * @param $index
     * @return array
     */
    function getList($index)
    {
        return $this->redisDriver->getList($this->sessionId, $index);
    }

    /**
     * @param $key
     * @param $value
     * @return int
     */
    function appendToList($key, $value)
    {
        $result = $this->redisDriver->appendToList($this->sessionId, $key, $value);

        return $result;
    }

    /**
     * @param $index
     * @return int
     */
    function clearList($index)
    {
        return $this->redisDriver->clearList($this->sessionId, $index);
    }


    /**
     * @param $milliseconds
     * @throws AsmException
     * @internal param $sessionID
     * @return mixed
     */
    function renewLock($milliseconds)
    {
        $this->redisDriver->renewLock($this->sessionId, $this->lockToken, $milliseconds);
    }

}


