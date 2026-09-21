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
use function strlen;

/**
 * Base class for AEAD (Authenticated Encryption with Associated Data) ciphers
 * 
 * Provides encryption/decryption with authentication tags for GCM/CCM modes.
 * Output format: base64(IV + tag + ciphertext)
 */
class OpenSSLAeadCipher
{
    protected const TAG_LENGTH = 16;

    protected static string $algorithm = '';

    protected static function setAlgorithm(string $algorithm): void
    {
        if (!OpenSSL::isCipherSupported($algorithm)) {
            throw new RuntimeException("Cipher method '{$algorithm}' is not supported");
        }
        self::$algorithm = $algorithm;
    }

    protected static function generateIv(): string
    {
        $ivLength = OpenSSL::getCipherInitializationVectorLength(self::$algorithm);
        return OpenSSL::generateSecureRandomBytes($ivLength);
    }

    /**
     * Encrypt data with authentication
     * 
     * @param string $data Plaintext data
     * @param string $passphrase Encryption key
     * @param string $iv Initialization vector (auto-generated if empty)
     * @param int $options OpenSSL options flags
     * @param string $aad Additional authenticated data
     * 
     * @return string Base64-encoded: IV + tag + ciphertext
     * 
     * @throws RuntimeException If encryption fails
     */
    public static function encrypt(string $data, string $passphrase, string $iv = '', int $options = OPENSSL_RAW_DATA, string $aad = ''): string
    {
        if ($iv === '') {
            $iv = self::generateIv();
        }

        $tag = '';
        $cipherText = OpenSSL::encrypt($data, self::$algorithm, $passphrase, $options, $iv, $tag, $aad, static::TAG_LENGTH);

        if ($cipherText === false) {
            $error = OpenSSL::getLastErrorMessage() ?: 'Unknown encryption error';
            throw new RuntimeException("AEAD encryption failed: {$error}");
        }

        return base64_encode($iv . $tag . $cipherText);
    }

    /**
     * Decrypt authenticated data
     * 
     * @param string $base64Data Base64-encoded encrypted data (IV + tag + ciphertext)
     * @param string $passphrase Decryption key
     * @param string $iv IV if provided separately (empty to extract from data)
     * @param string $tag Auth tag if provided separately (empty to extract from data)
     * @param string $aad Additional authenticated data
     * @param int $options OpenSSL options flags
     * 
     * @return string Decrypted plaintext
     * 
     * @throws RuntimeException If decryption or authentication fails
     */
    public static function decrypt(string $base64Data, string $passphrase, string $iv = '', string $tag = '', string $aad = '', int $options = OPENSSL_RAW_DATA): string
    {
        $ivLength = OpenSSL::getCipherInitializationVectorLength(self::$algorithm);

        if ($iv === '' || $tag === '') {
            $extracted = self::extractComponents($base64Data, $ivLength, static::TAG_LENGTH);
            $iv = $iv !== '' ? $iv : $extracted['iv'];
            $tag = $tag !== '' ? $tag : $extracted['tag'];
            $cipherText = $extracted['ciphertext'];
        } else {
            $cipherText = base64_decode($base64Data, true);
            if ($cipherText === false) {
                throw new RuntimeException('Invalid base64 encoding');
            }
        }

        $decrypted = OpenSSL::decrypt($cipherText, self::$algorithm, $passphrase, $options, $iv, $tag, $aad);

        if ($decrypted === false) {
            throw new RuntimeException('AEAD decryption failed: authentication tag mismatch or corrupted data');
        }

        return $decrypted;
    }

    protected static function extractComponents(string $base64Data, int $ivLength, int $tagLength): array
    {
        $data = base64_decode($base64Data, true);

        if ($data === false) {
            throw new RuntimeException('Invalid base64 encoding');
        }

        $minLength = $ivLength + $tagLength;
        if (strlen($data) < $minLength) {
            throw new RuntimeException('Encrypted data is too short');
        }

        return [
            'iv' => substr($data, 0, $ivLength),
            'tag' => substr($data, $ivLength, $tagLength),
            'ciphertext' => substr($data, $ivLength + $tagLength)
        ];
    }
}