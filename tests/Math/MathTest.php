<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Math;

use Clover\Classes\Math\Basic;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MathTest extends TestCase
{
    #[DataProvider('hypotenuseProvider')]
    public function testGetHypotenuse(float $expected, int $a, int $b): void
    {
        $this->assertEqualsWithDelta($expected, Basic::getHypotenuse($a, $b), 0.0001);
    }

    public static function hypotenuseProvider(): array
    {
        return [
            '3-4-5 triangle' => [5.0, 3, 4],
            '5-12-13 triangle' => [13.0, 5, 12],
            '8-15-17 triangle' => [17.0, 8, 15],
            'zeros' => [0.0, 0, 0],
            'negative values' => [5.0, -3, -4],
        ];
    }

    #[DataProvider('negativePositiveProvider')]
    public function testNegativeAndPositive(int|float $input, int|float $expectedNegative, int|float $expectedPositive): void
    {
        $math = new Basic();
        $this->assertEquals($expectedNegative, $math->negative($input));
        $this->assertEquals($expectedPositive, $math->positive($input));
    }

    public static function negativePositiveProvider(): array
    {
        return [
            'positive integer' => [5, -5, 5],
            'negative integer' => [-5, -5, 5],
            'zero' => [0, 0, 0],
            'positive float' => [5.5, -5.5, 5.5],
            'negative float' => [-5.5, -5.5, 5.5],
        ];
    }

    #[DataProvider('averageProvider')]
    public function testGetAverage(float $expected, array $values): void
    {
        $this->assertEqualsWithDelta($expected, Basic::getAverage($values), 0.0001);
    }

    public static function averageProvider(): array
    {
        return [
            'positive integers' => [3.0, [1, 2, 3, 4, 5]],
            'negative integers' => [0.0, [-2, -1, 0, 1, 2]],
            'single float' => [2.5, [2.5]],
            'mixed floats' => [2.5, [1.5, 2.5, 3.5]],
        ];
    }

    public function testGetAverageWithEmptyArrayThrowsError(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        Basic::getAverage([]);
    }

    #[DataProvider('modPowProvider')]
    public function testModPow(int $expected, int $base, int $exp, int $mod): void
    {
        $this->assertEquals($expected, Basic::modPow($base, $exp, $mod));
    }

    public static function modPowProvider(): array
    {
        return [
            '2^3 % 5' => [3, 2, 3, 5],
            '5^2 % 7' => [4, 5, 2, 7],
            'large elements' => [1, 10, 0, 7],
            'base 0' => [0, 0, 5, 7],
        ];
    }

    public function testModPowDivisionByZero(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        Basic::modPow(2, 3, 0);
    }

    #[DataProvider('roundingProvider')]
    public function testRoundingFunctions(float|int $input, float $expectedAbs, float $expectedCeil, float $expectedFloor, float $expectedRound): void
    {
        $this->assertEqualsWithDelta($expectedAbs, Basic::absoluteValue($input), 0.0001);
        $this->assertEqualsWithDelta($expectedCeil, Basic::ceiling($input), 0.0001);
        $this->assertEqualsWithDelta($expectedFloor, Basic::floorValue($input), 0.0001);
        $this->assertEqualsWithDelta($expectedRound, Basic::roundValue($input), 0.0001);
    }

    public static function roundingProvider(): array
    {
        return [
            'positive int' => [10, 10.0, 10.0, 10.0, 10.0],
            'negative int' => [-10, 10.0, -10.0, -10.0, -10.0],
            'positive float down' => [3.4, 3.4, 4.0, 3.0, 3.0],
            'positive float up' => [3.5, 3.5, 4.0, 3.0, 4.0],
            'negative float down' => [-3.1, 3.1, -3.0, -4.0, -3.0],
            'negative float up' => [-3.6, 3.6, -3.0, -4.0, -4.0],
        ];
    }

    public function testRoundValueWithPrecision(): void
    {
        $this->assertEqualsWithDelta(3.14, Basic::roundValue(3.14159, 2), 0.0001);
        $this->assertEqualsWithDelta(3.142, Basic::roundValue(3.14159, 3), 0.0001);
        $this->assertEqualsWithDelta(10.0, Basic::roundValue(14.5, -1), 0.0001);
    }

    public function testTrigonometry(): void
    {
        $this->assertEqualsWithDelta(0.0, Basic::sine(0.0), 0.0001);
        $this->assertEqualsWithDelta(1.0, Basic::cosine(0.0), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::tangent(0.0), 0.0001);
        $this->assertEqualsWithDelta(1.0, Basic::sine(M_PI_2), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::cosine(M_PI_2), 0.0001);
    }

    public function testInverseTrigonometry(): void
    {
        $this->assertEqualsWithDelta(0.0, Basic::arcSine(0.0), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::arcCosine(1.0), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::arcTangent(0.0), 0.0001);
        $this->assertEqualsWithDelta(M_PI_2, Basic::arcSine(1.0), 0.0001);
    }

    public function testHyperbolicFunctions(): void
    {
        $this->assertEqualsWithDelta(0.0, Basic::hyperbolicSine(0.0), 0.0001);
        $this->assertEqualsWithDelta(1.0, Basic::hyperbolicCosine(0.0), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::hyperbolicTangent(0.0), 0.0001);
    }

    public function testExponentialAndLogarithms(): void
    {
        $this->assertEqualsWithDelta(1.0, Basic::exponential(0.0), 0.0001);
        $this->assertEqualsWithDelta(0.0, Basic::naturalLogarithm(1.0), 0.0001);
        $this->assertEqualsWithDelta(1.0, Basic::logarithmBase10(10.0), 0.0001);
        $this->assertEqualsWithDelta(2.0, Basic::logarithm(4.0, 2.0), 0.0001);
        
        // Error case: Logarithm of 0 or negative
        $this->assertTrue(is_infinite(Basic::naturalLogarithm(0.0)));
        $this->assertTrue(is_nan(Basic::naturalLogarithm(-1.0)));
    }

    public function testPowerAndRoot(): void
    {
        $this->assertEquals(8, Basic::power(2, 3));
        $this->assertEquals(0.25, Basic::power(2, -2));
        $this->assertEqualsWithDelta(3.0, Basic::squareRoot(9.0), 0.0001);
        $this->assertTrue(is_nan(Basic::squareRoot(-1.0)));
    }

    #[DataProvider('minMaxProvider')]
    public function testMinMax(mixed $expectedMin, mixed $expectedMax, mixed $inputArgs): void
    {
        // Testing array input
        $this->assertEquals($expectedMin, Basic::minimum($inputArgs));
        $this->assertEquals($expectedMax, Basic::maximum($inputArgs));
        
        // Testing variadic arguments
        if (is_array($inputArgs)) {
            $this->assertEquals($expectedMin, Basic::minimum(...$inputArgs));
            $this->assertEquals($expectedMax, Basic::maximum(...$inputArgs));
        }
    }

    public static function minMaxProvider(): array
    {
        return [
            'positive ints' => [1, 3, [1, 2, 3]],
            'negative ints' => [-5, -1, [-5, -2, -1]],
            'mixed types' => [-2.5, 4, [-2.5, 0, 4]],
        ];
    }

    public function testRandom(): void
    {
        $int = Basic::randomInteger(1, 10);
        $this->assertGreaterThanOrEqual(1, $int);
        $this->assertLessThanOrEqual(10, $int);

        $float = Basic::randomFloat();
        $this->assertGreaterThanOrEqual(0.0, $float);
        $this->assertLessThanOrEqual(1.0, $float);
    }

    public function testNumberChecks(): void
    {
        $this->assertTrue(Basic::isFiniteNumber(1.0));
        $this->assertFalse(Basic::isFiniteNumber(log(0))); // -INF
        
        $this->assertTrue(Basic::isInfiniteNumber(log(0)));
        $this->assertFalse(Basic::isInfiniteNumber(1.0));
        
        $this->assertTrue(Basic::isNaN(acos(2.0))); // NaN
        $this->assertFalse(Basic::isNaN(1.0));
    }

    public function testRadiansDegreesConversion(): void
    {
        $this->assertEqualsWithDelta(180.0, Basic::radiansToDegrees(M_PI), 0.0001);
        $this->assertEqualsWithDelta(M_PI, Basic::degreesToRadians(180.0), 0.0001);
    }

    public function testIntegerDivisionAndModulo(): void
    {
        $this->assertEquals(3, Basic::integerDivision(10, 3));
        $this->assertEquals(-3, Basic::integerDivision(-10, 3));
        $this->assertEqualsWithDelta(1.5, Basic::floatingPointModulo(5.5, 2.0), 0.0001);
    }

    public function testIntegerDivisionByZero(): void
    {
        $this->expectException(\DivisionByZeroError::class);
        Basic::integerDivision(10, 0);
    }

    public function testVectorOperations(): void
    {
        $vecA = [1, 2, 3];
        $vecB = [4, 5, 6];

        $this->assertEqualsWithDelta(32.0, Basic::dot($vecA, $vecB), 0.0001);
        $this->assertEqualsWithDelta(sqrt(14), Basic::linalgNorm($vecA), 0.0001);
        $this->assertEqualsWithDelta(6.0, Basic::sum($vecA), 0.0001);
        $this->assertEqualsWithDelta(2.0, Basic::mean($vecA), 0.0001);
        $this->assertEquals(3, Basic::max($vecA));
    }
    
    public function testVectorOperationsEmpty(): void
    {
        $this->assertEqualsWithDelta(0.0, Basic::sum([]), 0.0001);
        $this->assertNull(Basic::mean([]));
        $this->assertNull(Basic::max([]));
        $this->assertEqualsWithDelta(0.0, Basic::linalgNorm([]), 0.0001);
    }

    #[DataProvider('distanceProvider')]
    public function testDistances(float $expectedEuclidean, float $expectedManhattan, array $pointA, array $pointB): void
    {
        $this->assertEqualsWithDelta($expectedEuclidean, Basic::getEuclideanDistance($pointA, $pointB), 0.0001);
        $this->assertEqualsWithDelta($expectedManhattan, Basic::getManhattanDistance($pointA, $pointB), 0.0001);
    }

    public static function distanceProvider(): array
    {
        return [
            '2D positive' => [5.0, 7.0, [1, 1], [4, 5]],
            '2D negative' => [5.0, 7.0, [-1, -1], [-4, -5]],
            '3D' => [sqrt(27), 9.0, [0, 0, 0], [3, 3, 3]],
            'same point' => [0.0, 0.0, [1, 2], [1, 2]],
        ];
    }
}
