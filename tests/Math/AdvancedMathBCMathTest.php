<?php

declare(strict_types=1);

namespace Clover\Tests\Math;
use InvalidArgumentException;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Math\AdvancedMathBCMath;

class AdvancedMathBCMathTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        if (!extension_loaded('bcmath')) {
            self::markTestSkipped('bcmath extension is not enabled.');
        }
        AdvancedMathBCMath::setScale(20);
    }

    // ========================================================================
    // Scale / Configuration Tests
    // ========================================================================

    public function testSetScale()
    {
        AdvancedMathBCMath::setScale(15);
        $reflection = new \ReflectionClass(AdvancedMathBCMath::class);
        $scaleProperty = $reflection->getProperty('scale');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $scaleProperty->setAccessible(true);
        }

        $this->assertSame(15, $scaleProperty->getValue());
        AdvancedMathBCMath::setScale(20);
    }

    // ========================================================================
    // Basic Arithmetic Tests
    // ========================================================================

    public function testBcadd()
    {
        $this->assertEquals('3.00000000000000000000', AdvancedMathBCMath::bcadd('1', '2'));
        $this->assertEquals('0.00000000000000000000', AdvancedMathBCMath::bcadd('-5', '5'));
        $this->assertEquals('0.30000000000000000000', AdvancedMathBCMath::bcadd('0.1', '0.2'));
    }

    public function testBcsub()
    {
        $this->assertEquals('-1.00000000000000000000', AdvancedMathBCMath::bcsub('1', '2'));
        $this->assertEquals('0.00000000000000000000', AdvancedMathBCMath::bcsub('5', '5'));
        $this->assertEquals('3.50000000000000000000', AdvancedMathBCMath::bcsub('10.5', '7'));
    }

    public function testBcmul()
    {
        $this->assertEquals('6.00000000000000000000', AdvancedMathBCMath::bcmul('2', '3'));
        $this->assertEquals('0.00000000000000000000', AdvancedMathBCMath::bcmul('0', '999'));
        $this->assertEquals('-15.00000000000000000000', AdvancedMathBCMath::bcmul('-3', '5'));
    }

    public function testBcdiv()
    {
        $this->assertEqualsWithDelta('2.00000000000000000000', AdvancedMathBCMath::bcdiv('6', '3'), 1e-6);
        $this->assertEquals('0', AdvancedMathBCMath::bcdiv('0', '5'));
    }

    public function testBcdivByZeroThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::bcdiv('1', '0');
    }

    public function testBcpow()
    {
        $this->assertEquals('8.00000000000000000000', AdvancedMathBCMath::bcpow('2', 3));
        $this->assertEquals('1.00000000000000000000', AdvancedMathBCMath::bcpow('5', 0));
        $this->assertEquals('100.00000000000000000000', AdvancedMathBCMath::bcpow('10', 2));
    }

    public function testBcsqrt()
    {
        $this->assertEquals('3.00000000000000000000', AdvancedMathBCMath::bcsqrt('9'));
        $this->assertEquals('0.00000000000000000000', AdvancedMathBCMath::bcsqrt('0'));
        $this->assertEqualsWithDelta(1.41421356, (float) AdvancedMathBCMath::bcsqrt('2'), 1e-6);
    }

    public function testBcsqrtNegativeThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::bcsqrt('-4');
    }

    public function testBccomp()
    {
        $this->assertEquals(1, AdvancedMathBCMath::bccomp('5', '3'));
        $this->assertEquals(-1, AdvancedMathBCMath::bccomp('3', '5'));
        $this->assertEquals(0, AdvancedMathBCMath::bccomp('5', '5'));
    }

    public function testBcabs()
    {
        $this->assertEquals('5', AdvancedMathBCMath::bcabs('-5'));
        $this->assertEquals('5', AdvancedMathBCMath::bcabs('5'));
        $this->assertEquals('0.123', AdvancedMathBCMath::bcabs('-0.123'));
    }

    public function testBcabsInvalidThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::bcabs('abc');
    }

    public function testBcmax()
    {
        $num1 = '123.456';
        $num2 = '78.901';
        $this->assertSame($num1, AdvancedMathBCMath::bcmax($num1, $num2, 10));
        $this->assertSame($num1, AdvancedMathBCMath::bcmax($num2, $num1, 10));
        $this->assertSame($num1, AdvancedMathBCMath::bcmax($num1, '123.456', 10));
    }

    public function testBcmin()
    {
        $this->assertSame('78.901', AdvancedMathBCMath::bcmin('123.456', '78.901', 10));
        $this->assertSame('-5', AdvancedMathBCMath::bcmin('-5', '3', 10));
        $this->assertSame('0', AdvancedMathBCMath::bcmin('0', '0', 10));
    }

    public function testBcmodPrecise()
    {
        $this->assertEquals('1.00000000000000000000', AdvancedMathBCMath::bcmod_precise('10', '3'));
        $this->assertEquals('0.00000000000000000000', AdvancedMathBCMath::bcmod_precise('9', '3'));
    }

    public function testBcmodPreciseDivByZeroThrowsException()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::bcmod_precise('10', '0');
    }

    // ========================================================================
    // Trigonometric Tests
    // ========================================================================

    public function testBccos()
    {
        $angle = '1.04719755';
        $expectedCos = '0.50000000';
        $actualCos = AdvancedMathBCMath::bccos($angle, 20);
        $this->assertEqualsWithDelta((float) $expectedCos, (float) $actualCos, 0.00000001);
    }

    public function testBccosZero()
    {
        $actualCos = AdvancedMathBCMath::bccos('0', 20);
        $this->assertEqualsWithDelta(1.0, (float) $actualCos, 0.00000001);
    }

    public function testBccosPi()
    {
        $actualCos = AdvancedMathBCMath::bccos((string) M_PI, 20);
        $this->assertEqualsWithDelta(-1.0, (float) $actualCos, 0.00000001);
    }

    public function testBcsinZero()
    {
        $actualSin = AdvancedMathBCMath::bcsin('0');
        $this->assertEqualsWithDelta(0.0, (float) $actualSin, 0.00000001);
    }

    public function testBcsinPiOverTwo()
    {
        $actualSin = AdvancedMathBCMath::bcsin((string) (M_PI / 2));
        $this->assertEqualsWithDelta(1.0, (float) $actualSin, 0.00000001);
    }

    public function testBctan()
    {
        $actualTan = AdvancedMathBCMath::bctan((string) (M_PI / 4));
        $this->assertEqualsWithDelta(1.0, (float) $actualTan, 0.0001);
    }

    // ========================================================================
    // Calculus Tests
    // ========================================================================

    public function testImplicitDerivativeNumerical()
    {
        $implicitFunction = function (string $x, string $y): string {
            $num1 = AdvancedMathBCMath::bcpow($x, 2);
            $num2 = AdvancedMathBCMath::bcpow($y, 2);
            return AdvancedMathBCMath::bcsub(AdvancedMathBCMath::bcadd($num1, $num2), '25');
        };
        $dy_dx = AdvancedMathBCMath::implicitDerivativeNumerical($implicitFunction, '3', '4');
        $this->assertEqualsWithDelta(bcdiv(bcmul('-1', '3'), '4', 20), (float) $dy_dx, 1e-3);
    }

    public function testParametricDerivativeNumerical()
    {
        $gx = function (string $t): string {
            return AdvancedMathBCMath::bcmul('2', $t);
        };
        $hy = function (string $t): string {
            return AdvancedMathBCMath::bcpow($t, 2);
        };
        $result = AdvancedMathBCMath::parametricDerivativeNumerical($gx, $hy, '3');
        $this->assertEqualsWithDelta(3.0, (float) $result, 1e-3);
    }

    public function testNumericalDefiniteIntegralBCMath()
    {
        AdvancedMathBCMath::setScale(20);
        $fx = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 2);
        };
        $integral = AdvancedMathBCMath::numericalDefiniteIntegralBCMath($fx, '0', '2', 1000);
        $expected = bcdiv('8', '3', 20);
        $this->assertEqualsWithDelta((float) $expected, (float) $integral, 1e-5);
    }

    public function testNumericalDefiniteIntegralBCMathNegativeStepsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $fx = function (string $x): string {
            return $x;
        };
        AdvancedMathBCMath::numericalDefiniteIntegralBCMath($fx, '0', '1', 0);
    }

    public function testCentralDifferenceDerivative()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 3);
        };
        $derivative = AdvancedMathBCMath::centralDifferenceDerivative($func, '2');
        $this->assertEqualsWithDelta(12.0, (float) $derivative, 1e-4);
    }

    public function testCentralDifferenceDerivativeAtZero()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 2);
        };
        $derivative = AdvancedMathBCMath::centralDifferenceDerivative($func, '0');
        $this->assertEqualsWithDelta(0.0, (float) $derivative, 1e-6);
    }

    public function testArcLengthNumericalBCMath()
    {
        $dfdx = function (string $x): string {
            return '1';
        };
        $length = AdvancedMathBCMath::arcLengthNumericalBCMath($dfdx, '0', '1', 1000);
        $this->assertEqualsWithDelta(sqrt(2), (float) $length, 1e-4);
    }

    public function testTrapezoidalRule()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 2);
        };
        $integral = AdvancedMathBCMath::trapezoidalRule($func, '0', '1', 1000);
        $this->assertEqualsWithDelta(1.0 / 3.0, (float) $integral, 1e-5);
    }

    public function testTrapezoidalRuleInvalidStepsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $func = function (string $x): string {
            return $x;
        };
        AdvancedMathBCMath::trapezoidalRule($func, '0', '1', 0);
    }

    public function testSimpsonRule()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 2);
        };
        $integral = AdvancedMathBCMath::simpsonRule($func, '0', '1', 100);
        $this->assertEqualsWithDelta(1.0 / 3.0, (float) $integral, 1e-10);
    }

    public function testSimpsonRuleOddStepsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $func = function (string $x): string {
            return $x;
        };
        AdvancedMathBCMath::simpsonRule($func, '0', '1', 3);
    }

    public function testVolumeOfRevolutionDiskMethodNumericalBCMath()
    {
        $fx = function (string $x): string {
            return $x;
        };
        $volume = AdvancedMathBCMath::volumeOfRevolutionDiskMethodNumericalBCMath($fx, '0', '3', 1000);
        $expected = bcmul((string) pi(), bcdiv('9', '2', 20), 20);
        $this->assertEqualsWithDelta($expected, $volume, 1e-5);
    }

    // ========================================================================
    // Multivariable Calculus Tests
    // ========================================================================

    public function testMultivariableFunctionValue()
    {
        $func = function (array $vars): string {
            return AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcmul($vars[0], '2'), $vars[1]);
        };
        $result = AdvancedMathBCMath::multivariableFunctionValue($func, ['5', '3']);
        $this->assertEquals('13.00000000000000000000', $result);
    }

    public function testPartialDerivativeNumerical()
    {
        $func = function (array $vars): string {
            return AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcpow($vars[0], '2'), AdvancedMathBCMath::bcpow($vars[1], '2'));
        };
        $dfdx = AdvancedMathBCMath::partialDerivativeNumerical($func, ['3', '4'], 0);
        $dfdy = AdvancedMathBCMath::partialDerivativeNumerical($func, ['3', '4'], 1);
        $this->assertEqualsWithDelta('6', (float) $dfdx, 1e-3);
        $this->assertEqualsWithDelta('8', (float) $dfdy, 1e-3);
    }

    public function testPartialDerivativeNumericalInvalidIndexThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $func = function (array $vars): string {
            return $vars[0];
        };
        AdvancedMathBCMath::partialDerivativeNumerical($func, ['1'], 5);
    }

    public function testGradientNumerical()
    {
        $func = function (array $vars): string {
            return AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcpow($vars[0], 2), AdvancedMathBCMath::bcpow($vars[1], 2));
        };
        $gradient = AdvancedMathBCMath::gradientNumerical($func, ['3', '4']);
        $this->assertCount(2, $gradient);
        $this->assertEqualsWithDelta(6.0, (float) $gradient[0], 1e-3);
        $this->assertEqualsWithDelta(8.0, (float) $gradient[1], 1e-3);
    }

    public function testSecondPartialDerivativeNumerical()
    {
        $func = function (array $vars): string {
            return AdvancedMathBCMath::bcmul(AdvancedMathBCMath::bcpow($vars[0], 2), $vars[1]);
        };
        $d2fdxdy = AdvancedMathBCMath::secondPartialDerivativeNumerical($func, ['3', '4'], 0, 1);
        $this->assertEqualsWithDelta(6.0, (float) $d2fdxdy, 1e-2);
    }

    public function testSecondPartialDerivativeNumericalDiagonal()
    {
        $func = function (array $vars): string {
            return AdvancedMathBCMath::bcpow($vars[0], 3);
        };
        $d2fdx2 = AdvancedMathBCMath::secondPartialDerivativeNumerical($func, ['2', '0'], 0, 0);
        $this->assertEqualsWithDelta(12.0, (float) $d2fdx2, 1e-2);
    }

    public function testDoubleIntegralNumerical()
    {
        $func = function (string $x, string $y): string {
            return AdvancedMathBCMath::bcmul($x, $y);
        };
        $integral = AdvancedMathBCMath::doubleIntegralNumerical($func, '0', '1', '0', '1', 100);
        $this->assertEqualsWithDelta('0.25', (float) $integral, 1e-3);
    }

    public function testDoubleIntegralNumericalInvalidStepsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        $func = function (string $x, string $y): string {
            return '1';
        };
        AdvancedMathBCMath::doubleIntegralNumerical($func, '0', '1', '0', '1', 0);
    }

    public function testTripleIntegralNumerical()
    {
        $func = function (string $x, string $y, string $z): string {
            return $x;
        };
        $integral = AdvancedMathBCMath::tripleIntegralNumerical($func, '0', '1', '0', '1', '0', '1', 50);
        $this->assertEqualsWithDelta('0.5', (float) $integral, 1e-3);
    }

    public function testTripleIntegralNumericalConstant()
    {
        $func = function (string $x, string $y, string $z): string {
            return '1';
        };
        $integral = AdvancedMathBCMath::tripleIntegralNumerical($func, '0', '1', '0', '1', '0', '1', 20);
        $this->assertEqualsWithDelta(1.0, (float) $integral, 1e-3);
    }

    // ========================================================================
    // Surface / Line Integral Tests
    // ========================================================================

    public function testSurfaceIntegralNumericalBCMath()
    {
        $vectorField = function (array $r): array {
            return [$r[0], $r[1], $r[2]];
        };
        $surface = function (string $u, string $v): array {
            return [bcmul('2', (string) cos((float) $u)), bcmul('2', (string) sin((float) $u)), $v];
        };
        $integral = AdvancedMathBCMath::surfaceIntegralNumericalBCMath($vectorField, $surface, '0', (string) pi(), '0', '2', 30, 30);
        $this->assertEqualsWithDelta(8 * M_PI, (float) $integral, 1.0);
    }

    // ========================================================================
    // Taylor Series Tests
    // ========================================================================

    public function testTaylorSeriesNumericalBCMath()
    {
        $funcValue = function (string $x) {
            return exp((float) $x);
        };

        $derivatives = [
            function (string $x) {
                return exp((float) $x);
            },
            function (string $x) {
                return exp((float) $x);
            },
        ];
        $result = AdvancedMathBCMath::taylorSeriesNumericalBCMath('1', '0', $funcValue, $derivatives, 3);
        $this->assertEqualsWithDelta('2.5', (float) $result, 1e-5);
    }

    public function testTaylorSeriesNumericalBCMathSingleTerm()
    {
        $funcValue = function (string $x): string {
            return (string) sin((float) $x);
        };
        $result = AdvancedMathBCMath::taylorSeriesNumericalBCMath('0', '0', $funcValue, [], 1);
        $this->assertEqualsWithDelta(0.0, (float) $result, 1e-5);
    }

    // ========================================================================
    // Vector Tests
    // ========================================================================

    public function testVectorMagnitudeBCMath()
    {
        $magnitude = AdvancedMathBCMath::vectorMagnitudeBCMath(['3', '4', '12']);
        $this->assertEquals('13.00000000000000000000', $magnitude);
    }

    public function testVectorMagnitudeBCMath2D()
    {
        $magnitude = AdvancedMathBCMath::vectorMagnitudeBCMath(['3', '4']);
        $this->assertEqualsWithDelta(5.0, (float) $magnitude, 1e-10);
    }

    public function testVectorDotProductBCMath()
    {
        $dotProduct = AdvancedMathBCMath::vectorDotProductBCMath(['1', '2', '3'], ['4', '5', '6']);
        $this->assertEqualsWithDelta('32.0000000000000000000000000', $dotProduct, 1e-6);
    }

    public function testVectorDotProductBCMathDimensionMismatchThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::vectorDotProductBCMath(['1', '2'], ['1', '2', '3']);
    }

    public function testVectorCrossProduct2DBCMath()
    {
        $crossProduct = AdvancedMathBCMath::vectorCrossProduct2DBCMath(['1', '2'], ['3', '4']);
        $this->assertEquals('-2', (float) $crossProduct);
    }

    public function testVectorCrossProduct2DBCMathDimensionThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::vectorCrossProduct2DBCMath(['1', '2', '3'], ['3', '4']);
    }

    public function testVectorCrossProduct3DBCMath()
    {
        $crossProduct = AdvancedMathBCMath::vectorCrossProduct3DBCMath(['1', '2', '3'], ['4', '5', '6']);
        $this->assertSame(['-3', '6', '-3'], array_map(function ($value) {
            return (string) ((int) $value);
        }, $crossProduct));
    }

    public function testVectorCrossProduct3DBCMathDimensionThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::vectorCrossProduct3DBCMath(['1', '2'], ['3', '4']);
    }

    public function testVectorAddBCMath()
    {
        $result = AdvancedMathBCMath::vectorAddBCMath(['1', '2', '3'], ['4', '5', '6']);
        $this->assertEqualsWithDelta(5.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(7.0, (float) $result[1], 1e-10);
        $this->assertEqualsWithDelta(9.0, (float) $result[2], 1e-10);
    }

    public function testVectorAddBCMathDimensionMismatchThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::vectorAddBCMath(['1', '2'], ['1', '2', '3']);
    }

    public function testVectorNormalizeBCMath()
    {
        $normalized = AdvancedMathBCMath::vectorNormalizeBCMath(['3', '4']);
        $this->assertNotNull($normalized);
        $this->assertEqualsWithDelta(0.6, (float) $normalized[0], 1e-10);
        $this->assertEqualsWithDelta(0.8, (float) $normalized[1], 1e-10);
        $mag = AdvancedMathBCMath::vectorMagnitudeBCMath($normalized);
        $this->assertEqualsWithDelta(1.0, (float) $mag, 1e-10);
    }

    public function testVectorNormalizeBCMathZeroVector()
    {
        $result = AdvancedMathBCMath::vectorNormalizeBCMath(['0', '0', '0']);
        $this->assertNull($result);
    }

    public function testVectorAngleBCMath()
    {
        $angle = AdvancedMathBCMath::vectorAngleBCMath(['1', '0'], ['0', '1']);
        $this->assertNotNull($angle);
        $this->assertEqualsWithDelta(M_PI / 2, (float) $angle, 1e-6);
    }

    public function testVectorAngleBCMathParallel()
    {
        $angle = AdvancedMathBCMath::vectorAngleBCMath(['1', '0'], ['5', '0']);
        $this->assertNotNull($angle);
        $this->assertEqualsWithDelta(0.0, (float) $angle, 1e-6);
    }

    public function testVectorAngleBCMathZeroVector()
    {
        $result = AdvancedMathBCMath::vectorAngleBCMath(['0', '0'], ['1', '0']);
        $this->assertNull($result);
    }

    public function testVectorSubtract()
    {
        $result = AdvancedMathBCMath::vectorSubtract(['5', '3'], ['2', '1']);
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(3.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(2.0, (float) $result[1], 1e-10);
    }

    public function testVectorSubtractDimensionMismatch()
    {
        $result = AdvancedMathBCMath::vectorSubtract(['1', '2'], ['1', '2', '3']);
        $this->assertNull($result);
    }

    public function testVectorScalarMultiply()
    {
        $result = AdvancedMathBCMath::vectorScalarMultiply(['1', '2', '3'], '3');
        $this->assertEqualsWithDelta(3.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(6.0, (float) $result[1], 1e-10);
        $this->assertEqualsWithDelta(9.0, (float) $result[2], 1e-10);
    }

    public function testVectorProjection()
    {
        $proj = AdvancedMathBCMath::vectorProjection(['3', '4'], ['1', '0']);
        $this->assertEqualsWithDelta(3.0, (float) $proj[0], 1e-10);
        $this->assertEqualsWithDelta(0.0, (float) $proj[1], 1e-10);
    }

    // ========================================================================
    // Geometry Tests
    // ========================================================================

    public function testPlanePointDistanceBCMath()
    {
        $distance = AdvancedMathBCMath::planePointDistanceBCMath('2', '3', '-1', '5', '1', '-2', '3');
        $this->assertEqualsWithDelta('0.53452248382484876936', $distance, 1e-15);
    }

    public function testPlanePointDistanceBCMathOnPlane()
    {
        $distance = AdvancedMathBCMath::planePointDistanceBCMath('1', '0', '0', '-1', '1', '0', '0');
        $this->assertEqualsWithDelta(0.0, (float) $distance, 1e-10);
    }

    public function testPlanePlaneDistanceBCMath()
    {
        $distanceParallel = AdvancedMathBCMath::planePlaneDistanceBCMath('2', '3', '-1', '5', '2', '3', '-1', '10');
        $this->assertEqualsWithDelta('1.33630620956212192342', $distanceParallel, 1e-15);

        $distanceNonParallel = AdvancedMathBCMath::planePlaneDistanceBCMath('2', '3', '-1', '5', '3', '4', '-2', '10');
        $this->assertNull($distanceNonParallel);
    }

    public function testLineLineDistanceBCMath()
    {
        $p1 = ['1', '2', '0'];
        $d1 = ['2', '1', '-1'];
        $p2 = ['3', '0', '1'];
        $d2 = ['-1', '2', '1'];
        $distance = AdvancedMathBCMath::lineLineDistanceBCMath($p1, $d1, $p2, $d2);
        $expectedNumerator = abs((float) AdvancedMathBCMath::vectorDotProductBCMath(
            [AdvancedMathBCMath::bcsub($p2[0], $p1[0]), AdvancedMathBCMath::bcsub($p2[1], $p1[1]), AdvancedMathBCMath::bcsub($p2[2], $p1[2])],
            AdvancedMathBCMath::vectorCrossProduct3DBCMath($d1, $d2)
        ));
        $expectedDenominator = AdvancedMathBCMath::vectorMagnitudeBCMath(AdvancedMathBCMath::vectorCrossProduct3DBCMath($d1, $d2));
        $this->assertEqualsWithDelta(bcdiv((string) $expectedNumerator, $expectedDenominator, 20), $distance, 1e-10);

        $distanceParallel = AdvancedMathBCMath::lineLineDistanceBCMath($p1, $d1, ['3', '4', '-1'], ['4', '2', '-2']);
        $this->assertNull($distanceParallel);
    }

    public function testSphereEquationValueBCMath()
    {
        $value = AdvancedMathBCMath::sphereEquationValueBCMath('3', '0', '0', '0', '0', '0', '3');
        $this->assertEquals('0.00000000000000000000', $value);
    }

    public function testSphereEquationValueBCMathOutside()
    {
        $value = AdvancedMathBCMath::sphereEquationValueBCMath('5', '0', '0', '0', '0', '0', '3');
        $this->assertEqualsWithDelta(16.0, (float) $value, 1e-10);
    }

    public function testIsPointOnSphereBCMath()
    {
        $this->assertTrue(AdvancedMathBCMath::isPointOnSphereBCMath('3', '0', '0', '0', '0', '0', '3'));
        $this->assertFalse(AdvancedMathBCMath::isPointOnSphereBCMath('5', '0', '0', '0', '0', '0', '3'));
    }

    public function testEuclideanDistance()
    {
        $distance = AdvancedMathBCMath::euclideanDistance(['0', '0'], ['3', '4']);
        $this->assertEqualsWithDelta(5.0, (float) $distance, 1e-10);
    }

    public function testEuclideanDistanceSamePoint()
    {
        $distance = AdvancedMathBCMath::euclideanDistance(['1', '2', '3'], ['1', '2', '3']);
        $this->assertEqualsWithDelta(0.0, (float) $distance, 1e-10);
    }

    public function testEuclideanDistanceDimensionMismatch()
    {
        $result = AdvancedMathBCMath::euclideanDistance(['1', '2'], ['1', '2', '3']);
        $this->assertNull($result);
    }

    // ========================================================================
    // Matrix Tests
    // ========================================================================

    public function testDeterminant()
    {
        $matrix = [['2', '1'], ['1', '-1']];
        $det = AdvancedMathBCMath::determinant($matrix, 20);
        $this->assertEqualsWithDelta(-3.0, (float) $det, 1e-10);
    }

    public function testDeterminant3x3()
    {
        $matrix = [['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']];
        $det = AdvancedMathBCMath::determinant($matrix, 20);
        $this->assertEqualsWithDelta(0.0, (float) $det, 1e-10);
    }

    public function testDeterminant1x1()
    {
        $this->assertEquals('7', AdvancedMathBCMath::determinant([['7']], 20));
    }

    public function testDeterminantEmpty()
    {
        $this->assertEquals('1', AdvancedMathBCMath::determinant([], 20));
    }

    public function testTransposeMatrix()
    {
        $matrix = [['1', '2', '3'], ['4', '5', '6']];
        $transposed = AdvancedMathBCMath::transposeMatrix($matrix);
        $this->assertEquals([['1', '4'], ['2', '5'], ['3', '6']], $transposed);
    }

    public function testTransposeMatrixEmpty()
    {
        $this->assertEquals([], AdvancedMathBCMath::transposeMatrix([]));
    }

    public function testMatrixMultiply()
    {
        $A = [['1', '2'], ['3', '4']];
        $B = [['5', '6'], ['7', '8']];
        $result = AdvancedMathBCMath::matrixMultiply($A, $B, 20);
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(19.0, (float) $result[0][0], 1e-10);
        $this->assertEqualsWithDelta(22.0, (float) $result[0][1], 1e-10);
        $this->assertEqualsWithDelta(43.0, (float) $result[1][0], 1e-10);
        $this->assertEqualsWithDelta(50.0, (float) $result[1][1], 1e-10);
    }

    public function testMatrixMultiplyIncompatibleDimensions()
    {
        $A = [['1', '2']];
        $B = [['1', '2'], ['3', '4'], ['5', '6']];
        $result = AdvancedMathBCMath::matrixMultiply($A, $B, 20);
        $this->assertNull($result);
    }

    public function testInverseMatrix()
    {
        $matrix = [['4', '7'], ['2', '6']];
        $inverse = AdvancedMathBCMath::inverseMatrix($matrix, 20);
        $this->assertNotNull($inverse);
        $product = AdvancedMathBCMath::matrixMultiply($matrix, $inverse, 20);
        $this->assertEqualsWithDelta(1.0, (float) $product[0][0], 1e-10);
        $this->assertEqualsWithDelta(0.0, (float) $product[0][1], 1e-10);
        $this->assertEqualsWithDelta(0.0, (float) $product[1][0], 1e-10);
        $this->assertEqualsWithDelta(1.0, (float) $product[1][1], 1e-10);
    }

    public function testInverseMatrixSingular()
    {
        $matrix = [['1', '2'], ['2', '4']];
        $this->assertNull(AdvancedMathBCMath::inverseMatrix($matrix, 20));
    }

    public function testCreateIdentityMatrix()
    {
        $identity = AdvancedMathBCMath::createIdentityMatrix(3);
        $this->assertEquals([['1', '0', '0'], ['0', '1', '0'], ['0', '0', '1']], $identity);
    }

    public function testConvertToColumnMatrix()
    {
        $result = AdvancedMathBCMath::convertToColumnMatrix(['1', '2', '3']);
        $this->assertEquals([['1'], ['2'], ['3']], $result);
    }

    public function testMatrixTrace()
    {
        $matrix = [['1', '2', '3'], ['4', '5', '6'], ['7', '8', '9']];
        $trace = AdvancedMathBCMath::matrixTrace($matrix);
        $this->assertEqualsWithDelta(15.0, (float) $trace, 1e-10);
    }

    public function testMatrixTraceNonSquare()
    {
        $this->assertNull(AdvancedMathBCMath::matrixTrace([['1', '2', '3'], ['4', '5', '6']]));
    }

    public function testMatrixTraceEmpty()
    {
        $this->assertEquals('0', AdvancedMathBCMath::matrixTrace([]));
    }

    public function testMatrixFrobeniusNorm()
    {
        $matrix = [['1', '2'], ['3', '4']];
        $norm = AdvancedMathBCMath::matrixFrobeniusNorm($matrix);
        $this->assertEqualsWithDelta(sqrt(30), (float) $norm, 1e-10);
    }

    public function testMatrixScalarMultiply()
    {
        $matrix = [['1', '2'], ['3', '4']];
        $result = AdvancedMathBCMath::matrixScalarMultiply($matrix, '2');
        $this->assertEqualsWithDelta(2.0, (float) $result[0][0], 1e-10);
        $this->assertEqualsWithDelta(4.0, (float) $result[0][1], 1e-10);
        $this->assertEqualsWithDelta(6.0, (float) $result[1][0], 1e-10);
        $this->assertEqualsWithDelta(8.0, (float) $result[1][1], 1e-10);
    }

    public function testMatrixAdd()
    {
        $A = [['1', '2'], ['3', '4']];
        $B = [['5', '6'], ['7', '8']];
        $result = AdvancedMathBCMath::matrixAdd($A, $B);
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(6.0, (float) $result[0][0], 1e-10);
        $this->assertEqualsWithDelta(8.0, (float) $result[0][1], 1e-10);
        $this->assertEqualsWithDelta(10.0, (float) $result[1][0], 1e-10);
        $this->assertEqualsWithDelta(12.0, (float) $result[1][1], 1e-10);
    }

    public function testMatrixAddDimensionMismatch()
    {
        $this->assertNull(AdvancedMathBCMath::matrixAdd([['1', '2']], [['1', '2'], ['3', '4']]));
    }

    public function testMatrixSubtract()
    {
        $A = [['5', '6'], ['7', '8']];
        $B = [['1', '2'], ['3', '4']];
        $result = AdvancedMathBCMath::matrixSubtract($A, $B);
        $this->assertNotNull($result);
        $this->assertEqualsWithDelta(4.0, (float) $result[0][0], 1e-10);
        $this->assertEqualsWithDelta(4.0, (float) $result[0][1], 1e-10);
    }

    public function testLuDecomposition()
    {
        $A = [['2', '1'], ['1', '-1']];
        $lu = AdvancedMathBCMath::luDecomposition($A, 20);
        $this->assertNotNull($lu);
        [$L, $U] = $lu;
        $product = AdvancedMathBCMath::matrixMultiply($L, $U, 20);
        $this->assertEqualsWithDelta(2.0, (float) $product[0][0], 1e-10);
        $this->assertEqualsWithDelta(1.0, (float) $product[0][1], 1e-10);
    }

    // ========================================================================
    // Linear System Solver Tests
    // ========================================================================

    public function testCramersRule()
    {
        $A = [['2', '1'], ['1', '-1']];
        $b = ['3', '0'];
        $expectedSolution = ['1.00000000000000000000', '1.00000000000000000000'];
        $actualSolution = AdvancedMathBCMath::cramersRule($A, $b, 20);
        $this->assertEquals($expectedSolution, $actualSolution);
    }

    public function testCramersRuleSingular()
    {
        $this->assertNull(AdvancedMathBCMath::cramersRule([['1', '2'], ['2', '4']], ['3', '6'], 20));
    }

    public function testCramersRule3x3()
    {
        $A = [['1', '0', '0'], ['0', '1', '0'], ['0', '0', '1']];
        $b = ['5', '7', '9'];
        $solution = AdvancedMathBCMath::cramersRule($A, $b, 20);
        $this->assertNotNull($solution);
        $this->assertEqualsWithDelta(5.0, (float) $solution[0], 1e-10);
        $this->assertEqualsWithDelta(7.0, (float) $solution[1], 1e-10);
        $this->assertEqualsWithDelta(9.0, (float) $solution[2], 1e-10);
    }

    public function testSolveLinearSystemLU()
    {
        $A = [['2', '1'], ['1', '-1']];
        $b = ['3', '0'];
        $expectedSolution = ['1.00000000000000000000', '1.00000000000000000000'];
        $actualSolution = AdvancedMathBCMath::solveLinearSystemLU($A, $b, 20);
        $this->assertEquals($expectedSolution, $actualSolution);
    }

    public function testJacobiIteration()
    {
        $A = [['4', '1'], ['1', '3']];
        $b = ['5', '5'];
        $initialGuess = ['0', '0'];
        $actualSolution = AdvancedMathBCMath::jacobiIteration($A, $b, $initialGuess, 100, '1e-15', 20);
        $this->assertNotNull($actualSolution);
        $this->assertEqualsWithDelta(10.0 / 11.0, (float) $actualSolution[0], 1e-10);
        $this->assertEqualsWithDelta(15.0 / 11.0, (float) $actualSolution[1], 1e-10);
    }

    public function testGaussSeidel()
    {
        $A = [['4', '1'], ['1', '3']];
        $b = ['5', '5'];
        $initialGuess = ['0', '0'];
        $actualSolution = AdvancedMathBCMath::gaussSeidel($A, $b, $initialGuess, 10000, '1e-15');
        $this->assertNotNull($actualSolution);
        $this->assertEqualsWithDelta(10.0 / 11.0, (float) $actualSolution[0], 1e-10);
        $this->assertEqualsWithDelta(15.0 / 11.0, (float) $actualSolution[1], 1e-10);
    }

    public function testGaussSeidelInvalidDimensionsThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::gaussSeidel([['1', '2']], ['3', '4'], ['0', '0']);
    }

    // ========================================================================
    // Root Finding Tests
    // ========================================================================

    public function testNewtonMethod()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcsub(AdvancedMathBCMath::bcpow($x, 2), '4');
        };
        $derivative = function (string $x): string {
            return AdvancedMathBCMath::bcmul('2', $x);
        };
        $root = AdvancedMathBCMath::newtonMethod($func, $derivative, '3');
        $this->assertNotNull($root);
        $this->assertEqualsWithDelta(2.0, (float) $root, 1e-10);
    }

    public function testNewtonMethodNegativeRoot()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcsub(AdvancedMathBCMath::bcpow($x, 2), '9');
        };
        $derivative = function (string $x): string {
            return AdvancedMathBCMath::bcmul('2', $x);
        };
        $root = AdvancedMathBCMath::newtonMethod($func, $derivative, '-5');
        $this->assertNotNull($root);
        $this->assertEqualsWithDelta(-3.0, (float) $root, 1e-10);
    }

    public function testNewtonMethodZeroDerivative()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcpow($x, 3);
        };
        $derivative = function (string $x): string {
            return '0';
        };
        $root = AdvancedMathBCMath::newtonMethod($func, $derivative, '1');
        $this->assertNull($root);
    }

    public function testBisectionMethod()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcsub(AdvancedMathBCMath::bcpow($x, 2), '4');
        };
        $root = AdvancedMathBCMath::bisectionMethod($func, '0', '5');
        $this->assertNotNull($root);
        $this->assertEqualsWithDelta(2.0, (float) $root, 1e-10);
    }

    public function testBisectionMethodSameSign()
    {
        $func = function (string $x): string {
            return AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcpow($x, 2), '1');
        };
        $this->assertNull(AdvancedMathBCMath::bisectionMethod($func, '1', '5'));
    }

    // ========================================================================
    // Interpolation Tests
    // ========================================================================

    public function testNewtonInterpolation()
    {
        $xData = ['1', '2', '3'];
        $yData = ['1', '4', '9'];
        $actualY = AdvancedMathBCMath::newtonInterpolation($xData, $yData, '2.5', 20);
        $this->assertEquals('6.25000000000000000000', $actualY);
    }

    public function testNewtonInterpolationEmpty()
    {
        $this->assertEquals('0', AdvancedMathBCMath::newtonInterpolation([], [], '1', 20));
    }

    public function testLagrangePolynomial()
    {
        $xData = ['1', '2', '3'];
        $yData = ['1', '4', '9'];
        $actualY = AdvancedMathBCMath::lagrangePolynomial($xData, $yData, '2.5', 20);
        $this->assertEquals('6.25000000000000000000', $actualY);
    }

    public function testLagrangePolynomialEmpty()
    {
        $this->assertEquals('0', AdvancedMathBCMath::lagrangePolynomial([], [], '1', 20));
    }

    public function testLagrangePolynomialExactPoint()
    {
        $actualY = AdvancedMathBCMath::lagrangePolynomial(['0', '1', '2'], ['0', '1', '4'], '1', 20);
        $this->assertEqualsWithDelta(1.0, (float) $actualY, 1e-10);
    }

    // ========================================================================
    // Optimization Tests
    // ========================================================================

    public function testGoldenSectionSearch()
    {
        $func = function ($x) {
            return AdvancedMathBCMath::bcadd(AdvancedMathBCMath::bcmul($x, $x, 20), AdvancedMathBCMath::bcmul('-5', $x, 20), 20);
        };
        $actualMin = AdvancedMathBCMath::goldenSectionSearch($func, '0', '10', '1e-10', 100, 20);
        $this->assertEqualsWithDelta(2.5, (float) $actualMin, 1e-6);
    }

    public function testGoldenSectionSearchQuadratic()
    {
        $func = function ($x) {
            $diff = AdvancedMathBCMath::bcsub($x, '3');
            return AdvancedMathBCMath::bcmul($diff, $diff);
        };
        $min = AdvancedMathBCMath::goldenSectionSearch($func, '0', '10', '1e-10', 200, 20);
        $this->assertEqualsWithDelta(3.0, (float) $min, 1e-6);
    }

    // ========================================================================
    // Statistics Tests
    // ========================================================================

    public function testAverage()
    {
        $this->assertEqualsWithDelta(3.0, (float) AdvancedMathBCMath::average(['1', '2', '3', '4', '5']), 1e-10);
    }

    public function testAverageEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::average([]));
    }

    public function testVariance()
    {
        $data = ['2', '4', '4', '4', '5', '5', '7', '9'];
        $variance = AdvancedMathBCMath::variance($data);
        $this->assertNotNull($variance);
        $this->assertEqualsWithDelta(4.5714285714286, (float) $variance, 1e-5);
    }

    public function testVarianceTooFewPoints()
    {
        $this->assertNull(AdvancedMathBCMath::variance(['1']));
    }

    public function testStandardDeviation()
    {
        $data = ['2', '4', '4', '4', '5', '5', '7', '9'];
        $std = AdvancedMathBCMath::standardDeviation($data);
        $this->assertNotNull($std);
        $this->assertEqualsWithDelta(2.1380899352994, (float) $std, 1e-5);
    }

    public function testCovariance()
    {
        $cov = AdvancedMathBCMath::covariance(['1', '2', '3'], ['4', '5', '6']);
        $this->assertNotNull($cov);
        $this->assertEqualsWithDelta(1.0, (float) $cov, 1e-10);
    }

    public function testCovarianceDimensionMismatch()
    {
        $this->assertNull(AdvancedMathBCMath::covariance(['1', '2'], ['1', '2', '3']));
    }

    public function testPearsonCorrelation()
    {
        $corr = AdvancedMathBCMath::pearsonCorrelation(['1', '2', '3', '4'], ['2', '4', '6', '8']);
        $this->assertNotNull($corr);
        $this->assertEqualsWithDelta(1.0, (float) $corr, 1e-10);
    }

    public function testPearsonCorrelationNegative()
    {
        $corr = AdvancedMathBCMath::pearsonCorrelation(['1', '2', '3', '4'], ['8', '6', '4', '2']);
        $this->assertNotNull($corr);
        $this->assertEqualsWithDelta(-1.0, (float) $corr, 1e-10);
    }

    public function testPearsonCorrelationInvalid()
    {
        $this->assertNull(AdvancedMathBCMath::pearsonCorrelation(['1'], ['2']));
    }

    public function testAutoCorrelationFunction()
    {
        $data = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        $acf = AdvancedMathBCMath::autoCorrelationFunction($data, 2);
        $this->assertNotNull($acf);
        $this->assertCount(3, $acf);
        $this->assertEqualsWithDelta(0.9, (float) $acf[0], 1e-10);
    }

    // ========================================================================
    // Entropy / Information Theory Tests
    // ========================================================================

    public function testShannonEntropy()
    {
        $actualEntropy = AdvancedMathBCMath::shannonEntropy(['0.5', '0.5']);
        $this->assertEqualsWithDelta(1.0, (float) $actualEntropy, 1e-10);

        $actualEntropy2 = AdvancedMathBCMath::shannonEntropy(['0.8', '0.2']);
        $this->assertEqualsWithDelta(0.72192809488736, (float) $actualEntropy2, 1e-10);

        $this->assertNull(AdvancedMathBCMath::shannonEntropy(['0.5', '1.1']));
        $this->assertNull(AdvancedMathBCMath::shannonEntropy(['0.5', '-0.1']));
        $this->assertNull(AdvancedMathBCMath::shannonEntropy([]));
    }

    public function testShannonEntropyCertain()
    {
        $entropy = AdvancedMathBCMath::shannonEntropy(['1']);
        $this->assertEqualsWithDelta(0.0, (float) $entropy, 1e-10);
    }

    public function testShannonEntropyUniform()
    {
        $entropy = AdvancedMathBCMath::shannonEntropy(['0.25', '0.25', '0.25', '0.25']);
        $this->assertEqualsWithDelta(2.0, (float) $entropy, 1e-5);
    }

    public function testCrossEntropy()
    {
        $ce = AdvancedMathBCMath::crossEntropy(['0.5', '0.5'], ['0.5', '0.5']);
        $this->assertEqualsWithDelta(1.0, (float) $ce, 1e-10);
    }

    public function testCrossEntropyInvalid()
    {
        $this->assertNull(AdvancedMathBCMath::crossEntropy(['0.5'], ['0.3', '0.7']));
        $this->assertNull(AdvancedMathBCMath::crossEntropy([], []));
    }

    public function testKlDivergence()
    {
        $kl = AdvancedMathBCMath::klDivergence(['0.5', '0.5'], ['0.5', '0.5']);
        $this->assertNotNull($kl);
        $this->assertEqualsWithDelta(0.0, (float) $kl, 1e-10);
    }

    public function testKlDivergenceNonZero()
    {
        $kl = AdvancedMathBCMath::klDivergence(['0.9', '0.1'], ['0.5', '0.5']);
        $this->assertNotNull($kl);
        $this->assertGreaterThan(0.0, (float) $kl);
    }

    public function testKlDivergenceZeroQ()
    {
        $this->assertNull(AdvancedMathBCMath::klDivergence(['0.5', '0.5'], ['1', '0']));
    }

    public function testJointEntropy()
    {
        $je = AdvancedMathBCMath::jointEntropy([['0.25', '0.25'], ['0.25', '0.25']]);
        $this->assertEqualsWithDelta(2.0, (float) $je, 1e-5);
    }

    public function testJointEntropyEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::jointEntropy([]));
    }

    public function testRenyiEntropy()
    {
        $re = AdvancedMathBCMath::renyiEntropy(['0.5', '0.5'], '2');
        $this->assertNotNull($re);
        $this->assertEqualsWithDelta(1.0, (float) $re, 1e-5);
    }

    public function testRenyiEntropyInvalidAlpha()
    {
        $this->assertNull(AdvancedMathBCMath::renyiEntropy(['0.5', '0.5'], '1'));
        $this->assertNull(AdvancedMathBCMath::renyiEntropy(['0.5', '0.5'], '0'));
    }

    public function testCategoricalEntropy()
    {
        $entropy = AdvancedMathBCMath::categoricalEntropy(['a', 'a', 'b', 'b']);
        $this->assertEqualsWithDelta(1.0, (float) $entropy, 1e-5);
    }

    public function testCategoricalEntropyEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::categoricalEntropy([]));
    }

    // ========================================================================
    // Regression Tests
    // ========================================================================

    public function testPolynomialRegression()
    {
        $xData = ['1', '2', '3', '4'];
        $yData = ['1', '4', '9', '16'];
        $coefficients = AdvancedMathBCMath::polynomialRegression($xData, $yData, 2);
        $this->assertNotNull($coefficients);
        $this->assertEqualsWithDelta(1.0, (float) $coefficients[0], 1e-5);
        $this->assertEqualsWithDelta(0.0, (float) $coefficients[1], 1e-5);
        $this->assertEqualsWithDelta(0.0, (float) $coefficients[2], 1e-5);
    }

    public function testPolynomialRegressionInsufficientData()
    {
        $this->assertNull(AdvancedMathBCMath::polynomialRegression(['1'], ['1'], 2));
    }

    public function testGramSchmidt()
    {
        $vectors = [['1', '1'], ['1', '0']];
        $orthonormal = AdvancedMathBCMath::gramSchmidt($vectors);
        $this->assertCount(2, $orthonormal);
        $dot = AdvancedMathBCMath::vectorDotProduct($orthonormal[0], $orthonormal[1]);
        $this->assertEqualsWithDelta(0.0, (float) $dot, 1e-10);
        $mag0 = AdvancedMathBCMath::vectorMagnitude($orthonormal[0]);
        $this->assertEqualsWithDelta(1.0, (float) $mag0, 1e-10);
    }

    public function testGramSchmidtEmpty()
    {
        $this->assertEquals([], AdvancedMathBCMath::gramSchmidt([]));
    }

    // ========================================================================
    // Runge-Kutta Tests
    // ========================================================================

    public function testRungeKutta4()
    {
        $f = function (string $t, string $y): string {
            return $y;
        };
        $result = AdvancedMathBCMath::rungeKutta4('0', '1', $f, '0.1');
        $this->assertEqualsWithDelta(exp(0.1), (float) $result, 1e-5);
    }

    public function testRungeKutta4Vector()
    {
        $f = function (string $t, array $y): array {
            return [$y[1], AdvancedMathBCMath::bcmul('-1', $y[0])];
        };
        $result = AdvancedMathBCMath::rungeKutta4Vector('0', ['1', '0'], $f, '0.1');
        $this->assertCount(2, $result);
        $this->assertEqualsWithDelta(cos(0.1), (float) $result[0], 1e-4);
        $this->assertEqualsWithDelta(-sin(0.1), (float) $result[1], 1e-4);
    }

    // ========================================================================
    // Text / Entropy Analysis Tests
    // ========================================================================

    public function testTextRandomness()
    {
        $randomness = AdvancedMathBCMath::textRandomness('aabb');
        $this->assertNotNull($randomness);
        $this->assertEqualsWithDelta(1.0, (float) $randomness, 1e-5);
    }

    public function testTextRandomnessEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::textRandomness(''));
    }

    public function testTextRandomnessSingleChar()
    {
        $randomness = AdvancedMathBCMath::textRandomness('aaaa');
        $this->assertEqualsWithDelta(0.0, (float) $randomness, 1e-10);
    }

    public function testAnalyzeText()
    {
        $analysis = AdvancedMathBCMath::analyzeText('hello world hello');
        $this->assertNotNull($analysis);
        $this->assertArrayHasKey('randomness', $analysis);
        $this->assertArrayHasKey('wordFrequencies', $analysis);
        $this->assertArrayHasKey('wordEntropy', $analysis);
        $this->assertEquals(2, $analysis['wordFrequencies']['hello']);
        $this->assertEquals(1, $analysis['wordFrequencies']['world']);
    }

    public function testAnalyzeTextEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::analyzeText(''));
    }

    public function testSentimentAnalysis()
    {
        $dictionary = ['good' => '1', 'bad' => '-1', 'great' => '2'];
        $sentiment = AdvancedMathBCMath::sentimentAnalysis('good great', $dictionary);
        $this->assertEqualsWithDelta(1.5, (float) $sentiment, 1e-10);
    }

    public function testSentimentAnalysisNoMatch()
    {
        $sentiment = AdvancedMathBCMath::sentimentAnalysis('hello world', ['good' => '1']);
        $this->assertEquals('0', $sentiment);
    }

    public function testSentimentAnalysisEmpty()
    {
        $this->assertEquals('0', AdvancedMathBCMath::sentimentAnalysis('', ['good' => '1']));
    }

    // ========================================================================
    // Distance / Clustering Tests
    // ========================================================================

    public function testAverageDistanceToCentroid()
    {
        $dataPoints = [['0', '0'], ['2', '0'], ['0', '2'], ['2', '2']];
        $centroid = ['1', '1'];
        $avgDist = AdvancedMathBCMath::averageDistanceToCentroid($dataPoints, $centroid);
        $this->assertNotNull($avgDist);
        $this->assertEqualsWithDelta(sqrt(2), (float) $avgDist, 1e-5);
    }

    public function testAverageDistanceToCentroidEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::averageDistanceToCentroid([], ['0', '0']));
    }

    // ========================================================================
    // PageRank Test
    // ========================================================================

    public function testSimplePageRank()
    {
        $linkGraph = ['A' => ['B', 'C'], 'B' => ['C'], 'C' => ['A']];
        $result = AdvancedMathBCMath::simplePageRank($linkGraph, 100, '0.85');
        $this->assertNotEmpty($result);
        $sum = bcadd(bcadd($result['A'], $result['B'], 10), $result['C'], 10);
        $this->assertEqualsWithDelta(1.0, (float) $sum, 1e-5);
    }

    public function testSimplePageRankEmpty()
    {
        $this->assertEquals([], AdvancedMathBCMath::simplePageRank([]));
    }

    // ========================================================================
    // Miscellaneous Tests
    // ========================================================================

    public function testPortfolioRisk()
    {
        $risk = AdvancedMathBCMath::portfolioRisk(['0.5', '0.5'], ['0.1', '0.2']);
        $this->assertNotNull($risk);
        $this->assertEqualsWithDelta(sqrt(0.0125), (float) $risk, 1e-5);
    }

    public function testPortfolioRiskEmpty()
    {
        $this->assertNull(AdvancedMathBCMath::portfolioRisk([], []));
    }

    public function testRotationMatrix2D()
    {
        $matrix = AdvancedMathBCMath::rotationMatrix2D('0');
        $this->assertEqualsWithDelta(1.0, (float) $matrix[0][0], 1e-5);
        $this->assertEqualsWithDelta(0.0, (float) $matrix[0][1], 1e-5);
        $this->assertEqualsWithDelta(0.0, (float) $matrix[1][0], 1e-5);
        $this->assertEqualsWithDelta(1.0, (float) $matrix[1][1], 1e-5);
    }

    public function testSimpleAnomalyDetection()
    {
        $data = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];
        $this->assertFalse(AdvancedMathBCMath::simpleAnomalyDetection($data, 0.5));
    }

    public function testInformationGain()
    {
        $features = ['sunny', 'sunny', 'overcast', 'rainy', 'rainy'];
        $target = ['no', 'no', 'yes', 'yes', 'yes'];
        $ig = AdvancedMathBCMath::informationGain($features, $target);
        $this->assertNotNull($ig);
        $this->assertArrayHasKey(0, $ig);
    }

    public function testContinuousEntropy()
    {
        $data = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10'];
        $entropy = AdvancedMathBCMath::continuousEntropy($data, 5);
        $this->assertNotNull($entropy);
        $this->assertGreaterThan(0.0, (float) $entropy);
    }

    public function testVectorMagnitude3DBCMath()
    {
        $mag = AdvancedMathBCMath::vectorMagnitude3DBCMath(['3', '4', '0']);
        $this->assertEqualsWithDelta(5.0, (float) $mag, 1e-10);
    }

    public function testVectorMagnitude3DBCMathInvalidDimensionThrows()
    {
        $this->expectException(InvalidArgumentException::class);
        AdvancedMathBCMath::vectorMagnitude3DBCMath(['1', '2']);
    }

    public function testVectorSubtract3DBCMath()
    {
        $result = AdvancedMathBCMath::vectorSubtract3DBCMath(['5', '6', '7'], ['1', '2', '3']);
        $this->assertEqualsWithDelta(4.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(4.0, (float) $result[1], 1e-10);
        $this->assertEqualsWithDelta(4.0, (float) $result[2], 1e-10);
    }

    public function testVectorMultiplyScalar3DBCMath()
    {
        $result = AdvancedMathBCMath::vectorMultiplyScalar3DBCMath(['1', '2', '3'], '3');
        $this->assertEqualsWithDelta(3.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(6.0, (float) $result[1], 1e-10);
        $this->assertEqualsWithDelta(9.0, (float) $result[2], 1e-10);
    }

    public function testVectorDivideScalar3DBCMath()
    {
        $result = AdvancedMathBCMath::vectorDivideScalar3DBCMath(['6', '9', '12'], '3');
        $this->assertEqualsWithDelta(2.0, (float) $result[0], 1e-10);
        $this->assertEqualsWithDelta(3.0, (float) $result[1], 1e-10);
        $this->assertEqualsWithDelta(4.0, (float) $result[2], 1e-10);
    }

    public function testBcneg()
    {
        $this->assertEquals('-5', AdvancedMathBCMath::bcneg('5'));
        $this->assertEquals('5', AdvancedMathBCMath::bcneg('-5'));
    }

    public function testBcfactorial()
    {
        $result = AdvancedMathBCMath::bcfact(5);
        $this->assertEqualsWithDelta(120.0, (float) $result, 1e-10);
    }

    public function testLinePlaneDistanceBCMathParallel()
    {
        $distance = AdvancedMathBCMath::linePlaneDistanceBCMath('0', '0', '0', '1', '0', '0', '0', '0', '1', '-5');
        $this->assertNotNull($distance);
        $this->assertEqualsWithDelta(5.0, (float) $distance, 1e-10);
    }

    public function testLinePlaneDistanceBCMathNotParallel()
    {
        $distance = AdvancedMathBCMath::linePlaneDistanceBCMath('0', '0', '0', '0', '0', '1', '0', '0', '1', '-5');
        $this->assertNull($distance);
    }
}
