<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\BaseObject as BaseObject;
use InvalidArgumentException;
use function strlen;
use function count;
use function intval;
use function chr;

/**
 * Class IntegerObject
 *
 * Represents an integer and provides methods for integer operations.
 */
#[\AllowDynamicProperties]
class IntegerObject extends BaseObject
{
    /**
     * The raw integer data.
     *
     * @var mixed
     */
    protected $rawData;

    /**
     * Constructor for IntegerObject.
     *
     * @param mixed $data The integer data.
     */
    public function __construct($data)
    {
        $this->rawData = $data;
    }

    /**
     * Gets the raw integer data.
     *
     * @return int The raw integer data.
     */
    public function toInteger()
    {
        return (int) ($this->rawData);
    }

    /**
     * Gets the raw integer data.
     *
     * @return mixed The raw integer data.
     */
    public function __toString(): string
    {
        return (string) ($this->rawData);
    }

    /**
     * Gets the raw integer data.
     *
     * @param mixed $value
     * 
     * @return mixed The raw integer data.
     */
    public static function isNumeric(mixed $value): bool
    {
        return is_numeric($value);
    }

    /**
     * Checks if the integer has any zero byte.
     *
     * @param int $v The integer to check.
     * 
     * @return bool True if there is a zero byte, false otherwise.
     */
    public static function hasZeroByte(int $v): bool
    {
        return (($v - 0x01010101) & (~$v) & 0x80808080) !== 0;
    }

    /**
     * Converts an integer to its byte representation.
     *
     * @param int $integer The integer to convert.
     * 
     * @return string The byte representation of the integer.
     */
    public static function toBytes(int $integer): string
    {
        $length = strlen((string) $integer);

        $result = "";

        for ($i = $length - 1; $i >= 0; $i--) {
            $result .= chr((int) floor(($integer / pow(256, $i))));
        }

        return $result;
    }

    /**
     * Gets the raw integer data.
     *
     * @param int $number
     * 
     * @return int The raw integer data.
     */
    public function isDivisor(int $number): bool
    {
        return ($this->getRawData() % $number) == 0;
    }

    /**
     * Rounds a number up to a specified number of decimal places.
     *
     * @param float $value The number to round up.
     * @param int|float $places The number of decimal places to round to.
     * @return float|int The rounded number.
     */
    public static function roundUp(float $value, int|float $places): float|int
    {
        $mult = pow(10, abs($places));
        return $places < 0 ?
            ceil($value / $mult) * $mult :
            ceil($value * $mult) / $mult;
    }

    //http://sandbox.onlinephpfunctions.com/code/777ac7c4528357ee21426ad7cab6dcdbdd00edf0
    /**
     * Converts a number from one base to another.
     *
     * @param int $str The number to convert.
     * @param int $frombase The base of the input number.
     * @param int $tobase The base to convert the number to.
     * 
     * @return int|string The converted number as a string.
     */
    public static function baseConvert(int $str, int $frombase = 10, int $tobase = 36): int|string
    {
        $str = trim((string) $str);

        if (intval($frombase) != 10) {
            $len = strlen($str);
            $q = 0;

            for ($i = 0; $i < $len; $i++) {
                $r = base_convert($str[$i], $frombase, 10);
                $q = bcadd(bcmul($q, (string) $frombase), $r);
            }
        } else {
            $q = $str;
        }

        if (intval($tobase) != 10) {
            $s = '';

            while (bccomp($q, '0', 0) > 0) {
                $r = intval(bcmod($q, (string) $tobase));
                $s = base_convert((string) $r, 10, $tobase) . $s;
                $q = bcdiv($q, (string) $tobase, 0);
            }
        } else {
            $s = $q;
        }

        return $s;
    }

    /**
     * Finds common divisors of two integers.
     *
     * @param int $num1 The first integer.
     * @param int $num2 The second integer.
     * 
     * @return array An array of common divisors.
     */
    public static function findCommonDivisors(int $num1, int $num2): array
    {
        $divisors1 = self::findDivisors($num1);
        $divisors2 = self::findDivisors($num2);

        return array_intersect($divisors1, $divisors2);
    }

    /**
     * Finds all divisors of a given integer.
     *
     * @param int $num The integer to find divisors for.
     * 
     * @return array An array of divisors.
     */
    public static function findDivisors(int $num): array
    {
        $divisors = [];
        for ($i = 1; $i <= abs($num); $i++) {
            if ($num % $i === 0) {
                $divisors[] = $i;
            }
        }

        return $divisors;
    }

    /**
     * Finds common multiples of two integers up to a specified limit.
     *
     * @param int $num1 The first integer.
     * @param int $num2 The second integer.
     * @param int $limit The number of multiples to consider for each integer.
     *
     * @return array An array of common multiples.
     */
    public static function findCommonMultiples(int $num1, int $num2, int $limit = 100): array
    {
        $multiples1 = [];
        for ($i = 1; $i <= $limit; $i++) {
            $multiples1[] = $num1 * $i;
        }

        $multiples2 = [];
        for ($i = 1; $i <= $limit; $i++) {
            $multiples2[] = $num2 * $i;
        }

        return array_intersect($multiples1, $multiples2);
    }

    /**
     * Finds the greatest common divisor (GCD) of two integers.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     *
     * @return int The greatest common divisor of the two integers.
     */
    public static function findGreatestCommonDivisor(int $a, int $b): int
    {
        $a = abs($a);
        $b = abs($b);

        while ($b !== 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }

        return $a;
    }

    /**
     * Finds the least common multiple (LCM) of two integers.
     *
     * @param int $a The first integer.
     * @param int $b The second integer.
     *
     * @return int The least common multiple of the two integers.
     */
    public static function findLeastCommonMultiple(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }

        return abs($a * $b) / self::findGreatestCommonDivisor($a, $b);
    }

    public function length(): int
    {
        return 1;
    }

    /**
     * Calculates the number of permutations (nPr).
     * The number of ways to choose r elements from a set of n distinct elements, where the order matters.
     *
     * @param int $n The total number of distinct elements.
     * @param int $r The number of elements to choose.
     *
     * @return int The number of permutations.
     *
     * @throws InvalidArgumentException If n < r, or if n or r is negative.
     */
    public static function permutation(int $n, int $r): int
    {
        if ($n < $r || $n < 0 || $r < 0) {
            throw new InvalidArgumentException('n must be greater than or equal to r, and n and r must be non-negative.');
        }

        return self::factorial($n) / self::factorial($n - $r);
    }

    /**
     * Calculates the number of combinations (nCr).
     * The number of ways to choose r elements from a set of n distinct elements, where the order does not matter.
     *
     * @param int $n The total number of distinct elements.
     * @param int $r The number of elements to choose.
     *
     * @return int The number of combinations.
     *
     * @throws InvalidArgumentException If n < r, or if n or r is negative.
     */
    public static function combination(int $n, int $r): int
    {
        if ($n < $r || $n < 0 || $r < 0) {
            throw new InvalidArgumentException('n must be greater than or equal to r, and n and r must be non-negative.');
        }

        return self::factorial($n) / (self::factorial($r) * self::factorial($n - $r));
    }

    /**
     * Calculates the factorial of a non-negative integer (n!).
     *
     * @param int $n The non-negative integer.
     *
     * @return int The factorial value.
     *
     * @throws InvalidArgumentException If n is negative.
     */
    private static function factorial(int $n): int
    {
        if ($n < 0) {
            throw new InvalidArgumentException('n must be non-negative.');
        }

        $result = 1;
        for ($i = 2; $i <= $n; $i++) {
            $result *= $i;
        }

        return $result;
    }

    /**
     * Calculates the probability of a specific event.
     * Assumes all outcomes are equally likely.
     *
     * @param int $favorableOutcomes The number of favorable outcomes.
     * @param int $totalPossibleOutcomes The total number of possible outcomes.
     *
     * @return float The probability (a value between 0 and 1).
     *
     * @throws InvalidArgumentException If the total number of possible outcomes is not positive.
     */
    public static function probability(int $favorableOutcomes, int $totalPossibleOutcomes): float
    {
        if ($totalPossibleOutcomes <= 0) {
            throw new InvalidArgumentException('The total number of possible outcomes must be positive.');
        }

        return $favorableOutcomes / $totalPossibleOutcomes;
    }

    /**
     * Calculates the conditional probability P(A|B).
     * The probability of event A occurring given that event B has already occurred.
     * P(A|B) = P(A ∩ B) / P(B)
     * Here, we directly accept the number of outcomes for A and B intersection and B.
     *
     * @param int $outcomesOfAandB The number of outcomes where both A and B occur.
     * @param int $outcomesOfB The number of outcomes where B occurs.
     *
     * @return float The conditional probability (a value between 0 and 1).
     *
     * @throws InvalidArgumentException If the number of outcomes for B is not positive.
     */
    public static function conditionalProbability(int $outcomesOfAandB, int $outcomesOfB): float
    {
        if ($outcomesOfB <= 0) {
            throw new InvalidArgumentException('The number of outcomes for B must be positive.');
        }

        return $outcomesOfAandB / $outcomesOfB;
    }

    /**
     * Calculates the population variance.
     *
     * @param array $data An array of numerical data.
     *
     * @return float|null The population variance, or null if the data array is empty.
     */
    public static function populationVariance(array $data): ?float
    {
        $n = count($data);
        if ($n === 0) {
            return null;
        }

        $mean = array_sum($data) / $n;
        $squaredDifferencesSum = 0;

        foreach ($data as $value) {
            $squaredDifferencesSum += pow($value - $mean, 2);
        }

        return $squaredDifferencesSum / $n;
    }

    /**
     * Calculates the sample variance.
     *
     * @param array $data An array of numerical data.
     *
     * @return float|null The sample variance, or null if the data array has fewer than two elements.
     */
    public static function sampleVariance(array $data): ?float
    {
        $n = count($data);
        if ($n <= 1) {
            return null;
        }

        $mean = array_sum($data) / $n;
        $squaredDifferencesSum = 0;
        foreach ($data as $value) {
            $squaredDifferencesSum += pow($value - $mean, 2);
        }

        return $squaredDifferencesSum / ($n - 1);
    }

    /**
     * Calculates the population standard deviation.
     *
     * @param array $data An array of numerical data.

     * @return float|null The population standard deviation, or null if the data array is empty.
     */
    public static function populationStandardDeviation(array $data): ?float
    {
        $variance = self::populationVariance($data);
        return $variance !== null ? sqrt($variance) : null;
    }

    /**
     * Calculates the sample standard deviation.
     *
     * @param array $data An array of numerical data.

     * @return float|null The sample standard deviation, or null if the data array has fewer than two elements.
     */
    public static function sampleStandardDeviation(array $data): ?float
    {
        $variance = self::sampleVariance($data);
        return $variance !== null ? sqrt($variance) : null;
    }

}
