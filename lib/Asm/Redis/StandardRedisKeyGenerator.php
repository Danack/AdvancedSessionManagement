<?php

declare(strict_types = 1);

namespace Asm\Redis;

use Asm\RedisKeyGenerator;

class StandardRedisKeyGenerator implements RedisKeyGenerator
{
    public function generateSessionDataKey(string $sessionID): string
    {
        return 'session:'.$sessionID;
    }

    public function generateZombieKey(string $dyingSessionID): string
    {
        return 'zombie:'.$dyingSessionID;
    }

    public function generateLockKey(string $sessionID) : string
    {
        return 'session:'.$sessionID.':lock';
    }

    public function generateProfileKey(string $sessionID) : string
    {
        return 'session:'.$sessionID.':profile';
    }

    function generateAsyncKey(string $sessionID, string $index): string
    {
//        $key = 'session:' . $sessionID . ':async';
        // TODO - index needs to be used better than this:
        $key = 'session:' . $sessionID . ':async:' . $index;

        return $key;
    }
}
