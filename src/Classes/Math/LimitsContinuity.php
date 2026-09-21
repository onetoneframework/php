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
 * Class LimitsContinuity
 *
 * A utility class for limits and continuity calculations.
 */
class LimitsContinuity
{
    /**
     * Approach a value to a target value within a small epsilon range.
     *
     * @param float $x The value to be approached.
     * @param float $target The target value to approach.
     * @return float The approached value, which is equal to the target if within epsilon, otherwise returns the original value.
     */
    public static function approachValue(float $x, float $target): float
    {
        $epsilon = 1e-6;
        if (abs($x - $target) < $epsilon) {
            return $target;
        }

        return $x;
    }
}
