<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\AreaUnit;

class Area
{
    /**
     * Square meters per unit.
     *
     * Metric units are exact by SI definition.
     * Imperial units derived from 1 inch = 0.0254 m (exact).
     * 1 acre = 43 560 ft² (exact, US survey).
     */
    private const SQUARE_METERS_PER_UNIT = [
        AreaUnit::SQUARE_METER => 1.0,
        AreaUnit::SQUARE_KILOMETER => 1_000_000.0,
        AreaUnit::SQUARE_CENTIMETER => 0.000_1,
        AreaUnit::SQUARE_MILLIMETER => 0.000_001,
        AreaUnit::SQUARE_MICROMETER => 1.0e-12,
            // 1 mile = 1 609.344 m → 1 mi² = 1609.344² m²
        AreaUnit::SQUARE_MILE => 2_589_988.110_336,
            // 1 yard = 0.9144 m → 1 yd² = 0.9144² m²
        AreaUnit::SQUARE_YARD => 0.836_127_36,
            // 1 foot = 0.3048 m → 1 ft² = 0.3048² m²
        AreaUnit::SQUARE_FOOT => 0.092_903_04,
            // 1 inch = 0.0254 m → 1 in² = 0.0254² m²
        AreaUnit::SQUARE_INCH => 0.000_645_16,
            // 1 acre = 43 560 ft² × 0.092 903 04 m²/ft²
        AreaUnit::ACRE => 4_046.856_422_4,
        AreaUnit::HECTARE => 10_000.0,
    ];

    public static function getConversionFactor(string $from, string $to): ?float
    {
        if (!isset(self::SQUARE_METERS_PER_UNIT[$from], self::SQUARE_METERS_PER_UNIT[$to])) {
            return null;
        }

        if ($from === $to) {
            return 1.0;
        }

        return self::SQUARE_METERS_PER_UNIT[$from] / self::SQUARE_METERS_PER_UNIT[$to];
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
