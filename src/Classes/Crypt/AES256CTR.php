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
 * AES-256-CTR cipher implementation
 * 
 * 256-bit AES in Counter Mode (stream cipher).
 * Key: 32 bytes, IV/Nonce: 16 bytes
 * No padding required; ciphertext same length as plaintext.
 * Supports parallel encryption/decryption and random access.
 */
class AES256CTR extends OpenSSLCipher
{
    protected const METHOD = 'aes-256-ctr';
    protected const KEY_LENGTH = 32;

    /**
     * Encrypt with AES-256-CTR
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 32-byte encryption key
     * @param string $iv 16-byte nonce/IV (auto-generated if empty)
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded: IV + ciphertext
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
     * Decrypt AES-256-CTR data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 32-byte decryption key
     * @param string $iv IV/nonce if provided separately
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
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

    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('AES-256-CTR requires a 256-bit (32 bytes) key');
        }
    }
}