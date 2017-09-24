<?php

declare(strict_types = 1);


namespace ASM;


interface CookieGenerator
{
    public function getHeaders(
        $sessionId,
        $privacy,
        $domain,
        $path,
        $secure,
        $httpOnly
    );
}