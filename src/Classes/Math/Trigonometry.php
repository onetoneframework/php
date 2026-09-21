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
 * Class Trigonometry
 *
 * A utility class for trigonometric mathematical operations.
 */
class Trigonometry
{
    /**
     * Convert degrees to radians.
     *
     * @param float $degrees
     * 
     * @return float
     */
    public static function degreesToRadians(float $degrees): float
    {
        return deg2rad($degrees);
    }

    /**
     * Convert radians to degrees.
     *
     * @param float $radians
     * 
     * @return float
     */
    public static function radiansToDegrees(float $radians): float
    {
        return rad2deg($radians);
    }

    /**
     * Calculate the sine of an angle in radians.
     *
     * @param float $radians
     * 
     * @return float
     */
    public static function sin(float $radians): float
    {
        return sin($radians);
    }

    /**
     * Calculate the cosine of an angle in radians.
     *
     * @param float $radians
     * 
     * @return float
     */
    public static function cos(float $radians): float
    {
        return cos($radians);
    }

    /**
     * Calculate the tangent of an angle in radians.
     *
     * @param float $radians
     * 
     * @return float
     */
    public static function tan(float $radians): float
    {
        return tan($radians);
    }

    /**
     * Calculate the arcsine of a value.
     *
     * @param float $value
     * 
     * @return float
     */
    public static function asin(float $value): float
    {
        return asin($value);
    }

    /**
     * Calculate the arccosine of a value.
     *
     * @param float $value
     * 
     * @return float
     */
    public static function acos(float $value): float
    {
        return acos($value);
    }

    /**
     * Calculate the arctangent of a value.
     *
     * @param float $value
     * 
     * @return float
     */
    public static function atan(float $value): float
    {
        return atan($value);
    }

    /**
     * Calculate the arctangent of the quotient of its arguments.
     *
     * @param float $y
     * @param float $x
     * 
     * @return float
     */
    public static function atan2(float $y, float $x): float
    {
        return atan2($y, $x);
    }

}
