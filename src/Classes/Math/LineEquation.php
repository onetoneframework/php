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
 * Class LineEquation
 *
 * A utility class for line equation calculations in 2D and 3D space.
 */
class LineEquation
{
    /**
     * Calculate the y-coordinate of a point on a line in 2D space given the slope, y-intercept, and x-coordinate.
     *
     * @param float $slope The slope of the line.
     * @param float $yIntercept The y-intercept of the line.
     * @param float $x The x-coordinate of the point on the line.
     * @return float The y-coordinate of the point on the line corresponding to the given x-coordinate.
     */
    public static function lineInPlaneSlopeIntercept(float $slope, float $yIntercept, float $x): float
    {
        return $slope * $x + $yIntercept;
    }

    /**
     * Calculate the point on a line in 3D space given a point, direction vector, and parameter t.
     * @param float $pointX The x-coordinate of the point on the line.
     * @param float $pointY The y-coordinate of the point on the line.
     * @param float $pointZ The z-coordinate of the point on the line.
     * @param float $directionX The x-component of the direction vector of the line.
     * @param float $directionY The y-component of the direction vector of the line.
     * @param float $directionZ The z-component of the direction vector of the line.
     * @param float $t The parameter that determines the position along the line (t=0 gives the original point, t=1 gives the point at the direction vector, etc.).
     * @return array{x: float, y: float, z: float} An associative array containing the x, y, and z coordinates of the point on the line corresponding to the parameter t.
     */
    public static function lineInSpaceParametric(float $pointX,float $pointY,float $pointZ,float $directionX,float $directionY,float $directionZ,float $t): array {
        return [
            'x' => $pointX + $t * $directionX,
            'y' => $pointY + $t * $directionY,
            'z' => $pointZ + $t * $directionZ,
        ];
    }
}
?>
