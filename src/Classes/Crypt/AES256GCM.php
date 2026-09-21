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
 * AES-256-GCM authenticated cipher implementation
 * 
 * 256-bit AES in Galois/Counter Mode with authentication.
 * Key: 32 bytes, IV: 12 bytes (recommended), Tag: 16 bytes
 * Recommended for modern applications requiring authenticated encryption.
 */
class AES256GCM extends OpenSSLAeadCipher
{
    protected const METHOD = 'aes-256-gcm';
    protected const KEY_LENGTH = 32;

    /**
     * Encrypt with AES-256-GCM authentication
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 32-byte encryption key
     * @param string $iv 12-byte IV (auto-generated if empty)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded: IV + tag + ciphertext
     * 
     * @throws RuntimeException If key length is invalid
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA, string $aad = ''): string
    {
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(12);
        }

        return parent::encrypt($string, $key, $iv, $options, $aad);
    }

    /**
     * Decrypt AES-256-GCM authenticated data
     * 
     * @param string $string Base64-encoded encrypted data
     * @param string $key 32-byte decryption key
     * @param string $iv IV if provided separately (empty to extract from data)
     * @param string $tag Auth tag if provided separately (empty to extract from data)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     * 
     * @throws RuntimeException If key length is invalid or authentication fails
     */
    public static function decrypt(string $string, string $key, string $iv = '', string $tag = '', string $aad = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        return parent::decrypt($string, $key, $iv, $tag, $aad, $options);
    }

    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('AES-256-GCM requires a 256-bit (32 bytes) key');
        }
    }
}