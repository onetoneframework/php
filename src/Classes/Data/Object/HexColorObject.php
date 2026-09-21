<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

/**
 * Class HexColorObject
 */
class HexColorObject
{
    /**
     * Validates whether a given string is a valid hexadecimal color code.
     *
     * @param string|null $hex The hexadecimal color code to validate (e.g., "#ff5733").
     * 
     * @return bool True if the input is a valid hex color code, false otherwise.
     */
    public static function isValid(?string $hex): bool
    {
        if ($hex === null || $hex === '') {
            return false;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $hex) === 1;
    }

    /**
     * Converts a hexadecimal color code to its RGB components.
     *
     * @param string $hex The hexadecimal color code (e.g., "#ff5733").
     * 
     * @return array An associative array with keys 'r', 'g', and 'b' representing the RGB values.
     */
    public static function toRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return ['r' => $r, 'g' => $g, 'b' => $b];
    }
}
