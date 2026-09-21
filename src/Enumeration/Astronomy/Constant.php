<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Astronomy;

/**
 * Astronomy constants enumeration
 *
 * Each enum case represents a physical or orbital constant mentioned
 * in the provided text. English comments explain the origin or meaning
 * of each constant.
 */
enum Constant
{
    // Newtonian gravitational constant (CODATA 2018 recommended value used here)
    case Gravitational;

    // Gravitational constant value derived in the Zhihu post (author's estimate)
    case GravitationalAuthor;

    // Solar mass in kilograms (value used in the Zhihu examples: 1.989e30 kg)
    case SolarMassKg;

    // Earth mass in kilograms (approximate: 5.97219e24 kg)
    case EarthMassKg;

    // Earth mean radius in meters (approximate: 6.371e6 m)
    case EarthRadiusMeters;

    // Astronomical unit in meters (1 AU ≈ 1.495978707e11 m)
    case AuMeters;

    // Parsec in meters
    case ParsecMeters;

    // Light year in meters
    case LightYearMeters;

    // Speed of light in meters per second
    case SpeedOfLightMps;

    // Representative supermassive black hole mass in kilograms
    case BlackHoleMassKg;

    // Earth's perihelion distance q in meters (147,098,039 km)
    case EarthPerihelionMeters;

    // Earth's aphelion distance R in meters (152,097,701 km)
    case EarthAphelionMeters;

    // Earth's perihelion orbital speed in meters per second (≈ 30.2878486 km/s)
    case EarthPerihelionSpeedMps;

    // Earth's aphelion orbital speed in meters per second (≈ 29.292245 km/s)
    case EarthAphelionSpeedMps;

    // Earth's orbital eccentricity (derived in the text: 0.01671034)
    case EarthEccentricity;

    // Halley comet perihelion distance in astronomical units (text uses 0.59 AU)
    case HalleyPerihelionAu;

    // Halley comet perihelion distance in meters (converted from 0.59 AU)
    case HalleyPerihelionMeters;

    // Halley comet perihelion speed in meters per second (54.3878624 km/s)
    case HalleyPerihelionSpeedMps;

    // Halley comet semi-major axis in astronomical units (a ≈ 17.9540035763 AU)
    case HalleySemiMajorAxisAu;

    // Halley comet semi-major axis in meters (converted from AU)
    case HalleySemiMajorAxisMeters;

    // Halley comet orbital period in years (T ≈ 76.075 years)
    case HalleyPeriodYears;

    // Halley comet aphelion distance in meters (R = a + c; text gives numeric R in km)
    case HalleyAphelionMeters;

    // Halley comet aphelion (far) orbital speed in meters per second (≈ 0.90856878493 km/s)
    case HalleyAphelionSpeedMps;

    public function value(): float
    {
        return match ($this) {
            // Standard gravitational constant (CODATA 2018/2019 approximate)
            self::Gravitational => 6.6743e-11,

            // Gravitational constant as derived in the Zhihu post (author's estimate)
            // G = 6.67285535681 × 10^-11 N·m²/kg² (used in the post for demonstration)
            self::GravitationalAuthor => 6.67285535681e-11,

            // Solar mass (value used in the post and common astronomical calculations)
            self::SolarMassKg => 1.989e30,

            // Earth mass (approximate)
            self::EarthMassKg => 5.97219e24,

            // Earth mean radius in meters (approximate)
            self::EarthRadiusMeters => 6.371e6,

            // Astronomical unit in meters (1 AU)
            self::AuMeters => 1.495978707e11,

            // Parsec in meters
            self::ParsecMeters => 3.085677581491367e16,

            // Light year in meters
            self::LightYearMeters => 9.4607304725808e15,

            // Speed of light in vacuum (m/s)
            self::SpeedOfLightMps => 299792458.0,

            // Black hole mass used in the enum (approximate)
            self::BlackHoleMassKg => 1.2925e40,

            // Earth's perihelion distance q = 147,098,039 km -> meters
            self::EarthPerihelionMeters => 147098039000.0,

            // Earth's aphelion distance R = 152,097,701 km -> meters
            self::EarthAphelionMeters => 152097701000.0,

            // Earth's perihelion speed V ≈ 30.2878486 km/s -> m/s
            self::EarthPerihelionSpeedMps => 30287.8486,

            // Earth's aphelion speed v ≈ 29.292245 km/s -> m/s
            self::EarthAphelionSpeedMps => 29292.245,

            // Earth's orbital eccentricity as derived in the post
            self::EarthEccentricity => 0.01671034,

            // Halley comet perihelion distance in AU (text uses 0.59 AU)
            self::HalleyPerihelionAu => 0.59,

            // Halley comet perihelion distance in meters (0.59 AU -> meters)
            self::HalleyPerihelionMeters => 0.59 * 1.495978707e11,

            // Halley comet perihelion speed 54.3878624 km/s -> m/s
            self::HalleyPerihelionSpeedMps => 54387.8624,

            // Halley comet semi-major axis in AU (text: 17.9540035763 AU)
            self::HalleySemiMajorAxisAu => 17.9540035763,

            // Halley comet semi-major axis in meters (converted from AU)
            self::HalleySemiMajorAxisMeters => 17.9540035763 * 1.495978707e11,

            // Halley comet orbital period in years (text: 76.075 years)
            self::HalleyPeriodYears => 76.075,

            // Halley comet aphelion distance in meters (text gives R in km ≈ 5,283,498,642.67 km)
            // Convert km -> m: 5,283,498,642.67 km = 5.28349864267e12 m
            // The text's R (in km) was 5,283,498,642.67 km; convert to meters here.
            self::HalleyAphelionMeters => 5.28349864267e12,

            // Halley comet aphelion speed ≈ 0.90856878493 km/s -> m/s
            self::HalleyAphelionSpeedMps => 908.56878493,
        };
    }
}
