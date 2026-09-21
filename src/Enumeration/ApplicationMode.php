<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Enumeration;

/**
 * Application Mode Enumeration
 *
 * Defines the different operational modes of the application (development, test, production).
 */
enum ApplicationMode: string
{
    case DEVELOPMENT = 'development';
    case TEST = 'test';
    case PRODUCTION = 'production';

    public function isProduction(): bool
    {
        return self::PRODUCTION === $this;
    }

    public function isDevelopment(): bool
    {
        return self::DEVELOPMENT === $this;
    }

    public function isTest(): bool
    {
        return self::TEST === $this;
    }

    public static function fromString(string $mode): self
    {
        $normalized = strtolower($mode);

        return match ($normalized) {
            'dev', 'development' => self::DEVELOPMENT,
            'test' => self::TEST,
            'prod', 'production' => self::PRODUCTION,
            default => throw new \InvalidArgumentException("Unknown application mode: {$mode}"),
        };
    }
}