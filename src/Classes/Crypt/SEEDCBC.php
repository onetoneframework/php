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
 * SEED-CBC cipher implementation
 * 
 * Korean standard 128-bit block cipher in CBC mode (TTAS.KO-12.0004/R1).
 * Key: 16 bytes, IV: 16 bytes, Block size: 16 bytes
 * ISO/IEC 18033-3 standardized cipher, widely used in Korean financial systems.
 */
class SEEDCBC extends OpenSSLCipher
{
    protected const METHOD = 'seed-cbc';
    protected const KEY_LENGTH = 16;

    /**
     * Encrypt with SEED-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 16-byte encryption key
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
     * Decrypt SEED-CBC data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 16-byte decryption key
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
            throw new RuntimeException('SEED-CBC is not supported by this OpenSSL installation');
        }
    }

    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('SEED requires a 128-bit (16 bytes) key');
        }
    }
}