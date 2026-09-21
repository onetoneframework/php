<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

class CodePoint
{
    /**
     * Check if a code point is a CJK character
     * 
     * @param int $codePoint
     * 
     * @return bool
     */
    public static function isCJK(int $codePoint): bool
    {
        return $codePoint >= 0x1100 && ($codePoint <= 0x115f || ($codePoint >= 0x2e80 && $codePoint <= 0xa4cf && $codePoint != 0x303f));
    }

    /**
     * Check if a code point is a Fullwidth Forms character
     * 
     * @param int $codePoint
     * 
     * @return bool
     */
    public static function isFullWidthForms(int $codePoint): bool
    {
        return $codePoint >= 0x1100 && $codePoint >= 0xff00 && $codePoint <= 0xff60;
    }
}
