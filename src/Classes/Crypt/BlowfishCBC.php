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
 * Blowfish-CBC cipher implementation
 * 
 * Variable key-length block cipher (32-448 bits) in CBC mode.
 * Key: 4-56 bytes (recommended 16+ bytes), IV: 8 bytes, Block size: 8 bytes
 * 
 * @deprecated For new applications, use AES instead. Blowfish's 64-bit block size
 *             makes it vulnerable to birthday attacks in CBC mode with large data.
 */
class BlowfishCBC extends OpenSSLCipher
{
    protected const METHOD = 'bf-cbc';
    protected const MIN_KEY_LENGTH = 4;
    protected const MAX_KEY_LENGTH = 56;

    /**
     * Encrypt with Blowfish-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 4-56 byte encryption key
     * @param string $iv 8-byte IV (auto-generated if empty)
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
     * Decrypt Blowfish-CBC data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 4-56 byte decryption key
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
            throw new RuntimeException(sprintf('Blowfish key must be %d-%d bytes', self::MIN_KEY_LENGTH, self::MAX_KEY_LENGTH));
        }
    }
}