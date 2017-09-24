<?php

declare(strict_types = 1);

namespace Asm\Encrypter;

use Asm\Encrypter;
use Asm\EncrypterFactory;

class NullEncrypterFactory implements EncrypterFactory
{
    public function create(array $cookie) : Encrypter
    {
        return new NullEncrypter();
    }
}
