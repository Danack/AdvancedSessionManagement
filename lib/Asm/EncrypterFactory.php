<?php

declare(strict_types = 1);

namespace Asm;

interface EncrypterFactory
{
    public function create(array $cookie) : Encrypter;
}
