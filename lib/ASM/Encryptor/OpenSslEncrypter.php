<?php

declare(strict_types = 1);

namespace ASM\Encryptor;

use ASM\Encrypter;
use ASM\DecryptionFailureException;

class OpenSslEncrypter implements Encrypter
{
    /** @var string */
    private $keyName;

    /**
     * Encryption and authentication key
     * @var string
     */
    protected $key;

    private function __construct(string $keyName, string $encodedKey)
    {
        $this->keyName = $keyName;
        $this->key = base64_decode($encodedKey);
    }


    public function getCookieHeaders()
    {
        return [$this->keyName => base64_encode($this->key) ];
    }

    public static function fromEncodedKey($keyName, $encodedKey)
    {
        return new self($keyName, $encodedKey);
    }

    public static function fromNull($keyName)
    {
        $encodedKey = self::generateNewKey();
        return new self($keyName, $encodedKey);
    }

    protected static function generateNewKey() : string
    {
        $key         = random_bytes(64); // 32 for encryption and 32 for authentication
        $encKey      = base64_encode($key);

        return $encKey;
    }

    /**
     * @param string $data
     * @return string
     */
    public function encrypt(string $data) : string
    {
        return $this->encryptInternal($data, $this->key);
    }

    /**
     * @param $data
     * @param $key
     * @return string
     */
    private function encryptInternal($data, $key) : string
    {
        $iv = random_bytes(16); // AES block size in CBC mode
        // Encryption
        $ciphertext = openssl_encrypt(
            $data,
            'AES-256-CBC',
            mb_substr($key, 0, 32, '8bit'),
            OPENSSL_RAW_DATA,
            $iv
        );
        // Authentication
        $hmac = hash_hmac(
            'SHA256',
            $iv . $ciphertext,
            mb_substr($key, 32, null, '8bit'),
            true
        );
        return $hmac . $iv . $ciphertext;
    }

    /**
     * @param string $data
     * @return string
     */
    public function decrypt(string $data) : string
    {
        return $this->decryptInternal($data, $this->key);
    }


    /**
     * Authenticate and decrypt
     *
     * @param string $data
     * @param string $key
     * @return string
     */
    private function decryptInternal($data, $key) : string
    {
        $hmac       = mb_substr($data, 0, 32, '8bit');
        $iv         = mb_substr($data, 32, 16, '8bit');
        $ciphertext = mb_substr($data, 48, null, '8bit');
        // Authentication
        $hmacNew = hash_hmac(
            'SHA256',
            $iv . $ciphertext,
            mb_substr($key, 32, null, '8bit'),
            true
        );
        if (! hash_equals($hmac, $hmacNew)) {
            throw new DecryptionFailureException('Failed to decrypt session data.');
        }
        // Decrypt
        return openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            mb_substr($key, 0, 32, '8bit'),
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}