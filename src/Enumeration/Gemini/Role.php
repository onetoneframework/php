<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Gemini;

/**
 * Enumeration class for Gemini roles.
 */
abstract class Role
{
    public const MODEL = 'MODEL';
    public const USER = 'USER';

    /**
     * Get the finish reason constant from a string code.
     *
     * @param string $code The finish reason code.
     * @return string The corresponding finish reason constant.
     * @throws \InvalidArgumentException If the code is unknown.
     */
    public static function from(string $code): string
    {
        return match ($code) {
            'model' => self::MODEL,
            'user' => self::USER,
            default => throw new \InvalidArgumentException("Unknown finish reason code: $code"),
        };
    }
}