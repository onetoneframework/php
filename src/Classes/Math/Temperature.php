<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\TemperatureUnit;

class Temperature
{
    /**
     * Temperature conversions are non-linear (offset + scale), so a single
     * multiplicative factor does not exist.  All conversions are routed through
     * Celsius as the canonical intermediate.
     */
    private static function toCelsius(float $value, string $unit): ?float
    {
        return match ($unit) {
            TemperatureUnit::CELSIUS => $value,
            TemperatureUnit::KELVIN => $value - 273.15,
            TemperatureUnit::FAHRENHEIT => ($value - 32.0) * 5.0 / 9.0,
            TemperatureUnit::RANKINE => ($value - 491.67) * 5.0 / 9.0,
            default => null,
        };
    }

    private static function fromCelsius(float $celsius, string $unit): ?float
    {
        return match ($unit) {
            TemperatureUnit::CELSIUS => $celsius,
            TemperatureUnit::KELVIN => $celsius + 273.15,
            TemperatureUnit::FAHRENHEIT => $celsius * 9.0 / 5.0 + 32.0,
            TemperatureUnit::RANKINE => ($celsius + 273.15) * 9.0 / 5.0,
            default => null,
        };
    }

    public static function convert(float $value, string $from, string $to): ?float
    {
        if ($from === $to) {
            return $value;
        }

        $celsius = self::toCelsius($value, $from);

        if ($celsius === null) {
            return null;
        }

        return self::fromCelsius($celsius, $to);
    }
}
