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
 * Class SpaceVector
 *
 * A class representing a vector in 3D space with common vector operations.
 */
class SpaceVector
{
    public float $x;
    public float $y;
    public float $z;

    /**
     * SpaceVector constructor.
     *
     * @param float $x The x-coordinate of the vector.
     * @param float $y The y-coordinate of the vector.
     * @param float $z The z-coordinate of the vector.
     */
    public function __construct(float $x, float $y, float $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Calculate the magnitude (length) of the vector.
     * @return float The magnitude of the vector.
     */
    public function magnitude(): float
    {
        return sqrt(pow($this->x, 2) + pow($this->y, 2) + pow($this->z, 2));
    }

    /**
     * Calculate the distance between this vector and another vector.
     * @param SpaceVector $other The other vector to calculate the distance to.
     * @return float The distance between the two vectors.
     */
    public function add(SpaceVector $other): SpaceVector
    {
        return new SpaceVector($this->x + $other->x, $this->y + $other->y, $this->z + $other->z);
    }

    /**
     * Subtract another vector from this vector.
     * @param SpaceVector $other The other vector to subtract.
     * @return SpaceVector The resulting vector after subtraction.
     */
    public function subtract(SpaceVector $other): SpaceVector
    {
        return new SpaceVector($this->x - $other->x, $this->y - $other->y, $this->z - $other->z);
    }

    /**
     * Multiply this vector by a scalar value.
     * @param float $scalar The scalar value to multiply the vector by.
     * @return SpaceVector The resulting vector after scalar multiplication.
     */
    public function scalarMultiply(float $scalar): SpaceVector
    {
        return new SpaceVector($this->x * $scalar, $this->y * $scalar, $this->z * $scalar);
    }

    /**
     * Calculate the dot product of this vector with another vector.
     * @param SpaceVector $other The other vector to calculate the dot product with.
     * @return float The dot product of the two vectors.
     */
    public function dotProduct(SpaceVector $other): float
    {
        return $this->x * $other->x + $this->y * $other->y + $this->z * $other->z;
    }

    /**
     * Calculate the cross product of this vector with another vector.
     * @param SpaceVector $other The other vector to calculate the cross product with.
     * @return SpaceVector The resulting vector after calculating the cross product.
     */
    public function crossProduct(SpaceVector $other): SpaceVector
    {
        return new SpaceVector(
            $this->y * $other->z - $this->z * $other->y,
            $this->z * $other->x - $this->x * $other->z,
            $this->x * $other->y - $this->y * $other->x
        );
    }
}
