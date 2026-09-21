<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\LengthUnit;

/**
 * Utility class for length unit normalization and conversion.
 */
class Length
{
    /**
     * Normalize a free-form length unit string to a LengthUnit constant value.
     *
     * Accepts common abbreviations, full English names, British spellings, and
     * plural forms. Comparison is case-insensitive and trims surrounding
     * whitespace. Returns LengthUnit::METERS when the input is not recognized.
     *
     * @param string $unit  Raw unit string supplied by the caller.
     * @return string       A LengthUnit constant value.
     */
    public static function normalize(string $unit): string
    {
        switch (strtolower(trim($unit))) {
            case 'am':
            case 'attometer':
            case 'attometers':
            case 'attometre':
            case 'attometres':
                return LengthUnit::ATTOMETER;
            case 'fm':
            case 'femtometer':
            case 'femtometers':
            case 'femtometre':
            case 'femtometres':
                return LengthUnit::FEMTOMETER;
            case 'pm':
            case 'picometer':
            case 'picometers':
            case 'picometre':
            case 'picometres':
                return LengthUnit::PICOMETER;
            case 'å':
            case 'angstrom':
            case 'angstroms':
                return LengthUnit::ANGSTROM;
            case 'nm':
            case 'nanometer':
            case 'nanometers':
            case 'nanometre':
            case 'nanometres':
                return LengthUnit::NANOMETER;
            case 'um':
            case 'µm':
            case 'micrometer':
            case 'micrometers':
            case 'micrometre':
            case 'micrometres':
            case 'micron':
            case 'microns':
                return LengthUnit::MICROMETER;
            case 'mm':
            case 'millimeter':
            case 'millimeters':
            case 'millimetre':
            case 'millimetres':
            case 'milimeter':
            case 'milimeters':
                return LengthUnit::MILIMETERS;
            case 'cm':
            case 'centimeter':
            case 'centimeters':
            case 'centimetre':
            case 'centimetres':
                return LengthUnit::CENTIMETERS;
            case 'dm':
            case 'decimeter':
            case 'decimeters':
            case 'decimetre':
            case 'decimetres':
                return LengthUnit::DECIMETER;
            case 'm':
            case 'meter':
            case 'meters':
            case 'metre':
            case 'metres':
                return LengthUnit::METERS;
            case 'dam':
            case 'decameter':
            case 'decameters':
            case 'decametre':
            case 'decametres':
            case 'dekameter':
            case 'dekameters':
            case 'dekametre':
            case 'dekametres':
                return LengthUnit::DECAMETERS;
            case 'hm':
            case 'hectometer':
            case 'hectometers':
            case 'hectometre':
            case 'hectometres':
                return LengthUnit::HECTOMETERS;
            case 'km':
            case 'kilometer':
            case 'kilometers':
            case 'kilometre':
            case 'kilometres':
                return LengthUnit::KILLOMETERS;
            case 'megameter':
            case 'megameters':
            case 'megametre':
            case 'megametres':
                return LengthUnit::MEGAMETERS;
            case 'gm':
            case 'gigameter':
            case 'gigameters':
            case 'gigametre':
            case 'gigametres':
                return LengthUnit::GIGAMETERS;
            case 'tm':
            case 'terameter':
            case 'terameters':
            case 'terametre':
            case 'terametres':
                return LengthUnit::TERAMETERS;
            case 'petameter':
            case 'petameters':
            case 'petametre':
            case 'petametres':
                return LengthUnit::PETAMETERS;
            case 'exameter':
            case 'exameters':
            case 'exametre':
            case 'exametres':
                return LengthUnit::EXAMETERS;
            case 'zm':
            case 'zettameter':
            case 'zettameters':
            case 'zettametre':
            case 'zettametres':
                return LengthUnit::ZETTAMETERS;
            case 'ym':
            case 'yottameter':
            case 'yottameters':
            case 'yottametre':
            case 'yottametres':
            case 'yotameter':
            case 'yotameters':
                return LengthUnit::YOTAMETERS;
            case 'au':
            case 'ua':
            case 'astronomical':
            case 'astronomical unit':
            case 'astronomical units':
                return LengthUnit::ASTRONOMICAL;
            case 'ly':
            case 'lightyear':
            case 'lightyears':
            case 'light year':
            case 'light years':
            case 'light-year':
            case 'light-years':
                return LengthUnit::LIGHTYEAR;
            case 'pc':
            case 'parsec':
            case 'parsecs':
                return LengthUnit::PARSEC;
            case 'in':
            case '"':
            case 'inch':
            case 'inches':
                return LengthUnit::INCHES;
            case "'":
            case 'ft':
            case 'foot':
            case 'feet':
                return LengthUnit::FEET;
            case 'yd':
            case 'yard':
            case 'yards':
                return LengthUnit::YARDS;
            case 'mi':
            case 'mile':
            case 'miles':
                return LengthUnit::MILES;
            case 'nmi':
            case 'nautical mile':
            case 'nautical miles':
                return LengthUnit::NAUTICAL_MILES;
            case 'fur':
            case 'furlong':
            case 'furlongs':
                return LengthUnit::FURLONG;
            case 'megafurlong':
            case 'megafurlongs':
                return LengthUnit::MEGAFURLONG;
            case 'ch':
            case 'chain':
            case 'chains':
                return LengthUnit::CGAUB;
            case 'lea':
            case 'league':
            case 'leagues':
                return LengthUnit::LEAGUE;
            case 'ftm':
            case 'fathom':
            case 'fathoms':
                return LengthUnit::FTM;
            case 'marathon':
            case 'marathons':
                return LengthUnit::MARATHON;
            case 'half marathon':
            case 'half-marathon':
            case 'half marathons':
            case 'half-marathons':
                return LengthUnit::HALF_MARATHON;
            case 'smoots':
                return LengthUnit::SMOOT;
            case 'qbit':
            case 'qbits':
                return LengthUnit::QBIT;
            default:
                return LengthUnit::METERS;
        }
    }

    /**
     * Get the conversion factor from one length unit to another.
     *
     * Returns null when either unit constant is not recognized.
     *
     * @param string $from  Source unit (a LengthUnit constant value).
     * @param string $to    Target unit (a LengthUnit constant value).
     * @return float|int|null  Multiplication factor, or null on error.
     */
    public static function getConversionFactor(string $from, string $to): float|int|null
    {
        if ($from === $to) {
            return 1;
        }

        $fromMeters = self::toMeters($from);
        $toMeters   = self::toMeters($to);

        if ($fromMeters === null || $toMeters === null || $toMeters == 0.0) {
            return null;
        }

        return $fromMeters / $toMeters;
    }

    /**
     * Return the number of meters that correspond to one unit of $unit.
     *
     * All physical values are exact definitions where available (SI, international
     * yard-and-pound treaty, etc.) or best-practice constants otherwise.
     *
     * Returns null for any unrecognized unit constant, which propagates as a
     * null return from getConversionFactor().
     *
     * @param string $unit  A LengthUnit constant value.
     * @return float|null   Meters per one unit, or null if unrecognized.
     */
    private static function toMeters(string $unit): ?float
    {
        return match ($unit) {

            // Sub-nanometer SI
            LengthUnit::ATTOMETER      => 1e-18,
            LengthUnit::FEMTOMETER     => 1e-15,
            LengthUnit::PICOMETER      => 1e-12,
            LengthUnit::ANGSTROM       => 1e-10,

            // Standard SI metric
            LengthUnit::NANOMETER      => 1e-9,
            LengthUnit::MICROMETER     => 1e-6,
            LengthUnit::MILIMETERS     => 1e-3,
            LengthUnit::CENTIMETERS    => 1e-2,
            LengthUnit::DECIMETER      => 1e-1,
            LengthUnit::METERS         => 1.0,
            LengthUnit::DECAMETERS     => 1e1,
            LengthUnit::HECTOMETERS    => 1e2,
            LengthUnit::KILLOMETERS    => 1e3,
            LengthUnit::MEGAMETERS     => 1e6,
            LengthUnit::GIGAMETERS     => 1e9,
            LengthUnit::TERAMETERS     => 1e12,
            LengthUnit::PETAMETERS     => 1e15,
            LengthUnit::EXAMETERS      => 1e18,
            LengthUnit::ZETTAMETERS    => 1e21,
            LengthUnit::YOTAMETERS     => 1e24,

            // Astronomical
            // IAU 2012 exact definition: 1 AU = 149 597 870 700 m
            LengthUnit::ASTRONOMICAL   => 1.495978707e11,
            // IAU 2012: 1 ly = 9 460 730 472 580 800 m (Julian year × c)
            LengthUnit::LIGHTYEAR      => 9.4607304725808e15,
            // IAU 2015: 1 pc = 648 000 / π AU
            LengthUnit::PARSEC         => 3.08567758149137e16,

            // Imperial / US customary  (international, exact)
            LengthUnit::INCHES         => 0.0254,
            LengthUnit::FEET           => 0.3048,
            LengthUnit::YARDS          => 0.9144,
            LengthUnit::MILES          => 1609.344,
            // International nautical mile (exact since 1954)
            LengthUnit::NAUTICAL_MILES => 1852.0,

            // Specialty / informal
            LengthUnit::FURLONG        => 201.168,          // 220 yd = ⅛ statute mile
            LengthUnit::MEGAFURLONG    => 201168000.0,      // 10⁶ furlongs
            LengthUnit::CGAUB          => 20.1168,          // Gunter's chain = 66 ft
            LengthUnit::LEAGUE         => 5556.0,           // Nautical league = 3 nmi
            LengthUnit::FTM            => 1.8288,           // Fathom = 6 ft
            LengthUnit::MARATHON       => 42195.0,          // IAAF standard
            LengthUnit::HALF_MARATHON  => 21097.5,
            LengthUnit::SMOOT          => 1.7018,           // Oliver Smoot's height
            LengthUnit::QBIT           => 0.4572,           // 18 inches (informal)

            default                    => null,
        };
    }
}
