<?php

declare(strict_types = 1);

namespace Asm;

interface RedisKeyGenerator
{
    public function generateSessionDataKey(string $sessionID) : string;

    public function generateZombieKey(string $dyingSessionID) : string;

    public function generateLockKey(string $sessionID) : string;

    public function generateProfileKey(string $sessionID) : string;

    public function generateAsyncKey(string $sessionID, string $index): string;
}
