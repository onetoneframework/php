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

/**
 * Class Velocity
 *
 * A utility class for velocity-related mathematical operations.
 */
class Velocity
{
    /**
     * Get the terminal velocity of a falling object.
     * 
     * v = the square root of ((2*m*g)/(ρ*A*C)).
     * 
     * @param float $mass (m) mass of the falling object
     * @param float $dragCoefficient (C) the drag coefficient
     * @param float $area (A) the projected area of the object
     * @param float $fluidDensity (ρ) the density of the fluid the object is falling through
     * @param float $gravity (g) the acceleration due to gravity
     * 
     * @throws InvalidArgumentException
     * 
     * @return float
     */
    public static function getTerminalVelocity(float $mass, float $dragCoefficient, float $area, float $fluidDensity = 1.225, float $gravity = 9.81): float
    {
        if ($mass <= 0 || $dragCoefficient <= 0 || $area <= 0 || $fluidDensity <= 0 || $gravity <= 0) {
            throw new InvalidArgumentException("All parameters must be positive numbers.");
        }

        return sqrt((2 * $mass * $gravity) / ($fluidDensity * $dragCoefficient * $area));
    }
}