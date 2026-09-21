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
 * Class ConicSections
 *
 * A utility class for conic section equations.
 */
class ConicSections
{
    /**
     * Calculate the value of the circle equation for a given point (x, y) and parameters (a, b, r).
     * The circle is centered at (a, b) with radius r.
     * @param float $x The x-coordinate of the point to evaluate.
     * @param float $y The y-coordinate of the point to evaluate.
     * @param float $a The x-coordinate of the center of the circle.
     * @param float $b The y-coordinate of the center of the circle.
     * @param float $r The radius of the circle.
     * @return float The value of the circle equation at the point (x, y). A value of 0 indicates the point is on the circle, a positive value indicates the point is outside the circle, and a negative value indicates the point is inside the circle.
     */
    public static function circleEquation(float $x, float $y, float $a, float $b, float $r): float
    {
        return pow($x - $a, 2) + pow($y - $b, 2) - pow($r, 2);
    }

    /**
     * Calculate the value of the ellipse equation for a given point (x, y) and parameters (h, k, a, b).
     * The ellipse is centered at (h, k) with semi-major axis a and semi-minor axis b.
     * @param float $x The x-coordinate of the point to evaluate.
     * @param float $y The y-coordinate of the point to evaluate.
     * @param float $h The x-coordinate of the center of the ellipse.
     * @param float $k The y-coordinate of the center of the ellipse.
     * @param float $a The length of the semi-major axis of the ellipse.
     * @param float $b The length of the semi-minor axis of the ellipse.
     * @return float The value of the ellipse equation at the point (x, y). A value of 0 indicates the point is on the ellipse, a positive value indicates the point is outside the ellipse, and a negative value indicates the point is inside the ellipse.
     */
    public static function ellipseEquation(float $x, float $y, float $h, float $k, float $a, float $b): float
    {
        return pow(($x - $h) / $a, 2) + pow(($y - $k) / $b, 2) - 1;
    }

    /**
     * Calculate the value of the parabola equation for a given point (x, y) and parameters (h, k, p).
     * The parabola is centered at (h, k) and opens along the x-axis with a focal distance of p.
     * @param float $y The y-coordinate of the point to evaluate.
     * @param float $k The y-coordinate of the vertex of the parabola.
     * @param float $p The focal distance of the parabola (the distance from the vertex to the focus).
     * @param float $x The x-coordinate of the point to evaluate.
     * @param float $h The x-coordinate of the vertex of the parabola.
     * @return float The value of the parabola equation at the point (x, y). A value of 0 indicates the point is on the parabola, a positive value indicates the point is outside the parabola, and a negative value indicates the point is inside the parabola.
     */
    public static function parabolaEquationXAxis(float $y, float $k, float $p, float $x, float $h): float
    {
        return pow($y - $k, 2) - 4 * $p * ($x - $h);
    }

    /**
     * Calculate the value of the hyperbola equation for a given point (x, y) and parameters (h, k, a, b).
     * The hyperbola is centered at (h, k) with semi-major axis a and semi-minor axis b, and opens along the x-axis.
     * @param float $x The x-coordinate of the point to evaluate.
     * @param float $y The y-coordinate of the point to evaluate.
     * @param float $h The x-coordinate of the center of the hyperbola.
     * @param float $k The y-coordinate of the center of the hyperbola.
     * @param float $a The length of the semi-major axis of the hyperbola.
     * @param float $b The length of the semi-minor axis of the hyperbola.
     * @return float The value of the hyperbola equation at the point (x, y). A value of 0 indicates the point is on the hyperbola, a positive value indicates the point is outside the hyperbola, and a negative value indicates the point is inside the hyperbola.
     */
    public static function hyperbolaEquationXAxis(float $x, float $y, float $h, float $k, float $a, float $b): float
    {
        return pow(($x - $h) / $a, 2) - pow(($y - $k) / $b, 2) - 1;
    }
}
