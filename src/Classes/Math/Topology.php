<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function count;

/**
 * Class Topology
 *
 * A utility class for topology-related mathematical operations.
 */
class Topology
{
    /**
     * Check if a point is on the boundary of a given shape.
     *
     * @param array $point The coordinates of the point to check.
     * @param array $boundaryPoint The coordinates of the boundary point to compare against.
     * @param float $tolerance The tolerance for determining if the point is on the boundary (default is 1e-6).
     * @return bool True if the point is on the boundary, false otherwise.
     */
    public static function isOnBoundary(array $point, array $boundaryPoint, float $tolerance = 1e-6): bool
    {
        return self::areConnected($point, $boundaryPoint, $tolerance);
    }

    /**
     * Check if two points are connected within a specified tolerance.
     *
     * @param array $point1 The coordinates of the first point.
     * @param array $point2 The coordinates of the second point.
     * @param float $tolerance The tolerance for determining if the points are connected (default is 1e-6).
     * @return bool True if the points are connected, false otherwise.
     */
    public static function areConnected(array $point1, array $point2, float $tolerance = 1e-6): bool
    {
        if (count($point1) !== count($point2)) {
            return false;
        }

        foreach (array_map(null, $point1, $point2) as [$coord1, $coord2]) {
            if (abs($coord1 - $coord2) > $tolerance) {
                return false;
            }
        }
        
        return true;
    }
}
