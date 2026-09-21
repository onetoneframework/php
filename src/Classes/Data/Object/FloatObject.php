<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\BaseObject as BaseObject;
use const PHP_FLOAT_EPSILON;

/**
 * Class FloatObject
 *
 * Represents a floating-point number and provides methods for float operations.
 */
#[\AllowDynamicProperties]
class FloatObject extends BaseObject
{
    protected $rawData;
    
    /**
     * Constructor to initialize the FloatObject with a float value.
     *
     * @param float $data The float value to be stored.
     */
    public function __construct(float $data)
    {
        $this->rawData = $data;
    }

    /**
     * Check if the float value is equal to another float within precision.
     *
     * @param float $compare The float value to compare with.
     * @return bool True if equal within precision, false otherwise.
     */
    public function isEqualsWithinPrecision(float $compare): bool
    {
        return abs($this->rawData - $compare) < PHP_FLOAT_EPSILON;
    }
}