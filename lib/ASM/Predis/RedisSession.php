<?php


namespace ASM\Predis;

use ASM\AsmException;
use ASM\Encrypter;
use ASM\Session;
use ASM\SessionManager;
use ASM\LostLockException;

class RedisSession implements Session
{
    protected $sessionId = null;

    /**
     * @var RedisDriver
     */
    protected $redisDriver;

    /** @var SessionManager */
    protected $sessionManager;

    /**
     * @var Encrypter
     */
    protected $encrypter;
    
    private $isActive = false;

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

    public function __construct(
        $sessionID,
        RedisDriver $redisDriver,
        SessionManager $sessionManager,
        Encrypter $encrypter,
        array $data,
        array $currentProfiles,
        $isActive,
        $lockToken
    ) {
        $this->sessionId = $sessionID;
        $this->redisDriver = $redisDriver;
        $this->sessionManager = $sessionManager;
        $this->encrypter = $encrypter;
        $this->data = $data;
        $this->currentProfiles = $currentProfiles;
        $this->isActive = $isActive;
        $this->lockToken = $lockToken;
    }

    /**
     *
     */
    public function __destruct()
    {
        $this->releaseLock();
    }
    
    /**
     * @param $privacy
     * @param null $lastModifiedTime
     * @param bool $domain
     * @param null $path
     * @param bool $secure
     * @param bool $httpOnly
     * @return array
     * @throws AsmException
     */
    public function getHeaders(
        $privacy,
        $path = null,
        $domain = false,
        $secure = false,
        $httpOnly = true
    ) {
        return $this->sessionManager->getHeaders(
            $this->sessionId,
            $privacy,
            $domain,
            $path,
            $secure,
            $httpOnly
        );
    }


    /**
     * @return mixed
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData(array $data)
    {
        $this->data = $data;
        $this->isActive = true;
    }

    public function save()
    {
        $this->redisDriver->save(
            $this,
            $this->encrypter,
            $this->data,
            $this->currentProfiles
        );
    }

    /**
     * @param bool $saveData
     * @return mixed|void
     */
    public function close($saveData = true)
    {
        if ($saveData) {
            $this->save();
        }

        $this->releaseLock();
    }

    public function delete()
    {
        $this->redisDriver->deleteSessionByID($this->sessionId);
        $this->releaseLock();
    }

    public function acquireLock($lockTimeMS, $acquireTimeoutMS)
    {
        $this->lockToken = $this->redisDriver->acquireLock(
            $this->sessionId,
            $lockTimeMS,
            $acquireTimeoutMS
        );
    }

    public function releaseLock()
    {
        if ($this->lockToken) {
            $lockToken = $this->lockToken;
            $this->lockToken = null;
            $this->redisDriver->releaseLock($this->sessionId, $lockToken);
        }
    }

    /**
     * @param $index
     * @param $increment
     * @return int
     */
    public function increment($index, $increment)
    {
        return $this->redisDriver->increment($this->sessionId, $index, $increment);
    }

    /**
     * @param $index
     * @return array
     */
    public function getList($index)
    {
        return $this->redisDriver->getList($this->sessionId, $index);
    }

    /**
     * @param $key
     * @param $value
     * @return int
     */
    public function appendToList($key, $value)
    {
        $result = $this->redisDriver->appendToList($this->sessionId, $key, $value);

        return $result;
    }

    /**
     * @param $index
     * @return int
     */
    public function clearList($index)
    {
        return $this->redisDriver->clearList($this->sessionId, $index);
    }

    /**
     * @param $milliseconds
     * @throws AsmException
     * @internal param $sessionID
     * @return mixed
     */
    public function renewLock($milliseconds)
    {
        $this->redisDriver->renewLock($this->sessionId, $this->lockToken, $milliseconds);
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * Test whether the session thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     *
     * @return boolean
     */
    public function isLocked()
    {
        return ($this->lockToken != null);
    }

    /**
     * If the driver
     * @param $sessionID
     * @return boolean
     */
    public function validateLock()
    {
        return $this->redisDriver->validateLock(
            $this->sessionId,
            $this->lockToken
        );
    }

    /**
     * @return void
     */
    public function forceReleaseLocks()
    {
        $this->redisDriver->forceReleaseLockByID($this->sessionId);
    }

    /**
     * @param $name
     * @param $value
     */
    public function set($name, $value)
    {
        $this->data[$name] = $value;
        $this->isActive = true;
    }

    /**
     * @inheritdoc
     */
    public function get($name, $default = null, $clear = false)
    {
        if (array_key_exists($name, $this->data) == false) {
            return $default;
        }

        $value = $this->data[$name];

        if ($clear) {
            unset($this->data[$name]);
            $this->isActive = true;
        }

        return $value;
    }
}
