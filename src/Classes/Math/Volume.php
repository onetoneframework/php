<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\VolumeUnit;

/**
 * Class Volume
 *
 * A utility class for volume unit conversion.
 *
 * All conversions are derived from a single source-of-truth table that maps
 * every VolumeUnit constant to its equivalent value in liters.
 * The conversion factor from unit A to unit B is computed as:
 *
 *   factor = liters_per_A / liters_per_B
 *
 * This design means adding a new unit only requires one new entry in
 * LITERS_PER_UNIT; no per-pair case statements are needed.
 *
 * @package Clover\Classes\Math
 */
class Volume
{
    /**
     * How many liters are contained in exactly 1 of each VolumeUnit.
     *
     * Reference values:
     *   Metric/SI units are exact by definition.
     *   US customary units are based on the US liquid gallon = 3.785 411 784 L (exact, since 1964).
     *   Imperial units are based on the imperial gallon    = 4.546 09 L        (exact, since 1985).
     *   Cubic imperial/US-survey units are derived from the international inch  = 25.4 mm (exact).
     *
     * @var array<string, float>
     */
    private const LITERS_PER_UNIT = [
            // ── Cubic SI / metric ────────────────────────────────────────────────
        VolumeUnit::CUBIC_KILOMETER => 1_000_000_000_000.0,    // 1 km³  = 10^12 L
        VolumeUnit::CUBIC_METER => 1_000.0,                 // 1 m³   = 1 000 L
        VolumeUnit::CUBIC_CENTIMETER => 0.001,                   // 1 cm³  = 1 mL
        VolumeUnit::CUBIC_MILLIMETER => 0.000_001,               // 1 mm³  = 0.001 mL

            // ── Metric volume ─────────────────────────────────────────────────────
        VolumeUnit::LITER => 1.0,
        VolumeUnit::MILLILITER => 0.001,

            // ── Cubic imperial / US customary ─────────────────────────────────────
            // 1 mile  = 1 609.344 m  → 1 mi³ = 1609.344³ m³ × 1000 L/m³
        VolumeUnit::CUBIC_MILE => 4_168_181_825_440.579,
            // 1 yard  = 0.9144 m     → 1 yd³ = 0.9144³ × 1000 L
        VolumeUnit::CUBIC_YARD => 764.554_857_984,
            // 1 foot  = 0.3048 m     → 1 ft³ = 0.3048³ × 1000 L
        VolumeUnit::CUBIC_FOOT => 28.316_846_592,
            // 1 inch  = 0.0254 m     → 1 in³ = 0.0254³ × 1000 L
        VolumeUnit::CUBIC_INCH => 0.016_387_064,

            // ── US customary volume ───────────────────────────────────────────────
            // US gallon = 231 in³ = 3.785 411 784 L (exact)
        VolumeUnit::US_GALLON => 3.785_411_784,
            // 1 US quart  = 1/4  US gallon
        VolumeUnit::US_QUART => 0.946_352_946,
            // 1 US pint   = 1/8  US gallon
        VolumeUnit::US_PINT => 0.473_176_473,
            // 1 US cup    = 1/16 US gallon
        VolumeUnit::US_CUP => 0.236_588_236_5,
            // 1 US fl oz  = 1/128 US gallon
        VolumeUnit::US_FLUID_OUNCE => 0.029_573_529_6,
            // 1 US tbsp   = 1/256 US gallon
        VolumeUnit::US_TABLE_SPOON => 0.014_786_764_781_25,
            // 1 US tsp    = 1/768 US gallon
        VolumeUnit::US_TEA_SPOON => 0.004_928_921_593_75,

            // ── Imperial volume ───────────────────────────────────────────────────
            // Imperial gallon = 4.546 09 L (exact)
        VolumeUnit::IMPERIAL_GALLON => 4.546_09,
            // 1 imperial quart = 1/4  imperial gallon
        VolumeUnit::IMPERIAL_QUART => 1.136_522_5,
            // 1 imperial pint  = 1/8  imperial gallon
        VolumeUnit::IMPERIAL_PINT => 0.568_261_25,
            // 1 imperial cup   = 1/16 imperial gallon
        VolumeUnit::IMPERIAL_CUP => 0.284_130_625,
            // 1 imperial fl oz = 1/160 imperial gallon
        VolumeUnit::IMPERIAL_FLUID_OUNCE => 0.028_413_062_5,
            // 1 imperial tbsp  = 1/160/16 × imperial gallon  (= 3 imperial tsp)
        VolumeUnit::IMPERIAL_TABLE_SPOON => 0.017_758_164_062_5,
            // 1 imperial tsp   = 1/4800 imperial gallon
        VolumeUnit::IMPERIAL_TEA_SPOON => 0.005_919_388_020_833,
    ];

    /**
     * Return the multiplication factor to convert a value from $from to $to.
     *
     * Usage example:
     *   $liters = $gallons * Volume::getConversionFactor(VolumeUnit::US_GALLON, VolumeUnit::LITER);
     *
     * Returns null when either unit is not recognised.
     * Returns 1.0 when $from === $to.
     *
     * @param string $from Source volume unit (a VolumeUnit constant).
     * @param string $to   Target volume unit (a VolumeUnit constant).
     *
     * @return float|null The conversion factor, or null if a unit is unknown.
     */
    public static function getConversionFactor(string $from, string $to): float|null
    {
        if (!isset(self::LITERS_PER_UNIT[$from], self::LITERS_PER_UNIT[$to])) {
            return null;
        }

        if ($from === $to) {
            return 1.0;
        }

        // factor = (liters in 1 $from) / (liters in 1 $to)
        return self::LITERS_PER_UNIT[$from] / self::LITERS_PER_UNIT[$to];
    }

    /**
     * Convert a volume value from one unit to another.
     *
     * @param float  $value The quantity to convert.
     * @param string $from  Source volume unit (a VolumeUnit constant).
     * @param string $to    Target volume unit (a VolumeUnit constant).
     *
     * @return float|null The converted value, or null if a unit is unknown.
     */
    public static function convert(float $value, string $from, string $to): float|null
    {
        $factor = self::getConversionFactor($from, $to);

        if ($factor === null) {
            return null;
        }

        return $value * $factor;
    }
}
