<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Framework\Component;
use function is_string;

/**
 * App URL resolver for environment-based absolute URLs.
 */
class AppUrl
{
    /**
     * Resolve configured public base URL.
     * 
     * @param string $default
     * @return string
     */
    public static function base(string $default = 'http://localhost:8080'): string
    {
        $base = self::getEnvValue('APP_PUBLIC_URL');

        return rtrim($base !== null && $base !== '' ? $base : $default, '/');
    }

    /**
     * Build absolute URL from public base and path.
     * 
     * @param string $path
     * @param string $defaultBase
     * @return string
     */
    public static function absolute(string $path, string $defaultBase = 'http://localhost:8080'): string
    {
        $normalizedPath = str_starts_with($path, '/') ? $path : '/' . $path;

        return self::base($defaultBase) . $normalizedPath;
    }

    private static function getEnvValue(string $key): ?string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return is_string($value) ? trim($value) : null;
    }
}
