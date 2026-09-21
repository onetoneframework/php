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
 * Camellia-256-CBC cipher implementation
 * 
 * Japanese/European 256-bit block cipher in CBC mode.
 * Key: 32 bytes, IV: 16 bytes, Block size: 16 bytes
 * Approved by ISO/IEC, NESSIE, and CRYPTREC. Security equivalent to AES.
 */
class Camellia256CBC extends OpenSSLCipher
{
    protected const METHOD = 'camellia-256-cbc';
    protected const KEY_LENGTH = 32;

    /**
     * Encrypt with Camellia-256-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 32-byte encryption key
     * @param string $iv 16-byte IV (auto-generated if empty)
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded ciphertext with IV prepended
     * 
     * @throws RuntimeException If cipher not supported
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateSupport();
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(OpenSSL::getCipherInitializationVectorLength(self::METHOD));
        }

        return parent::encrypt($string, $key, $iv, $options);
    }

    /**
     * Decrypt Camellia-256-CBC data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 32-byte decryption key
     * @param string $iv IV if provided separately
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     */
    public static function decrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateSupport();
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

    private static function validateSupport(): void
    {
        if (!OpenSSL::isCipherSupported(self::METHOD)) {
            throw new RuntimeException('Camellia-256-CBC is not supported by this OpenSSL installation');
        }
    }

    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('Camellia-256 requires a 256-bit (32 bytes) key');
        }
    }
}