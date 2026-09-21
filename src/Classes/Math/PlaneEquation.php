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
 * Class PlaneEquation
 *
 * A utility class for plane equation calculations in 3D space.
 */
class PlaneEquation
{
    /**
     * Calculate the value of the plane equation given a normal vector, a point on the plane, and a test point.
     *
     * @param float $normalX The x-component of the normal vector.
     * @param float $normalY The y-component of the normal vector.
     * @param float $normalZ The z-component of the normal vector.
     * @param float $pointX The x-coordinate of a point on the plane.
     * @param float $pointY The y-coordinate of a point on the plane.
     * @param float $pointZ The z-coordinate of a point on the plane.
     * @param float $x The x-coordinate of the test point.
     * @param float $y The y-coordinate of the test point.
     * @param float $z The z-coordinate of the test point.
     * @return float The value of the plane equation for the test point. A positive value indicates the point is above the plane, negative indicates below, and zero indicates on the plane.
     */
    public static function planeFromNormalAndPoint(float $normalX, float $normalY, float $normalZ, float $pointX, float $pointY, float $pointZ, float $x, float $y, float $z): float
    {
        return $normalX * ($x - $pointX) + $normalY * ($y - $pointY) + $normalZ * ($z - $pointZ);
    }

    /**
     * Calculate the value of the plane equation in general form given coefficients and a test point.
     *
     * @param float $a The coefficient of x in the plane equation.
     * @param float $b The coefficient of y in the plane equation.
     * @param float $c The coefficient of z in the plane equation.
     * @param float $d The constant term in the plane equation.
     * @param float $x The x-coordinate of the test point.
     * @param float $y The y-coordinate of the test point.
     * @param float $z The z-coordinate of the test point.
     * @return float The value of the plane equation for the test point. A positive value indicates the point is above the plane, negative indicates below, and zero indicates on the plane.
     */
    public static function planeGeneralFormValue(float $a, float $b, float $c, float $d, float $x, float $y, float $z): float
    {
        return $a * $x + $b * $y + $c * $z + $d;
    }
}
