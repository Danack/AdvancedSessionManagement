<?php

declare(strict_types = 1);

namespace Asm;

use Asm\Encrypter;

interface CookieGenerator
{
    public function getHeaders(
        Encrypter $encrypter,
        string $sessionId,
        string $privacy,
        ?string $domain,
        ?string $path,
        bool $secure,
        bool $httpOnly
    ): array;
}
