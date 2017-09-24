<?php

declare(strict_types = 1);

namespace ASM\Encryptor;

use ASM\Encrypter;
use ASM\EncrypterFactory;

class NullEncrypterFactory implements EncrypterFactory
{
    public function create(array $cookie) : Encrypter
    {
        return new NullEncrypter();
    }
}
