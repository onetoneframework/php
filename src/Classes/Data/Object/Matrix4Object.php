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

/**
 * Class Matrix4Object
 *
 * Represents a 4x4 matrix and provides methods for matrix operations.
 */
class Matrix4Object implements ArrayAccess
{
    private $data;

    /**
     * Constructor for Matrix4Object.
     *
     * @param array|null $data A 4x4 array representing the matrix. If null, initializes to the identity matrix.
     * 
     * @return void
     */
    public function __construct(?array $data = null)
    {
        if ($data == null) {
            $data = [
                [1.0, 0.0, 0.0, 0.0],
                [0.0, 1.0, 0.0, 0.0],
                [0.0, 0.0, 1.0, 0.0],
                [0.0, 0.0, 0.0, 1.0],
            ];
        }

        $this->data = $data;
    }

    /**
     * Multiplies this matrix by another Matrix4Object.
     *
     * @param Matrix4Object $other The other matrix to multiply with.
     * 
     * @return Matrix4Object The resulting matrix after multiplication.
     */
    public function multiply(Matrix4Object $other): Matrix4Object
    {
        $result = [
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
            [0, 0, 0, 0],
        ];
        $otherArray = $other->toArray();

        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                for ($k = 0; $k < 4; $k++) {
                    $result[$i][$j] += $this->data[$i][$k] * $otherArray[$k][$j];
                }
            }
        }

        return new Matrix4Object($result);
    }

    /**
     * Scales the matrix by a scalar or a Vector3Object.
     *
     * @param mixed $value The scalar value or Vector3Object to scale by.
     * 
     * @return Matrix4Object The scaled matrix.
     */
    public function scale(mixed $value): Matrix4Object
    {
        if ($value instanceof Vector3Object || is_numeric($value)) {
            return new Matrix4Object([
                [$this->data[0][0] * $value, $this->data[0][1] * $value, $this->data[0][2] * $value, $this->data[0][3] * $value],
                [$this->data[1][0] * $value, $this->data[1][1] * $value, $this->data[1][2] * $value, $this->data[1][3] * $value],
                [$this->data[2][0] * $value, $this->data[2][1] * $value, $this->data[2][2] * $value, $this->data[2][3] * $value],
                [$this->data[3][0], $this->data[3][1], $this->data[3][2], $this->data[3][3]]
            ]);
        } else {
            return new Matrix4Object([
                [$this->data[0][0] * $value, $this->data[0][1] * $value, $this->data[0][2] * $value, $this->data[0][3] * $value],
                [$this->data[1][0] * $value, $this->data[1][1] * $value, $this->data[1][2] * $value, $this->data[1][3] * $value],
                [$this->data[2][0] * $value, $this->data[2][1] * $value, $this->data[2][2] * $value, $this->data[2][3] * $value],
                [$this->data[3][0] * $value, $this->data[3][1] * $value, $this->data[3][2] * $value, $this->data[3][3] * $value]
            ]);
        }
    }

    /**
     * Returns the matrix as a 4x4 array.
     *
     * @return array The 4x4 array representing the matrix.
     */
    public function toArray(): mixed
    {
        return $this->data;
    }

    /**
     * Returns the string representation of the matrix.
     *
     * @return string The string representation.
     */
    public function __toString(): string
    {
        return sprintf(
            'Matrix4Objectx4((%f, %f, %f, %f), (%f, %f, %f, %f), (%f, %f, %f, %f), (%f, %f, %f, %f))',
            $this->data[0][0],
            $this->data[0][1],
            $this->data[0][2],
            $this->data[0][3],
            $this->data[1][0],
            $this->data[1][1],
            $this->data[1][2],
            $this->data[1][3],
            $this->data[2][0],
            $this->data[2][1],
            $this->data[2][2],
            $this->data[2][3],
            $this->data[3][0],
            $this->data[3][1],
            $this->data[3][2],
            $this->data[3][3]
        );
    }

    /**
     * Check if an offset exists in the matrix.
     *
     * @param mixed $offset
     * 
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return ($offset >= 0 && $offset <= 3);
    }

    /**
     * Get the value at a specific offset in the matrix.
     *
     * @param mixed $offset
     * 
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset];
    }

    /**
     * Set the value at a specific offset in the matrix.
     *
     * @param mixed $offset
     * @param mixed $value
     * 
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->data[$offset] = $value;
    }

    /**
     * Unset the value at a specific offset in the matrix.
     *
     * @param mixed $offset
     * 
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
}