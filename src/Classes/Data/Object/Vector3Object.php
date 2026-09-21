<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use ArrayAccess;
use function in_array;

/**
 * Class Vector3Object
 *
 * Represents a three-dimensional vector and provides methods for vector operations.
 */
class Vector3Object implements ArrayAccess
{
    #region Properties

    /**
     * The x component of the vector.
     *
     * @var int|float
     */
    public int|float $x;

    /**
     * The y component of the vector.
     *
     * @var int|float
     */
    public int|float $y;

    /**
     * The z component of the vector.
     *
     * @var int|float
     */
    public int|float $z;

    #region Function

    /**
     * Constructor for Vector3Object.
     *
     * @param int|float $x The x component of the vector.
     * @param int|float $y The y component of the vector.
     * @param int|float $z The z component of the vector.
     */
    public function __construct(int|float $x, int|float $y, int|float $z)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Negates the vector.
     *
     * @return Vector3Object The negated vector.
     */
    public function negate(): Vector3Object
    {
        return new self(
            -$this->x,
            -$this->y,
            -$this->z
        );
    }

    /**
     * Subtracts another vector from this vector.
     * 
     * @param Vector3Object $other The vector to subtract.
     * 
     * @return Vector3Object The resulting vector after subtraction.
     */
    public function substract(Vector3Object $other): Vector3Object
    {
        return new self(
            $this->x - $other->x,
            $this->y - $other->y,
            $this->z - $other->z
        );
    }

    /**
     * Clamps the vector components to a maximum of 1.0.
     *
     * @param Vector3Object $other The vector to clamp.
     * 
     * @return Vector3Object The clamped vector.
     */
    public function clamp(Vector3Object $other): Vector3Object
    {
        return new self(
            max(min($other->x, 1.0)),
            max(min($other->y, 1.0)),
            max(min($other->z, 1.0)),
        );
    }

    /**
     * Adds another vector to this vector.
     *
     * @param Vector3Object $other The vector to add.
     * 
     * @return Vector3Object The resulting vector after addition.
     */
    public function add(Vector3Object $other): Vector3Object
    {
        return new self(
            $this->x + $other->x,
            $this->y + $other->y,
            $this->z + $other->z
        );
    }

    /**
     * Calculates the length (magnitude) of the vector.
     *
     * @return float The length of the vector.
     */
    public function length(): float
    {
        return sqrt($this->x * $this->x + $this->y * $this->y + $this->z * $this->z);
    }

    /**
     * Calculates the squared length of the vector.
     *
     * @return float The squared length of the vector.
     */
    public function lengthSquart(): float|int
    {
        return ($this->x * $this->x + $this->y * $this->y + $this->z * $this->z);
    }

    /**
     * Calculates the dot product with another vector.
     *
     * @param Vector3Object $other The other vector.
     * 
     * @return float|int The dot product.
     */
    public function dotProduct(Vector3Object $other): float|int
    {
        return ($this->x * $other->x + $this->y * $other->y + $this->z * $other->z);
    }

    /**
     * Normalizes the vector to have a length of 1.
     *
     * @return Vector3Object The normalized vector.
     */
    public function normalize(): static
    {
        $length = $this->length();

        if ($length == 0) {
            trigger_error('Vector length is 0, returning without modifying components', E_USER_NOTICE);
            return $this;
        }

        $this->x = $this->x / $length;
        $this->y = $this->y / $length;
        $this->z = $this->z / $length;

        return $this;
    }

    /**
     * Calculates the cross product with another vector.
     *
     * @param Vector3Object $other The other vector.
     * 
     * @return Vector3Object The cross product vector.
     */
    public function crossProduct(Vector3Object $other): Vector3Object
    {
        return new Vector3Object(
            $this->y * $other->z - $this->z * $other->y,
            $this->z * $other->x - $this->x * $other->z,
            $this->x * $other->y - $this->y * $other->x
        );
    }

    /**
     * Scales the vector by a scalar value.
     *
     * @param int|float $number The scalar value to scale by.
     * 
     * @return Vector3Object The scaled vector.
     */
    public function scale(int|float $number): Vector3Object
    {
        return new Vector3Object(
            $this->x * $number,
            $this->y * $number,
            $this->z * $number
        );
    }

    /**
     * Divides the vector by a scalar value.
     *
     * @param int|float $number The scalar value to divide by.
     * 
     * @return Vector3Object The resulting vector after division.
     */
    public function divide(int|float $number): Vector3Object
    {
        return new Vector3Object(
            $this->x / $number,
            $this->y / $number,
            $this->z / $number
        );
    }

    /**
     * Rounds the vector components up to the nearest integer.
     *
     * @return Vector3Object The vector with rounded components.
     */
    public function ceil(): Vector3Object
    {
        return new Vector3Object(
            ceil($this->x),
            ceil($this->y),
            ceil($this->z)
        );
    }

    /**
     * Returns the absolute values of the vector components.
     *
     * @return Vector3Object The vector with absolute component values.
     */
    public function abs(): Vector3Object
    {
        return new Vector3Object(
            abs($this->x),
            abs($this->y),
            abs($this->z)
        );
    }

    /**
     * Rounds the vector components down to the nearest integer.
     *
     * @return Vector3Object The vector with floored components.
     */
    public function floor(): Vector3Object
    {
        return new Vector3Object(
            floor($this->x),
            floor($this->y),
            floor($this->z)
        );
    }

    /**
     * ArrayAccess implementation methods.
     * 
     * @param mixed $offset
     * 
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, ['x', 'y', 'z']);
    }

    /**
     * Get the value at a specific offset.
     *
     * @param mixed $offset
     * 
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        switch ($offset) {
            case 'x':
                return $this->x;
            case 'y':
                return $this->y;
            case 'z':
                return $this->z;
            default:
                return null;
        }
    }

    /**
     * Set the value at a specific offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * 
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        switch ($offset) {
            case 'x':
                $this->x = $value;
                break;
            case 'y':
                $this->y = $value;
                break;
            case 'z':
                $this->z = $value;
                break;
        }
    }

    /**
     * Unset the value at a specific offset.
     *
     * @param mixed $offset
     * 
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        switch ($offset) {
            case 'x':
                unset($this->x);
                break;
            case 'y':
                unset($this->y);
                break;
            case 'z':
                unset($this->z);
                break;
        }
    }

}