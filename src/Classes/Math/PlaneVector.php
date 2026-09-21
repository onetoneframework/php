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
 * Class PlaneVector
 *
 * A class representing a 2D vector in a plane with basic vector operations.
 */
class PlaneVector
{
    // The x and y components of the vector
    public float $x;
    public float $y;

    /**
     * PlaneVector constructor.
     *
     * @param float $x The x-component of the vector.
     * @param float $y The y-component of the vector.
     */
    public function __construct(float $x, float $y)
    {
        $this->x = $x;
        $this->y = $y;
    }

    /**
     * Calculate the magnitude (length) of the vector.
     *
     * @return float The magnitude of the vector.
     */
    public function magnitude(): float
    {
        return sqrt(pow($this->x, 2) + pow($this->y, 2));
    }

    /**
     * Add another PlaneVector to this vector.
     *
     * @param PlaneVector $other The vector to add to this vector.
     * @return PlaneVector A new PlaneVector that is the result of the addition.
     */
    public function add(PlaneVector $other): PlaneVector
    {
        return new PlaneVector($this->x + $other->x, $this->y + $other->y);
    }

    /**
     * Subtract another PlaneVector from this vector.
     *
     * @param PlaneVector $other The vector to subtract from this vector.
     * @return PlaneVector A new PlaneVector that is the result of the subtraction.
     */
    public function subtract(PlaneVector $other): PlaneVector
    {
        return new PlaneVector($this->x - $other->x, $this->y - $other->y);
    }

    /**
     * Multiply the vector by a scalar value.
     *
     * @param float $scalar The scalar value to multiply the vector by.
     * @return PlaneVector A new PlaneVector that is the result of the scalar multiplication.
     */
    public function scalarMultiply(float $scalar): PlaneVector
    {
        return new PlaneVector($this->x * $scalar, $this->y * $scalar);
    }

    /**
     * Calculate the dot product of this vector with another vector.
     *
     * @param PlaneVector $other The other vector to compute the dot product with.
     * @return float The dot product of the two vectors.
     */
    public function dotProduct(PlaneVector $other): float
    {
        return $this->x * $other->x + $this->y * $other->y;
    }

    /**
     * Calculate the cross product (also known as the 2D pseudo-cross product) of this vector with another vector.
     * The cross product in 2D can be represented as a scalar value that indicates the magnitude of the vector perpendicular to the plane defined by the two vectors.
     * @param PlaneVector $other The other vector to compute the cross product with.
     * @return float The scalar value representing the cross product of the two vectors.
     */
    public function crossProduct2D(PlaneVector $other): float
    {
        return $this->x * $other->y - $this->y * $other->x;
    }
}
