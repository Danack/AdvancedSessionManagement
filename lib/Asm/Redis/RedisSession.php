<?php


namespace Asm\Redis;

use Asm\AsmException;
use Asm\Encrypter;
use Asm\Session;
use Asm\SessionManager;
use Asm\LostLockException;

class RedisSession implements Session
{
    protected string $sessionId;

    /**
     * @var RedisDriver
     */
    protected RedisDriver $redisDriver;

    /** @var SessionManager */
    protected SessionManager $sessionManager;

    /**
     * @var Encrypter
     */
    protected $encrypter;
    
    private bool $isActive = false;

    /**
     * @var array
     */
    protected array $data;

    /**
     * @var \Asm\Profile\SimpleProfile[]
     */
    protected array $currentProfiles;

    /**
     * @var ?string A token for each lock. It allows us to detect when another
     * process has force released the lock, and it is no longer owned by this process.
     * TODO - replace this with a type, that also stores the type of lock.
     */
    protected ?string $lockToken;

    public function __construct(
        string         $sessionID,
        RedisDriver    $redisDriver,
        SessionManager $sessionManager,
        Encrypter      $encrypter,
        array          $data,
        array          $currentProfiles,
        bool           $isActive,
        ?string        $lockToken
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

    public function getHeaders(
        string $privacy,
        ?string $path = null,
        ?string $domain = null,
        bool $secure = false,
        bool $httpOnly = true
    ): array {
        return $this->sessionManager->getHeaders(
            $this->encrypter,
            $this->sessionId,
            $privacy,
            $domain,
            $path,
            $secure,
            $httpOnly
        );
    }


    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
        $this->isActive = true;
    }

    public function save(): void
    {
        $this->redisDriver->save(
            $this,
            $this->encrypter,
            $this->data,
            $this->currentProfiles
        );
    }

    public function close(bool $saveData = true): void
    {
        if ($saveData) {
            $this->save();
        }

        $this->releaseLock();
    }

    public function delete(): void
    {
        $this->redisDriver->deleteSessionByID($this->sessionId);
        $this->releaseLock();
    }

    public function acquireLock($lockTimeMS, $acquireTimeoutMS): void
    {
        $this->lockToken = $this->redisDriver->acquireLock(
            $this->sessionId,
            $lockTimeMS,
            $acquireTimeoutMS
        );
    }

    public function releaseLock(): void
    {
        if ($this->lockToken !== null) {
            $lockToken = $this->lockToken;
            $this->lockToken = null;
            $this->redisDriver->releaseLock($this->sessionId, $lockToken);
        }
    }


    public function increment(string $index, int $increment): int
    {
        return $this->redisDriver->increment($this->sessionId, $index, $increment);
    }

    public function getList(string $index): array
    {
        return $this->redisDriver->getList($this->sessionId, $index);
    }

    public function appendToList(string $key, string|int $value): int
    {
        $result = $this->redisDriver->appendToList($this->sessionId, $key, $value);

        return $result;
    }

    public function clearList(string $index): void
    {
//        return $this->redisDriver->clearList($this->sessionId, $index);
        $this->redisDriver->clearList($this->sessionId, $index);
    }


    public function renewLock(int $milliseconds): void
    {
        if ($this->lockToken === null) {
            // er - why is this possible?
            // probably should throw exception.
            return;
        }

        $this->redisDriver->renewLock($this->sessionId, $this->lockToken, $milliseconds);
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * Test whether the session thinks the data is locked. The result may
     * not be accurate when another process has force released the lock.
     *
     * @return boolean
     */
    public function isLocked(): bool
    {
        return ($this->lockToken != null);
    }

    public function validateLock(): bool
    {
        if ($this->lockToken === null) {
            // er - why is this possible?
            return false;
        }

        return $this->redisDriver->validateLock(
            $this->sessionId,
            $this->lockToken
        );
    }

    /**
     * @return void
     */
    public function forceReleaseLocks(): void
    {
        $this->redisDriver->forceReleaseLockByID($this->sessionId);
    }

    public function set(string $name, int|bool|array|string|float $value): void
    {
        $this->data[$name] = $value;
        $this->isActive = true;
    }

    /**
     * @inheritdoc
     */
    public function get(
        string $name,
        int|bool|array|string|float $default = null,
        bool $clear = false
    ): int|bool|array|string|float|null {
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
