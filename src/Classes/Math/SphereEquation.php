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
 * Class SphereEquation
 *
 * A utility class for sphere equation-related mathematical operations.
 */
class SphereEquation
{
    /**
     * Calculate the value of the sphere equation for a given point.
     *
     * @param float $centerX The x-coordinate of the sphere's center.
     * @param float $centerY The y-coordinate of the sphere's center.
     * @param float $centerZ The z-coordinate of the sphere's center.
     * @param float $radius The radius of the sphere.
     * @param float $x The x-coordinate of the point to evaluate.
     * @param float $y The y-coordinate of the point to evaluate.
     * @param float $z The z-coordinate of the point to evaluate.
     * @return float The value of the sphere equation for the given point.
     */
    public static function sphereValue(float $centerX, float $centerY, float $centerZ, float $radius, float $x, float $y, float $z): float
    {
        return pow($x - $centerX, 2) + pow($y - $centerY, 2) + pow($z - $centerZ, 2) - pow($radius, 2);
    }

    /**
     * Check if a point is on the surface of a sphere defined by its center and radius.
     *
     * @param float $centerX The x-coordinate of the sphere's center.
     * @param float $centerY The y-coordinate of the sphere's center.
     * @param float $centerZ The z-coordinate of the sphere's center.
     * @param float $radius The radius of the sphere.
     * @param float $pointX The x-coordinate of the point to check.
     * @param float $pointY The y-coordinate of the point to check.
     * @param float $pointZ The z-coordinate of the point to check.
     * @param float $tolerance The tolerance for determining if the point is on the sphere (default is 1e-6).
     * @return bool True if the point is on the surface of the sphere, false otherwise.
     */
    public static function isPointOnSphere(float $centerX, float $centerY, float $centerZ, float $radius, float $pointX, float $pointY, float $pointZ, float $tolerance = 1e-6): bool
    {
        return abs(self::sphereValue($centerX, $centerY, $centerZ, $radius, $pointX, $pointY, $pointZ)) < $tolerance;
    }
}
