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
use function in_array;

class SodiumCipher
{
    /**
     * Current cipher algorithm
     * 
     * @var string
     */
    protected static string $algorithm = '';

    protected $blockSize = 16;

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
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new RuntimeException("Hash algorithm '{$algorithm}' is not supported");
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
     * Encrypt data using the current cipher algorithm
     * 
     * @param string $data The plaintext data to encrypt
     * @param string $passphrase The passphrase for key derivation
     * @param string $iv Optional initialization vector (not used in Sodium)
     * @param int $options Optional encryption options (not used in Sodium)
     * 
     * @return string The encrypted ciphertext, base64-encoded
     * 
     * @throws RuntimeException If encryption fails or parameters are invalid
     */
    public static function encrypt(string $data, string $passphrase, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        if (empty(self::$algorithm)) {
            throw new RuntimeException('Cipher algorithm not set');
        }

        if (strlen($passphrase) === 0) {
            throw new RuntimeException('Passphrase cannot be empty');
        }

        if (strlen($data) === 0) {
            throw new RuntimeException('Data to encrypt cannot be empty');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $data = sodium_pad($data, self::$blockSize);
        $key = hash(self::$algorithm, $passphrase, true);
        $ciphertext = sodium_crypto_secretbox($data, $nonce, $key);

        sodium_memzero($data);
        sodium_memzero($key);

        return base64_encode($nonce . $ciphertext);
    }

    /**
     * Decrypt data using the current cipher algorithm
     * 
     * @param string $data The base64-encoded ciphertext to decrypt
     * @param string $passphrase The passphrase for key derivation
     * @param string $iv Optional initialization vector (not used in Sodium)
     * @param int $options Optional decryption options (not used in Sodium)
     * 
     * @return string The decrypted plaintext
     * 
     * @throws RuntimeException If decryption fails or parameters are invalid
     */
    public static function decrypt(string $data, string $passphrase, string $iv = '', int $options = OPENSSL_RAW_DATA): string
    {
        if (mb_strlen($data, '8bit') < (SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + SODIUM_CRYPTO_SECRETBOX_MACBYTES)) {
            throw new RuntimeException('Ciphertext is too short');
        }

        $decoded = base64_decode($data);
        $nonce = substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = hash(self::$algorithm, $passphrase, true);
        $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);

        sodium_memzero($key);
        sodium_memzero($ciphertext);

        if ($decrypted === false) {
            throw new RuntimeException('Decryption failed');
        }

        $decrypted = sodium_unpad($decrypted, self::$blockSize);

        return rtrim($decrypted, "\0");
    }
}
