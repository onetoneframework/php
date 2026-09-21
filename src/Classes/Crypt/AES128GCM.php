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
 * AES-128-GCM authenticated cipher implementation
 * 
 * 128-bit AES in Galois/Counter Mode with authentication.
 * Key: 16 bytes, IV: 12 bytes (recommended), Tag: 16 bytes
 * Provides both confidentiality and integrity verification.
 */
class AES128GCM extends OpenSSLAeadCipher
{
    protected const METHOD = 'aes-128-gcm';
    protected const KEY_LENGTH = 16;

    /**
     * Encrypt with AES-128-GCM authentication
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 16-byte encryption key
     * @param string $iv 12-byte IV (auto-generated if empty)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded: IV + tag + ciphertext
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA, string $aad = ''): string
    {
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(12);
        }

        return parent::encrypt($string, $key, $iv, $options, $aad);
    }

    /**
     * Decrypt AES-128-GCM authenticated data
     * 
     * @param string $string Base64-encoded encrypted data
     * @param string $key 16-byte decryption key
     * @param string $iv IV if provided separately (empty to extract from data)
     * @param string $tag Auth tag if provided separately (empty to extract from data)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     */
    public static function decrypt(string $string, string $key, string $iv = '', string $tag = '', string $aad = '', int $options = OPENSSL_RAW_DATA): string
    {
        parent::setAlgorithm(self::METHOD);

        return parent::decrypt($string, $key, $iv, $tag, $aad, $options);
    }
}