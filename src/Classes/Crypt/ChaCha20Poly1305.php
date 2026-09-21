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
 * ChaCha20-Poly1305 authenticated cipher implementation
 * 
 * Stream cipher with Poly1305 MAC for authenticated encryption.
 * Key: 32 bytes, IV/Nonce: 12 bytes, Tag: 16 bytes
 * Designed as an alternative to AES-GCM, particularly on systems without AES hardware acceleration.
 */
class ChaCha20Poly1305 extends OpenSSLAeadCipher
{
    protected const METHOD = 'chacha20-poly1305';
    protected const KEY_LENGTH = 32;
    protected const IV_LENGTH = 12;

    /**
     * Encrypt with ChaCha20-Poly1305 authentication
     * 
     * @param string $string Plaintext to encrypt
     * @param string $key 32-byte encryption key
     * @param string $iv 12-byte nonce (auto-generated if empty)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Base64-encoded: nonce + tag + ciphertext
     * 
     * @throws RuntimeException If key length is invalid or cipher not supported
     */
    public static function encrypt(string $string, string $key, string $iv = '', int $options = OPENSSL_RAW_DATA, string $aad = ''): string
    {
        self::validateSupport();
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        if ($iv === '') {
            $iv = OpenSSL::generateSecureRandomBytes(self::IV_LENGTH);
        }

        return parent::encrypt($string, $key, $iv, $options, $aad);
    }

    /**
     * Decrypt ChaCha20-Poly1305 authenticated data
     * 
     * @param string $string Base64-encoded encrypted data
     * @param string $key 32-byte decryption key
     * @param string $iv Nonce if provided separately (empty to extract from data)
     * @param string $tag Auth tag if provided separately (empty to extract from data)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options
     * 
     * @return string Decrypted plaintext
     * 
     * @throws RuntimeException If authentication fails
     */
    public static function decrypt(string $string, string $key, string $iv = '', string $tag = '', string $aad = '', int $options = OPENSSL_RAW_DATA): string
    {
        self::validateSupport();
        self::validateKeyLength($key);
        parent::setAlgorithm(self::METHOD);

        return parent::decrypt($string, $key, $iv, $tag, $aad, $options);
    }

    private static function validateSupport(): void
    {
        if (!OpenSSL::isCipherSupported(self::METHOD)) {
            throw new RuntimeException('ChaCha20-Poly1305 requires OpenSSL 1.1.0 or later');
        }
    }

    private static function validateKeyLength(string $key): void
    {
        if (mb_strlen($key, '8bit') !== self::KEY_LENGTH) {
            throw new RuntimeException('ChaCha20-Poly1305 requires a 256-bit (32 bytes) key');
        }
    }
}