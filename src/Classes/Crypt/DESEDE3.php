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
 * Triple DES (3DES/DES-EDE3) cipher implementation
 * 
 * Triple DES encryption in CBC mode using three 56-bit keys.
 * Key: 24 bytes (3 x 8 bytes), IV: 8 bytes, Block size: 8 bytes
 * 
 * @deprecated Use AES for new implementations. 3DES is kept for legacy compatibility.
 */
class DESEDE3 extends OpenSSLCipher
{
    protected const METHOD = 'des-ede3-cbc';
    protected const KEY_LENGTH = 24;

    /**
     * Encrypt data using Triple DES CBC
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 24-byte encryption key
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
     * Decrypt data using Triple DES CBC
     * 
     * @param string $string Base64-encoded ciphertext
     * @param string $key 24-byte decryption key
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