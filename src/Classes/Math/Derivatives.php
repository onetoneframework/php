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
 * Class Derivatives
 *
 * A utility class for derivative-related mathematical operations.
 */
class Derivatives
{
    /**
     * Calculate the numerical derivative of a function at a given point.
     *
     * @param callable $function The function for which to calculate the derivative.
     * @param float    $x        The point at which to calculate the derivative.
     * @param float    $h        A small increment for the numerical approximation.
     * 
     * @return float The approximate derivative value at point x.
     */
    public static function numericalDerivative(callable $function, float $x, float $h = 1e-6): float
    {
        return ($function($x + $h) - $function($x)) / $h;
    }

    /**
     * Calculate the derivative of a power function f(x) = x^n.
     *
     * @param float $x The point at which to calculate the derivative.
     * @param int   $n The exponent in the power function.
     * 
     * @return float The derivative value at point x.
     */
    public static function powerRuleDerivative(float $x, int $n): float
    {
        return $n * pow($x, $n - 1);
    }
}