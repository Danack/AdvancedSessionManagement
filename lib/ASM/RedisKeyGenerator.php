<?php

declare(strict_types = 1);

namespace ASM;

interface RedisKeyGenerator
{
    public function generateSessionDataKey(string $sessionID) : string;

    public function generateZombieKey(string $dyingSessionID) : string;

    public function generateLockKey(string $sessionID) : string;

    public function generateProfileKey(string $sessionID) : string;
}
