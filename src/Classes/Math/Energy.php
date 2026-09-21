<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\EnergyUnit;

class Energy
{
    /**
     * Joules per unit.
     *
     * 1 Wh  = 3 600 J (exact).
     * 1 kWh = 3 600 000 J (exact).
     * 1 kcal (thermochemical) = 4 184 J (exact).
     * 1 BTU (ISO) = 1 055.055 852 62 J.
     */
    private const JOULES_PER_UNIT = [
		EnergyUnit::JOULE => 1.0,
		EnergyUnit::KILOJOULE => 1_000.0,
		EnergyUnit::WATT_HOUR => 3_600.0,
		EnergyUnit::KILOWATT_HOUR => 3_600_000.0,
		EnergyUnit::KILOCALORIE => 4_184.0,
		EnergyUnit::BTU => 1_055.055_852_62,
    ];

    public static function getConversionFactor(string $from, string $to): ?float
    {
        if (!isset(self::JOULES_PER_UNIT[$from], self::JOULES_PER_UNIT[$to])) {
            return null;
        }

        if ($from === $to) {
            return 1.0;
        }

        return self::JOULES_PER_UNIT[$from] / self::JOULES_PER_UNIT[$to];
    }

    public static function convert(float $value, string $from, string $to): ?float
    {
        $factor = self::getConversionFactor($from, $to);

        if ($factor === null) {
            return null;
        }

        return $value * $factor;
    }
}
