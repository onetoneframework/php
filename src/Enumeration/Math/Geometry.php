<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Math;

/**
 * Enumeration class for mathematical geometry constants.
 */
abstract class Geometry
{
    public const SUN_MEAN_LONGITUDE_PER_DAY = 0.98564736;
    public const SUN_MEAN_LONGITUDE_EPOCH = 278.956807;
    public const SUN_PERIHELION_LONGITUDE = 282.869498;
    public const SUN_PERIHELION_LONGITUDE_RATE = 0.00004708;
    public const MOON_MEAN_LONGITUDE_EPOCH = 27.836584;
    public const MOON_MEAN_LONGITUDE_PER_DAY = 13.17639648;
    public const MOON_PERIGEE_LONGITUDE = 280.425774;
    public const MOON_PERIGEE_LONGITUDE_RATE = 0.11140356;
    public const MOON_ASCENDING_NODE_LONGITUDE = 202.489407;
    public const MOON_ASCENDING_NODE_LONGITUDE_RATE = 0.05295377;
}