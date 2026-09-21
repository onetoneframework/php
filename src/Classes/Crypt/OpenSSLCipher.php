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
 * Base class for OpenSSL symmetric cipher implementations
 * 
 * Provides common encryption/decryption functionality for CBC mode ciphers.
 * Extended by specific cipher implementations (AES, DES, etc.)
 */
class OpenSSLCipher
{
    /**
     * Current cipher algorithm
     * 
     * @var string
     */
    protected static string $algorithm = '';

    /**
     * Set and validate the cipher algorithm
     * 
     * @param string $algorithm The cipher method name
     * 
     * @return void
     * 
     * @throws RuntimeException If algorithm is not supported
     */
    protected static function setAlgorithm(string $algorithm): void
    {
        if (!OpenSSL::isCipherSupported($algorithm)) {
            throw new RuntimeException("Cipher method '{$algorithm}' is not supported");
        }

        self::$algorithm = $algorithm;
    }

    /**
     * Get the current algorithm
     * 
     * @return string Current cipher algorithm
     */
    protected static function getAlgorithm(): string
    {
        return self::$algorithm;
    }

    /**
     * Generate a secure IV for the current algorithm
     * 
     * @return string Random IV bytes
     */
    protected static function generateIv(): string
    {
        $ivLength = OpenSSL::getCipherInitializationVectorLength(self::$algorithm);
        return OpenSSL::generateSecureRandomBytes($ivLength);
    }

    /**
     * Get required key length for the current algorithm
     * 
     * @return int|false Key length in bytes or false if unavailable
     */
    protected static function getRequiredKeyLength(): int|false
    {
        if (function_exists('openssl_cipher_key_length')) {
            return OpenSSL::getCipherKeyLength(self::$algorithm);
        }

        $algorithm = strtoupper(self::$algorithm);
        if (str_contains($algorithm, '128')) {
            return 16;
        }
        if (str_contains($algorithm, '192')) {
            return 24;
        }
        if (str_contains($algorithm, '256')) {
            return 32;
        }
        if (str_contains($algorithm, 'DES-EDE3')) {
            return 24;
        }
        if (str_contains($algorithm, 'DES')) {
            return 8;
        }
        if (str_contains($algorithm, 'BLOWFISH')) {
            return 16;
        }

        return false;
    }

    /**
     * Encrypt data using the configured cipher
     * 
     * Returns base64-encoded string with IV prepended to ciphertext.
     * Format: base64(IV + ciphertext)
     * 
     * @param string $data Plaintext data to encrypt
     * @param string $passphrase Encryption key
     * @param string $iv Initialization vector (auto-generated if empty)
     * @param int $options OpenSSL options flags
     * 
     * @return string Base64-encoded encrypted data with IV
     * 
     * @throws RuntimeException If encryption fails
     */
    public static function encrypt(string $data, string $passphrase, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        if ($iv === '') {
            $iv = self::generateIv();
        }

        $cipherText = OpenSSL::encrypt($data, self::$algorithm, $passphrase, $options, $iv);

        if ($cipherText === false) {
            $error = OpenSSL::getLastErrorMessage() ?: 'Unknown encryption error';
            throw new RuntimeException("Encryption failed: {$error}");
        }

        return base64_encode($iv . $cipherText);
    }

    /**
     * Decrypt data using the configured cipher
     * 
     * @param string $data Ciphertext to decrypt (not base64-encoded)
     * @param string $passphrase Decryption key
     * @param string $iv Initialization vector
     * @param int $options OpenSSL options flags
     * 
     * @return string Decrypted plaintext
     * 
     * @throws RuntimeException If decryption fails
     */
    public static function decrypt(string $data, string $passphrase, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        $decrypted = OpenSSL::decrypt($data, self::$algorithm, $passphrase, $options, $iv);

        if ($decrypted === false) {
            $error = OpenSSL::getLastErrorMessage() ?: 'Decryption failed (invalid key, IV, or corrupted data)';
            throw new RuntimeException("Decryption failed: {$error}");
        }

        return $decrypted;
    }

    /**
     * Extract IV and ciphertext from base64-encoded encrypted data
     * 
     * @param string $base64Data Base64-encoded data (IV + ciphertext)
     * @param int $ivLength Length of IV in bytes
     * 
     * @return array{iv: string, ciphertext: string} Extracted components
     * 
     * @throws RuntimeException If data is too short
     */
    protected static function extractIvAndCiphertext(string $base64Data, int $ivLength): array
    {
        $data = base64_decode($base64Data, true);

        if ($data === false) {
            throw new RuntimeException('Invalid base64 encoding');
        }

        if (strlen($data) < $ivLength) {
            throw new RuntimeException('Encrypted data is too short');
        }

        return [
            'iv' => substr($data, 0, $ivLength),
            'ciphertext' => substr($data, $ivLength)
        ];
    }
}