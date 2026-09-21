<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math;

use Clover\Enumeration\AngleUnit;
use M_PI;

/**
 * Class Angle
 *
 * A utility class for angle-related mathematical operations.
 */
class Angle
{
    /** Radians per unit */
    private const RADIANS_PER_UNIT = [
        AngleUnit::DEGREE => M_PI / 180.0,
        AngleUnit::RADIAN => 1.0,
        AngleUnit::MINUTE => M_PI / 10_800.0,
        AngleUnit::SECOND => M_PI / 648_000.0,
        AngleUnit::CIRCLE => M_PI * 2.0,
        AngleUnit::QUADRANT => M_PI / 2.0,
    ];

    /**
     * Determine if the angle has crossed a target angle between two angles.
     * @param float $lonPrev The previous angle in degrees.
     * @param float $lonNow The current angle in degrees.
     * @param float $target The target angle in degrees to check for crossing.
     * @return bool True if the angle has crossed the target angle, false otherwise.
     */
    public static function crossedAngle(float $lonPrev, float $lonNow, float $target): bool
    {
        $a = self::normalizeAngle($lonPrev);
        $b = self::normalizeAngle($lonNow);
        $t = self::normalizeAngle($target);

        // unwrap b relative to a
        $bUnwrap = $b;
        if ($bUnwrap - $a < -180) {
            $bUnwrap += 360;
        }
        if ($bUnwrap - $a > 180) {
            $bUnwrap -= 360;
        }

        $tUnwrap = $t;
        if ($tUnwrap - $a < -180) {
            $tUnwrap += 360;
        }
        if ($tUnwrap - $a > 180) {
            $tUnwrap -= 360;
        }

        return ($a <= $tUnwrap && $tUnwrap < $bUnwrap) || ($a >= $tUnwrap && $tUnwrap > $bUnwrap);
    }

    /**
     * Calculate the smallest difference between two angles.
     * @param float $a The first angle in degrees.
     * @param float $b The second angle in degrees.
     * @return float The smallest difference between the two angles in degrees, in the range [-180, 180).
     */
    public static function angleDiff(float $a, float $b): float
    {
        $d = self::normalizeAngle($b) - self::normalizeAngle($a);

        if ($d < -180) {
            $d += 360;
        }

        if ($d > 180) {
            $d -= 360;
        }

        return $d;
    }

    /**
     * Normalize an angle to the range [0, 360).
     * @param float $deg The angle in degrees to normalize.
     * @return float The normalized angle in the range [0, 360).
     */
    public static function normalizeAngle(float $deg): float
    {
        $x = fmod($deg, 360.0);

        if ($x < 0) { // negative value
            $x += 360.0;
        }

        return $x;
    }

    /**
     * Convert degrees to radians.
     * @param float $deg The angle in degrees to convert.
     * @return float The angle in radians.
     */
    public static function deg2rad(float $deg): float
    {
        return $deg * M_PI / 180.0;
    }

    /**
     * Convert radians to degrees.
     * @param float $r The angle in radians to convert.
     * @return float The angle in degrees.
     */
    public static function rad2degf(float $r): float|int
    {
        return $r * 180.0 / M_PI;
    }

    /**
     * Normalize an angle to the range [0, 360).
     * @param float $x The angle in degrees to normalize.
     * @return float The normalized angle in the range [0, 360).
     */
    public static function norm360(float $x): float
    {
        $r = fmod($x, 360.0);
        return ($r < 0) ? $r + 360.0 : $r;
    }

    /**
     * Normalize an angle to the range [-180, 180).
     * @param float $x The angle in degrees to normalize.
     * @return float The normalized angle in the range [-180, 180).
     */
    public static function norm180(float $x): float
    {
        $r = self::norm360($x);
        if ($r > 180.0) {
            return $r - 360.0;
        }

        // Prefer -180 over +180 so the range is symmetric in (-180, 180].
        if (abs($r - 180.0) < 1e-9) {
            return -180.0;
        }

        return $r;
    }

    public static function getConversionFactor(string $from, string $to): ?float
    {
        if (!isset(self::RADIANS_PER_UNIT[$from], self::RADIANS_PER_UNIT[$to])) {
            return null;
        }

        if ($from === $to) {
            return 1.0;
        }

        return self::RADIANS_PER_UNIT[$from] / self::RADIANS_PER_UNIT[$to];
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
