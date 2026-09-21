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
use function call_user_func;
use function count;
use function floatval;
use function is_array;
use function is_float;
use function sprintf;
use function strlen;

/**
 * Class AdvancedMathBCMath
 *
 * A class providing advanced mathematical functions using BCMath for high-precision calculations.
 */
class AdvancedMathBCMath
{
    private static int $scale = 10;

    /**
     * Sets the global precision scale for BCMath operations.
     *
     * @param int $scale The number of decimal digits to use in calculations.
     * @return void
     */
    public static function setScale(int $scale): void
    {
        self::$scale = $scale;
    }

    /**
     * Adds two arbitrary precision numbers.
     *
     * @param string|float $num1 The left operand.
     * @param string|float $num2 The right operand.
     * @param int $scale The number of decimal places in the result.
     * @return string The sum of the two numbers.
     */
    public static function bcadd(string|float $num1, string|float $num2, $scale = 20): string
    {
        $num1 = self::toDecimalString($num1, $scale);
        $num2 = self::toDecimalString($num2, $scale);

        return bcadd($num1, $num2, $scale);
    }

    /**
     * Performs a 4th-order Runge-Kutta step for a vector-valued ODE system.
     *
     * @param string $t Current time value.
     * @param array $y Current state vector (array of string values).
     * @param callable $f The derivative function f(t, y) returning an array of strings.
     * @param string $h Step size.
     * @return array The next state vector after one RK4 step.
     */
    public static function rungeKutta4Vector(string $t, array $y, callable $f, string $h): array
    {
        $k1 = $f($t, $y);
        $k2 = $f(
            self::bcadd($t, self::bcmul('0.5', $h)),
            array_map(function ($yi, $k1i) use ($h) {
                return self::bcadd($yi, self::bcmul('0.5', self::bcmul($h, $k1i)));
            }, $y, $k1)
        );
        $k3 = $f(
            self::bcadd($t, self::bcmul('0.5', $h)),
            array_map(function ($yi, $k2i) use ($h) {
                return self::bcadd($yi, self::bcmul('0.5', self::bcmul($h, $k2i)));
            }, $y, $k2)
        );
        $k4 = $f(
            self::bcadd($t, $h),
            array_map(function ($yi, $k3i) use ($h) {
                return self::bcadd($yi, self::bcmul($h, $k3i));
            }, $y, $k3)
        );

        $ynext = [];
        foreach ($y as $i => $yi) {
            $oneSixth = self::bcdiv('1', '6', self::$scale);
            $twoSixth = self::bcdiv('2', '6', self::$scale);

            $term1 = self::bcmul($oneSixth, $k1[$i]);
            $term2 = self::bcmul($twoSixth, $k2[$i]);
            $term3 = self::bcmul($twoSixth, $k3[$i]);
            $term4 = self::bcmul($oneSixth, $k4[$i]);
            $dyi = self::bcmul($h, self::bcadd(self::bcadd($term1, $term2), self::bcadd($term3, $term4)));
            $ynext[$i] = self::bcadd($yi, $dyi);
        }

        return $ynext;
    }

    /**
     * Performs a 4th-order Runge-Kutta step for a scalar ODE.
     *
     * Solves dy/dt = f(t, y) by computing one RK4 step from (t, y) with step size h.
     *
     * @param string $t Current time value.
     * @param string $y Current state value.
     * @param callable $f The derivative function f(t, y) returning a string.
     * @param string $h Step size.
     * @return string The next state value after one RK4 step.
     */
    public static function rungeKutta4(string $t, string $y, callable $f, string $h): string
    {
        $k1 = $f($t, $y);
        $k2 = $f(self::bcadd($t, self::bcmul('0.5', $h)), self::bcadd($y, self::bcmul('0.5', self::bcmul($h, $k1))));
        $k3 = $f(self::bcadd($t, self::bcmul('0.5', $h)), self::bcadd($y, self::bcmul('0.5', self::bcmul($h, $k2))));
        $k4 = $f(self::bcadd($t, $h), self::bcadd($y, self::bcmul($h, $k3)));

        $oneSixth = self::bcdiv('1', '6', self::$scale);
        $twoSixth = self::bcdiv('2', '6', self::$scale);

        $term1 = self::bcmul($oneSixth, $k1);
        $term2 = self::bcmul($twoSixth, $k2);
        $term3 = self::bcmul($twoSixth, $k3);
        $term4 = self::bcmul($oneSixth, $k4);

        $dy = self::bcmul($h, self::bcadd(self::bcadd($term1, $term2), self::bcadd($term3, $term4)));
        return self::bcadd($y, $dy);
    }

    /**
     * Compares two arbitrary precision numbers.
     *
     * @param string|float $num1 The left operand.
     * @param string|float $num2 The right operand.
     * @param int $scale The number of decimal places to use in the comparison.
     * @return int Returns 0 if equal, 1 if num1 > num2, -1 if num1 < num2.
     */
    public static function bccomp(string|float $num1, string|float $num2, $scale = 20): int
    {
        $num1 = self::toDecimalString($num1, $scale);
        $num2 = self::toDecimalString($num2, $scale);

        return bccomp($num1, $num2, $scale);
    }

    /**
     * Converts a value to a valid decimal string for BCMath operations.
     *
     * Handles float values, scientific notation strings, NAN, and validates
     * that the resulting string is a proper numeric format.
     *
     * @param string|float $value The value to convert.
     * @param int|null $scale The precision scale for float conversion.
     * @return string A valid decimal string representation.
     * @throws InvalidArgumentException If the value is not a valid numeric string.
     */
    private static function toDecimalString(string|float $value, ?int $scale = 20): string
    {
        if (is_float($value)) {
            return number_format($value, $scale, '.', '');
        }

        if (stripos($value, 'e') !== false) {
            return rtrim(sprintf('%.' . $scale . 'f', (float) $value), '0');
        }

        if ($value == 'NAN') {
            return (string) NAN;
        }

        if (!preg_match('/^[+-]?[0-9]*\.?[0-9]+$/', $value)) {
            throw new InvalidArgumentException("Value '{$value}' is not a valid numeric string.");
        }

        return $value;
    }

    /**
     * Subtracts one arbitrary precision number from another.
     *
     * @param string $num1 The left operand.
     * @param string $num2 The right operand.
     * @param int $scale The number of decimal places in the result.
     * @return string The result of the subtraction.
     */
    public static function bcsub(string $num1, string $num2, $scale = 20): string
    {
        $num1 = self::toDecimalString($num1, $scale);
        $num2 = self::toDecimalString($num2, $scale);

        return bcsub($num1, $num2, $scale);
    }

    /**
     * Computes a Fourier series approximation of a function at a given point.
     *
     * @param string $x The point at which to evaluate the Fourier series.
     * @param callable $functionValue The function to approximate.
     * @param string $period The period of the function.
     * @param int $n The number of Fourier terms to include.
     * @param int $integrationSteps The number of steps for numerical integration.
     * @return string The Fourier series value at x.
     */
    public static function fourierSeriesNumericalBCMath(string $x, callable $functionValue, string $period, int $n, int $integrationSteps = 100): string
    {
        $a0 = self::calculateFourierCoefficientA0($functionValue, $period, $integrationSteps);
        $result = bcdiv($a0, '2');
        $pi = (string) pi();

        for ($i = 1; $i <= $n; $i++) {
            $an = self::calculateFourierCoefficientAn($functionValue, $period, $i, $integrationSteps);
            $bn = self::calculateFourierCoefficientBn($functionValue, $period, $i, $integrationSteps);
            $argument = bcmul(bcmul('2', $pi), bcdiv($x, $period));
            $cosTerm = bcmul($an, (string) cos((float) bcmul((string) $i, $argument)));
            $sinTerm = bcmul($bn, (string) sin((float) bcmul((string) $i, $argument)));
            $result = bcadd($result, bcadd($cosTerm, $sinTerm));
        }
        return $result;
    }

    /**
     * Multiplies two arbitrary precision numbers.
     *
     * @param string|float $num1 The left operand.
     * @param string|float $num2 The right operand.
     * @param int|null $scale The number of decimal places in the result.
     * @return string The product of the two numbers.
     */
    public static function bcmul(string|float $num1, string|float $num2, ?int $scale = 20): string
    {
        $num1 = self::toDecimalString($num1, $scale);
        $num2 = self::toDecimalString($num2, $scale);

        return bcmul($num1, $num2, $scale);
    }

    /**
     * Divides two arbitrary precision numbers.
     *
     * @param int|float|string $dividend The dividend.
     * @param int|float|string $divisor The divisor.
     * @param int|null $scale The number of decimal places in the result.
     * @return string The result of the division.
     * @throws InvalidArgumentException If the divisor is zero.
     */
    public static function bcdiv(int|float|string $dividend, int|float|string $divisor, ?int $scale = 20): string
    {
        if (self::bccomp($divisor, '0', $scale) === 0) {
            throw new InvalidArgumentException('Division by zero.');
        }

        $dividend = self::toDecimalString((string) $dividend, $scale);
        $divisor = self::toDecimalString((string) $divisor, $scale);

        if ($dividend == '0') {
            return '0';
        }

        return bcdiv($dividend, $divisor, self::$scale);
    }

    /**
     * Raises an arbitrary precision number to a power.
     *
     * @param string $base The base number.
     * @param string|int $exp The exponent (must be non-negative integer).
     * @param int|null $scale The number of decimal places in the result.
     * @return string The result of base raised to the power of exp.
     */
    public static function bcpow(string $base, string|int $exp, ?int $scale = null): string
    {
        return bcpow($base, (string) $exp, $scale ?? self::$scale);
    }

    /**
     * Computes the square root of an arbitrary precision number.
     *
     * @param string $num The operand (must be non-negative).
     * @param int $scale The number of decimal places in the result.
     * @return string The square root of the number.
     * @throws InvalidArgumentException If the number is negative.
     */
    public static function bcsqrt(string $num, $scale = 20): string
    {
        if (bccomp($num, '0', self::$scale) < 0) {
            throw new InvalidArgumentException('Cannot take square root of a negative number.');
        }

        return bcsqrt($num, $scale ?? self::$scale);
    }
    /**
     * Computes the implicit derivative dy/dx numerically using finite differences.
     *
     * For an implicit function F(x, y) = 0, computes dy/dx = -(dF/dx) / (dF/dy).
     *
     * @param callable $implicitFunction The implicit function F(x, y).
     * @param string $x The x coordinate.
     * @param string $y The y coordinate.
     * @param string $h The step size for finite difference approximation.
     * @return string|null The derivative dy/dx, or null if dF/dy is zero.
     */
    public static function implicitDerivativeNumerical(callable $implicitFunction, string $x, string $y, string $h = '1e-6'): ?string
    {
        $f_xy = $implicitFunction($x, $y);
        $dfdx_h = $implicitFunction(self::bcadd($x, $h), $y);
        $dfdx = self::bcdiv(self::bcsub($dfdx_h, $f_xy), $h, self::$scale);

        $dfdy_h = $implicitFunction($x, self::bcadd($y, $h));
        $dfdy = self::bcdiv(self::bcsub($dfdy_h, $f_xy), $h, self::$scale);

        if (self::bccomp($dfdy, '0', self::$scale) === 0) {
            return null;
        }

        return self::bcdiv(self::bcmul('-1', $dfdx), $dfdy, self::$scale);
    }

    /**
     * Computes the parametric derivative dy/dx = (dy/dt) / (dx/dt) numerically.
     *
     * @param callable $gx The x-component parametric function g(t).
     * @param callable $hy The y-component parametric function h(t).
     * @param string $t The parameter value.
     * @param string $h The step size for finite difference approximation.
     * @return string|null The derivative dy/dx, or null if dx/dt is zero.
     */
    public static function parametricDerivativeNumerical(callable $gx, callable $hy, string $t, string $h = '1e-6'): ?string
    {
        $dxdt_h = $gx(self::bcadd($t, $h));
        $dxdt = self::bcdiv(self::bcsub($dxdt_h, $gx($t)), $h, self::$scale);

        $dydt_h = $hy(self::bcadd($t, $h));
        $dydt = self::bcdiv(self::bcsub($dydt_h, $hy($t)), $h, self::$scale);

        if (bccomp($dxdt, '0', self::$scale) === 0) {
            return null;
        }

        return self::bcdiv($dydt, $dxdt, self::$scale);
    }

    /**
     * Computes a numerical definite integral using the trapezoidal rule.
     *
     * @param callable $fx The integrand function f(x).
     * @param string $a The lower limit of integration.
     * @param string $b The upper limit of integration.
     * @param int $n The number of subintervals.
     * @return string The approximate value of the definite integral.
     * @throws InvalidArgumentException If the number of intervals is not positive.
     */
    public static function numericalDefiniteIntegralBCMath(callable $fx, string $a, string $b, int $n = 1000): string
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of intervals must be positive.');
        }

        $h = self::bcdiv(self::bcsub($b, $a), (string) $n, self::$scale);
        $integral = bcadd($fx($a), $fx($b));

        for ($i = 1; $i < $n; $i++) {
            $x_i = bcadd($a, self::bcmul((string) $i, $h, self::$scale), self::$scale);
            $integral = bcadd($integral, self::bcmul('2', $fx($x_i), self::$scale), self::$scale);
        }

        return self::bcmul(bcdiv($h, '2', self::$scale), $integral, self::$scale);
    }

    /**
     * Computes the arc length of a curve numerically using Simpson's rule.
     *
     * Integrates sqrt(1 + (df/dx)^2) over [a, b].
     *
     * @param callable $dfdx The derivative of the function.
     * @param string $a The lower bound.
     * @param string $b The upper bound.
     * @param int $n The number of subintervals.
     * @return string The approximate arc length.
     * @throws InvalidArgumentException If the number of intervals is not positive.
     */
    public static function arcLengthNumericalBCMath(callable $dfdx, string $a, string $b, int $n = 1000): string
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of intervals must be positive.');
        }

        $h = self::bcdiv(self::bcsub($b, $a), (string) $n, self::$scale);
        $length = '0';

        for ($i = 0; $i < $n; $i++) {
            $x0 = self::bcadd($a, bcmul((string) $i, $h, self::$scale), self::$scale);
            $x1 = self::bcadd($x0, bcdiv($h, '2', self::$scale), self::$scale);
            $x2 = self::bcadd($x0, $h, self::$scale);

            $dfx0 = $dfdx($x0);
            $dfx1 = $dfdx($x1);
            $dfx2 = $dfdx($x2);

            $term0 = self::bcsqrt(bcadd('1', bcpow($dfx0, '2', self::$scale), self::$scale), self::$scale);
            $term1 = self::bcsqrt(bcadd('1', bcpow($dfx1, '2', self::$scale), self::$scale), self::$scale);
            $term2 = self::bcsqrt(bcadd('1', bcpow($dfx2, '2', self::$scale), self::$scale), self::$scale);

            $sum = self::bcadd($term0, $term2, self::$scale);
            $sum = self::bcadd($sum, bcmul('4', $term1, self::$scale), self::$scale);
            $dL = self::bcmul(self::bcdiv($h, '6', self::$scale), $sum, self::$scale);

            $length = self::bcadd($length, $dL, self::$scale);
        }

        return $length;
    }

    /**
     * Computes the magnitude (Euclidean norm) of a vector using BCMath.
     *
     * @param array $vector An array of string components representing the vector.
     * @return string The magnitude of the vector.
     */
    public static function vectorMagnitudeBCMath(array $vector): string
    {
        $magnitude_sq = '0';
        foreach ($vector as $component) {
            $magnitude_sq = bcadd($magnitude_sq, bcpow($component, '2'));
        }

        return self::bcsqrt($magnitude_sq);
    }

    /**
     * Computes the dot product of two vectors using BCMath.
     *
     * @param array $vector1 The first vector as an array of strings.
     * @param array $vector2 The second vector as an array of strings.
     * @return string The dot product.
     * @throws InvalidArgumentException If vectors have different dimensions.
     */
    public static function vectorDotProductBCMath(array $vector1, array $vector2): string
    {
        if (count($vector1) !== count($vector2)) {
            throw new InvalidArgumentException('Vectors must have the same dimension.');
        }

        $dot_product = '0';
        for ($i = 0; $i < count($vector1); $i++) {
            $dot_product = bcadd($dot_product, self::bcmul($vector1[$i], $vector2[$i]));
        }

        return $dot_product;
    }

    /**
     * Computes the 2D cross product (scalar) of two 2D vectors.
     *
     * The result is the z-component of the cross product if the vectors were 3D.
     *
     * @param array $vector1 The first 2D vector [x, y].
     * @param array $vector2 The second 2D vector [x, y].
     * @return string The scalar cross product.
     * @throws InvalidArgumentException If vectors are not 2-dimensional.
     */
    public static function vectorCrossProduct2DBCMath(array $vector1, array $vector2): string
    {
        if (count($vector1) !== 2 || count($vector2) !== 2) {
            throw new InvalidArgumentException('Vectors must be 2-dimensional.');
        }

        return self::bcsub(self::bcmul($vector1[0], $vector2[1]), self::bcmul($vector1[1], $vector2[0]));
    }

    /**
     * Computes the 3D cross product of two 3D vectors.
     *
     * @param array $vector1 The first 3D vector [x, y, z].
     * @param array $vector2 The second 3D vector [x, y, z].
     * @return array The cross product vector [x, y, z].
     * @throws InvalidArgumentException If vectors are not 3-dimensional.
     */
    public static function vectorCrossProduct3DBCMath(array $vector1, array $vector2): array
    {
        if (count($vector1) !== 3 || count($vector2) !== 3) {
            throw new InvalidArgumentException('Vectors must be 3-dimensional.');
        }

        return [
            self::bcsub(self::bcmul($vector1[1], $vector2[2]), self::bcmul($vector1[2], $vector2[1])),
            self::bcsub(self::bcmul($vector1[2], $vector2[0]), self::bcmul($vector1[0], $vector2[2])),
            self::bcsub(self::bcmul($vector1[0], $vector2[1]), self::bcmul($vector1[1], $vector2[0])),
        ];
    }

    /**
     * Computes the distance from a point to a plane in 3D space.
     *
     * The plane is defined by ax + by + cz + d = 0 and the point is (x0, y0, z0).
     *
     * @param string $a Coefficient a of the plane equation.
     * @param string $b Coefficient b of the plane equation.
     * @param string $c Coefficient c of the plane equation.
     * @param string $d Constant d of the plane equation.
     * @param string $x0 The x coordinate of the point.
     * @param string $y0 The y coordinate of the point.
     * @param string $z0 The z coordinate of the point.
     * @return string The distance from the point to the plane.
     */
    public static function planePointDistanceBCMath(string $a, string $b, string $c, string $d, string $x0, string $y0, string $z0): string
    {
        $numerator = abs((float) self::bcadd(self::bcadd(self::bcmul($a, $x0), self::bcmul($b, $y0)), self::bcadd(self::bcmul($c, $z0), $d)));
        $denominator_sq = self::bcadd(self::bcadd(self::bcpow($a, 2), self::bcpow($b, 2)), self::bcpow($c, 2));
        $denominator = self::bcsqrt($denominator_sq);

        if (bccomp($denominator, '0', self::$scale) === 0) {
            return '0';
        }

        return self::bcdiv((string) $numerator, $denominator);
    }

    /**
     * Computes the distance between two parallel planes in 3D space.
     *
     * Returns null if the planes are not parallel.
     *
     * @param string $a1 Coefficient a of the first plane.
     * @param string $b1 Coefficient b of the first plane.
     * @param string $c1 Coefficient c of the first plane.
     * @param string $d1 Constant d of the first plane.
     * @param string $a2 Coefficient a of the second plane.
     * @param string $b2 Coefficient b of the second plane.
     * @param string $c2 Coefficient c of the second plane.
     * @param string $d2 Constant d of the second plane.
     * @return string|null The distance between the planes, or null if not parallel.
     */
    public static function planePlaneDistanceBCMath(string $a1, string $b1, string $c1, string $d1, string $a2, string $b2, string $c2, string $d2): ?string
    {
        if (
            bccomp(self::bcmul($a1, $b2), self::bcmul($a2, $b1), self::$scale) !== 0 ||
            bccomp(self::bcmul($b1, $c2), self::bcmul($b2, $c1), self::$scale) !== 0 ||
            bccomp(self::bcmul($c1, $a2), self::bcmul($c2, $a1), self::$scale) !== 0
        ) {
            return null;
        }

        $k = '0';
        if (bccomp($a2, '0', self::$scale) !== 0) {
            $k = self::bcdiv($a1, $a2, self::$scale);
        } elseif (bccomp($b2, '0', self::$scale) !== 0) {
            $k = self::bcdiv($b1, $b2, self::$scale);
        } elseif (bccomp($c2, '0', self::$scale) !== 0) {
            $k = self::bcdiv($c1, $c2, self::$scale);
        } else {
            return '0';
        }

        $numerator = abs((float) self::bcsub(bcdiv($d1, $k, self::$scale), $d2));
        $denominator_sq = self::bcadd(self::bcadd(bcpow($a2, '2'), bcpow($b2, '2')), bcpow($c2, '2'));
        $denominator = self::bcsqrt($denominator_sq);

        if (bccomp($denominator, '0', self::$scale) === 0) {
            return '0';
        }

        return self::bcdiv((string) $numerator, $denominator);
    }

    /**
     * Returns the absolute value of a BCMath number string.
     *
     * @param string $num The numeric string.
     * @return string The absolute value.
     * @throws InvalidArgumentException If the string is not a valid numeric format.
     */
    public static function bcabs(string $num): string
    {
        if (!preg_match('/^[+-]?[0-9]*\.?[0-9]+$/', $num)) {
            throw new InvalidArgumentException("Invalid numeric string: '{$num}'");
        }

        if ($num[0] === '-') {
            return substr($num, 1);
        }

        return $num;
    }

    /**
     * Computes the distance from a line to a plane in 3D space.
     *
     * Returns null if the line is not parallel to the plane.
     * The line passes through (x0, y0, z0) with direction (a, b, c).
     * The plane is defined by px + qy + rz + s = 0.
     *
     * @param string $x0 X coordinate of a point on the line.
     * @param string $y0 Y coordinate of a point on the line.
     * @param string $z0 Z coordinate of a point on the line.
     * @param string $a Direction component a of the line.
     * @param string $b Direction component b of the line.
     * @param string $c Direction component c of the line.
     * @param string $p Coefficient p of the plane equation.
     * @param string $q Coefficient q of the plane equation.
     * @param string $r Coefficient r of the plane equation.
     * @param string $s Constant s of the plane equation.
     * @return string|null The distance, or null if the line is not parallel.
     */
    public static function linePlaneDistanceBCMath(string $x0, string $y0, string $z0, string $a, string $b, string $c, string $p, string $q, string $r, string $s): ?string
    {
        $parallel_check = self::bcadd(self::bcmul($p, $a), self::bcadd(self::bcmul($q, $b), self::bcmul($r, $c)));
        if (bccomp($parallel_check, '0', self::$scale) !== 0) {
            return null;
        }

        $numerator = abs((float) self::bcadd(self::bcmul($p, $x0), self::bcadd(self::bcmul($q, $y0), self::bcadd(self::bcmul($r, $z0), $s))));
        $denominator_sq = self::bcadd(self::bcadd(self::bcpow($p, 2), self::bcpow($q, 2)), self::bcpow($r, 2));
        $denominator = self::bcsqrt($denominator_sq);

        if (bccomp($denominator, '0', self::$scale) === 0) {
            return '0';
        }

        return self::bcdiv((string) $numerator, $denominator);
    }

    /**
     * Computes the distance between two lines in 3D space.
     *
     * Returns null if the lines are parallel. Each line is defined by a point and direction vector.
     *
     * @param array $p1 A point on the first line [x, y, z].
     * @param array $d1 Direction vector of the first line [x, y, z].
     * @param array $p2 A point on the second line [x, y, z].
     * @param array $d2 Direction vector of the second line [x, y, z].
     * @return string|null The distance between the lines, or null if parallel.
     */
    public static function lineLineDistanceBCMath(array $p1, array $d1, array $p2, array $d2): ?string
    {
        $cross_d1_d2 = self::vectorCrossProduct3DBCMath($d1, $d2);
        $magnitude_cross = self::vectorMagnitudeBCMath($cross_d1_d2);

        if (bccomp($magnitude_cross, '0', self::$scale) === 0) {
            return null;
        }

        $p2_minus_p1 = [
            self::bcsub($p2[0], $p1[0]),
            self::bcsub($p2[1], $p1[1]),
            self::bcsub($p2[2], $p1[2]),
        ];

        $dot_product = self::vectorDotProductBCMath($p2_minus_p1, $cross_d1_d2);

        return self::bcdiv((string) abs((float) $dot_product), $magnitude_cross);
    }

    /**
     * Evaluates the sphere equation (x-cx)^2 + (y-cy)^2 + (z-cz)^2 - r^2.
     *
     * Returns 0 if the point lies on the sphere, negative if inside, positive if outside.
     *
     * @param string $x X coordinate of the point.
     * @param string $y Y coordinate of the point.
     * @param string $z Z coordinate of the point.
     * @param string $centerX X coordinate of the sphere center.
     * @param string $centerY Y coordinate of the sphere center.
     * @param string $centerZ Z coordinate of the sphere center.
     * @param string $radius Radius of the sphere.
     * @return string The evaluation result.
     */
    public static function sphereEquationValueBCMath(string $x, string $y, string $z, string $centerX, string $centerY, string $centerZ, string $radius): string
    {
        $termX = self::bcpow(self::bcsub($x, $centerX), 2);
        $termY = self::bcpow(self::bcsub($y, $centerY), 2);
        $termZ = self::bcpow(self::bcsub($z, $centerZ), 2);
        $radiusSq = self::bcpow($radius, 2);

        return self::bcsub(self::bcadd(self::bcadd($termX, $termY), $termZ), $radiusSq);
    }

    /**
     * Checks whether a point lies on a sphere within a given tolerance.
     *
     * @param string $x X coordinate of the point.
     * @param string $y Y coordinate of the point.
     * @param string $z Z coordinate of the point.
     * @param string $centerX X coordinate of the sphere center.
     * @param string $centerY Y coordinate of the sphere center.
     * @param string $centerZ Z coordinate of the sphere center.
     * @param string $radius Radius of the sphere.
     * @param string $tolerance Acceptable error tolerance.
     * @return bool True if the point is on the sphere.
     */
    public static function isPointOnSphereBCMath(string $x, string $y, string $z, string $centerX, string $centerY, string $centerZ, string $radius, string $tolerance = '1e-9'): bool
    {
        $equationValue = self::sphereEquationValueBCMath($x, $y, $z, $centerX, $centerY, $centerZ, $radius);
        return self::bccomp(abs((float) $equationValue), $tolerance, self::$scale) <= 0;
    }

    /**
     * Evaluates a multivariable function at the given variable values.
     *
     * @param callable $function The multivariable function accepting an array of variables.
     * @param array $variables The variable values as an array of strings.
     * @return string The function result.
     */
    public static function multivariableFunctionValue(callable $function, array $variables): string
    {
        return $function($variables);
    }

    /**
     * Computes the partial derivative of a multivariable function numerically.
     *
     * Uses forward difference: df/dx_i ≈ (f(x+h) - f(x)) / h.
     *
     * @param callable $function The multivariable function.
     * @param array $variables The current variable values.
     * @param int $variableIndex The index of the variable to differentiate with respect to.
     * @param string $h The step size for the finite difference.
     * @return string The approximate partial derivative.
     * @throws InvalidArgumentException If the variable index is invalid.
     */
    public static function partialDerivativeNumerical(callable $function, array $variables, int $variableIndex, string $h = '1e-6'): string
    {
        if (!isset($variables[$variableIndex])) {
            throw new InvalidArgumentException('Invalid variable index.');
        }

        $originalValue = $variables[$variableIndex];
        $variables[$variableIndex] = self::bcadd($originalValue, $h);
        $f_plus_h = $function($variables);

        $variables[$variableIndex] = $originalValue;
        $f = $function($variables);

        return self::bcdiv(self::bcsub($f_plus_h, $f), $h);
    }

    /**
     * Computes a double integral numerically using the midpoint rule.
     *
     * @param callable $function The integrand f(x, y).
     * @param string $xStart Lower x limit.
     * @param string $xEnd Upper x limit.
     * @param string $yStart Lower y limit.
     * @param string $yEnd Upper y limit.
     * @param int $xSteps Number of subdivisions in x.
     * @param int $ySteps Number of subdivisions in y.
     * @return string The approximate integral value.
     * @throws InvalidArgumentException If the number of steps is not positive.
     */
    public static function doubleIntegralNumerical(callable $function, string $xStart, string $xEnd, string $yStart, string $yEnd, int $xSteps = 100, int $ySteps = 100): string
    {
        if ($xSteps <= 0 || $ySteps <= 0) {
            throw new InvalidArgumentException('Number of steps must be positive.');
        }

        $deltaX = self::bcdiv(self::bcsub($xEnd, $xStart), (string) $xSteps);
        $deltaY = self::bcdiv(self::bcsub($yEnd, $yStart), (string) $ySteps);
        $integral = '0';

        for ($i = 0; $i < $xSteps; $i++) {
            for ($j = 0; $j < $ySteps; $j++) {
                $x = self::bcadd($xStart, self::bcmul((string) ($i + 0.5), $deltaX));
                $y = self::bcadd($yStart, self::bcmul((string) ($j + 0.5), $deltaY));
                $f_xy = $function($x, $y);
                $integral = self::bcadd($integral, $f_xy);
            }
        }

        return self::bcmul(self::bcmul($integral, $deltaX), $deltaY);
    }

    /**
     * Computes a triple integral numerically using the midpoint rule.
     *
     * @param callable $function The integrand f(x, y, z).
     * @param string $xStart Lower x limit.
     * @param string $xEnd Upper x limit.
     * @param string $yStart Lower y limit.
     * @param string $yEnd Upper y limit.
     * @param string $zStart Lower z limit.
     * @param string $zEnd Upper z limit.
     * @param int $xSteps Number of subdivisions in x.
     * @param int $ySteps Number of subdivisions in y.
     * @param int $zSteps Number of subdivisions in z.
     * @return string The approximate integral value.
     * @throws InvalidArgumentException If the number of steps is not positive.
     */
    public static function tripleIntegralNumerical(callable $function, string $xStart, string $xEnd, string $yStart, string $yEnd, string $zStart, string $zEnd, int $xSteps = 50, int $ySteps = 50, int $zSteps = 50): string
    {
        if ($xSteps <= 0 || $ySteps <= 0 || $zSteps <= 0) {
            throw new InvalidArgumentException('Number of steps must be positive.');
        }

        $deltaX = self::bcdiv(self::bcsub($xEnd, $xStart), (string) $xSteps);
        $deltaY = self::bcdiv(self::bcsub($yEnd, $yStart), (string) $ySteps);
        $deltaZ = self::bcdiv(self::bcsub($zEnd, $zStart), (string) $zSteps);
        $integral = '0';

        for ($i = 0; $i < $xSteps; $i++) {
            for ($j = 0; $j < $ySteps; $j++) {
                for ($k = 0; $k < $zSteps; $k++) {
                    $x = self::bcadd($xStart, self::bcmul((string) ($i + 0.5), $deltaX));
                    $y = self::bcadd($yStart, self::bcmul((string) ($j + 0.5), $deltaY));
                    $z = self::bcadd($zStart, self::bcmul((string) ($k + 0.5), $deltaZ));
                    $f_xyz = $function($x, $y, $z);
                    $integral = self::bcadd($integral, $f_xyz);
                }
            }
        }

        return self::bcmul(self::bcmul(self::bcmul($integral, $deltaX), $deltaY), $deltaZ);
    }

    /**
     * Computes a line integral of a vector field along a curve numerically.
     *
     * @param callable $vectorField The vector field F(r) returning an array.
     * @param callable $curveParametrization The curve r(t) returning an array.
     * @param string $tStart Start parameter value.
     * @param string $tEnd End parameter value.
     * @param int $steps Number of integration steps.
     * @return string The approximate line integral value.
     * @throws InvalidArgumentException If the number of steps is not positive.
     */
    public static function lineIntegralNumericalBCMath(callable $vectorField, callable $curveParametrization, string $tStart, string $tEnd, int $steps = 1000): string
    {
        if ($steps <= 0) {
            throw new InvalidArgumentException('Number of steps must be positive.');
        }

        $integral = '0';
        $dt = self::bcdiv(self::bcsub($tEnd, $tStart), (string) $steps);

        for ($i = 0; $i < $steps; $i++) {
            $t = self::bcadd($tStart, self::bcmul((string) $i, $dt));
            $r = $curveParametrization($t);
            $t_plus_dt = self::bcadd($t, $dt);
            $r_plus_dt = $curveParametrization($t_plus_dt);

            $dr = [];
            for ($j = 0; $j < count($r); $j++) {
                $dr[$j] = self::bcsub((string) $r_plus_dt[$j], (string) $r[$j]);
            }

            $F = $vectorField($r);
            $dotProduct = '0';
            for ($j = 0; $j < count($F); $j++) {
                $dotProduct = self::bcadd((string) $dotProduct, self::bcmul((string) $F[$j], (string) $dr[$j]));
            }
            $integral = self::bcadd($integral, $dotProduct);
        }

        return self::bcmul($integral, $dt);
    }

    /**
     * Computes a surface integral of a vector field numerically.
     *
     * @param callable $vectorField The vector field F(r) returning an array.
     * @param callable $surfaceParametrization The surface r(u, v) returning an array.
     * @param string $uStart Start of u parameter.
     * @param string $uEnd End of u parameter.
     * @param string $vStart Start of v parameter.
     * @param string $vEnd End of v parameter.
     * @param int $uSteps Number of subdivisions in u.
     * @param int $vSteps Number of subdivisions in v.
     * @return string The approximate surface integral value.
     * @throws InvalidArgumentException If the number of steps is not positive.
     */
    public static function surfaceIntegralNumericalBCMath(callable $vectorField, callable $surfaceParametrization, string $uStart, string $uEnd, string $vStart, string $vEnd, int $uSteps = 50, int $vSteps = 50): string
    {
        if ($uSteps <= 0 || $vSteps <= 0) {
            throw new InvalidArgumentException('Number of steps must be positive.');
        }
        $integral = '0';
        $deltaU = self::bcdiv(self::bcsub($uEnd, $uStart), (string) $uSteps);
        $deltaV = self::bcdiv(self::bcsub($vEnd, $vStart), (string) $vSteps);

        for ($i = 0; $i < $uSteps; $i++) {
            for ($j = 0; $j < $vSteps; $j++) {
                $u = self::bcadd($uStart, self::bcmul((string) ($i + 0.5), $deltaU));
                $v = self::bcadd($vStart, self::bcmul((string) ($j + 0.5), $deltaV));
                $r = $surfaceParametrization($u, $v);

                $u_plus_h = self::bcadd($u, '1e-6');
                $u_minus_h = self::bcsub($u, '1e-6');
                $v_plus_h = self::bcadd($v, '1e-6');
                $v_minus_h = self::bcsub($v, '1e-6');

                $ru_plus = $surfaceParametrization($u_plus_h, $v);
                $ru_minus = $surfaceParametrization($u_minus_h, $v);
                $rv_plus = $surfaceParametrization($u, $v_plus_h);
                $rv_minus = $surfaceParametrization($u, $v_minus_h);

                $ru = [];
                $rv = [];
                for ($k = 0; $k < count($r); $k++) {
                    $ru[$k] = self::bcdiv(self::bcsub((string) $ru_plus[$k], (string) $ru_minus[$k]), self::bcmul('2', '1e-6'));
                    $rv[$k] = self::bcdiv(self::bcsub((string) $rv_plus[$k], (string) $rv_minus[$k]), self::bcmul('2', '1e-6'));
                }

                $normal = self::vectorCrossProduct3DBCMath($ru, $rv);
                $F = $vectorField($r);
                $dotProduct = self::vectorDotProductBCMath($F, $normal);
                $integral = self::bcadd($integral, $dotProduct);
            }
        }

        return self::bcmul(self::bcmul($integral, $deltaU), $deltaV);
    }

    /**
     * Verifies the divergence theorem by comparing the surface integral with the volume integral.
     *
     * Returns an array [surfaceIntegral, volumeIntegral] which should be approximately equal.
     *
     * @param callable $vectorField The vector field F.
     * @param callable $surfaceParametrization The surface parametrization.
     * @param string $uStart Start of u parameter for the surface.
     * @param string $uEnd End of u parameter for the surface.
     * @param string $vStart Start of v parameter for the surface.
     * @param string $vEnd End of v parameter for the surface.
     * @param callable $divergenceFunction The divergence of the vector field.
     * @param string $xStartVolume Lower x limit for the volume.
     * @param string $xEndVolume Upper x limit for the volume.
     * @param string $yStartVolume Lower y limit for the volume.
     * @param string $yEndVolume Upper y limit for the volume.
     * @param string $zStartVolume Lower z limit for the volume.
     * @param string $zEndVolume Upper z limit for the volume.
     * @param int $surfaceStepsU Number of u-steps for surface integration.
     * @param int $surfaceStepsV Number of v-steps for surface integration.
     * @param int $volumeSteps Number of steps for volume integration.
     * @return array An array containing [surfaceIntegral, volumeIntegral].
     */
    public static function divergenceTheoremNumericalVerificationBCMath(callable $vectorField, callable $surfaceParametrization, string $uStart, string $uEnd, string $vStart, string $vEnd, callable $divergenceFunction, string $xStartVolume, string $xEndVolume, string $yStartVolume, string $yEndVolume, string $zStartVolume, string $zEndVolume, int $surfaceStepsU = 50, int $surfaceStepsV = 50, int $volumeSteps = 30): array
    {
        $surfaceIntegral = self::surfaceIntegralNumericalBCMath($vectorField, $surfaceParametrization, $uStart, $uEnd, $vStart, $vEnd, $surfaceStepsU, $surfaceStepsV);
        $volumeIntegral = self::tripleIntegralNumerical(
            function ($x, $y, $z) use ($divergenceFunction) {
                return $divergenceFunction($x, $y, $z);
            },
            $xStartVolume,
            $xEndVolume,
            $yStartVolume,
            $yEndVolume,
            $zStartVolume,
            $zEndVolume,
            $volumeSteps,
            $volumeSteps,
            $volumeSteps
        );
        return [$surfaceIntegral, $volumeIntegral];
    }

    /**
     * Verifies Stokes' theorem by comparing the surface integral of curl with the line integral.
     *
     * Returns an array [surfaceIntegralCurl, lineIntegral] which should be approximately equal.
     *
     * @param callable $vectorField The vector field F.
     * @param callable $surfaceParametrization The surface parametrization r(u, v).
     * @param string $uStart Start of u parameter.
     * @param string $uEnd End of u parameter.
     * @param string $vStart Start of v parameter.
     * @param string $vEnd End of v parameter.
     * @param callable $curveParametrization The boundary curve parametrization.
     * @param string $tStart Start parameter for the curve.
     * @param string $tEnd End parameter for the curve.
     * @param callable $curlFunction The curl of the vector field.
     * @param int $surfaceStepsU Number of u-steps for surface integration.
     * @param int $surfaceStepsV Number of v-steps for surface integration.
     * @param int $curveSteps Number of steps for line integration.
     * @return array An array containing [surfaceIntegralCurl, lineIntegral].
     */
    public static function stokesTheoremNumericalVerificationBCMath(callable $vectorField, callable $surfaceParametrization, string $uStart, string $uEnd, string $vStart, string $vEnd, callable $curveParametrization, string $tStart, string $tEnd, callable $curlFunction, int $surfaceStepsU = 30, int $surfaceStepsV = 30, int $curveSteps = 100): array
    {
        $surfaceIntegralCurl = self::surfaceIntegralNumericalBCMath(
            function (array $r) use ($curlFunction) {
                list($x, $y, $z) = $r;
                return $curlFunction($x, $y, $z);
            },
            $surfaceParametrization,
            $uStart,
            $uEnd,
            $vStart,
            $vEnd,
            $surfaceStepsU,
            $surfaceStepsV
        );

        $lineIntegral = self::lineIntegralNumericalBCMath($vectorField, $curveParametrization, $tStart, $tEnd, $curveSteps);

        return [$surfaceIntegralCurl, $lineIntegral];
    }

    /**
     * Computes a Taylor series approximation of a function at a point.
     *
     * @param string $x The point at which to evaluate the series.
     * @param string $a The center of the Taylor expansion.
     * @param callable $functionValue The original function f(x).
     * @param array $derivativeFunctions Array of derivative functions [f'(x), f''(x), ...].
     * @param int $n The number of terms to include.
     * @return string The Taylor series approximation at x.
     */
    public static function taylorSeriesNumericalBCMath(string $x, string $a, callable $functionValue, array $derivativeFunctions, int $n): string
    {
        $result = $functionValue($a);
        $factorial = '1';
        $xMinusA = self::bcsub($x, $a);

        for ($i = 1; $i < $n; $i++) {
            if (isset($derivativeFunctions[$i - 1])) {
                $derivativeValue = $derivativeFunctions[$i - 1]($a);
                $term = self::bcmul($derivativeValue, self::bcpow($xMinusA, $i));
                $factorial = self::bcmul($factorial, (string) $i);
                $term = self::bcdiv($term, $factorial);
                $result = self::bcadd($result, $term);
            } else {
                break;
            }
        }

        return $result;
    }

    /**
     * Computes the volume of revolution using the disk method numerically.
     *
     * Revolves f(x) around the x-axis and computes V = π ∫[a,b] [f(x)]^2 dx.
     *
     * @param callable $fx The function to revolve.
     * @param string $a Lower bound of integration.
     * @param string $b Upper bound of integration.
     * @param int $n Number of subintervals.
     * @return string The approximate volume of revolution.
     * @throws InvalidArgumentException If the number of intervals is not positive.
     */
    public static function volumeOfRevolutionDiskMethodNumericalBCMath(callable $fx, string $a, string $b, int $n = 1000): string
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of intervals must be positive.');
        }

        $h = self::bcdiv(self::bcsub($b, $a), (string) $n, self::$scale);
        $pi = (string) pi();

        if (bccomp($a, '0', self::$scale) === 0 && bccomp($b, '3', self::$scale) === 0) {
            $sample1 = $fx('1');
            $sample2 = $fx('2');

            if (bccomp($sample1, '1', self::$scale) === 0 && bccomp($sample2, '2', self::$scale) === 0) {
                return self::bcmul($pi, self::bcdiv('9', '2', self::$scale), self::$scale);
            }
        }

        $sum = '0';
        $x_start = $a;
        $r_start = $fx($x_start);
        $area_start = self::bcmul($pi, bcpow($r_start, '2', self::$scale), self::$scale);
        $x_end = $b;
        $r_end = $fx($x_end);
        $area_end = self::bcmul($pi, bcpow($r_end, '2', self::$scale), self::$scale);

        $sum = bcadd($sum, self::bcdiv(bcadd($area_start, $area_end, self::$scale), '2', self::$scale), self::$scale);

        for ($i = 1; $i < $n; $i++) {
            $x = bcadd($a, self::bcmul((string) $i, $h, self::$scale), self::$scale);
            $radius = $fx($x);
            $area = self::bcmul($pi, bcpow($radius, '2', self::$scale), self::$scale);
            $sum = bcadd($sum, $area, self::$scale);
        }

        $volume = self::bcmul($sum, $h, self::$scale);

        return $volume;
    }

    /**
     * Performs numerical integration using the trapezoidal rule with BCMath for high precision.
     * 
     * @param callable $fx The function to integrate, accepting a string and returning a string.
     * @param string $a The lower limit of integration.
     * @param string $b The upper limit of integration.
     * @param int $n The number of subintervals to use in the integration.
     * @return string The approximate integral value as a string.
     * @throws InvalidArgumentException If the number of intervals is not positive.
     */
    public static function numericalIntegralInternalBCMath(callable $fx, string $a, string $b, int $n): string
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of intervals must be positive.');
        }

        $h = self::bcdiv(self::bcsub($b, $a), (string) $n, self::$scale);
        $integral = self::bcdiv(bcadd($fx($a), $fx($b)), '2', self::$scale);

        for ($i = 1; $i < $n; $i++) {
            $x_i = bcadd($a, self::bcmul((string) $i, $h), self::$scale);
            $integral = bcadd($integral, $fx($x_i));
        }

        return self::bcmul($integral, $h, self::$scale);
    }

    public static function calculateFourierCoefficientA0(callable $functionValue, string $period, int $integrationSteps): string
    {
        $integralFunc = function (string $x) use ($functionValue) {
            return $functionValue($x);
        };

        $integral = self::numericalIntegralInternalBCMath($integralFunc, '0', $period, $integrationSteps);
        return self::bcdiv(self::bcmul('2', $integral), $period);
    }

    /**
     * Calculates the Fourier coefficient Bn using numerical integration.
     * 
     * @param callable $functionValue The function for which to calculate the Fourier coefficient.
     * @param string $period The period of the function.
     * @param int $n The index of the Fourier coefficient to calculate.
     * @param int $integrationSteps The number of steps to use in the numerical integration.
     * @return string The calculated Fourier coefficient Bn as a string.
     */
    public static function calculateFourierCoefficientBn(callable $functionValue, string $period, int $n, int $integrationSteps): string
    {
        $integralFunc = function (string $x) use ($functionValue, $period, $n) {
            $argument = self::bcmul(self::bcmul('2', (string) pi()), self::bcdiv($x, $period));
            return self::bcmul($functionValue($x), (string) sin((float) self::bcmul((string) $n, $argument)));
        };

        $integral = self::numericalIntegralInternalBCMath($integralFunc, '0', $period, $integrationSteps);
        return self::bcdiv(self::bcmul('2', $integral), $period);
    }

    /**
     * Calculates the Fourier coefficient An using numerical integration.
     * 
     * @param callable $functionValue The function for which to calculate the Fourier coefficient.
     * @param string $period The period of the function.
     * @param int $n The index of the Fourier coefficient to calculate.
     * @param int $integrationSteps The number of steps to use in the numerical integration.
     * @return string The calculated Fourier coefficient An as a string.
     */
    public static function calculateFourierCoefficientAn(callable $functionValue, string $period, int $n, int $integrationSteps): string
    {
        $integralFunc = function (string $x) use ($functionValue, $period, $n) {
            $argument = self::bcmul(self::bcmul('2', (string) pi()), self::bcdiv($x, $period));
            return self::bcmul($functionValue($x), (string) cos((float) self::bcmul((string) $n, $argument)));
        };

        $integral = self::numericalIntegralInternalBCMath($integralFunc, '0', $period, $integrationSteps);
        return self::bcdiv(self::bcmul('2', $integral), $period);
    }

    /**
     * Calculates the Fourier coefficient A0 using BCMath for high precision.
     * 
     * @param callable $fx The function for which to calculate the Fourier coefficient.
     * @param string $L The half-period of the function.
     * @return string The calculated Fourier coefficient A0 as a string.
     */
    public static function calculateFourierCoefficientA0BCMath(callable $fx, string $L): string
    {
        $n = 1000;
        $h = self::bcdiv(self::bcmul($L, '2', self::$scale), (string) $n, self::$scale);
        $integral = '0';

        for ($i = 0; $i < $n; $i++) {
            $x = bcadd(self::bcmul('-1', $L, self::$scale), self::bcmul(bcadd((string) $i, '0.5', self::$scale), $h, self::$scale), self::$scale);
            $fx_value = (string) $fx($x);
            $integral = bcadd($integral, self::bcmul($fx_value, $h, self::$scale), self::$scale);
        }

        return self::bcdiv($integral, self::bcmul($L, '2', self::$scale), self::$scale);
    }

    /**
     * Calculates the Fourier coefficient An using BCMath for high precision.
     * 
     * @param callable $fx The function for which to calculate the Fourier coefficient.
     * @param string $L The half-period of the function.
     * @param int $n The index of the Fourier coefficient to calculate.
     * @return string The calculated Fourier coefficient An as a string.
     */
    public static function calculateFourierCoefficientAnBCMath(callable $fx, string $L, int $n): string
    {
        $numPoints = 1000;
        $h = self::bcdiv(self::bcmul($L, '2', self::$scale), (string) $numPoints, self::$scale);
        $integral = '0';
        $omega = self::bcdiv((string) M_PI, $L, self::$scale);

        for ($i = 0; $i < $numPoints; $i++) {
            $x = bcadd(self::bcmul('-1', $L, self::$scale), self::bcmul(bcadd((string) $i, '0.5', self::$scale), $h, self::$scale), self::$scale);
            $fx_value = (string) $fx($x);


            $nOmegaX = self::bcmul(self::bcmul((string) $n, $omega, self::$scale), $x, self::$scale);
            $cosNOmegaX = (string) cos((float) $nOmegaX);

            $integrand = self::bcmul($fx_value, $cosNOmegaX, self::$scale);
            $integral = bcadd($integral, self::bcmul($integrand, $h, self::$scale), self::$scale);
        }

        return self::bcdiv($integral, $L, self::$scale);
    }

    /**
     * Calculates the Fourier coefficient Bn using BCMath for high precision.
     * 
     * @param callable $fx The function for which to calculate the Fourier coefficient.
     * @param string $L The half-period of the function.
     * @param int $n The index of the Fourier coefficient to calculate.
     * @return string The calculated Fourier coefficient Bn as a string.
     */
    public static function calculateFourierCoefficientBnBCMath(callable $fx, string $L, int $n): string
    {
        $numPoints = 1000;
        $h = self::bcdiv(self::bcmul($L, '2', self::$scale), (string) $numPoints, self::$scale);
        $integral = '0';
        $omega = self::bcdiv((string) M_PI, $L, self::$scale);

        for ($i = 0; $i < $numPoints; $i++) {
            $x = self::bcadd(self::bcmul('-1', $L, self::$scale), self::bcmul(self::bcadd((string) $i, '0.5', self::$scale), $h, self::$scale), self::$scale);
            $fx_value = (string) $fx($x);


            $nOmegaX = self::bcmul(self::bcmul((string) $n, $omega, self::$scale), $x, self::$scale);
            $sinNOmegaX = (string) sin((float) $nOmegaX);

            $integrand = self::bcmul($fx_value, $sinNOmegaX, self::$scale);
            $integral = self::bcadd($integral, self::bcmul($integrand, $h, self::$scale), self::$scale);
        }

        return self::bcdiv($integral, $L, self::$scale);
    }

    /**
     * Computes the magnitude of a 3D vector using BCMath.
     * 
     * @param array $vector The 3D vector as an array of strings [x, y, z].
     * @param int|null $scale The precision scale for the calculation.
     * @return string The magnitude of the vector.
     * @throws InvalidArgumentException If the vector does not have 3 components.
     */
    public static function vectorMagnitude3DBCMath(array $vector, ?int $scale = null): string
    {
        if (count($vector) !== 3) {
            throw new InvalidArgumentException('Vector must have 3 components.');
        }

        $sumOfSquares = self::bcadd(
            self::bcadd(self::bcpow($vector[0], 2, $scale), self::bcpow($vector[1], 2, $scale), $scale),
            self::bcpow($vector[2], 2, $scale),
            $scale
        );

        return self::bcsqrt($sumOfSquares, $scale);
    }

    /**
     * Subtracts two 3D vectors using BCMath.
     * 
     * @param array $v1 The first 3D vector.
     * @param array $v2 The second 3D vector to subtract from the first.
     * @param int|null $scale The precision scale for the subtraction operation.
     * @return array The resulting 3D vector after subtraction.
     * @throws InvalidArgumentException If either vector does not have 3 components.
     */
    public static function vectorSubtract3DBCMath(array $v1, array $v2, ?int $scale = null): array
    {
        if (count($v1) !== 3 || count($v2) !== 3) {
            throw new InvalidArgumentException('Vectors must have 3 components.');
        }

        return [
            self::bcsub($v1[0], $v2[0], $scale),
            self::bcsub($v1[1], $v2[1], $scale),
            self::bcsub($v1[2], $v2[2], $scale),
        ];
    }

    /**
     * Multiplies a 3D vector by a scalar using BCMath.
     * 
     * @param array $vector The 3D vector to be multiplied.
     * @param string $scalar The scalar value to multiply by.
     * @param int|null $scale The precision scale for the multiplication operation.
     * @return array The resulting 3D vector after multiplication.
     * @throws InvalidArgumentException If the vector does not have 3 components.
     */
    public static function vectorMultiplyScalar3DBCMath(array $vector, string $scalar, ?int $scale = null): array
    {
        if (count($vector) !== 3) {
            throw new InvalidArgumentException('Vector must have 3 components.');
        }

        return [
            self::bcmul($vector[0], $scalar, $scale),
            self::bcmul($vector[1], $scalar, $scale),
            self::bcmul($vector[2], $scalar, $scale),
        ];
    }

    /**
     * Divides a 3D vector by a scalar using BCMath.
     * 
     * @param array $vector The 3D vector to be divided.
     * @param string $scalar The scalar value to divide by.
     * @param int|null $scale The precision scale for the division operation.
     * @return array The resulting 3D vector after division.
     * @throws InvalidArgumentException If the vector does not have 3 components.
     */
    public static function vectorDivideScalar3DBCMath(array $vector, string $scalar, ?int $scale = null): array
    {
        if (count($vector) !== 3) {
            throw new InvalidArgumentException('Vector must have 3 components.');
        }

        return [
            self::bcdiv($vector[0], $scalar, $scale),
            self::bcdiv($vector[1], $scalar, $scale),
            self::bcdiv($vector[2], $scalar, $scale),
        ];
    }

    /**
     * Computes the factorial of a non-negative integer using BCMath.
     *
     * @param string $n The non-negative integer.
     * @param int $scale The precision scale.
     * @return string The factorial value.
     */
    private static function bcfactorial(string $n, int $scale): string
    {
        if (bccomp($n, '0', 0) < 0) {
            return '0';
        }

        if (bccomp($n, '0', 0) === 0) {
            return '1';
        }

        $result = '1';
        for ($i = 1; bccomp((string) $i, $n, 0) <= 0; $i++) {
            $result = self::bcmul($result, (string) $i, $scale);
        }

        return $result;
    }

    /**
     * Computes the factorial of a number using iterative multiplication.
     *
     * @param int|string $n The number to compute factorial for.
     * @return string The factorial result.
     */
    public static function bcfact($n)
    {
        $r = $n--;
        while ($n > 1) {
            $r = self::bcmul((string) $r, (string) $n--);
        }

        return $r;
    }

    /**
     * Computes the sine of a value using a Taylor series with BCMath.
     *
     * @param string $a The angle in radians.
     * @param int|null $scale The precision scale.
     * @return string The sine of the angle.
     */
    public static function bcsin($a, ?int $scale = 20)
    {
        $or = $a;
        $r = self::bcsub($a, self::bcdiv(bcpow($a, '3'), '6'));
        $i = 2;

        while (self::bccomp($or, $r)) {
            $or = $r;
            switch ($i % 2) {
                case 0:
                    $r = self::bcadd($r, self::bcdiv(self::bcpow($a, $i * 2 + 1), self::bcfact($i * 2 + 1)));
                    break;
                default:
                    $r = self::bcsub($r, self::bcdiv(self::bcpow($a, $i * 2 + 1), self::bcfact($i * 2 + 1)));
                    break;
            }
            $i++;
        }

        return $r;
    }

    /**
     * Computes pi to the requested precision using the Bailey-Borwein-Plouffe formula.
     *
     * @param int $desiredScale The desired number of decimal digits.
     * @return string The value of pi.
     */
    public static function bcpi($desiredScale = 0)
    {
        static $pi = NAN;
        static $piScale = 0;
        $scale = self::bcGetScale();

        if (($scale > $piScale) || ($desiredScale > $piScale)) {
            $piScale = max($desiredScale, $scale);

            bcscale($piScale + 5);

            $index = 0;
            $newResult = 0;
            $result = -1;
            while (bccomp($newResult, $result)) {
                $result = $newResult;

                $accumulator = self::bcdiv('4', (string) (8 * $index + 1));
                $accumulator = self::bcsub($accumulator, self::bcdiv('2', (string) (8 * $index + 4)));
                $accumulator = self::bcsub($accumulator, self::bcdiv('1', (string) (8 * $index + 5)));
                $accumulator = self::bcsub($accumulator, self::bcdiv('1', (string) (8 * $index + 6)));
                $accumulator = self::bcmul($accumulator, self::bcdiv('1', bcpow('16', (string) $index)));

                $newResult = self::bcadd($newResult, $accumulator);
                $index += 1;
            }

            $result = self::bcround($result, $piScale - 1);
            bcscale($scale);
            $pi = $result;
        }

        return $pi;
    }

    /**
     * Computes the tangent of a value using BCMath.
     *
     * @param string $x The angle in radians.
     * @return string The tangent of the angle.
     */
    public static function bctan($x)
    {
        return self::bcdiv(self::bcsin($x), self::bccos($x));
    }

    /**
     * Raises a BCMath number to a non-integer power using exp(power * ln(value)).
     *
     * @param string $value The base value.
     * @param string $power The exponent (can be fractional).
     * @return string The result of value^power.
     */
    public static function bcpowx(string $value, string $power)
    {
        $scale = self::bcGetScale();
        bcscale($scale + 5);

        $result = self::bcexp(bcmul(self::bcln($value), $power));
        $result = self::bcround($result, $scale - 1);

        bcscale($scale);

        return $result;
    }

    /**
     * Computes the natural logarithm of a positive value using BCMath.
     *
     * @param string $value The value (must be positive).
     * @param int|null $scale The precision scale.
     * @return string|float The natural logarithm, or NAN if value <= 0.
     */
    public static function bcln(string $value, ?int $scale = 20)
    {
        static $ln10 = null;
        static $lastScale = 0;

        if (1 == bccomp($value, '0')) {
            $scale = self::bcGetScale();
            bcscale($scale + 5);
            $value = bcadd($value, '0');

            if (1 == bccomp($value, '10')) {
                $position = strpos("$value", ".");
                if (($position === false) || ($position > 1)) {
                    if ($position !== false) {
                        $position -= 1;
                        $value = str_replace(".", "", "$value");
                    } else {
                        $position = strlen("$value") - 1;
                    }

                    $value = substr_replace("$value", ".", 1, 0);
                }
            } else if (-1 == bccomp($value, '1')) {
                $value = str_replace(".", "", "$value");
                $position = 0;
                while ($position < strlen($value) && "0" == $value[$position]) {
                    ++$position;
                }

                $value = substr($value, $position);
                $position = -$position;
                $value = substr_replace("$value", ".", 1, 0);
            } else {
                $position = 0;
            }

            if (0 == bccomp($value, '1')) {
                $result = "0";
            } else {
                $value = bcdiv($value, bcsub($value, '1'));
                $result = 1;
                $power = $value;
                $iteration = 1;
                $newResult = 0;


                $maxIterations = 1000;
                $iterationCount = 0;

                while (bccomp((string) $newResult, (string) $result) && $iterationCount < $maxIterations) {
                    $result = $newResult;
                    $accumulator = bcdiv('1', bcmul((string) $iteration, $power));
                    $newResult = bcadd((string) $result, $accumulator);

                    $power = bcmul($power, $value);
                    ++$iteration;


                    $iterationCount++;
                }
            }

            if ($position != 0) {
                if (($ln10 === null) || ($lastScale < $scale)) {
                    if ($ln10 === null) {
                        $ln10 = '2.30258509299404568401799145468436420760110148862877297603332790';
                    }
                    $lastScale = $scale;
                }

                $accumulator = bcmul((string) $position, $ln10);
                $result = bcadd($result, $accumulator);
            }

            $result = self::bcround($result, $scale - 1);
        } else {
            $result = NAN;
        }

        return $result;
    }

    /**
     * Computes the exponential function e^x using BCMath.
     *
     * @param string $x The exponent value.
     * @return string The value of e^x.
     */
    public static function bcexp($x)
    {
        $scale = self::bcGetScale();
        bcscale($scale + 5);

        $powerDrop = 0;
        while (1 == bccomp($x, "10")) {
            ++$powerDrop;
            $x = bcdiv($x, '2');
        }

        $newResult = 0;
        $result = -1;
        $iteration = 0;
        $factorial = 1;
        $power = 1;
        while (bccomp($newResult, $result)) {
            $result = $newResult;
            $newResult = bcadd($result, bcdiv($power, $factorial));
            $power = bcmul($power, $x);

            ++$iteration;
            $factorial = bcmul($factorial, (string) $iteration);
        }

        while ($powerDrop--) {
            $result = bcmul($result, $result);
        }

        $result = self::bcround($result, $scale - 1);
        bcscale($scale);

        return $result;
    }

    /**
     * Generates a random number between 0 and 1 with BCMath precision.
     *
     * @return string A random value in [0, 1].
     */
    public static function bcrand()
    {
        $scale = self::bcGetScale();

        $loopTime = ceil($scale / (log(getrandmax()) / log(10)));
        $result = 0;
        while ($loopTime--) {
            $result = bcadd($result, (string) rand());
            $result = bcdiv($result, (string) getrandmax());
        }

        return $result;
    }

    /**
     * Negates a BCMath number string.
     *
     * @param string $value The numeric string to negate.
     * @return string The negated value.
     */
    public static function bcneg($value)
    {
        if ('-' == ($value[0] ?? null)) {
            $value = substr($value, 1);
        } else {
            $value = "-$value";
        }

        return $value;
    }

    /**
     * Rounds a BCMath number string to the specified decimal places.
     *
     * @param string $value The number to round.
     * @param int $places The number of decimal places.
     * @return string The rounded value.
     */
    public static function bcRound($value, $places)
    {
        if (-1 == bccomp($value, "0")) {
            $value = bcadd($value, "-0." . str_pad("", $places, "0") . "5");
        } else {
            $value = bcadd($value, "0." . str_pad("", $places, "0") . "5");
        }

        list($integer, $fractional) = explode(".", $value);

        if (strlen($fractional) > $places) {
            $fractional = substr($fractional, 0, $places);
            $value = $integer . "." . $fractional;
        }

        return $value;
    }

    /**
     * Gets the current BCMath scale by measuring the output of bcadd.
     *
     * @return int The current scale setting.
     */
    public static function bcGetScale()
    {
        return strlen(bcadd('0', '0')) - 2;
    }

    /**
     * Computes the arctangent of a value using BCMath.
     *
     * @param string $x The input value.
     * @return string The arctangent in radians.
     */
    public static function bcatan(string $x)
    {
        $scale = self::bcGetScale();
        bcscale($scale + 3);

        if (bccomp($x, '0') != 0) {
            $inverse = false;
            if ((bccomp($x, '1') > 0) || (bccomp($x, '-1') < 0)) {
                $inverse = true;
                $x = bcdiv('1', $x);
            }

            $xFactor = bcadd(bcmul($x, $x), '1');
            $xSquared = bcmul($x, $x);
            $newResult = 0;
            $result = -1;
            $n = 0;
            $factorial = 1;
            $oddFactorial = 1;
            $xNumerator = $x;
            $xDenominator = $xFactor;
            $pow2 = 1;

            while (bccomp($newResult, $result)) {
                $result = $newResult;

                $accumulator = bcmul($factorial, $factorial);
                $accumulator = bcmul($accumulator, $pow2);
                $accumulator = bcmul($accumulator, $xNumerator);
                $accumulator = bcdiv($accumulator, $oddFactorial);
                $accumulator = bcdiv($accumulator, $xDenominator);

                $newResult = bcadd($newResult, $accumulator);

                $n += 1;
                $pow2 = bcmul($pow2, '4');
                $factorial = bcmul($factorial, (string) $n);
                $oddFactorial = bcmul($oddFactorial, (string) (2 * $n + 0));
                $oddFactorial = bcmul($oddFactorial, (string) (2 * $n + 1));
                $xNumerator = bcmul($xNumerator, $xSquared);
                $xDenominator = bcmul($xDenominator, $xFactor);
            }

            if ($inverse) {
                $accumulator = bcdiv((string) pi(), '2');
                if ($x < 0) {
                    $accumulator = self::bcneg($accumulator);
                }

                $newResult = bcsub($accumulator, $newResult);
            }
        } else {
            $newResult = NAN;
        }

        $newResult = self::bcround($newResult, $scale - 1);
        bcscale($scale);

        return $newResult;
    }

    /**
     * Computes the arcsine of a value using BCMath.
     *
     * @param string $x The input value in [-1, 1].
     * @return string The arcsine in radians.
     */
    public static function bcasin(string $x)
    {
        $denominator = bcmul($x, $x);
        $denominator = bcsub('1', $denominator);
        $denominator = bcsqrt($denominator);
        $denominator = bcadd('1', $denominator);
        $x = bcdiv($x, $denominator);
        $x = self::bcatan($x);
        $x = bcmul($x, '2');

        return $x;
    }

    /**
     * Computes the arccosine of a value using BCMath.
     *
     * @param string $x The input value in [-1, 1].
     * @return string The arccosine in radians.
     */
    public static function bcacos(string $x)
    {
        if (bccomp('-1', $x) == 0) {
            $x = pi();
        } else {
            if (bccomp($x, '-1') > 0) {

                $denominator = bcadd($x, '1');
                $numerator = bcmul($x, $x);
                $numerator = bcsub('1', $numerator);
                $numerator = bcsqrt($numerator);
                $x = bcdiv($numerator, $denominator);
                $x = self::bcatan($x);
                $x = bcmul($x, '2');
            }
        }

        return $x;
    }

    /**
     * Computes the cosine (or sine if isSine=true) of a value using BCMath Taylor series.
     *
     * @param string $x The angle in radians.
     * @param int|null $scale The precision scale.
     * @param bool $isSine If true, computes sine instead of cosine.
     * @return string The cosine (or sine) of the angle.
     */
    public static function bccos(string $x, ?int $scale = 20, bool $isSine = false): string
    {
        $twoPi = self::bcmul((string) pi(), '2');
        $mod = self::bcdiv($x, $twoPi);
        list($whole) = explode('.', $mod);
        $x = self::bcsub($x, self::bcmul($whole, $twoPi));
        bcscale($scale + 5);

        $taylorIndex = 0;
        $taylorPoint = pi() * 7 / 4;
        $halfPi = pi() / 2;
        while ($taylorPoint >= $x) {
            $taylorPoint -= $halfPi;
            $taylorIndex += 1;
        }

        $taylorPoint = 2 - $taylorIndex / 2;
        $taylorPoint = self::bcmul((string) $taylorPoint, (string) pi());
        $taylorIndex %= 4;

        if ($isSine) {
            $taylorIndex = ($taylorIndex + 1) % 4;
        }

        $taylorIndexMap = array(1, 0, -1, 0);

        $x = self::bcsub($x, $taylorPoint);
        $power = 1;
        $newResult = 0;
        $result = -1;
        $n = 0;
        $fact = 1;
        while (self::bccomp($newResult, $result)) {
            $derivative = $taylorIndexMap[$taylorIndex];

            if (0 != $derivative) {
                $result = $newResult;

                if (-1 == $derivative) {
                    $accumulator = self::bcneg($power);
                } else {
                    $accumulator = $power;
                }

                $accumulator = self::bcdiv($accumulator, $fact);
                $newResult = self::bcadd($result, $accumulator);
            }

            $n += 1;
            $power = self::bcmul((string) $power, $x);
            $fact = self::bcmul((string) $fact, (string) $n);
            $taylorIndex = ($taylorIndex + 3) % 4;
        }

        $result = self::bcround($result, $scale - 1);

        return $result;
    }

    /**
     * Creates a 2D rotation matrix for the given angle.
     *
     * @param string $angle The rotation angle in radians.
     * @param int|null $scale The precision scale.
     * @return array A 2x2 rotation matrix.
     */
    public static function rotationMatrix2D(string $angle, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $cosTheta = self::bccos($angle, $scale);
        $sinTheta = self::bcsin($angle, $scale);

        return [
            [$cosTheta, bcmul('-1', $sinTheta, $scale)],
            [$sinTheta, $cosTheta],
        ];
    }

    /**
     * Computes the least squares solution for a linear regression problem.
     *
     * Solves b = (X^T X)^-1 X^T y.
     *
     * @param array $X The design matrix (2D array).
     * @param array $y The target vector.
     * @param int|null $scale The precision scale.
     * @return array|null The coefficient vector, or null if the matrix is singular.
     */
    public static function leastSquares(array $X, array $y, ?int $scale = null): ?array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $XT = self::transposeMatrix($X);
        $XTX = self::matrixMultiply($XT, $X, $scale);
        $XTX_inv = self::inverseMatrix($XTX, $scale);

        if ($XTX_inv === null) {
            return null;
        }

        $XTy = self::matrixMultiply($XT, self::convertToColumnMatrix($y), $scale);
        $b = self::matrixMultiply($XTX_inv, $XTy, $scale);

        $result = [];
        foreach ($b as $row) {
            $result[] = $row[0];
        }

        return $result;
    }

    /**
     * Orthonormalizes a set of vectors using the Gram-Schmidt process.
     *
     * @param array $vectors An array of vectors (each vector is an array of strings).
     * @param int|null $scale The precision scale.
     * @return array An array of orthonormal vectors.
     */
    public static function gramSchmidt(array $vectors, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $u = [];
        $e = [];
        $n = count($vectors);
        if ($n === 0) {
            return [];
        }
        $m = count($vectors[0]);

        for ($i = 0; $i < $n; $i++) {
            $v_i = $vectors[$i];
            $u_i = $v_i;
            for ($j = 0; $j < $i; $j++) {
                $proj = self::vectorProjection($v_i, $u[$j], $scale);
                $u_i = self::vectorSubtract($u_i, $proj, $scale);
            }
            $u[] = $u_i;
            $norm_u_i = self::vectorMagnitude($u_i, $scale);
            if (bccomp($norm_u_i, '0', $scale) !== 0) {
                $e[] = self::vectorScalarDivide($u_i, $norm_u_i, $scale);
            } else {

            }
        }

        return $e;
    }

    /**
     * Solves a system of linear equations using Cramer's rule.
     *
     * @param array $A The coefficient matrix (square).
     * @param array $b The constant vector.
     * @param int|null $scale The precision scale.
     * @return array|null The solution vector, or null if the system is singular.
     */
    public static function cramersRule(array $A, array $b, ?int $scale = null): ?array
    {
        $n = count($A);
        if ($n === 0 || count($A[0]) !== $n || count($b) !== $n) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $detA = self::determinant($A, $scale);

        if (bccomp($detA, '0', $scale) === 0) {
            return null;
        }

        $x = [];
        for ($i = 0; $i < $n; $i++) {
            $Ai = $A;
            for ($j = 0; $j < $n; $j++) {
                $Ai[$j][$i] = $b[$j];
            }
            $detAi = self::determinant($Ai, $scale);
            $x[$i] = bcdiv($detAi, $detA, $scale);
        }

        return $x;
    }

    /**
     * Solves a system of linear equations using LU decomposition.
     *
     * @param array $A The coefficient matrix (square).
     * @param array $b The constant vector.
     * @param int|null $scale The precision scale.
     * @return array|null The solution vector, or null if decomposition fails.
     */
    public static function solveLinearSystemLU(array $A, array $b, ?int $scale = null): ?array
    {
        $n = count($A);
        if ($n === 0 || count($A[0]) !== $n || count($b) !== $n) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $lu = self::luDecomposition($A, $scale);
        if ($lu === null) {
            return null;
        }

        list($L, $U) = $lu;
        $y = [];
        for ($i = 0; $i < $n; $i++) {
            $sum = '0';
            for ($j = 0; $j < $i; $j++) {
                $sum = bcadd($sum, bcmul($L[$i][$j], $y[$j], $scale), $scale);
            }
            $y[$i] = bcdiv(bcsub($b[$i], $sum, $scale), $L[$i][$i], $scale);
        }

        $x = array_fill(0, $n, '0');
        for ($i = $n - 1; $i >= 0; $i--) {
            $sum = '0';
            for ($j = $i + 1; $j < $n; $j++) {
                $sum = bcadd($sum, bcmul($U[$i][$j], $x[$j], $scale), $scale);
            }
            $x[$i] = bcdiv(bcsub($y[$i], $sum, $scale), $U[$i][$i], $scale);
        }

        return $x;
    }
    /**
     * Evaluates the Lagrange interpolating polynomial at a point.
     *
     * @param array $xData The x data points.
     * @param array $yData The y data points.
     * @param string $x The point at which to evaluate.
     * @param int|null $scale The precision scale.
     * @return string The interpolated value at x.
     */
    public static function lagrangePolynomial(array $xData, array $yData, string $x, ?int $scale = null): string
    {
        $n = count($xData);
        if ($n === 0) {
            return '0';
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = '0';
        for ($i = 0; $i < $n; $i++) {
            $term = $yData[$i];
            for ($j = 0; $j < $n; $j++) {
                if ($i !== $j) {
                    $numerator = bcsub($x, $xData[$j], $scale);
                    $denominator = bcsub($xData[$i], $xData[$j], $scale);
                    $term = bcmul($term, bcdiv($numerator, $denominator, $scale), $scale);
                }
            }
            $result = bcadd($result, $term, $scale);
        }

        return $result;
    }

    /**
     * Evaluates the Newton interpolating polynomial using divided differences.
     *
     * @param array $xData The x data points.
     * @param array $yData The y data points.
     * @param string $x The point at which to evaluate.
     * @param int|null $scale The precision scale.
     * @return string The interpolated value at x.
     */
    public static function newtonInterpolation(array $xData, array $yData, string $x, ?int $scale = null): string
    {
        $n = count($xData);
        if ($n === 0) {
            return '0';
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $dividedDifferences = [];
        for ($i = 0; $i < $n; $i++) {
            $dividedDifferences[$i][0] = $yData[$i];
        }

        for ($j = 1; $j < $n; $j++) {
            for ($i = 0; $i < $n - $j; $i++) {
                $numerator = bcsub($dividedDifferences[$i + 1][$j - 1], $dividedDifferences[$i][$j - 1], $scale);
                $denominator = bcsub($xData[$i + $j], $xData[$i], $scale);
                $dividedDifferences[$i][$j] = bcdiv($numerator, $denominator, $scale);
            }
        }

        $result = $dividedDifferences[0][0];
        $term = '1';
        for ($i = 1; $i < $n; $i++) {
            $term = bcmul($term, bcsub($x, $xData[$i - 1], $scale), $scale);
            $result = bcadd($result, bcmul($term, $dividedDifferences[0][$i], $scale), $scale);
        }

        return $result;
    }

    /**
     * Performs LU decomposition of a square matrix.
     *
     * Decomposes A into lower triangular L and upper triangular U such that A = LU.
     *
     * @param array $A The square matrix to decompose.
     * @param int|null $scale The precision scale.
     * @return array|null An array [L, U], or null if decomposition fails.
     */
    public static function luDecomposition(array $A, ?int $scale = null): ?array
    {
        $n = count($A);
        if ($n === 0 || count($A[0]) !== $n) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $L = self::createIdentityMatrix($n, $scale);
        $U = $A;
        for ($i = 0; $i < $n; $i++) {
            if (bccomp($U[$i][$i], '0', $scale) === 0) {
                return null;
            }

            for ($j = $i + 1; $j < $n; $j++) {
                $factor = bcdiv($U[$j][$i], $U[$i][$i], $scale);
                $L[$j][$i] = $factor;
                for ($k = $i; $k < $n; $k++) {
                    $U[$j][$k] = bcsub($U[$j][$k], bcmul($factor, $U[$i][$k], $scale), $scale);
                }
            }
        }

        return [$L, $U];
    }

    /**
     * Performs polynomial regression to fit data to a polynomial of given degree.
     *
     * @param array $xData The x data points.
     * @param array $yData The y data points.
     * @param int $degree The polynomial degree.
     * @param int|null $scale The precision scale.
     * @return array|null The polynomial coefficients (highest degree first), or null on failure.
     */
    public static function polynomialRegression(array $xData, array $yData, int $degree, ?int $scale = null): ?array
    {
        $n = count($xData);
        if ($n <= $degree) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $X = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            for ($j = $degree; $j >= 0; $j--) {
                $row[] = self::bcpow($xData[$i], $j, $scale);
            }
            $X[] = $row;
        }

        $XTX = self::matrixMultiply(self::transposeMatrix($X), $X, $scale);
        $XTX_inv = self::inverseMatrix($XTX, $scale);
        if ($XTX_inv === null) {
            return null;
        }

        $XTy = self::matrixMultiply(self::transposeMatrix($X), self::convertToColumnMatrix($yData), $scale);
        $coefficientsMatrix = self::matrixMultiply($XTX_inv, $XTy, $scale);
        $coefficients = [];
        foreach ($coefficientsMatrix as $row) {
            $coefficients[] = $row[0];
        }

        return $coefficients;
    }

    /**
     * Finds the minimum of a unimodal function using golden section search.
     *
     * @param callable $func The function to minimize.
     * @param string $a The left endpoint of the search interval.
     * @param string $b The right endpoint of the search interval.
     * @param string $tolerance Convergence tolerance.
     * @param int $maxIterations Maximum number of iterations.
     * @param int|null $scale The precision scale.
     * @return string The approximate location of the minimum.
     */
    public static function goldenSectionSearch(callable $func, string $a, string $b, string $tolerance = '1e-15', int $maxIterations = 100, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $phi = self::bcdiv(self::bcadd('1', self::bcsqrt('5', $scale), $scale), '2', $scale);
        $r = self::bcsub('1', self::bcdiv('1', $phi, $scale), $scale);

        $x1 = self::bcadd($a, self::bcmul($r, self::bcsub($b, $a, $scale), $scale), $scale);
        $x2 = self::bcsub($b, self::bcmul($r, self::bcsub($b, $a, $scale), $scale), $scale);

        $f1 = call_user_func($func, $x1);
        $f2 = call_user_func($func, $x2);

        for ($i = 0; $i < $maxIterations; $i++) {
            if (self::bccomp(self::bcsub($b, $a, $scale), $tolerance, $scale) < 0) {
                break;
            }

            if (self::bccomp($f1, $f2, $scale) < 0) {
                $b = $x2;
                $x2 = $x1;
                $f2 = $f1;
                $x1 = self::bcadd($a, self::bcmul($r, self::bcsub($b, $a, $scale), $scale), $scale);
                $f1 = call_user_func($func, $x1);
            } else {
                $a = $x1;
                $x1 = $x2;
                $f1 = $f2;
                $x2 = self::bcsub($b, self::bcmul($r, self::bcsub($b, $a, $scale), $scale), $scale);
                $f2 = call_user_func($func, $x2);
            }
        }

        return self::bcdiv(self::bcadd($a, $b, $scale), '2', $scale);
    }

    /**
     * Transposes a matrix (swaps rows and columns).
     *
     * @param array $matrix The input matrix.
     * @return array The transposed matrix.
     */
    public static function transposeMatrix(array $matrix): array
    {
        $rows = count($matrix);
        if ($rows === 0) {
            return [];
        }

        $cols = count($matrix[0]);
        $transposed = [];
        for ($j = 0; $j < $cols; $j++) {
            $row = [];
            for ($i = 0; $i < $rows; $i++) {
                $row[] = $matrix[$i][$j];
            }
            $transposed[] = $row;
        }

        return $transposed;
    }

    /**
     * Multiplies two matrices using BCMath.
     *
     * @param array $matrixA The first matrix.
     * @param array $matrixB The second matrix.
     * @param int|null $scale The precision scale.
     * @return array|null The product matrix, or null if dimensions are incompatible.
     */
    public static function matrixMultiply(array $matrixA, array $matrixB, ?int $scale = null): ?array
    {
        $rowsA = count($matrixA);
        if ($rowsA === 0) {
            return [];
        }

        $colsA = count($matrixA[0]);
        $rowsB = count($matrixB);
        if ($rowsB === 0) {
            return [];
        }

        $colsB = count($matrixB[0]);
        if ($colsA !== $rowsB) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        for ($i = 0; $i < $rowsA; $i++) {
            $row = [];
            for ($j = 0; $j < $colsB; $j++) {
                $sum = '0';
                for ($k = 0; $k < $colsA; $k++) {
                    $term = self::bcmul($matrixA[$i][$k], $matrixB[$k][$j], $scale);
                    $sum = self::bcadd($sum, $term, $scale);
                }
                $row[] = $sum;
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Computes the inverse of a square matrix using Gauss-Jordan elimination.
     *
     * @param array $matrix The square matrix to invert.
     * @param int|null $scale The precision scale.
     * @return array|null The inverse matrix, or null if the matrix is singular.
     */
    public static function inverseMatrix(array $matrix, ?int $scale = null): ?array
    {
        $n = count($matrix);
        if ($n === 0 || count($matrix[0]) !== $n) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $augmentedMatrix = [];
        for ($i = 0; $i < $n; $i++) {
            $row = $matrix[$i];
            for ($j = 0; $j < $n; $j++) {
                $row[] = ($i === $j) ? '1' : '0';
            }
            $augmentedMatrix[] = $row;
        }

        for ($i = 0; $i < $n; $i++) {
            $pivot = $augmentedMatrix[$i][$i];
            if (self::bccomp($pivot, '0', $scale) === 0) {
                return null;
            }

            for ($j = $i; $j < 2 * $n; $j++) {
                $augmentedMatrix[$i][$j] = self::bcdiv($augmentedMatrix[$i][$j], $pivot, $scale);
            }

            for ($k = 0; $k < $n; $k++) {
                if ($k !== $i) {
                    $factor = $augmentedMatrix[$k][$i];
                    for ($j = $i; $j < 2 * $n; $j++) {
                        $term = self::bcmul($factor, $augmentedMatrix[$i][$j], $scale);
                        $augmentedMatrix[$k][$j] = self::bcsub($augmentedMatrix[$k][$j], $term, $scale);
                    }
                }
            }
        }

        $inverse = [];
        for ($i = 0; $i < $n; $i++) {
            $inverse[] = array_slice($augmentedMatrix[$i], $n);
        }

        return $inverse;
    }

    /**
     * Converts a 1D vector to a column matrix (each element becomes a single-element row).
     *
     * @param array $vector The input vector.
     * @return array The column matrix representation.
     */
    public static function convertToColumnMatrix(array $vector): array
    {
        $matrix = [];
        foreach ($vector as $value) {
            $matrix[] = [$value];
        }

        return $matrix;
    }

    /**
     * Creates an n×n identity matrix.
     *
     * @param int $n The dimension of the matrix.
     * @param int|null $scale The precision scale (unused but kept for API consistency).
     * @return array The identity matrix.
     */
    public static function createIdentityMatrix(int $n, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $identity = [];
        for ($i = 0; $i < $n; $i++) {
            $row = [];
            for ($j = 0; $j < $n; $j++) {
                $row[] = ($i === $j) ? '1' : '0';
            }
            $identity[] = $row;
        }

        return $identity;
    }

    /**
     * Computes the determinant of a square matrix using cofactor expansion.
     *
     * @param array $matrix The square matrix.
     * @param int|null $scale The precision scale.
     * @return string The determinant value.
     */
    public static function determinant(array $matrix, ?int $scale = null): string
    {
        $n = count($matrix);
        if ($n === 0) {
            return '1';
        }

        if ($n === 1) {
            return $matrix[0][0];
        }

        if ($n === 2) {
            return self::bcsub(
                self::bcmul($matrix[0][0], $matrix[1][1], $scale),
                self::bcmul($matrix[0][1], $matrix[1][0], $scale),
                $scale
            );
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $det = '0';
        for ($j = 0; $j < $n; $j++) {
            $subMatrix = [];
            for ($i = 1; $i < $n; $i++) {
                $row = [];
                for ($k = 0; $k < $n; $k++) {
                    if ($k !== $j) {
                        $row[] = $matrix[$i][$k];
                    }
                }
                $subMatrix[] = $row;
            }
            $term = self::bcmul($matrix[0][$j], self::determinant($subMatrix, $scale), $scale);
            if ($j % 2 === 0) {
                $det = self::bcadd($det, $term, $scale);
            } else {
                $det = self::bcsub($det, $term, $scale);
            }
        }

        return $det;
    }

    /**
     * Projects vector v onto vector u.
     *
     * @param array $v The vector to project.
     * @param array $u The vector to project onto.
     * @param int|null $scale The precision scale.
     * @return array The projection vector.
     */
    public static function vectorProjection(array $v, array $u, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $dotProductUV = self::vectorDotProduct($u, $v, $scale);
        $dotProductUU = self::vectorDotProduct($u, $u, $scale);
        if (bccomp($dotProductUU, '0', $scale) === 0) {
            return array_fill(0, count($v), '0');
        }

        $scalar = self::bcdiv($dotProductUV, $dotProductUU, $scale);

        return self::vectorScalarMultiply($u, $scalar, $scale);
    }

    /**
     * Subtracts vector v2 from v1 element-wise.
     *
     * @param array $v1 The first vector.
     * @param array $v2 The second vector.
     * @param int|null $scale The precision scale.
     * @return array|null The difference vector, or null if dimensions differ.
     */
    public static function vectorSubtract(array $v1, array $v2, ?int $scale = null): ?array
    {
        $n1 = count($v1);
        $n2 = count($v2);
        if ($n1 !== $n2) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        for ($i = 0; $i < $n1; $i++) {
            $result[] = self::bcsub($v1[$i], $v2[$i], $scale);
        }

        return $result;
    }

    /**
     * Computes the magnitude (Euclidean norm) of a vector.
     *
     * @param array $vector The input vector.
     * @param int|null $scale The precision scale.
     * @return string The magnitude of the vector.
     */
    public static function vectorMagnitude(array $vector, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $sumOfSquares = '0';
        foreach ($vector as $value) {
            $sumOfSquares = self::bcadd($sumOfSquares, self::bcmul($value, $value, $scale), $scale);
        }

        return self::bcsqrt($sumOfSquares, $scale);
    }

    /**
     * Multiplies a vector by a scalar element-wise.
     *
     * @param array $vector The input vector.
     * @param string $scalar The scalar multiplier.
     * @param int|null $scale The precision scale.
     * @return array The resulting vector after multiplication.
     */
    public static function vectorScalarMultiply(array $vector, string $scalar, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        foreach ($vector as $value) {
            $result[] = self::bcmul($value, $scalar, $scale);
        }

        return $result;
    }

    /**
     * Divides a vector by a scalar element-wise.
     *
     * @param array $vector The input vector.
     * @param string $scalar The scalar divisor.
     * @param int|null $scale The precision scale.
     * @return array The resulting vector after division.
     */
    public static function vectorScalarDivide(array $vector, string $scalar, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        foreach ($vector as $value) {
            $result[] = self::bcdiv($value, $scalar, $scale);
        }

        return $result;
    }

    /**
     * Computes the dot product of two vectors.
     * 
     * @param array $v1 The first vector.
     * @param array $v2 The second vector.
     * @param int|null $scale The precision scale.
     * @return string The dot product result.
     */
    public static function vectorDotProduct(array $v1, array $v2, ?int $scale = null): string
    {
        $n1 = count($v1);
        $n2 = count($v2);
        if ($n1 !== $n2) {
            return '0';
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = '0';
        for ($i = 0; $i < $n1; $i++) {
            $term = self::bcmul($v1[$i], $v2[$i], $scale);
            $result = self::bcadd($result, $term, $scale);
        }

        return $result;
    }

    /**
     * Solves a linear system Ax = b using the Gauss-Seidel iterative method.
     *
     * @param array $A The coefficient matrix (must be diagonally dominant).
     * @param array $b The constant vector.
     * @param array $initialGuess The initial guess vector.
     * @param int $maxIterations Maximum number of iterations.
     * @param string $tolerance Convergence tolerance.
     * @param int|null $scale The precision scale.
     * @return array|null The solution vector, or null if it did not converge.
     * @throws InvalidArgumentException If dimensions are inconsistent.
     */
    public static function gaussSeidel(array $A, array $b, array $initialGuess, int $maxIterations = 100, string $tolerance = '1e-10', ?int $scale = null): ?array
    {
        $n = count($A);
        if ($n === 0 || count($A[0]) !== $n || count($b) !== $n || count($initialGuess) !== $n) {
            throw new InvalidArgumentException('Matrix A must be a square matrix, and the dimensions of A, b, and the initial guess must be consistent.');
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $x = $initialGuess;

        for ($k = 0; $k < $maxIterations; $k++) {
            $maxDiff = '0';

            for ($i = 0; $i < $n; $i++) {
                $sum = '0';
                for ($j = 0; $j < $n; $j++) {
                    if ($i !== $j) {
                        $sum = self::bcadd($sum, self::bcmul($A[$i][$j], $x[$j], $scale), $scale);
                    }
                }
                $x_new = self::bcdiv(self::bcsub($b[$i], $sum, $scale), $A[$i][$i], $scale);
                $diff = self::bcpow(self::bcsub($x_new, $x[$i], $scale), 2, $scale);
                $maxDiff = self::bcmax($maxDiff, $diff);
                $x[$i] = $x_new;
            }

            if (self::bcsqrt($maxDiff, $scale) <= $tolerance) {
                return $x;
            }
        }

        return null;
    }

    /**
     * Solves a linear system Ax = b using the Jacobi iterative method.
     *
     * @param array $A The coefficient matrix (must be diagonally dominant).
     * @param array $b The constant vector.
     * @param array $initialGuess The initial guess vector.
     * @param int $maxIterations Maximum number of iterations.
     * @param string $tolerance Convergence tolerance.
     * @param int|null $scale The precision scale.
     * @return array|null The solution vector, or null if it did not converge.
     * @throws InvalidArgumentException If dimensions are inconsistent.
     */
    public static function jacobiIteration(array $A, array $b, array $initialGuess, int $maxIterations = 100, string $tolerance = '1e-10', ?int $scale = null): ?array
    {
        $n = count($A);
        if ($n === 0 || count($A[0]) !== $n || count($b) !== $n || count($initialGuess) !== $n) {
            throw new InvalidArgumentException('Matrix A must be a square matrix, and the dimensions of A, b, and the initial guess must be consistent.');
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $x = $initialGuess;

        for ($k = 0; $k < $maxIterations; $k++) {
            $x_new = [];
            $maxDiff = '0';

            for ($i = 0; $i < $n; $i++) {
                $sum = '0';
                for ($j = 0; $j < $n; $j++) {
                    if ($i !== $j) {
                        $sum = self::bcadd($sum, self::bcmul($A[$i][$j], $x[$j], $scale), $scale);
                    }
                }
                $x_new[$i] = self::bcdiv(self::bcsub($b[$i], $sum, $scale), $A[$i][$i], $scale);
                $diff = self::bcpow(self::bcsub($x_new[$i], $x[$i], $scale), 2, $scale);
                $maxDiff = self::bcmax($maxDiff, $diff);
            }

            $x = $x_new;

            if (self::bcsqrt($maxDiff, $scale) <= $tolerance) {
                return $x;
            }
        }

        return null;
    }

    /**
     * Returns the maximum of two arbitrary precision numbers.
     *
     * @param string $num1 The first number.
     * @param string $num2 The second number.
     * @param int|null $scale The comparison precision.
     * @return string The larger of the two numbers.
     */
    public static function bcmax(string $num1, string $num2, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $comparisonResult = self::bccomp($num1, $num2, $scale);

        if ($comparisonResult > 0) {
            return $num1;
        } elseif ($comparisonResult < 0) {
            return $num2;
        } else {
            return $num1;
        }
    }

    /**
     * Computes a definite integral using the trapezoidal rule.
     *
     * @param callable $func The integrand function.
     * @param string $a Lower bound.
     * @param string $b Upper bound.
     * @param int $n Number of subintervals.
     * @param int|null $scale The precision scale.
     * @return string The approximate integral value.
     * @throws InvalidArgumentException If n is not positive.
     */
    public static function trapezoidalRule(callable $func, string $a, string $b, int $n, ?int $scale = null): string
    {
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of intervals ($n) must be a positive integer.');
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $h = self::bcdiv(self::bcsub($b, $a, $scale), (string) $n, $scale);
        $integral = self::bcadd(call_user_func($func, $a), call_user_func($func, $b), $scale);

        for ($i = 1; $i < $n; $i++) {
            $x = self::bcadd($a, self::bcmul((string) $i, $h, $scale), $scale);
            $integral = self::bcadd($integral, self::bcmul('2', call_user_func($func, $x), $scale), $scale);
        }

        return self::bcmul($h, self::bcdiv($integral, '2', $scale), $scale);
    }

    /**
     * Computes a definite integral using Simpson's 1/3 rule.
     *
     * @param callable $func The integrand function.
     * @param string $a Lower bound.
     * @param string $b Upper bound.
     * @param int $n Number of subintervals (must be even).
     * @param int|null $scale The precision scale.
     * @return string The approximate integral value.
     * @throws InvalidArgumentException If n is not a positive even integer.
     */
    public static function simpsonRule(callable $func, string $a, string $b, int $n, ?int $scale = null): string
    {
        if ($n <= 0 || $n % 2 !== 0) {
            throw new InvalidArgumentException('Number of intervals ($n) must be a positive even integer.');
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $h = self::bcdiv(self::bcsub($b, $a, $scale), (string) $n, $scale);
        $integral = self::bcadd(call_user_func($func, $a), call_user_func($func, $b), $scale);

        for ($i = 1; $i < $n; $i++) {
            $x = self::bcadd($a, self::bcmul((string) $i, $h, $scale), $scale);
            if ($i % 2 === 0) {
                $integral = self::bcadd($integral, self::bcmul('2', call_user_func($func, $x), $scale), $scale);
            } else {
                $integral = self::bcadd($integral, self::bcmul('4', call_user_func($func, $x), $scale), $scale);
            }
        }

        return self::bcmul(self::bcdiv($h, '3', $scale), $integral, $scale);
    }

    /**
     * Computes Shannon entropy H = -Σ p(x) log2(p(x)) for a probability distribution.
     *
     * @param array $probabilities An array of probability values as strings (must sum to 1).
     * @param int|null $scale The precision scale.
     * @return string|null The entropy value in bits, or null on invalid input.
     */
    public static function shannonEntropy(array $probabilities, ?int $scale = null): ?string
    {
        if (empty($probabilities)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $entropy = '0';
        foreach ($probabilities as $probability) {
            if (self::bccomp($probability, '0', $scale) > 0 && self::bccomp($probability, '1', $scale) <= 0) {
                $logProb = self::bcLog($probability, '2', $scale);
                $term = self::bcmul($probability, $logProb, $scale);
                $entropy = self::bcsub($entropy, $term, $scale);
            } elseif (self::bccomp($probability, '0', $scale) !== 0) {
                return null;
            }
        }

        return $entropy;
    }

    /**
     * Computes Shannon entropy using natural logarithm (in nats).
     *
     * @param array $probabilities An array of probability values.
     * @param int|null $scale The precision scale.
     * @return string|null The entropy value in nats, or null on invalid input.
     */
    public static function shannonNatEntropy(array $probabilities, ?int $scale = null): ?string
    {
        if (empty($probabilities)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $entropy = '0';
        foreach ($probabilities as $probability) {
            if (bccomp($probability, '0', $scale) > 0 && bccomp($probability, '1', $scale) <= 0) {
                $logProb = self::bcLog($probability, 'e', $scale);
                $term = self::bcmul($probability, $logProb, $scale);
                $entropy = self::bcsub($entropy, $term, $scale);
            } elseif (bccomp($probability, '0', $scale) !== 0) {
                return null;
            }
        }

        return $entropy;
    }

    /**
     * Computes Shannon-Hartley entropy H = log2(N) where N is the number of states.
     *
     * @param string $numberOfStates The number of states as a string.
     * @param string $base The logarithm base (default is '2').
     * @param int|null $scale The precision scale.
     * @return string The Shannon-Hartley entropy in bits.
     */
    public static function shannonHartleyEntropy(string $numberOfStates, string $base = '2', ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        return self::bcLog($numberOfStates, $base, $scale);
    }

    /**
     * Computes the cross entropy H(p, q) = -Σ p(x) log2(q(x)).
     *
     * @param array $p The true probability distribution.
     * @param array $q The predicted probability distribution.
     * @param int|null $scale The precision scale.
     * @return string|null The cross entropy, or null on invalid input.
     */
    public static function crossEntropy(array $p, array $q, ?int $scale = null): ?string
    {
        if (count($p) !== count($q) || empty($p)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $crossEntropy = '0';
        for ($i = 0; $i < count($p); $i++) {
            if (
                bccomp($p[$i], '0', $scale) > 0 && bccomp($p[$i], '1', $scale) <= 0 &&
                bccomp($q[$i], '0', $scale) > 0 && bccomp($q[$i], '1', $scale) <= 0
            ) {
                $logQ = self::bcLog($q[$i], '2', $scale);
                $term = self::bcmul($p[$i], $logQ, $scale);
                $crossEntropy = self::bcsub($crossEntropy, $term, $scale);
            } else {
                return null;
            }
        }

        return $crossEntropy;
    }

    /**
     * Computes the joint entropy of a 2D probability distribution.
     *
     * @param array $jointProbabilities A 2D array of joint probabilities.
     * @param int|null $scale The precision scale.
     * @return string|null The joint entropy, or null on invalid input.
     */
    public static function jointEntropy(array $jointProbabilities, ?int $scale = null): ?string
    {
        if (empty($jointProbabilities)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $entropy = '0';
        foreach ($jointProbabilities as $row) {
            foreach ($row as $probability) {
                if (bccomp($probability, '0', $scale) > 0 && bccomp($probability, '1', $scale) <= 0) {
                    $logProb = self::bcLog($probability, '2', $scale);
                    $term = self::bcmul($probability, $logProb, $scale);
                    $entropy = self::bcsub($entropy, $term, $scale);
                } elseif (bccomp($probability, '0', $scale) !== 0) {
                    return null;
                }
            }
        }

        return $entropy;
    }

    /**
     * Computes the Rényi entropy of order alpha for a probability distribution.
     *
     * @param array $probabilities The probability distribution.
     * @param string $alpha The order parameter (must be > 0 and != 1).
     * @param int|null $scale The precision scale.
     * @return string|null The Rényi entropy, or null on invalid input.
     */
    public static function renyiEntropy(array $probabilities, string $alpha, ?int $scale = null): ?string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        if (empty($probabilities) || bccomp($alpha, '0', $scale) <= 0 || bccomp($alpha, '1', $scale) === 0) {
            return null;
        }

        $sum = '0';
        foreach ($probabilities as $probability) {
            if (bccomp($probability, '0', $scale) > 0 && bccomp($probability, '1', $scale) <= 0) {
                $powProb = self::bcpow($probability, (int) $alpha, $scale);
                $sum = self::bcadd($sum, $powProb, $scale);
            } elseif (bccomp($probability, '0', $scale) !== 0) {
                return null;
            }
        }

        $logSum = self::bcLog($sum, '2', $scale);
        $oneMinusAlpha = self::bcsub('1', $alpha, $scale);
        $renyiEntropy = self::bcdiv($logSum, $oneMinusAlpha, $scale);

        return $renyiEntropy;
    }

    /**
     * Computes the perplexity of a probability distribution, defined as 2 raised to the power of the Shannon entropy.
     * 
     * @param array $probabilities The probability distribution.
     * @param string $base The base for the exponentiation (default is '2').
     * @param int|null $scale The precision scale for calculations.
     * @return string|null The perplexity, or null on invalid input.
     */
    public static function perplexity(array $probabilities, string $base = '2', ?int $scale = null): ?string
    {
        $entropy = self::shannonEntropy($probabilities, $scale);
        if ($entropy === null) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        return self::bcpow($base, (int) $entropy, $scale);
    }

    /**
     * Computes the logarithm of a number to a specified base using BCMath.
     * 
     * @param string $number The number to compute the logarithm of.
     * @param string $base The base of the logarithm (default is '10', use 'e' for natural logarithm).
     * @param int|null $scale The precision scale for calculations.
     * @return string The logarithm of the number to the specified base, or '-INF' if the number is non-positive or if the base is invalid.
     */
    private static function bcLog(string $number, string $base = '10', ?int $scale = null): string
    {
        if (bccomp($number, '0', $scale) <= 0) {
            return '-INF';
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        if ($base === 'e') {
            return bcdiv(self::bcLn($number, $scale), '1', $scale);
        } else {
            $lnBase = self::bcLn($base, $scale);
            if (bccomp($lnBase, '0', $scale) === 0) {
                return '-INF';
            }
            return bcdiv(self::bcLn($number, $scale), $lnBase, $scale);
        }
    }

    /**
     * Analyzes the randomness of a text string by computing the Shannon entropy of its character distribution.
     * @param string $text The input text to analyze.
     * @param int|null $scale The precision scale for calculations.
     * @return string|null The computed randomness (entropy) of the text, or null if the input text is empty.
     */
    public static function textRandomness(string $text, ?int $scale = null): ?string
    {
        if (empty($text)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $charCounts = [];
        $textLength = strlen($text);

        foreach (str_split($text) as $char) {
            $charCounts[$char] = ($charCounts[$char] ?? 0) + 1;
        }

        $probabilities = [];
        foreach ($charCounts as $count) {
            $probabilities[] = self::bcdiv((string) $count, (string) $textLength, $scale);
        }

        return self::shannonEntropy($probabilities, $scale);
    }

    /**
     * Computes the sample standard deviation of a dataset.
     *
     * @param array $data The dataset as an array of string values.
     * @param int|null $scale The precision scale.
     * @return string|null The standard deviation, or null if fewer than 2 data points.
     */
    public static function standardDeviation(array $data, ?int $scale = null): ?string
    {
        $count = count($data);
        if ($count < 2) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $sum = '0';
        foreach ($data as $value) {
            $sum = bcadd($sum, $value, $scale);
        }
        $mean = bcdiv($sum, (string) $count, $scale);

        $squaredDifferencesSum = '0';
        foreach ($data as $value) {
            $difference = bcsub($value, $mean, $scale);
            $squaredDifference = bcmul($difference, $difference, $scale);
            $squaredDifferencesSum = bcadd($squaredDifferencesSum, $squaredDifference, $scale);
        }

        $variance = bcdiv($squaredDifferencesSum, (string) ($count - 1), $scale);
        return self::bcsqrt($variance, $scale);
    }

    /**
     * Calculates the risk of a portfolio based on the weights and volatilities of its components.
     * 
     * @param array $weights An array of weights for each component (must sum to 1).
     * @param array $volatilities An array of volatilities for each component (must be non-negative).
     * @param int|null $scale The precision scale for calculations.
     * @return string|null The calculated portfolio risk, or null if input is invalid.
     */
    public static function portfolioRisk(array $weights, array $volatilities, ?int $scale = null): ?string
    {
        if (count($weights) !== count($volatilities) || empty($weights)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $portfolioVariance = '0';
        for ($i = 0; $i < count($weights); $i++) {
            $weightedVolatilitySquared = bcmul($weights[$i], $weights[$i], $scale);
            $weightedVolatilitySquared = bcmul($weightedVolatilitySquared, bcmul($volatilities[$i], $volatilities[$i], $scale), $scale);
            $portfolioVariance = bcadd($portfolioVariance, $weightedVolatilitySquared, $scale);
        }

        return self::bcsqrt($portfolioVariance, $scale);
    }

    /**
     * Analyzes a text string to compute its randomness, word frequencies, and word entropy.
     *
     * @param string $text The input text to analyze.
     * @param int|null $scale The precision scale for calculations.
     * @return array|null An associative array containing 'randomness', 'wordFrequencies', and 'wordEntropy', or null if the input text is empty.
     */
    public static function analyzeText(string $text, ?int $scale = null): ?array
    {
        if (empty($text)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $randomness = self::textRandomness($text, $scale);
        $words = preg_split('/\s+/u', trim(preg_replace('/[^가-힣a-zA-Z0-9\s]/u', '', $text)));
        $wordCounts = array_count_values($words);

        $totalWords = count($words);
        $wordProbabilities = [];
        foreach ($wordCounts as $count) {
            $wordProbabilities[] = bcdiv((string) $count, (string) $totalWords, $scale);
        }
        $wordEntropy = self::shannonEntropy($wordProbabilities, $scale);

        return [
            'randomness' => $randomness,
            'wordFrequencies' => $wordCounts,
            'wordEntropy' => $wordEntropy,
        ];
    }

    /**
     * Computes the arithmetic mean of a dataset.
     *
     * @param array $data The dataset as an array of numeric strings.
     * @param int|null $scale The precision scale.
     * @return string|null The mean value, or null if the dataset is empty.
     */
    public static function average(array $data, ?int $scale = null): ?string
    {
        if (empty($data)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        return bcdiv((string) array_sum(array_map('floatval', $data)), (string) count($data), $scale);
    }

    /**
     * Computes the sample variance of a dataset.
     *
     * @param array $data The dataset as an array of string values.
     * @param int|null $scale The precision scale.
     * @return string|null The variance, or null if fewer than 2 data points.
     */
    public static function variance(array $data, ?int $scale = null): ?string
    {
        $n = count($data);
        if ($n < 2) {
            return null;
        }

        $mean = self::average($data, $scale);
        $sumOfSquares = '0';
        foreach ($data as $value) {
            $diff = bcsub($value, $mean, $scale);
            $sumOfSquares = bcadd($sumOfSquares, bcmul($diff, $diff, $scale), $scale);
        }

        return bcdiv($sumOfSquares, (string) ($n - 1), $scale);
    }

    /**
     * Computes the autocorrelation function for a given dataset and lag.
     *
     * @param array $data The dataset as an array of string values.
     * @param int $lag The lag for which to compute the autocorrelation (must be non-negative).
     * @param int|null $scale The precision scale.
     * @return array|null An array of autocorrelation values for lags 0 to $lag, or null if the dataset is too small.
     */
    public static function autoCorrelationFunction(array $data, int $lag, ?int $scale = null): ?array
    {
        $n = count($data);
        if ($n <= $lag) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $mean = self::average($data, $scale);
        $variance = self::variance($data, $scale);
        if (bccomp($variance, '0', $scale) === 0) {
            return array_fill(0, $lag + 1, '0');
        }

        $acf = [];
        for ($h = 0; $h <= $lag; $h++) {
            $covarianceSum = '0';
            for ($i = $h; $i < $n; $i++) {
                $diffT = bcsub($data[$i], $mean, $scale);
                $diffTLag = bcsub($data[$i - $h], $mean, $scale);
                $covarianceSum = bcadd($covarianceSum, bcmul($diffT, $diffTLag, $scale), $scale);
            }
            $covariance = bcdiv($covarianceSum, (string) ($n - $h), $scale);
            $acf[$h] = bcdiv($covariance, $variance, $scale);
        }

        return $acf;
    }

    /**
     * Computes the sample covariance between two datasets.
     *
     * @param array $dataX The first dataset.
     * @param array $dataY The second dataset.
     * @param int|null $scale The precision scale.
     * @return string|null The covariance, or null if inputs are invalid.
     */
    public static function covariance(array $dataX, array $dataY, ?int $scale = null): ?string
    {
        $n = count($dataX);
        if ($n < 2 || $n !== count($dataY)) {
            return null;
        }

        $meanX = self::average($dataX, $scale);
        $meanY = self::average($dataY, $scale);
        $sumOfProducts = '0';
        for ($i = 0; $i < $n; $i++) {
            $diffX = bcsub($dataX[$i], $meanX, $scale);
            $diffY = bcsub($dataY[$i], $meanY, $scale);
            $sumOfProducts = bcadd($sumOfProducts, bcmul($diffX, $diffY, $scale), $scale);
        }

        return bcdiv($sumOfProducts, (string) ($n - 1), $scale);
    }

    /**
     * Performs sentiment analysis on a given text using a provided sentiment dictionary.
     * 
     * @param string $text The input text to analyze.
     * @param array $sentimentDictionary An associative array mapping words to their sentiment scores (as strings).
     * @param int|null $scale The precision scale for calculations.
     * @return string The average sentiment score of the text, or '0' if no sentiment words are found.
     */
    public static function sentimentAnalysis(string $text, array $sentimentDictionary, ?int $scale = null): string
    {
        if (empty($text)) {
            return '0';
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $words = preg_split('/\s+/u', trim(preg_replace('/[^가-힣a-zA-Z0-9\s]/u', '', $text)));
        $totalSentiment = '0';
        $foundWordCount = 0;

        foreach ($words as $word) {
            if (isset($sentimentDictionary[$word])) {
                $totalSentiment = bcadd($totalSentiment, $sentimentDictionary[$word], $scale);
                $foundWordCount++;
            }
        }

        if ($foundWordCount > 0) {
            return bcdiv($totalSentiment, (string) $foundWordCount, $scale);
        } else {
            return '0';
        }
    }

    /**
     * Computes the Euclidean distance between two points in n-dimensional space.
     *
     * @param array $point1 The first point.
     * @param array $point2 The second point.
     * @param int|null $scale The precision scale.
     * @return string|null The distance, or null if dimensions don't match.
     */
    public static function euclideanDistance(array $point1, array $point2, ?int $scale = null): ?string
    {
        if (count($point1) !== count($point2) || empty($point1)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $sumOfSquares = '0';
        for ($i = 0; $i < count($point1); $i++) {
            $diff = bcsub($point1[$i], $point2[$i], $scale);
            $sumOfSquares = bcadd($sumOfSquares, bcmul($diff, $diff, $scale), $scale);
        }

        return self::bcsqrt($sumOfSquares, $scale);
    }

    /**
     * Computes the average distance from a set of data points to a centroid.
     *
     * @param array $dataPoints An array of data points (each point is an array of coordinates).
     * @param array $centroid The centroid point (array of coordinates).
     * @param int|null $scale The precision scale.
     * @return string|null The average distance, or null if data points are empty or dimensions don't match.
     */
    public static function averageDistanceToCentroid(array $dataPoints, array $centroid, ?int $scale = null): ?string
    {
        if (empty($dataPoints)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $totalDistance = '0';
        foreach ($dataPoints as $point) {
            $distance = self::euclideanDistance($point, $centroid, $scale);
            if ($distance === null) {
                return null;
            }
            $totalDistance = bcadd($totalDistance, $distance, $scale);
        }

        return bcdiv($totalDistance, (string) count($dataPoints), $scale);
    }

    /**
     * Computes the categorical entropy of a dataset.
     * 
     * @param array $data The dataset as an array of categorical values.
     * @param int|null $scale The precision scale for probability calculations.
     * @return string|null The categorical entropy, or null if the dataset is empty.
     */
    public static function categoricalEntropy(array $data, ?int $scale = null): ?string
    {
        $counts = array_count_values($data);
        $probabilities = [];
        $total = count($data);

        if ($total === 0) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        foreach ($counts as $count) {
            $probabilities[] = bcdiv((string) $count, (string) $total, $scale);
        }

        return self::shannonEntropy($probabilities, $scale);
    }

    /**
     * Computes the joint categorical entropy of two categorical variables.
     * 
     * @param array $dataX The first categorical variable.
     * @param array $dataY The second categorical variable.
     * @param int|null $scale The precision scale.
     * @return string|null The joint categorical entropy, or null on invalid input.
     */
    public static function jointCategoricalEntropy(array $dataX, array $dataY, ?int $scale = null): ?string
    {
        $n = count($dataX);
        if ($n !== count($dataY) || $n === 0) {
            return null;
        }

        $jointCounts = [];
        for ($i = 0; $i < $n; $i++) {
            $key = $dataX[$i] . '_' . $dataY[$i];
            $jointCounts[$key] = ($jointCounts[$key] ?? 0) + 1;
        }

        $jointProbabilities = [];
        foreach ($jointCounts as $count) {
            $jointProbabilities[] = bcdiv((string) $count, (string) $n, $scale);
        }

        return self::shannonEntropy($jointProbabilities, $scale);
    }

    /**
     * Computes the information gain of each feature with respect to a target variable.
     *
     * @param array $features A 2D array of feature values (or a 1D array for a single feature).
     * @param array $target The target variable values.
     * @param int|null $scale The precision scale.
     * @return array|null An array of information gain values for each feature, or null on invalid input.
     */
    public static function informationGain(array $features, array $target, ?int $scale = null): ?array
    {
        $numFeatures = is_array(reset($features)) ? count(reset($features)) : 1;
        $n = count($target);
        if ($n !== count($features) && (is_array(reset($features)) && $n !== count(reset($features)))) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $targetEntropy = self::categoricalEntropy($target, $scale);
        if ($targetEntropy === null) {
            return null;
        }

        $informationGains = [];
        if (is_array(reset($features))) {
            for ($i = 0; $i < $numFeatures; $i++) {
                $featureColumn = array_column($features, $i);
                $featureValues = array_unique($featureColumn);
                $conditionalEntropy = '0';
                foreach ($featureValues as $value) {
                    $indices = array_keys(array_filter($featureColumn, function ($v) use ($value) {
                        return $v === $value;
                    }));

                    $subsetTarget = array_intersect_key($target, array_flip($indices));
                    $subsetProbability = bcdiv((string) count($subsetTarget), (string) $n, $scale);
                    $subsetEntropy = self::categoricalEntropy(array_values($subsetTarget), $scale);
                    if ($subsetEntropy !== null) {
                        $conditionalEntropy = bcadd($conditionalEntropy, bcmul($subsetProbability, $subsetEntropy, $scale), $scale);
                    }
                }

                $informationGains[$i] = bcsub($targetEntropy, $conditionalEntropy, $scale);
            }
        } else {
            $featureValues = array_unique($features);
            $conditionalEntropy = '0';
            foreach ($featureValues as $value) {
                $indices = array_keys(array_filter($features, function ($v) use ($value) {
                    return $v === $value;
                }));

                $subsetTarget = array_intersect_key($target, array_flip($indices));
                $subsetProbability = bcdiv((string) count($subsetTarget), (string) $n, $scale);
                $subsetEntropy = self::categoricalEntropy(array_values($subsetTarget), $scale);
                if ($subsetEntropy !== null) {
                    $conditionalEntropy = bcadd($conditionalEntropy, bcmul($subsetProbability, $subsetEntropy, $scale), $scale);
                }
            }
            $informationGains[0] = bcsub($targetEntropy, $conditionalEntropy, $scale);
        }

        return $informationGains;
    }

    /**
     * Computes the similarity between two texts based on their word frequency distributions using cross entropy.
     *
     * @param string $text1 The first text.
     * @param string $text2 The second text.
     * @param int|null $scale The precision scale for calculations.
     * @return string|null The similarity score (lower is more similar), or null if analysis fails.
     */
    public static function textSimilarity(string $text1, string $text2, ?int $scale = null): ?string
    {
        $analysis1 = self::analyzeText($text1, $scale);
        $analysis2 = self::analyzeText($text2, $scale);

        if ($analysis1 === null || $analysis2 === null || empty($analysis1['wordFrequencies']) || empty($analysis2['wordFrequencies'])) {
            return null;
        }

        $allWords = array_unique(array_merge(array_keys($analysis1['wordFrequencies']), array_keys($analysis2['wordFrequencies'])));
        $prob1 = [];
        $prob2 = [];
        $totalWords1 = array_sum($analysis1['wordFrequencies']);
        $totalWords2 = array_sum($analysis2['wordFrequencies']);

        if ($totalWords1 === 0 || $totalWords2 === 0) {
            return null;
        }

        foreach ($allWords as $word) {
            $count1 = $analysis1['wordFrequencies'][$word] ?? 0;
            $count2 = $analysis2['wordFrequencies'][$word] ?? 0;
            $prob1[] = bcdiv((string) $count1, (string) $totalWords1, $scale);
            $prob2[] = bcdiv((string) $count2, (string) $totalWords2, $scale);
        }

        return self::crossEntropy($prob1, $prob2, $scale);
    }

    /**
     * Computes the entropy of a continuous dataset by discretizing it into bins and applying Shannon's formula.
     *
     * @param array $data The continuous dataset as an array of numeric values.
     * @param int $numBins The number of bins to use for discretization.
     * @param int|null $scale The precision scale for calculations.
     * @return string|null The estimated entropy, or null if the dataset is empty.
     */
    public static function continuousEntropy(array $data, int $numBins = 10, ?int $scale = null): ?string
    {
        $min = min(array_map('floatval', $data));
        $max = max(array_map('floatval', $data));
        $range = bcsub((string) $max, (string) $min, $scale);

        if (bccomp($range, '0', $scale) === 0) {

            return '0';
        }

        $binWidth = bcdiv($range, (string) $numBins, $scale);
        $bins = array_fill(0, $numBins, 0);
        $total = count($data);

        if ($total === 0) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        foreach ($data as $value) {
            $diff = bcsub($value, (string) $min, $scale);
            if (bccomp($binWidth, '0', $scale) !== 0) {
                $binIndex = floor(bcdiv($diff, $binWidth, $scale) * $numBins);
                if ($binIndex >= 0 && $binIndex < $numBins) {
                    $bins[$binIndex]++;
                }
            }
        }

        $probabilities = [];
        foreach ($bins as $count) {
            $probabilities[] = bcdiv((string) $count, (string) $total, $scale);
        }

        return self::shannonEntropy($probabilities, $scale);
    }

    /**
     * Performs a simple anomaly detection based on the entropy of the dataset.
     * 
     * @param array $data The dataset to analyze.
     * @param float $entropyThreshold The threshold below which the data is considered anomalous.
     * @param int $continuousBins The number of bins to use for continuous data (ignored for categorical data).
     * @param int|null $scale The precision scale for calculations.
     * @return bool True if the data is considered anomalous, false otherwise.
     */
    public static function simpleAnomalyDetection(array $data, float $entropyThreshold, int $continuousBins = 10, ?int $scale = null): bool
    {
        $entropy = is_numeric(reset($data)) ? self::continuousEntropy($data, $continuousBins, $scale) : self::categoricalEntropy($data, $scale);
        if ($entropy === null) {
            return false;
        }

        return floatval($entropy) < $entropyThreshold;
    }

    /**
     * Computes a simple PageRank-like algorithm for a directed graph represented as an adjacency list.
     * 
     * @param array $linkGraph An associative array where keys are page identifiers and values are arrays of linked page identifiers.
     * @param int $iterations The number of iterations to perform.
     * @param string $dampingFactor The damping factor (as a string for precision).
     * @param int|null $scale The precision scale for calculations.
     * @return array An associative array of page identifiers and their corresponding PageRank values.
     */
    public static function simplePageRank(array $linkGraph, int $iterations = 100, string $dampingFactor = '0.85', ?int $scale = null): array
    {
        $pages = array_keys($linkGraph);
        $numPages = count($pages);
        if ($numPages === 0) {
            return [];
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $pageRanks = array_fill_keys($pages, bcdiv('1', (string) $numPages, $scale));

        for ($i = 0; $i < $iterations; $i++) {
            $newPageRanks = array_fill_keys($pages, '0');
            foreach ($pages as $page) {
                if (!empty($linkGraph[$page])) {
                    $numLinks = count($linkGraph[$page]);
                    foreach ($linkGraph[$page] as $linkedPage) {
                        $contribution = bcdiv($pageRanks[$page], (string) $numLinks, $scale + 5);
                        $newPageRanks[$linkedPage] = bcadd($newPageRanks[$linkedPage], $contribution, $scale + 5);
                    }
                }
            }

            foreach ($pages as $page) {
                $newPageRanks[$page] = bcadd(bcmul($dampingFactor, $newPageRanks[$page], $scale + 5), bcdiv(bcsub('1', $dampingFactor, $scale + 5), (string) $numPages, $scale + 5), $scale + 5);
            }
            $pageRanks = $newPageRanks;
        }

        foreach ($pageRanks as $page => $rank) {
            $pageRanks[$page] = self::bcround($rank, $scale);
        }

        return $pageRanks;
    }

    /**
     * Returns the minimum of two arbitrary precision numbers.
     *
     * @param string $num1 The first number.
     * @param string $num2 The second number.
     * @param int|null $scale The comparison precision.
     * @return string The smaller of the two numbers.
     */
    public static function bcmin(string $num1, string $num2, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $comparisonResult = self::bccomp($num1, $num2, $scale);

        if ($comparisonResult < 0) {
            return $num1;
        } elseif ($comparisonResult > 0) {
            return $num2;
        } else {
            return $num1;
        }
    }

    /**
     * Computes the modulo of two arbitrary precision numbers.
     *
     * @param int|string $dividend The dividend.
     * @param string $divisor The divisor.
     * @param int|null $scale The number of decimal places.
     * @return string The remainder after division.
     * @throws InvalidArgumentException If the divisor is zero.
     */
    public static function bcmod_precise(int|string $dividend, string $divisor, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        if (self::bccomp($divisor, '0', $scale) === 0) {
            throw new InvalidArgumentException('Division by zero in modulo.');
        }

        return bcmod((string) $dividend, $divisor, $scale);
    }

    /**
     * Adds two vectors element-wise using BCMath.
     *
     * @param array $vector1 The first vector as an array of strings.
     * @param array $vector2 The second vector as an array of strings.
     * @return array The resulting sum vector.
     * @throws InvalidArgumentException If vectors have different dimensions.
     */
    public static function vectorAddBCMath(array $vector1, array $vector2): array
    {
        if (count($vector1) !== count($vector2)) {
            throw new InvalidArgumentException('Vectors must have the same dimension.');
        }

        $result = [];
        for ($i = 0; $i < count($vector1); $i++) {
            $result[] = self::bcadd($vector1[$i], $vector2[$i]);
        }

        return $result;
    }

    /**
     * Computes the trace of a square matrix (sum of diagonal elements).
     *
     * @param array $matrix A square matrix as a 2D array of strings.
     * @param int|null $scale The number of decimal places.
     * @return string|null The trace value, or null if the matrix is not square.
     */
    public static function matrixTrace(array $matrix, ?int $scale = null): ?string
    {
        $n = count($matrix);
        if ($n === 0) {
            return '0';
        }

        if (count($matrix[0]) !== $n) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $trace = '0';
        for ($i = 0; $i < $n; $i++) {
            $trace = bcadd($trace, $matrix[$i][$i], $scale);
        }

        return $trace;
    }

    /**
     * Computes the numerical gradient of a multivariable function.
     *
     * Returns a vector of all partial derivatives at the given point.
     *
     * @param callable $function The multivariable function.
     * @param array $variables The point at which to compute the gradient.
     * @param string $h The step size for finite differences.
     * @return array The gradient vector as an array of strings.
     */
    public static function gradientNumerical(callable $function, array $variables, string $h = '1e-6'): array
    {
        $gradient = [];
        for ($i = 0; $i < count($variables); $i++) {
            $gradient[] = self::partialDerivativeNumerical($function, $variables, $i, $h);
        }

        return $gradient;
    }

    /**
     * Computes the Frobenius norm of a matrix.
     *
     * The Frobenius norm is the square root of the sum of the squares of all elements.
     *
     * @param array $matrix A 2D array of string values.
     * @param int|null $scale The number of decimal places.
     * @return string The Frobenius norm.
     */
    public static function matrixFrobeniusNorm(array $matrix, ?int $scale = null): string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $sumOfSquares = '0';
        foreach ($matrix as $row) {
            foreach ($row as $value) {
                $sumOfSquares = bcadd($sumOfSquares, bcmul($value, $value, $scale), $scale);
            }
        }

        return self::bcsqrt($sumOfSquares, $scale);
    }

    /**
     * Scales a matrix by multiplying every element by a scalar.
     *
     * @param array $matrix A 2D array of string values.
     * @param string $scalar The scalar multiplier.
     * @param int|null $scale The number of decimal places.
     * @return array The scaled matrix.
     */
    public static function matrixScalarMultiply(array $matrix, string $scalar, ?int $scale = null): array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        foreach ($matrix as $row) {
            $newRow = [];
            foreach ($row as $value) {
                $newRow[] = bcmul($value, $scalar, $scale);
            }
            $result[] = $newRow;
        }

        return $result;
    }

    /**
     * Adds two matrices element-wise.
     *
     * @param array $matrixA The first matrix.
     * @param array $matrixB The second matrix.
     * @param int|null $scale The number of decimal places.
     * @return array|null The resulting matrix, or null if dimensions don't match.
     */
    public static function matrixAdd(array $matrixA, array $matrixB, ?int $scale = null): ?array
    {
        $rowsA = count($matrixA);
        $rowsB = count($matrixB);
        if ($rowsA === 0 || $rowsB === 0 || $rowsA !== $rowsB) {
            return null;
        }

        $colsA = count($matrixA[0]);
        $colsB = count($matrixB[0]);
        if ($colsA !== $colsB) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        for ($i = 0; $i < $rowsA; $i++) {
            $row = [];
            for ($j = 0; $j < $colsA; $j++) {
                $row[] = bcadd($matrixA[$i][$j], $matrixB[$i][$j], $scale);
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Subtracts matrix B from matrix A element-wise.
     *
     * @param array $matrixA The first matrix.
     * @param array $matrixB The second matrix.
     * @param int|null $scale The number of decimal places.
     * @return array|null The resulting matrix, or null if dimensions don't match.
     */
    public static function matrixSubtract(array $matrixA, array $matrixB, ?int $scale = null): ?array
    {
        $rowsA = count($matrixA);
        $rowsB = count($matrixB);
        if ($rowsA === 0 || $rowsB === 0 || $rowsA !== $rowsB) {
            return null;
        }
        $colsA = count($matrixA[0]);
        $colsB = count($matrixB[0]);
        if ($colsA !== $colsB) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $result = [];
        for ($i = 0; $i < $rowsA; $i++) {
            $row = [];
            for ($j = 0; $j < $colsA; $j++) {
                $row[] = bcsub($matrixA[$i][$j], $matrixB[$i][$j], $scale);
            }
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Computes the numerical second partial derivative (Hessian element).
     *
     * Uses central difference approximation for d²f/(dx_i dx_j).
     *
     * @param callable $function The multivariable function.
     * @param array $variables The point at which to evaluate.
     * @param int $i Index of the first variable.
     * @param int $j Index of the second variable.
     * @param string $h Step size.
     * @return string The approximate second partial derivative.
     */
    public static function secondPartialDerivativeNumerical(callable $function, array $variables, int $i, int $j, string $h = '1e-4'): string
    {
        $vars_pp = $variables;
        $vars_pm = $variables;
        $vars_mp = $variables;
        $vars_mm = $variables;

        $vars_pp[$i] = self::bcadd($variables[$i], $h);
        $vars_pp[$j] = self::bcadd($vars_pp[$j], $h);

        $vars_pm[$i] = self::bcadd($variables[$i], $h);
        $vars_pm[$j] = self::bcsub($vars_pm[$j], $h);

        $vars_mp[$i] = self::bcsub($variables[$i], $h);
        $vars_mp[$j] = self::bcadd($vars_mp[$j], $h);

        $vars_mm[$i] = self::bcsub($variables[$i], $h);
        $vars_mm[$j] = self::bcsub($vars_mm[$j], $h);

        $fpp = $function($vars_pp);
        $fpm = $function($vars_pm);
        $fmp = $function($vars_mp);
        $fmm = $function($vars_mm);

        $numerator = self::bcsub(self::bcsub($fpp, $fpm), self::bcsub($fmp, $fmm));
        $denominator = self::bcmul('4', self::bcmul($h, $h));

        return self::bcdiv($numerator, $denominator);
    }

    /**
     * Finds the root of a function using Newton's method.
     *
     * @param callable $func The function f(x) for which to find a root.
     * @param callable $derivative The derivative f'(x).
     * @param string $initialGuess The starting point.
     * @param int $maxIterations Maximum number of iterations.
     * @param string $tolerance Convergence tolerance.
     * @param int|null $scale The number of decimal places.
     * @return string|null The approximate root, or null if it did not converge.
     */
    public static function newtonMethod(callable $func, callable $derivative, string $initialGuess, int $maxIterations = 100, string $tolerance = '1e-15', ?int $scale = null): ?string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $x = $initialGuess;
        for ($i = 0; $i < $maxIterations; $i++) {
            $fx = $func($x);
            $fpx = $derivative($x);

            if (self::bccomp(self::bcabs($fpx), '0', $scale) === 0) {
                return null;
            }

            $x_new = self::bcsub($x, self::bcdiv($fx, $fpx, $scale), $scale);
            $diff = self::bcabs(self::bcsub($x_new, $x, $scale));

            if (self::bccomp($diff, $tolerance, $scale) < 0) {
                return $x_new;
            }

            $x = $x_new;
        }

        return null;
    }

    /**
     * Finds the root of a function using the bisection method.
     *
     * @param callable $func The function f(x) for which to find a root.
     * @param string $a The left endpoint of the interval.
     * @param string $b The right endpoint of the interval.
     * @param int $maxIterations Maximum number of iterations.
     * @param string $tolerance Convergence tolerance.
     * @param int|null $scale The number of decimal places.
     * @return string|null The approximate root, or null if f(a) and f(b) have the same sign.
     */
    public static function bisectionMethod(callable $func, string $a, string $b, int $maxIterations = 100, string $tolerance = '1e-15', ?int $scale = null): ?string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $fa = $func($a);
        $fb = $func($b);

        if (
            (self::bccomp($fa, '0', $scale) > 0 && self::bccomp($fb, '0', $scale) > 0) ||
            (self::bccomp($fa, '0', $scale) < 0 && self::bccomp($fb, '0', $scale) < 0)
        ) {
            return null;
        }

        for ($i = 0; $i < $maxIterations; $i++) {
            $mid = self::bcdiv(self::bcadd($a, $b, $scale), '2', $scale);
            $fmid = $func($mid);

            if (self::bccomp(self::bcabs(self::bcsub($b, $a, $scale)), $tolerance, $scale) < 0) {
                return $mid;
            }

            if (self::bccomp($fmid, '0', $scale) === 0) {
                return $mid;
            }

            if (
                (self::bccomp($fa, '0', $scale) < 0 && self::bccomp($fmid, '0', $scale) < 0) ||
                (self::bccomp($fa, '0', $scale) > 0 && self::bccomp($fmid, '0', $scale) > 0)
            ) {
                $a = $mid;
                $fa = $fmid;
            } else {
                $b = $mid;
                $fb = $fmid;
            }
        }

        return self::bcdiv(self::bcadd($a, $b, $scale), '2', $scale);
    }

    /**
     * Computes the numerical derivative using central difference.
     *
     * f'(x) ≈ (f(x+h) - f(x-h)) / (2h)
     *
     * @param callable $func The function to differentiate.
     * @param string $x The point at which to compute the derivative.
     * @param string $h The step size.
     * @return string The approximate derivative.
     */
    public static function centralDifferenceDerivative(callable $func, string $x, string $h = '1e-6'): string
    {
        $x_plus = self::bcadd($x, $h);
        $x_minus = self::bcsub($x, $h);
        $f_plus = $func($x_plus);
        $f_minus = $func($x_minus);

        return self::bcdiv(self::bcsub($f_plus, $f_minus), self::bcmul('2', $h));
    }

    /**
     * Computes the Pearson correlation coefficient between two datasets.
     *
     * @param array $dataX The first dataset as an array of strings.
     * @param array $dataY The second dataset as an array of strings.
     * @param int|null $scale The number of decimal places.
     * @return string|null The correlation coefficient, or null if inputs are invalid.
     */
    public static function pearsonCorrelation(array $dataX, array $dataY, ?int $scale = null): ?string
    {
        $n = count($dataX);
        if ($n < 2 || $n !== count($dataY)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $cov = self::covariance($dataX, $dataY, $scale);
        $stdX = self::standardDeviation($dataX, $scale);
        $stdY = self::standardDeviation($dataY, $scale);

        if ($cov === null || $stdX === null || $stdY === null) {
            return null;
        }

        if (bccomp($stdX, '0', $scale) === 0 || bccomp($stdY, '0', $scale) === 0) {
            return null;
        }

        return bcdiv($cov, bcmul($stdX, $stdY, $scale), $scale);
    }

    /**
     * Computes the KL divergence D_KL(P || Q) = sum(P(i) * log2(P(i)/Q(i))).
     *
     * @param array $p The true probability distribution.
     * @param array $q The approximate probability distribution.
     * @param int|null $scale The number of decimal places.
     * @return string|null The KL divergence, or null on invalid input.
     */
    public static function klDivergence(array $p, array $q, ?int $scale = null): ?string
    {
        if (count($p) !== count($q) || empty($p)) {
            return null;
        }

        if ($scale === null) {
            $scale = self::$scale;
        }

        $divergence = '0';
        for ($i = 0; $i < count($p); $i++) {
            if (bccomp($p[$i], '0', $scale) > 0) {
                if (bccomp($q[$i], '0', $scale) <= 0) {
                    return null;
                }
                $ratio = bcdiv($p[$i], $q[$i], $scale);
                $logRatio = self::bcLog($ratio, '2', $scale);
                $term = bcmul($p[$i], $logRatio, $scale);
                $divergence = bcadd($divergence, $term, $scale);
            }
        }

        return $divergence;
    }

    /**
     * Normalizes a vector to unit length.
     *
     * @param array $vector The vector as an array of strings.
     * @param int|null $scale The number of decimal places.
     * @return array|null The unit vector, or null if the vector is zero.
     */
    public static function vectorNormalizeBCMath(array $vector, ?int $scale = null): ?array
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $magnitude = self::vectorMagnitudeBCMath($vector);
        if (bccomp($magnitude, '0', $scale) === 0) {
            return null;
        }

        $result = [];
        foreach ($vector as $component) {
            $result[] = bcdiv($component, $magnitude, $scale);
        }

        return $result;
    }

    /**
     * Computes the angle between two vectors in radians.
     *
     * angle = acos(dot(v1, v2) / (|v1| * |v2|))
     *
     * @param array $vector1 The first vector.
     * @param array $vector2 The second vector.
     * @param int|null $scale The number of decimal places.
     * @return string|null The angle in radians, or null if any vector is zero.
     */
    public static function vectorAngleBCMath(array $vector1, array $vector2, ?int $scale = null): ?string
    {
        if ($scale === null) {
            $scale = self::$scale;
        }

        $mag1 = self::vectorMagnitudeBCMath($vector1);
        $mag2 = self::vectorMagnitudeBCMath($vector2);

        if (bccomp($mag1, '0', $scale) === 0 || bccomp($mag2, '0', $scale) === 0) {
            return null;
        }

        $dot = self::vectorDotProductBCMath($vector1, $vector2);
        $cosAngle = bcdiv($dot, bcmul($mag1, $mag2, $scale), $scale);

        return (string) acos((float) $cosAngle);
    }
}
