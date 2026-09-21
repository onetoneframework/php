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
 * Class Series
 *
 * A utility class for series-related mathematical operations.
 */
class Series
{
    /**
     * Calculate the sum of an arithmetic series.
     * @param int $n The number of terms in the series.
     * @param int $firstTerm The first term of the series.
     * @param int $commonDifference The common difference between terms in the series.
     * @return int The sum of the arithmetic series.
     */
    public static function arithmeticSeriesSum(int $n, int $firstTerm, int $commonDifference): int
    {
        $lastTerm = $firstTerm + ($n - 1) * $commonDifference;
        return ($n * ($firstTerm + $lastTerm)) / 2;
    }

    /**
     * Calculate the sum of a geometric series.
     * @param int $n The number of terms in the series.
     * @param int $firstTerm The first term of the series.
     * @param int $commonRatio The common ratio of the series.
     * @return float The sum of the geometric series.
     */
    public static function geometricSeriesSum(int $n, int $firstTerm, int $commonRatio): float
    {
        if ($commonRatio === 1) {
            return $n * $firstTerm;
        }
        return $firstTerm * (1 - pow($commonRatio, $n)) / (1 - $commonRatio);
    }
}
