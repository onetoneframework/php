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
 * Class Scalar
 *
 * A class representing a scalar value with basic arithmetic operations.
 */
class Scalar
{
    public float $value;

    /**
     * Scalar constructor.
     *
     * @param float $value The scalar value.
     */
    public function __construct(float $value)
    {
        $this->value = $value;
    }

    /**
     * Add another scalar to this scalar.
     *
     * @param Scalar $other The scalar to add to this scalar.
     * @return Scalar The result of the addition.
     */
    public function add(Scalar $other): Scalar
    {
        return new Scalar($this->value + $other->value);
    }

    /**
     * Subtract another scalar from this scalar.
     *
     * @param Scalar $other The scalar to subtract from this scalar.
     * @return Scalar The result of the subtraction.
     */
    public function subtract(Scalar $other): Scalar
    {
        return new Scalar($this->value - $other->value);
    }

    /**
     * Multiply this scalar by another scalar.
     *
     * @param Scalar $other The scalar to multiply with.
     * @return Scalar The result of the multiplication.
     */
    public function multiply(Scalar $other): Scalar
    {
        return new Scalar($this->value * $other->value);
    }

    /**
     * Divide this scalar by another scalar.
     *
     * @param Scalar $other The scalar to divide by.
     * @return Scalar|null The result of the division, or null if division by zero occurs.
     */
    public function divide(Scalar $other): ?Scalar
    {
        if ($other->value === 0.0) {
            return null;
        }

        return new Scalar($this->value / $other->value);
    }
}
