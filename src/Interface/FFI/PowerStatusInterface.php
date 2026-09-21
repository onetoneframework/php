<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

/**
 * Power Status Interface
 *
 * Defines the structure for system power status information.
 * Contains battery and power management information for Windows systems.
 */
abstract class PowerStatusInterface
{
    /**
     * @var int Battery status flag.
     */
    public $BatteryFlag;

    /**
     * @var int Full battery life time in seconds.
     */
    public $BatteryFullLifeTime;

    /**
     * @var int Battery life percentage (0-100).
     */
    public $BatteryLifePercent;

    /**
     * @var int Remaining battery life time in seconds.
     */
    public $BatteryLifeTime;

    /**
     * The battery saver flag, named as the C struct names it.
     *
     * PowerSavingFlag stood here, and SYSTEM_POWER_STATUS has no such member -
     * so the shape described a field that could never be read.
     *
     * @var int 1 when battery saver is on, 0 otherwise.
     */
    public $SystemStatusFlag;
}