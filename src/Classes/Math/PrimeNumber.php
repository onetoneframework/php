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
 * Class PrimeNumber
 *
 * A utility class for prime number related functions.
 */
class PrimeNumber
{
    /**
     * Check if a number is prime.
     *
     * @param int $n The number to check for primality.
     * @return bool True if the number is prime, false otherwise.
     */
    public static function isPrime(int $n): bool
    {
        if ($n <= 1) {
            return false;
        }
        if ($n <= 3) {
            return true;
        }

        if ($n % 2 === 0) {
            return false;
        }

        $r = (int) floor(sqrt($n));
        for ($i = 3; $i <= $r; $i += 2) {
            if ($n % $i === 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate the greatest common divisor (GCD) of two integers.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The greatest common divisor of the two integers.
     */
    public static function gcd(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);
        while ($b !== 0) {
            $t = $b;
            $b = $a % $b;
            $a = $t;
        }
        return $a;
    }

    /**
     * Check if a number is a Fermat pseudoprime to a given base.
     *
     * @param int $n The number to check.
     * @param int $a The base to check against.
     * @return bool True if the number is a Fermat pseudoprime to the base, false otherwise.
     */
    public static function isFermatPseudoprime(int $n, int $a): bool
    {
        if (extension_loaded('gmp')) {
            // @phpstan-ignore-next-line
            if (gmp_cmp($n, "3") < 0) {
                return false;
            }
            // @phpstan-ignore-next-line
            if (gmp_gcd($a, $n) != 1) {
                return false;
            }
            // @phpstan-ignore-next-line
            $isPrime = gmp_prob_prime($n);
            if ($isPrime === 2) {
                return false;
            }

            // @phpstan-ignore-next-line
            $r = gmp_powm($a, gmp_sub($n, "1"), $n);
            // @phpstan-ignore-next-line
            return gmp_cmp($r, "1") === 0;
        }

        if ($n < 3) {
            return false;
        }
        if (self::gcd($a, $n) !== 1) {
            return false;
        }

        if (self::isPrime($n)) {
            return false;
        }

        return Basic::modPow($a, $n - 1, $n) === 1;
    }

}
