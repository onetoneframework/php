<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Throwable;

/**
 * Class Calculus
 *
 * A utility class for calculus-related mathematical operations.
 */
class Calculus
{
    /**
     * Calculate the numerical derivative of a function at a given point.
     *
     * @param callable $function
     * @param float $x
     * @param float $h
     * @return float|null
     */
    public static function numericalDerivative(callable $function, float $x, float $h = 0.0001): ?float
    {
        try {
            return ($function($x + $h) - $function($x)) / $h;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Calculate the numerical integral of a function over a given interval.
     *
     * @param callable $function
     * @param float $a
     * @param float $b
     * @param int $n
     * @return float|null
     */
    public static function numericalIntegral(callable $function, float $a, float $b, int $n = 1000): ?float
    {
        if ($a >= $b || $n <= 0) {
            return 0;
        }

        $h = ($b - $a) / $n;
        $sum = 0.5 * ($function($a) + $function($b));
        for ($i = 1; $i < $n; $i++) {
            try {
                $sum += $function($a + $i * $h);
            } catch (Throwable $e) {
                return null;
            }
        }

        return $h * $sum;
    }
}
