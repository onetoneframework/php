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
use function sprintf;

/**
 * CAST5-CBC cipher implementation
 * 
 * CAST-128 block cipher in CBC mode.
 * Key: 5-16 bytes (40-128 bits), IV: 8 bytes, Block size: 8 bytes
 * Used in PGP and GPG for message encryption.
 */
class CAST5CBC extends OpenSSLCipher
{
    protected const METHOD = 'cast5-cbc';
    protected const MIN_KEY_LENGTH = 5;
    protected const MAX_KEY_LENGTH = 16;

    /**
     * Encrypt with CAST5-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 5-16 byte encryption key
     * @param string $iv 8-byte IV (auto-generated if empty)
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded ciphertext with IV prepended
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
     * Decrypt CAST5-CBC data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 5-16 byte decryption key
     * @param string $iv IV if provided separately
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
        $length = mb_strlen($key, '8bit');
        if ($length < self::MIN_KEY_LENGTH || $length > self::MAX_KEY_LENGTH) {
            throw new RuntimeException(sprintf('CAST5 key must be %d-%d bytes', self::MIN_KEY_LENGTH, self::MAX_KEY_LENGTH));
        }
    }
}