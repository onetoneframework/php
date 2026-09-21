<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Abstract class defining constants for every supported length unit.
 */
abstract class LengthUnit
{
    /** Attometer  — 1 × 10⁻¹⁸ m */
    public const ATTOMETER = 'attometer';

    /** Femtometer — 1 × 10⁻¹⁵ m */
    public const FEMTOMETER = 'femtometer';

    /** Picometer  — 1 × 10⁻¹² m */
    public const PICOMETER = 'picometer';

    /** Angstrom   — 1 × 10⁻¹⁰ m */
    public const ANGSTROM = 'angstrom';

    /** Nanometer  — 1 × 10⁻⁹ m */
    public const NANOMETER = 'nanometer';

    /** Micrometer — 1 × 10⁻⁶ m */
    public const MICROMETER = 'micrometer';

    /** Millimeter — 1 × 10⁻³ m */
    public const MILIMETERS = 'milimeters';

    /** Centimeter — 1 × 10⁻² m */
    public const CENTIMETERS = 'centimeters';

    /** Decimeter  — 1 × 10⁻¹ m */
    public const DECIMETER = 'decimeter';

    /** Meter      — SI base unit of length */
    public const METERS = 'meters';

    /** Decameter  — 1 × 10¹ m */
    public const DECAMETERS = 'decameters';

    /** Hectometer — 1 × 10² m */
    public const HECTOMETERS = 'hectometers';

    /** Kilometer  — 1 × 10³ m */
    public const KILLOMETERS = 'killometers';

    /** Megameter  — 1 × 10⁶ m */
    public const MEGAMETERS = 'megameters';

    /** Gigameter  — 1 × 10⁹ m */
    public const GIGAMETERS = 'gigameters';

    /** Terameter  — 1 × 10¹² m */
    public const TERAMETERS = 'terameters';

    /** Petameter  — 1 × 10¹⁵ m */
    public const PETAMETERS = 'petameters';

    /** Exameter   — 1 × 10¹⁸ m */
    public const EXAMETERS = 'exameters';

    /** Zettameter — 1 × 10²¹ m */
    public const ZETTAMETERS = 'zettameters';

    /** Yottameter — 1 × 10²⁴ m */
    public const YOTAMETERS = 'yotameters';

    /** Astronomical Unit — ~1.496 × 10¹¹ m (mean Earth–Sun distance) */
    public const ASTRONOMICAL = 'astronomical';

    /** Light-year — ~9.461 × 10¹⁵ m */
    public const LIGHTYEAR = 'lightyear';

    /** Parsec — ~3.086 × 10¹⁶ m */
    public const PARSEC = 'parsec';

    /** Inch         — 0.0254 m (exact) */
    public const INCHES = 'inches';

    /** Foot         — 0.3048 m (exact) */
    public const FEET = 'feet';

    /** Yard         — 0.9144 m (exact) */
    public const YARDS = 'yards';

    /** Mile         — 1,609.344 m (exact) */
    public const MILES = 'miles';

    /** Nautical mile — 1,852 m (exact, international) */
    public const NAUTICAL_MILES = 'nautical miles';

    /** Furlong      — 201.168 m (⅛ mile) */
    public const FURLONG = 'furlong';

    /** Megafurlong  — 201,168,000 m (10⁶ furlongs) */
    public const MEGAFURLONG = 'megafurlong';

    /** Gunter's chain — 20.1168 m (66 ft) */
    public const CGAUB = 'chain';

    /** League (nautical) — 5,556 m (3 nautical miles) */
    public const LEAGUE = 'league';

    /** Fathom       — 1.8288 m (6 ft) */
    public const FTM = 'ftm';

    /** Marathon distance — 42,195 m */
    public const MARATHON = 'marathon';

    /** Half-marathon distance — 21,097.5 m */
    public const HALF_MARATHON = 'half marathon';

    /** Smoot        — 1.7018 m (Oliver Smoot's height) */
    public const SMOOT = 'smoot';

    /** Qbit         — 0.4572 m (18 inches; informal unit) */
    public const QBIT = 'qbit';
}
