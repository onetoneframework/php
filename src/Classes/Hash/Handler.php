<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Hash;

/**
 * Hash Handler Base Class
 */
class Handler
{
    /**
     * The hashing algorithm to use
     *
     * @var string
     */
    public string $algorithm;

    /**
     * Constructor
     *
     * @param string $algorithm The hashing algorithm to use
     */
    public function __construct(string $algorithm = 'sha256')
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Hash a password
     *
     * @param string $password The password to hash
     * @param string|int|null $algo The algorithm to use
     * @param array $options The options for the algorithm
     *
     * @return string The hashed password or false on failure
     */
    public function passwordHash(#[\SensitiveParameter] string $password, string|int|null $algo, array $options = []): string
    {
        return password_hash($password, $algo, $options);
    }

    /**
     * Verify a password against a hash
     *
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     *
     * @return bool True if the password matches the hash, false otherwise
     */
    public function passwordVerify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Generate a hash value (message digest)
     *
     * @param string $data The data to hash
     * @param bool|null $binary Whether to return raw binary data
     * @param array|null $options Additional options for the hashing algorithm
     *
     * @return string The hashed data
     */
    public function hash(string $data, bool|null $binary = false, array|null $options = []): string
    {
        return hash($this->algorithm, $data, $binary, $options);
    }

    /**
     * Generate a hash value using the contents of a given file
     *
     * @param string $filename The path to the file to hash
     * @param bool|null $binary Whether to return raw binary data
     * @param array|null $options Additional options for the hashing algorithm
     *
     * @return string|bool The hashed file data or false on failure
     */
    public function hashFile(string $filename, bool|null $binary = false, array|null $options = []): bool|string
    {
        return hash_file($this->algorithm, $filename, $binary, $options);
    }

    /**
     * Get the list of supported hashing algorithms
     *
     * @return array<string> The list of supported hashing algorithms
     * - md2, md4, md5
     * - sha1, sha224, sha256, sha384, sha512/224, sha512/256, sha512, sha3-224, sha3-256, sha3-384, sha3-512
     * - ripemd128, ripemd160, ripemd256, ripemd320
     * - whirlpool
     * - `tiger128,3`, `tiger160,3`, `tiger192,3`, `tiger128,4`, `tiger160,4`, `tiger192,4`
     * - snefru, snefru256
     * - gost, gost-crypto
     * - adler32
     * - crc32, crc32b, crc32c
     * - fnv132, fnv1a32, fnv164, fnv1a64
     * - joaat
     * - murmur3a, murmur3c, murmur3f
     * - xxh32, xxh64, xxh3, xxh128
     * - `haval128,3`, `haval160,3`, `haval192,3`, `haval224,3`, `haval256,3`, `haval128,4`, `haval160,4`, `haval192,4`, `haval224,4`, `haval256,4`, `haval128,5`, `haval160,5`, `haval192,5`, `haval224,5`, `haval256,5`
     */
    public function getAlgorithms(): array
    {
        return hash_algos();
    }
}
