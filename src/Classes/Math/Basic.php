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
use function count;
use function is_array;
use function func_get_args;

/**
 * Class Basic
 *
 * A basic math utility class providing various mathematical functions.
 */
class Basic
{
    #region function

    /**
     * Calculate the hypotenuse of a right triangle given the lengths of the two legs.
     * @param int $a The length of the first leg.
     * @param int $b The length of the second leg.
     * @return float The length of the hypotenuse.
     */
    public static function getHypotenuse(int $a, int $b): float
    {
        return sqrt($a * $a + $b * $b);
    }

    /**
     * Calculate the greatest common divisor (GCD) of two integers using the Euclidean algorithm.
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The greatest common divisor of the two integers.
     */
    public static function gcd(int $a, int $b): int
    {
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        return abs($a);
    }

    /**
     * Calculate the least common multiple (LCM) of two integers.
     * @param int $a The first integer.
     * @param int $b The second integer.
     * @return int The least common multiple of the two integers.
     */
    public static function lcm(int $a, int $b): int
    {
        if ($a == 0 || $b == 0) {
            return 0;
        }
        return abs($a * $b) / self::gcd($a, $b);
    }

    /**
     * Calculate the factorial of a non-negative integer.
     * @param int $n The non-negative integer to calculate the factorial of.
     * @return int The factorial of the given integer.
     * @throws InvalidArgumentException If the input is a negative integer.
     */
    public static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new InvalidArgumentException("Input must be a non-negative integer.");
        }
        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }
        return $result;
    }

    /**
     * Calculate the negative value of a number.
     *
     * @param int|float $x The number for which to calculate the negative value.
     *
     * @return float|int The negative value of the input number.
     */
    public function negative(int|float $x): float|int
    {
        return -abs($x);
    }

    /**
     * Calculate the positive value of a number.
     *
     * @param int|float $x The number for which to calculate the positive value.
     *
     * @return float|int The positive value of the input number.
     */
    public function positive(int|float $x): float|int
    {
        return abs($x);
    }

    /**
     * Calculate the average (mean) of an array of numbers.
     *
     * @param array $values An array of numbers for which to calculate the average.
     *
     * @return float|int The average of the input numbers.
     */
    public static function getAverage(array $values): float|int
    {
        return array_sum($values) / count($values);
    }

    /**
     * Calculate the modular exponentiation of a number.
     *
     * @param int $base The base number.
     * @param int $exp The exponent.
     * @param int $mod The modulus.
     *
     * @return int The result of (base^exp) mod mod.
     */
    public static function modPow(int $base, int $exp, int $mod): int
    {
        if (extension_loaded('gmp')) {
            // @phpstan-ignore-next-line
            return (int) gmp_intval(gmp_powm($base, $exp, $mod));
        }

        $base %= $mod;
        $result = 1;
        while ($exp > 0) {
            if ($exp & 1) {
                $result = ($result * $base) % $mod;
            }
            $base = ($base * $base) % $mod;
            $exp >>= 1;
        }
        return $result;
    }

    /**
     * Calculate the absolute value of a number.
     *
     * @param float|int $number The number for which to calculate the absolute value.
     *
     * @return float|int The absolute value of the input number.
     */
    public static function absoluteValue(float|int $number): float|int
    {
        return abs($number);
    }

    /**
     * Calculate the ceiling of a number.
     *
     * @param float|int $number The number to be ceiled.
     *
     * @return float The smallest integer greater than or equal to the given number.
     */
    public static function ceiling(float|int $number): float
    {
        return ceil($number);
    }

    /**
     * Calculate the floor of a number.
     *
     * @param float|int $number The number to be floored.
     *
     * @return float The largest integer less than or equal to the given number.
     */
    public static function floorValue(float|int $number): float
    {
        return floor($number);
    }

    /**
     * Round a number to a specified precision.
     *
     * @param float|int $number The number to round.
     * @param int $precision The number of decimal digits to round to (default is 0).
     * @param int $mode The rounding mode (default is PHP_ROUND_HALF_UP).
     *
     * @return float The rounded number.
     */
    public static function roundValue(float|int $number, int $precision = 0, int $mode = PHP_ROUND_HALF_UP): float
    {
        return round((float) $number, $precision, $mode);
    }

    public static function sine(float $angle): float
    {
        return sin($angle);
    }

    public static function cosine(float $angle): float
    {
        return cos($angle);
    }

    public static function tangent(float $angle): float
    {
        return tan($angle);
    }

    /**
     * Calculate the cotangent of an angle.
     *
     * @param float $angle The angle in radians for which to calculate the cotangent.
     *
     * @return float The cotangent of the given angle.
     */
    public static function cotangent(float $angle): float
    {
        return 1 / tan($angle);
    }

    public static function arcSine(float $sineValue): float
    {
        return asin($sineValue);
    }

    public static function arcCosine(float $cosineValue): float
    {
        return acos($cosineValue);
    }

    /**
     * Calculate the arc tangent of a value.
     *
     * @param float $tangentValue The value for which to calculate the arc tangent.
     *
     * @return float The arc tangent of the given value in radians.
     */
    public static function arcTangent(float $tangentValue): float
    {
        return atan($tangentValue);
    }

    /**
     * Calculate the arc tangent of the quotient of two values.
     *
     * @param float $y The value representing the opposite side of a right triangle.
     * @param float $x The value representing the adjacent side of a right triangle.
     *
     * @return float The arc tangent of the quotient y/x in radians, taking into account the signs of both arguments to determine the correct quadrant.
     */
    public static function arcTangent2(float $y, float $x): float
    {
        return atan2($y, $x);
    }

    public static function hyperbolicSine(float $angle): float
    {
        return sinh($angle);
    }

    public static function hyperbolicCosine(float $angle): float
    {
        return cosh($angle);
    }

    public static function hyperbolicTangent(float $angle): float
    {
        return tanh($angle);
    }

    public static function exponential(float $exponent): float
    {
        return exp($exponent);
    }

    /**
     * Calculate the natural logarithm of a number with an optional base.
     *
     * @param float $number The number for which to calculate the logarithm. Must be greater than 0.
     * @param float $base The base of the logarithm (default is e). Must be greater than 1.
     *
     * @return float The logarithm of the number to the specified base.
     */
    public static function naturalLogarithm(float $number, float $base = M_E): float
    {
        return log($number, $base);
    }

    public static function logarithmBase10(float $number): float
    {
        return log10($number);
    }

    public static function logarithmBase(float $number, float $base = M_E): float
    {
        return log($number, $base);
    }

    public static function logarithm(float $num, float $base): float|int
    {
        return log($num) / log($base);
    }

    public static function power(mixed $base, mixed $exponent): float|int|object
    {
        return pow($base, $exponent);
    }

    public static function squareRoot(float $number): float
    {
        return sqrt($number);
    }

    public static function minimum(mixed $values): mixed
    {
        return is_array($values) ? min($values) : min(func_get_args());
    }

    public static function maximum(mixed $values): mixed
    {
        return is_array($values) ? max($values) : max(func_get_args());
    }

    /**
     * Generate a random integer between the specified minimum and maximum values.
     *
     * @param int $min The minimum value (inclusive). Default is 0.
     * @param int $max The maximum value (inclusive). Default is PHP_INT_MAX.
     *
     * @return int A random integer between the specified minimum and maximum values.
     */
    public static function randomInteger(int $min = 0, int $max = PHP_INT_MAX): int
    {
        return rand($min, $max);
    }

    /**
     * Generate a random floating-point number between 0 (inclusive) and 1 (exclusive).
     *
     * @return float A random floating-point number between 0 and 1.
     */
    public static function randomFloat(): float|int
    {
        return (float) mt_rand() / mt_getrandmax();
    }

    /**
     * Calculate the inverse hyperbolic sine of a number.
     *
     * @param float $x The number for which to calculate the inverse hyperbolic sine.
     *
     * @return float The inverse hyperbolic sine of the input number.
     */
    public static function inverseHyperbolicSine(float $x): float
    {
        return asinh($x);
    }

    /**
     * Calculate the inverse hyperbolic cosine of a number.
     *
     * @param float $x The number for which to calculate the inverse hyperbolic cosine. Must be greater than or equal to 1.
     *
     * @return float The inverse hyperbolic cosine of the input number.
     */
    public static function inverseHyperbolicCosine(float $x): float
    {
        return acosh($x);
    }

    /**
     * Calculate the inverse hyperbolic tangent of a number.
     *
     * @param float $x The number for which to calculate the inverse hyperbolic tangent. Must be in the range (-1, 1).
     *
     * @return float The inverse hyperbolic tangent of the input number.
     */
    public static function inverseHyperbolicTangent(float $x): float
    {
        return atanh($x);
    }

    /**
     * Check if a number is finite.
     *
     * @param float $number The number to check.
     *
     * @return bool True if the number is finite, false otherwise.
     */
    public static function isFiniteNumber(float $number): bool
    {
        return is_finite($number);
    }

    /**
     * Check if a number is infinite.
     *
     * @param float $number The number to check.
     *
     * @return bool True if the number is infinite, false otherwise.
     */
    public static function isInfiniteNumber(float $number): bool
    {
        return is_infinite($number);
    }

    /**
     * Check if a number is NaN (Not a Number).
     *
     * @param float $number The number to check.
     *
     * @return bool True if the number is NaN, false otherwise.
     */
    public static function isNaN(float $number): bool
    {
        return is_nan($number);
    }

    /**
     * Convert degrees to radians.
     *
     * @param float $degree The angle in degrees to be converted.
     *
     * @return float The angle in radians.
     */
    public static function degreesToRadians(float $degree): float
    {
        return deg2rad($degree);
    }

    /**
     * Convert radians to degrees.
     *
     * @param float $radian The angle in radians to be converted.
     *
     * @return float The angle in degrees.
     */
    public static function radiansToDegrees(float $radian): float
    {
        return rad2deg($radian);
    }

    /**
     * Perform integer division of two integers.
     *
     * @param int $dividend The number to be divided.
     * @param int $divisor  The number by which to divide.
     *
     * @return int The result of the integer division.
     */
    public static function integerDivision(int $dividend, int $divisor): int
    {
        return intdiv($dividend, $divisor);
    }

    /**
     * Calculate the floating-point remainder of the division of two numbers.
     *
     * @param float $dividend The number to be divided.
     * @param float $divisor  The number by which to divide.
     *
     * @return float The floating-point remainder of the division.
     */
    public static function floatingPointModulo(float $dividend, float $divisor): float
    {
        return fmod($dividend, $divisor);
    }

    /**
     * Calculate the dot product of two vectors.
     *
     * @param array $a The first vector as an array of numbers.
     * @param array $b The second vector as an array of numbers.
     *
     * @return float|int The dot product of the two vectors.
     */
    public static function dot($a, $b): float|int
    {
        $dot = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
        }
        return $dot;
    }

    /**
     * Calculate the L2 norm (Euclidean norm) of a vector.
     *
     * @param array $a An array of numbers representing the vector.
     *
     * @return float The L2 norm of the vector.
     */
    public static function linalgNorm($a): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += $a[$i] ** 2;
        }
        return sqrt($sum);
    }

    /**
     * Calculate the sum of an array of numbers.
     *
     * @param array $a An array of numbers.
     *
     * @return float|int The sum of the numbers in the array.
     */
    public static function sum($a): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += $a[$i];
        }
        return $sum;
    }

    /**
     * Calculate the cosine distance between two vectors.
     *
     * @param array $vector_a The first vector as an array of numbers.
     * @param array $vector_b The second vector as an array of numbers.
     *
     * @return float|int The cosine distance between the two vectors, where 0 means they are identical and 1 means they are orthogonal.
     */
    public static function getCosineDistance($vector_a, $vector_b): float|int
    {
        $dot_product = 0.0;
        $norm_a = 0.0;
        $norm_b = 0.0;

        for ($i = 0; $i < count($vector_a); $i++) {
            $dot_product += $vector_a[$i] * $vector_b[$i];
            $norm_a += $vector_a[$i] ** 2;
            $norm_b += $vector_b[$i] ** 2;
        }

        $similarity = $dot_product / (sqrt($norm_a) * sqrt($norm_b));
        return 1 - $similarity;

    }

    /**
     * Calculate the mean (average) of an array of numbers.
     *
     * @param array $a An array of numbers.
     *
     * @return float|int|null The mean of the numbers in the array, or null if the array is empty.
     */
    public static function mean($a): float|int|null
    {
        if (count($a) === 0) {
            return null;
        }

        return self::sum($a) / count($a);
    }

    /**
     * Calculate the maximum value in an array of numbers.
     *
     * @param array $a An array of numbers.
     *
     * @return float|int|null The maximum value in the array, or null if the array is empty.
     */
    public static function max($a): mixed
    {
        if (count($a) === 0) {
            return null;
        }

        $max_val = $a[0];
        foreach ($a as $val) {
            if ($val > $max_val) {
                $max_val = $val;
            }
        }
        return $max_val;
    }

    /**
     * Calculate the Manhattan distance between two points in n-dimensional space.
     *
     * @param array $point_a The first point as an array of coordinates.
     * @param array $point_b The second point as an array of coordinates.
     *
     * @return float|int The Manhattan distance between the two points.
     */
    public static function getManhattanDistance($point_a, $point_b): float|int
    {
        $sum = 0.0;
        for ($i = 0; $i < count($point_a); $i++) {
            $sum += abs($point_a[$i] - $point_b[$i]);
        }
        return $sum;
    }

    /**
     * Calculate the Euclidean distance between two points in n-dimensional space.
     *
     * @param array $point_a The first point as an array of coordinates.
     * @param array $point_b The second point as an array of coordinates.
     *
     * @return float The Euclidean distance between the two points.
     */
    public static function getEuclideanDistance($point_a, $point_b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < count($point_a); $i++) {
            $sum += ($point_a[$i] - $point_b[$i]) ** 2;
        }
        return sqrt($sum);
    }

    /**
     * Convert Euler angles (roll, pitch, yaw) to a quaternion (w, x, y, z).
     *
     * @param float $roll  Rotation around the X-axis in radians.
     * @param float $pitch Rotation around the Y-axis in radians.
     * @param float $yaw   Rotation around the Z-axis in radians.
     *
     * @return array An array containing the quaternion components [w, x, y, z].
     */
    public static function eulerToQuaternion(float $roll, float $pitch, float $yaw): array
    {
        $phi = $roll / 2;
        $theta = $pitch / 2;
        $psi = $yaw / 2;

        $cosPhi = cos($phi);
        $sinPhi = sin($phi);
        $cosTheta = cos($theta);
        $sinTheta = sin($theta);
        $cosPsi = cos($psi);
        $sinPsi = sin($psi);

        $w = $cosPsi * $cosTheta * $cosPhi + $sinPsi * $sinTheta * $sinPhi;
        $x = $cosPsi * $cosTheta * $sinPhi - $sinPsi * $sinTheta * $cosPhi;
        $y = $cosPsi * $sinTheta * $cosPhi + $sinPsi * $cosTheta * $sinPhi;
        $z = $sinPsi * $cosTheta * $cosPhi - $cosPsi * $sinTheta * $sinPhi;

        return [$w, $x, $y, $z];
    }

    public static function absRound(float|int $number): float|int
    {
        if (function_exists('round')) {
            return round(abs($number));
        }

        return (0.5 + $number) << 0;
    }

}
