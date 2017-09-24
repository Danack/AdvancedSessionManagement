<?php

declare(strict_types = 1);

namespace Asm\Encrypter;

use Asm\Encrypter;

class NullEncrypter implements Encrypter
{
    public function encrypt(string $data) : string
    {
        return $data;
    }

    public function decrypt(string $data) : string
    {
        return $data;
    }

    public function getCookieHeaders()
    {
        return [];
    }
}
