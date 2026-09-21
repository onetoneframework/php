<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Component;

use Clover\Classes\Database\Driver\PHPDataObject;
use function is_string;

/**
 * Database configuration resolver for environment variables.
 */
final class DatabaseConfig
{
    public function __construct(
        public readonly string $host,
        public readonly ?string $port,
        public readonly string $username,
        public readonly string $password,
        public readonly string $database,
    ) {
    }

    /**
     * Build DB config from runtime environment.
     */
    public static function fromEnv(): self
    {
        return new self(
            host: self::env('MYSQL_HOST', ''),
            port: self::envNullable('MYSQL_PORT'),
            username: self::env('MYSQL_USERNAME', ''),
            password: self::env('MYSQL_PASSWORD', ''),
            database: self::env('MYSQL_DATABASE', ''),
        );
    }

    /**
     * Apply configuration values to PHPDataObject.
     */
    public function apply(PHPDataObject $db): void
    {
        $db->setHostName($this->host);
        $db->setPort($this->port);
        $db->setUsername($this->username);
        $db->setPassword($this->password);
        $db->setDatabase($this->database);
    }

    private static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    private static function envNullable(string $key): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
