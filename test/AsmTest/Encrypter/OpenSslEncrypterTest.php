<?php

declare(strict_types = 1);

namespace AsmTest\Encrypter;

use Asm\Encrypter;
use Asm\Encrypter\OpenSslEncrypter;
use PHPUnit\Framework\Assert;

/**
 * @group encrypter
 */
class OpenSslEncrypterTest extends AbstractEncrypterTest
{
    public function getEncrypter(): Encrypter
    {
        return OpenSslEncrypter::createNew('NullEncrypterTest');
    }

    public function testRoundTripToCookies()
    {
        $name = 'testRoundTripToCookies';
        $originalString = "My voice is my password.";

        $encrypter1 = OpenSslEncrypter::createNew($name);
        $encryptedString = $encrypter1->encrypt($originalString);
        $cookieHeaders = $encrypter1->getCookieHeaders();
        $encrypter2 = OpenSslEncrypter::createFromEncodedKey($name, $cookieHeaders[$name]);
        $decryptedString = $encrypter2->decrypt($encryptedString);
        Assert::assertEquals($originalString, $decryptedString);
    }
}
