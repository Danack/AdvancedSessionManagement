<?php

declare(strict_types = 1);

namespace AsmTest\Encrypter;

use Asm\Encrypter;
use Asm\Encrypter\NullEncrypter;

/**
 * @group encrypter
 */
class NullEncrypterTest extends AbstractEncrypterTest
{
    public function getEncrypter(): Encrypter
    {
        return new NullEncrypter();
    }
}
