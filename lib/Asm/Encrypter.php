<?php

declare(strict_types = 1);

namespace Asm;

interface Encrypter
{
    public function encrypt(string $data) : string;

    /**
     * Decrypt the encrypted session data.
     *
     * All implementions should throw a \Asm\DecryptionFailureException if
     * the session data can't be encrypted.
     *
     * @param string $data
     * @throws \Asm\DecryptionFailureException
     * @return string
     */
    public function decrypt(string $data) : string;

    /**
     * Return any cookies that should be stored on the client to
     * allow the decryption to work in future requests.
     * @return mixed
     */
    public function getCookieHeaders();
}
