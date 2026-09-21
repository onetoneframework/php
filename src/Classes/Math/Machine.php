<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use const M_PI;

/**
 * Class Machine
 *
 * A utility class for machine-related mathematical calculations.
 */
class Machine
{

    /**
     * Calculate cutting speed of milling
     * 
     * @param int $rpm Spindle Speed
     * @param int $mm Effective diameter
     * 
     * @return float|int
     */
    public static function calculateMillingCuttingSpeed(int $rpm, int $mm): float|int
    {
        return ($rpm * M_PI * $mm) / 1000;
    }

    /**
     * Calculate spindle speed
     * 
     * @param int $rpm Spindle Speed
     * @param int $mm Effective diameter
     * 
     * @return float
     */
    public static function calculateMillingSpindleSpeed(int $rpm, int $mm): float
    {
        return ceil((1000 * $rpm) / (M_PI * $mm));
    }

    /**
     * Calculate feed per tooth
     * 
     * @param int $rpm Spindle Speed
     * @param int $tableFee
     * @param int $numberOfTooth Number of tooth
     * 
     * @return float
     */
    public static function calculateMillingFeedPerTooth(int $rpm, int $tableFee, int $numberOfTooth): float
    {
        return $tableFee / ($rpm * $numberOfTooth);
    }

    /**
     * Calculate table pee
     * 
     * @param int $rpm Spindle Speed
     * @param int $feedPerTooth Feed per tooth
     * @param int $numberOfTooth Number of tooth
     * 
     * @return float|int
     */
    public static function calculateMillingTablePee(int $rpm, float $feedPerTooth, int $numberOfTooth): float|int
    {
        return $rpm * $numberOfTooth * $feedPerTooth;
    }
}
