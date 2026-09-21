<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Crypt;

use RuntimeException;

/**
 * AES-192-CBC cipher implementation
 * 
 * 192-bit AES encryption in Cipher Block Chaining mode.
 * Key: 24 bytes, IV: 16 bytes, Block size: 16 bytes
 */
class AES192CBC extends OpenSSLCipher
{
    protected const METHOD = 'AES-192-CBC';
    protected const KEY_LENGTH = 24;

    /**
     * Encrypt data using AES-192-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 24-byte encryption key
     * @param string $iv 16-byte IV (auto-generated if empty)
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded ciphertext with IV prepended
     * 
     * @throws RuntimeException If key length is invalid
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(OpenSSL::getCipherInitializationVectorLength(self::METHOD));
        }

        return parent::encrypt($string, $key, $iv, $options);
    }

    /**
     * Decrypt data using AES-192-CBC
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 24-byte decryption key
     * @param string $iv IV if provided separately
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     * 
     * @throws RuntimeException If key length is invalid
     */
    public static function decrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        $ivLength = OpenSSL::getCipherInitializationVectorLength(self::METHOD);

        if ($iv === '') {
            $extracted = parent::extractIvAndCiphertext($string, $ivLength);
            $iv = $extracted['iv'];
            $ciphertext = $extracted['ciphertext'];
        } else {
            $ciphertext = base64_decode($string, true);
        }

        return parent::decrypt($ciphertext, $key, $iv, $options);
    }

    /**
     * Validate that the provided key has the required AES-192 length.
     *
     * @param string $key
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('AES-192 requires a 192-bit (24 bytes) key');
        }
    }
}