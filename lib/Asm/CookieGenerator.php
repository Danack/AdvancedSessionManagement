<?php

declare(strict_types = 1);

namespace Asm;

use Asm\Encrypter;

interface CookieGenerator
{
    public function getHeaders(
        Encrypter $encrypter,
        $sessionId,
        $privacy,
        $domain,
        $path,
        $secure,
        $httpOnly
    );
}
