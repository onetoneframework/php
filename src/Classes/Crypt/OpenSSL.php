<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Crypt;

use OpenSSLAsymmetricKey;
use OpenSSLCertificate;
use OpenSSLCertificateSigningRequest;
use RuntimeException;
use function in_array;

/**
 * OpenSSL utility wrapper class
 * 
 * Provides static methods for OpenSSL cryptographic operations including
 * symmetric encryption/decryption, key generation, and certificate handling.
 */
class OpenSSL
{
    /**
     * Check if OpenSSL extension is loaded
     * 
     * @return bool True if extension is available
     */
    public static function isExtensionLoaded(): bool
    {
        return extension_loaded('openssl');
    }

    /**
     * Encrypts data using symmetric cipher
     * 
     * @param string $data The plaintext data to encrypt
     * @param string $algorithm The cipher method (e.g., 'AES-256-CBC')
     * @param string $passphrase The encryption key/passphrase
     * @param int $options Bitwise flags (OPENSSL_RAW_DATA, OPENSSL_ZERO_PADDING)
     * @param string $initializationVector The IV for CBC/CTR/GCM modes
     * @param string $tag Authentication tag for AEAD ciphers (GCM/CCM), passed by reference
     * @param string $aad Additional authenticated data for AEAD ciphers
     * @param int $tagLength Length of authentication tag (4-16 bytes for GCM)
     * 
     * @return string|false Encrypted data or false on failure
     */
    public static function encrypt(string $data, string $algorithm, string $passphrase, int $options = OPENSSL_RAW_DATA, string $initializationVector = "", string &$tag = "", string $aad = "", int $tagLength = 16): string|false
    {
        if (self::isAeadCipher($algorithm)) {
            return openssl_encrypt($data, $algorithm, $passphrase, $options, $initializationVector, $tag, $aad, $tagLength);
        }

        return openssl_encrypt($data, $algorithm, $passphrase, $options, $initializationVector);
    }

    /**
     * Decrypts data using symmetric cipher
     * 
     * @param string $data The encrypted data to decrypt
     * @param string $algorithm The cipher method
     * @param string $passphrase The decryption key/passphrase
     * @param int $options Bitwise flags
     * @param string $initializationVector The IV used during encryption
     * @param string $tag Authentication tag for AEAD ciphers
     * @param string $aad Additional authenticated data for AEAD ciphers
     * 
     * @return string|false Decrypted data or false on failure
     */
    public static function decrypt(string $data, string $algorithm, string $passphrase, int $options = OPENSSL_RAW_DATA, string $initializationVector = "", string $tag = "", string $aad = ""): string|false
    {
        if (self::isAeadCipher($algorithm)) {
            return openssl_decrypt($data, $algorithm, $passphrase, $options, $initializationVector, $tag, $aad);
        }

        return openssl_decrypt($data, $algorithm, $passphrase, $options, $initializationVector);
    }

    /**
     * Check if cipher is an AEAD (Authenticated Encryption with Associated Data) cipher
     * 
     * @param string $algorithm The cipher method name
     * 
     * @return bool True if AEAD cipher (GCM, CCM, Poly1305)
     */
    public static function isAeadCipher(string $algorithm): bool
    {
        $algorithm = strtolower($algorithm);
        return str_contains($algorithm, '-gcm') || str_contains($algorithm, '-ccm') || str_contains($algorithm, 'poly1305');
    }

    /**
     * Check if cipher method is supported
     * 
     * @param string $algorithm The cipher method to check
     * 
     * @return bool True if supported
     */
    public static function isCipherSupported(string $algorithm): bool
    {
        return in_array(strtolower($algorithm), array_map('strtolower', self::getCipherMethods()), true);
    }

    /**
     * Check if digest method is supported
     * 
     * @param string $algorithm The digest method to check
     * 
     * @return bool True if supported
     */
    public static function isDigestSupported(string $algorithm): bool
    {
        return in_array(strtolower($algorithm), array_map('strtolower', self::getDigestMethods()), true);
    }

    /**
     * Retrieve available certificate locations
     * 
     * @return array Certificate location paths
     */
    public static function getCertificateLocation(): array
    {
        return openssl_get_cert_locations();
    }

    /**
     * Returns the subject of a Certificate Signing Request
     * 
     * @param OpenSSLCertificateSigningRequest|string $csr The CSR
     * @param bool|null $shortNames Use short names in output
     * 
     * @return array|false Subject array or false on failure
     */
    public static function getCertificateSigningRequestSubject(OpenSSLCertificateSigningRequest|string $csr, ?bool $shortNames = true): array|false
    {
        return openssl_csr_get_subject($csr, $shortNames);
    }

    /**
     * Gets available digest/hash methods
     * 
     * @param bool|null $aliases Include algorithm aliases
     * 
     * @return array List of digest method names
     */
    public static function getDigestMethods(?bool $aliases = false): array
    {
        return openssl_get_md_methods($aliases);
    }

    /**
     * Gets the cipher key length in bytes
     * 
     * @param string $algorithm The cipher method
     * 
     * @return int|false Key length in bytes or false on failure
     * 
     * @throws RuntimeException If PHP version < 8.2.0
     */
    public static function getCipherKeyLength(string $algorithm): int|false
    {
        if (!function_exists('openssl_cipher_key_length')) {
            throw new RuntimeException('openssl_cipher_key_length requires PHP 8.2.0 or later');
        }

        return openssl_cipher_key_length($algorithm);
    }

    /**
     * Generate cryptographically secure pseudo-random bytes
     * 
     * @param int $length Number of bytes to generate
     * @param bool|null $strongResult Set to true if crypto strong algorithm was used
     * 
     * @return string Random byte string
     */
    public static function generatePseudoRandomStringOfBytes(int $length, ?bool &$strongResult = null): string
    {
        return openssl_random_pseudo_bytes($length, $strongResult);
    }

    /**
     * Generate secure random bytes (alias with validation)
     * 
     * @param int $length Number of bytes to generate
     * 
     * @return string Random byte string
     * 
     * @throws RuntimeException If crypto strong randomness not available
     */
    public static function generateSecureRandomBytes(int $length): string
    {
        $strong = false;
        $bytes = openssl_random_pseudo_bytes($length, $strong);

        if (!$strong) {
            throw new RuntimeException('Cryptographically strong random bytes not available');
        }

        return $bytes;
    }

    /**
     * Gets the IV length for a cipher method
     * 
     * @param string $algorithm The cipher method
     * 
     * @return int|false IV length in bytes or false on failure
     */
    public static function getCipherInitializationVectorLength(string $algorithm): int|false
    {
        return openssl_cipher_iv_length($algorithm);
    }

    /**
     * Gets the last OpenSSL error message
     * 
     * @return string|false Error message or false if no error
     */
    public static function getLastErrorMessage(): string|false
    {
        return openssl_error_string();
    }

    /**
     * Gets all OpenSSL error messages from the error queue
     * 
     * @return array Array of error messages
     */
    public static function getAllErrorMessages(): array
    {
        $errors = [];
        while ($error = openssl_error_string()) {
            $errors[] = $error;
        }
        return $errors;
    }

    /**
     * Clear the OpenSSL error queue
     * 
     * @return void
     */
    public static function clearErrors(): void
    {
        while (openssl_error_string() !== false) {
            // Drain the error queue
        }
    }

    /**
     * Gets available cipher methods
     * 
     * @param bool $aliases Include algorithm aliases
     * 
     * @return array List of cipher method names
     */
    public static function getCipherMethods(bool $aliases = false): array
    {
        return openssl_get_cipher_methods($aliases);
    }

    /**
     * Computes a digest hash value
     * 
     * @param string $data Data to hash
     * @param string $algorithm Digest algorithm (e.g., 'sha256')
     * @param bool $binary Return raw binary output
     * 
     * @return string|false Hash value or false on failure
     */
    public static function digest(string $data, string $algorithm, bool $binary = false): string|false
    {
        return openssl_digest($data, $algorithm, $binary);
    }

    /**
     * Generate a new private key
     * 
     * @param array $options Key generation options
     * 
     * @return OpenSSLAsymmetricKey|false Generated key or false on failure
     */
    public static function generatePrivateKey(array $options = []): OpenSSLAsymmetricKey|false
    {
        $defaultOptions = [
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        return openssl_pkey_new(array_merge($defaultOptions, $options));
    }

    /**
     * Get details of a private key
     * 
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $key The key
     * 
     * @return array|false Key details or false on failure
     */
    public static function getKeyDetails(OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $key): array|false
    {
        return openssl_pkey_get_details($key);
    }

    /**
     * Export private key to PEM format
     * 
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $key The key
     * @param string &$output PEM output passed by reference
     * @param string|null $passphrase Optional passphrase for encryption
     * @param array|null $options Configuration options
     * 
     * @return bool True on success
     */
    public static function exportPrivateKey(OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $key, string &$output, ?string $passphrase = null, ?array $options = null): bool
    {
        return openssl_pkey_export($key, $output, $passphrase, $options);
    }

    /**
     * Derive a key from a password using PBKDF2
     * 
     * @param string $password The password
     * @param string $salt The salt (should be random)
     * @param int $keyLength Desired key length in bytes
     * @param int $iterations Number of iterations
     * @param string $algorithm Hash algorithm
     * 
     * @return string|false Derived key or false on failure
     */
    public static function deriveKey(string $password, string $salt, int $keyLength, int $iterations = 10000, string $algorithm = 'sha256'): string|false
    {
        return openssl_pbkdf2($password, $salt, $keyLength, $iterations, $algorithm);
    }

    /**
     * Sign data using private key
     * 
     * @param string $data Data to sign
     * @param string &$signature Signature output passed by reference
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey The private key
     * @param string|int $algorithm Signature algorithm
     * 
     * @return bool True on success
     */
    public static function sign(string $data, string &$signature, OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey, string|int $algorithm = OPENSSL_ALGO_SHA256): bool
    {
        return openssl_sign($data, $signature, $privateKey, $algorithm);
    }

    /**
     * Verify signature using public key
     * 
     * @param string $data Original data
     * @param string $signature Signature to verify
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $publicKey The public key
     * @param string|int $algorithm Signature algorithm
     * 
     * @return int|false 1 if valid, 0 if invalid, false on error
     */
    public static function verify(string $data, string $signature, OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $publicKey, string|int $algorithm = OPENSSL_ALGO_SHA256): int|false
    {
        return openssl_verify($data, $signature, $publicKey, $algorithm);
    }

    /**
     * Encrypt data using public key
     * 
     * @param string $data Data to encrypt
     * @param string &$encrypted Encrypted output passed by reference
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $publicKey The public key
     * @param int $padding Padding mode
     * 
     * @return bool True on success
     */
    public static function publicEncrypt(string $data, string &$encrypted, OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $publicKey, int $padding = OPENSSL_PKCS1_OAEP_PADDING): bool
    {
        return openssl_public_encrypt($data, $encrypted, $publicKey, $padding);
    }

    /**
     * Decrypt data using private key
     * 
     * @param string $data Data to decrypt
     * @param string &$decrypted Decrypted output passed by reference
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey The private key
     * @param int $padding Padding mode
     * 
     * @return bool True on success
     */
    public static function privateDecrypt(string $data, string &$decrypted, OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey, int $padding = OPENSSL_PKCS1_OAEP_PADDING): bool
    {
        return openssl_private_decrypt($data, $decrypted, $privateKey, $padding);
    }

    /**
     * Get OpenSSL version text
     * 
     * @return array|string|int Version text (e.g. OpenSSL 3.5.5 27 Jan 2026)
     */
    public static function getVersion(): array|string|int
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException("OpenSSL extension is not enabled in PHP.");
        }

        return OPENSSL_VERSION_TEXT;
    }

    /**
     * Seal data using multiple public keys (envelope encryption)
     * 
     * @param string $data Data to seal
     * @param string &$sealedData Sealed output passed by reference
     * @param array &$envelopeKeys Encrypted keys output passed by reference
     * @param array $publicKeys Array of public keys
     * @param string $algorithm Cipher algorithm
     * @param string &$iv IV output passed by reference
     * 
     * @return int|false Length of sealed data or false on failure
     */
    public static function seal(string $data, string &$sealedData, array &$envelopeKeys, array $publicKeys, string $algorithm = 'AES-256-CBC', string &$iv = ''): int|false
    {
        return openssl_seal($data, $sealedData, $envelopeKeys, $publicKeys, $algorithm, $iv);
    }

    /**
     * Open sealed data using private key
     * 
     * @param string $sealedData The sealed data
     * @param string &$openedData Decrypted output passed by reference
     * @param string $envelopeKey The encrypted key
     * @param OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey The private key
     * @param string $algorithm Cipher algorithm
     * @param string $iv The IV
     * 
     * @return bool True on success
     */
    public static function open(string $sealedData, string &$openedData, string $envelopeKey, OpenSSLAsymmetricKey|OpenSSLCertificate|array|string $privateKey, string $algorithm = 'AES-256-CBC', string $iv = ''): bool
    {
        return openssl_open($sealedData, $openedData, $envelopeKey, $privateKey, $algorithm, $iv);
    }
}
