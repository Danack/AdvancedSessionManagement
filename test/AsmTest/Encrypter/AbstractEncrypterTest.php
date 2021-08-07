<?php

declare(strict_types = 1);

namespace AsmTest\Encrypter;

use Asm\Encrypter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;

abstract class AbstractEncrypterTest extends TestCase
{
    abstract public function getEncrypter() : Encrypter;

    public function testEncryptThenDecrypt()
    {
        $encrypter = $this->getEncrypter();
        $originalString = "This is a test string";
        $encryptedString = $encrypter->encrypt($originalString);
        $decryptedString = $encrypter->decrypt($encryptedString);
        Assert::assertEquals($originalString, $decryptedString);
    }
}
