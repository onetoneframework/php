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
 * Password Hashing Utility Class
 */
class PasswordHash
{
	public function __construct()
	{
	}

    /**
     * Verify a password against a hash
     *
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     *
     * @return bool True if the password matches the hash, false otherwise
     */
    public static function verify(string  $password, string  $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Check if a hash needs to be rehashed
     *
     * @param string $hash The hash to check
     * @param string|int|null $algo The algorithm to use
     * @param array $options The options for the algorithm
     *
     * @return bool True if the hash needs to be rehashed, false otherwise
     */
    public static function needsRehash(string $hash, string|int|null $algo, array $options = []): bool
    {
        return password_needs_rehash($hash, $algo, $options);
    }

    /**
     * Hash a password
     *
     * @param string $password The password to hash
     * @param string|int|null $algo The algorithm to use
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function hash(string $password, int|string|null $algo = null, array $options = []): string
    {
        return password_hash($password, $algo, $options);
    }
    
    /**
     * Hash a password using the default algorithm
     *
     * @param string $password The password to hash
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function defaultHash($password, $options = []): bool|string
    {
        return self::hash($password, PASSWORD_DEFAULT, $options);
    }

    /**
     * Hash a password using BCRYPT
     *
     * @param string $password The password to hash
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function bcryptHash($password, $options = []): bool|string
    {
        return self::hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * Hash a password using Argon2i
     *
     * @param string $password The password to hash
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function argon21Hash($password, $options = []): bool|string
    {
        return self::hash($password, PASSWORD_ARGON2I, $options);
    }

    /**
     * Hash a password using Argon2id
     *
     * @param string $password The password to hash
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function argon2IdHash($password, $options = []): bool|string
    {
        return self::hash($password, PASSWORD_ARGON2ID, $options);
    }

    /**
     * Hash a password using BCRYPT with the default cost
     *
     * @param string $password The password to hash
     * @param array $options The options for the algorithm
     *
     * @return string|false The hashed password or false on failure
     */
    public static function bcryptDefaultCostHash($password, $options = []): bool|string
    {
        return self::hash($password, PASSWORD_BCRYPT_DEFAULT_COST, $options);
    }

    /**
     * Get the list of supported password hashing algorithms
     *
     * @return array The list of supported password hashing algorithms
     */
    public static function getSupportedAlgorithm(): array
    {
        return password_algos();
    }

}
