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
 * Enumeration class for Gemini finish reasons.
 */
abstract class FinishReason
{
    public const MAX_TOKENS = 'MAX_TOKENS';
    public const OTHER = 'OTHER';
    public const RECITATION = 'RECITATION';
    public const SAFETY = 'SAFETY';
    public const STOP = 'STOP';

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
            'STOP' => self::STOP,
            'MAX_TOKENS' => self::MAX_TOKENS,
            'SAFETY' => self::SAFETY,
            'RECITATION' => self::RECITATION,
            'OTHER' => self::OTHER,
            default => throw new \InvalidArgumentException("Unknown finish reason code: $code"),
        };
    }
}