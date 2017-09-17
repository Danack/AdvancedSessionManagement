<?php

declare(strict_types = 1);

namespace ASM\Redis;

use ASM\RedisKeyGenerator;

class StandardRedisKeyGenerator implements RedisKeyGenerator
{
    /**
     * @param $sessionID
     * @return string
     *
     */
    public function generateSessionDataKey(string $sessionID) : string
    {
        return 'session:'.$sessionID;
    }

    public function generateZombieKey(string $dyingSessionID) : string
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

//    /**
//     * @param $sessionID
//     * @return string
//     */
//    function generateAsyncKey($sessionID)
//    {
//        $key = 'session:'.$sessionID.':async';
//
//        return $key;
//    }
}
