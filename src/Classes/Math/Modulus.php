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
 * Class Modulus
 *
 * A utility class for performing modular arithmetic operations on large numbers represented as strings.
 */
class Modulus
{
    /**
     * Calculate the sum of two numbers modulo m.
     * @param string $a The first number.
     * @param string $b The second number.
     * @param string $m The modulus.
     * @return string The result of (a + b) mod m.
     */
    public static function add(string $a, string $b, string $m): string
    {
        $x = bcadd($a, $b);
        while (bccomp($x, $m) >= 0) {
            $x = bcsub($x, $m);
        }

        return $x;
    }

    /**
     * Calculate the double of a number modulo m.
     * @param string $a The number to be doubled.
     * @param string $m The modulus.
     * @return string The result of (2 * a) mod m.
     */
    public static function double(string $a, string $m): string
    {
        $x = bcmul($a, '2');
        while (bccomp($x, $m) >= 0) {
            $x = bcsub($x, $m);
        }

        return $x;
    }

    /**
     * Calculate the difference of two numbers modulo m.
     * @param string $a The first number.
     * @param string $b The second number.
     * @param string $m The modulus.
     * @return string The result of (a - b) mod m.
     */
    public static function subtract(string $a, string $b, string $m): string
    {
        $x = bcsub($a, $b);
        while (bccomp($x, '0') < 0) {
            $x = bcadd($x, $m);
        }

        return $x;
    }

    /**
     * Calculate the product of two numbers modulo m.
     * @param string $a The first number.
     * @param string $b The second number.
     * @param string $m The modulus.
     * @return string The result of (a * b) mod m.
     */
    public static function multiply(string $a, string $b, string $m): string
    {
        $x = bcmul($a, $b);

        return bcmod($x, $m);
    }

    /**
     * Calculate the square of a number modulo m.
     * @param string $a The number to be squared.
     * @param string $m The modulus.
     * @return string The result of (a^2) mod m.
     */
    public static function square(string $a, string $m): string
    {
        $x = bcmul($a, $a);
        
        return bcmod($x, $m);
    }

}
