<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Crypt;

/**
 * DES-CBC cipher implementation
 * 
 * Data Encryption Standard in CBC mode.
 * Key: 8 bytes (56 effective bits), IV: 8 bytes, Block size: 8 bytes
 * 
 * @deprecated DES is cryptographically broken. Use AES or 3DES for legacy compatibility only.
 */
class DESCBC extends OpenSSLCipher
{
    protected const METHOD = 'des-cbc';
    protected const KEY_LENGTH = 8;

    /**
     * Encrypt with DES-CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 8-byte encryption key
     * @param string $iv 8-byte IV (auto-generated if empty)
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded ciphertext with IV prepended
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(OpenSSL::getCipherInitializationVectorLength(self::METHOD));
        }

        return parent::encrypt($string, $key, $iv, $options);
    }

    /**
     * Decrypt DES-CBC data
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 8-byte decryption key
     * @param string $iv IV if provided separately
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     */
    public static function decrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
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
}