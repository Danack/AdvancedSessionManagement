<?php

declare(strict_types = 1);

namespace Asm\Encrypter;

use Asm\Encrypter;
use Asm\EncrypterFactory;

class OpenSslEncrypterFactory implements EncrypterFactory
{
    private string $cookieName;

    /**
     * OpenSslEncrypterFactory constructor.
     * @param string $cookieName The name of the cookie the key should be stored in.
     */
    public function __construct(string $cookieName)
    {
        $this->cookieName = $cookieName;
    }

    public function create(array $cookies) : Encrypter
    {
        $encodedKey = null;
        if (array_key_exists($this->cookieName, $cookies) === true) {
            return OpenSslEncrypter::createFromEncodedKey($this->cookieName, $cookies[$this->cookieName]);
        }

        return OpenSslEncrypter::createNew($this->cookieName);
    }
}
