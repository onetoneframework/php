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
 * Class SolidGeometry
 *
 * A utility class for solid geometry-related mathematical operations.
 */
class SolidGeometry
{
    /**
     * Check if a point is inside a sphere defined by its center and radius.
     *
     * @param float $x The x-coordinate of the point.
     * @param float $y The y-coordinate of the point.
     * @param float $z The z-coordinate of the point.
     * @param float $centerX The x-coordinate of the sphere's center.
     * @param float $centerY The y-coordinate of the sphere's center.
     * @param float $centerZ The z-coordinate of the sphere's center.
     * @param float $radius The radius of the sphere.
     * @return bool True if the point is inside the sphere, false otherwise.
     */
    public static function cylinderEquation(float $x, float $y, float $z, float $radius, float $height): bool
    {
        return pow($x, 2) + pow($y, 2) <= pow($radius, 2) && $z >= 0 && $z <= $height;
    }
}
