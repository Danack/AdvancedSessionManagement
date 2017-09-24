<?php

declare(strict_types = 1);

namespace ASM;

interface EncrypterFactory
{
    public function create(array $cookie) : Encrypter;
}
