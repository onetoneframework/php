<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use function strlen;
use function ord;
use function in_array;

/**
 * Class CharacterObject
 *
 * Provides utility methods for character analysis.
 */
class CharacterObject
{

    /**
     * Checks if the character is a Hanja character.
     *
     * @param string $ch The character to check.
     * 
     * @return bool True if the character is a Hanja character, false otherwise.
     */
    public static function isHanja(string $ch): bool
    {
        if (strlen($ch) !== 3) {
            return false;
        }

        $ch1 = ord($ch[0]);
        $ch2 = ord($ch[1]);

        return ($ch1 >= 0xE0 && $ch1 <= 0xF9) && (($ch2 >= 0x31 && $ch2 <= 0x7E) || ($ch2 > 0x91 && $ch2 <= 0xFE));
    }

    /**
     * Checks if the character is a special character in the extended ASCII range.
     *
     * @param string $ch The character to check.
     * 
     * @return bool|int True if the character is a special character, false otherwise.
     */
    public static function isSpecialCharacter(string $ch): bool|int
    {
        if (strlen($ch) !== 1) {
            return false;
        }
        $ch1 = ord($ch[0]);

        if (in_array($ch1, [0xd4, 0xd9, 0xda, 0xdb, 0xdc, 0xdd, 0xde])) {
            return true;
        }

        return false;

    }

}
