<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

/**
 * Class Sequences
 *
 * A utility class for sequence-related mathematical operations.
 */
class Sequences
{
    public static function arithmeticSequenceGeneralTerm(int $firstTerm, int $commonDifference, int $n): int
    {
        return $firstTerm + ($n - 1) * $commonDifference;
    }

    public static function geometricSequenceGeneralTerm(int $firstTerm, int $commonRatio, int $n): float
    {
        return $firstTerm * pow($commonRatio, $n - 1);
    }
}
