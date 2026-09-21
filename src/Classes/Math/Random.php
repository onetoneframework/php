<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use InvalidArgumentException;
use function array_slice;
use function sprintf;

/**
 * Class Random
 *
 * A utility class for generating random numbers and values.
 */
class Random
{
    /**
     * Generate a random integer using the Mersenne Twister algorithm.
     *
     * @param int $min The minimum value (inclusive).
     * @param int $max The maximum value (inclusive).
     * @return int A random integer between min and max.
     * @throws \InvalidArgumentException If max is greater than mt_getrandmax().
     */
    public static function mersenneTwister(int $min, int $max): int
    {
        if ($max > mt_getrandmax()) {
            throw new InvalidArgumentException(sprintf("Max value must not exceed %d", mt_getrandmax()));
        }

        return mt_rand($min, $max);
    }

    /**
     * Generate a random float between a specified minimum and maximum value.
     *
     * @param float $min The minimum value (inclusive).
     * @param float $max The maximum value (exclusive).
     * @return float A random float between min and max.
     * @throws \InvalidArgumentException If min is not less than max, or if either value is not finite.
     */
    public static function float(float $min = 0.0, float $max = 1.0): float
    {
        if (!is_finite($min) || !is_finite($max) || $min >= $max) {
            throw new InvalidArgumentException('Require finite min < max');
        }
        // 53-bit precision using 64-bit integer
        $r = random_int(0, (1 << 53) - 1);
        $unit = $r / ((1 << 53) - 1);
        return $min + $unit * ($max - $min);
    }

    /**
     * Generate a random boolean value with a specified probability of being true.
     *
     * @param float $p The probability of returning true (between 0 and 1).
     * @return bool A random boolean value.
     * @throws \InvalidArgumentException If p is not between 0 and 1.
     */
    public static function bool(float $p = 0.5): bool
    {
        if ($p <= 0.0) {
            return false;
        }
        if ($p >= 1.0) {
            return true;
        }
        return self::float(0.0, 1.0) < $p;
    }

    /**
     * Generate a random number following a Gaussian (normal) distribution with specified mean and standard deviation.
     *
     * @param float $mean The mean of the distribution.
     * @param float $stddev The standard deviation of the distribution (must be non-negative).
     * @return float A random number drawn from the specified Gaussian distribution.
     * @throws \InvalidArgumentException If stddev is negative.
     */
    public static function gaussian(float $mean = 0.0, float $stddev = 1.0): float
    {
        if ($stddev < 0.0) {
            throw new InvalidArgumentException('stddev must be non-negative');
        }
        // Box-Muller transform
        $u1 = self::float(0.0, 1.0);
        $u2 = self::float(0.0, 1.0);
        $z0 = sqrt(-2.0 * log(max($u1, 1e-18))) * cos(2 * M_PI * $u2);
        return $mean + $z0 * $stddev;
    }

    /**
     * Generate an array of random integers within a specified range.
     *
     * @param int $n The number of random integers to generate.
     * @param int $min The minimum value (inclusive).
     * @param int $max The maximum value (inclusive).
     * @return array An array of random integers between min and max.
     * @throws \InvalidArgumentException If n is negative, or if max is less than min.
     */
    public static function indices(int $n, int $k): array
    {
        if ($n < 0 || $k < 0 || $k > $n) {
            throw new InvalidArgumentException('invalid n or k');
        }
        $indices = range(0, $n - 1);
        // partial shuffle
        for ($i = 0; $i < $k; $i++) {
            $j = random_int($i, $n - 1);
            [$indices[$i], $indices[$j]] = [$indices[$j], $indices[$i]];
        }

        return array_slice($indices, 0, $k);
    }

    /**
     * Generate a random string of bytes with a specified length.
     *
     * @param int $length The length of the random byte string to generate (must be non-negative).
     * @return string A random string of bytes.
     * @throws \InvalidArgumentException If length is negative.
     * @throws \Exception If an appropriate source of randomness cannot be found.
     */
    public static function bytes(int $length): string
    {
        if ($length < 0) {
            throw new InvalidArgumentException('length must be non-negative');
        }

        return random_bytes($length);
    }

}
