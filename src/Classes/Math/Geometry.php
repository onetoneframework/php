<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Classes\Math\Length;
use Clover\Enumeration\Math\Geometry as GeometryEnum;
use Clover\Enumeration\LengthUnit;

/**
 * Class Geometry
 *
 * A utility class for geometry-related mathematical operations.
 */
class Geometry
{
    public static function getMoonMeanLongitudeInDays(int $day): float|int
    {
        return $day * GeometryEnum::MOON_MEAN_LONGITUDE_EPOCH + GeometryEnum::MOON_MEAN_LONGITUDE_PER_DAY;
    }

    public static function getMoonLongitudeOfPerihelionInDays(int $day): float|int
    {
        return GeometryEnum::MOON_PERIGEE_LONGITUDE + GeometryEnum::MOON_PERIGEE_LONGITUDE_RATE * $day;
    }

    public static function getMoonTrueAnomalyInDays(int $day): float|int
    {
        $ml = self::getMoonMeanLongitudeInDays($day);
        $lp = self::getMoonLongitudeOfPerihelionInDays($day);
        return M_PI * ($ml - $lp) / 180;
    }

    public static function getLongitudeOfAscendingNode(int $day): float|int
    {
        return GeometryEnum::MOON_ASCENDING_NODE_LONGITUDE - GeometryEnum::MOON_ASCENDING_NODE_LONGITUDE_RATE * $day;
    }

    public static function getSunMeanLongitudeInDays(int $day): float|int
    {
        return $day * GeometryEnum::SUN_MEAN_LONGITUDE_PER_DAY + GeometryEnum::SUN_MEAN_LONGITUDE_EPOCH;
    }

    public static function getSunLongitudeOfPerihelionInDays(int $day): float|int
    {
        return GeometryEnum::SUN_PERIHELION_LONGITUDE + GeometryEnum::SUN_PERIHELION_LONGITUDE_RATE * $day;
    }

    public static function getSunTrueAnomalyInDays(int $day): float|int
    {
        $ml = self::getSunMeanLongitudeInDays($day);
        $lp = self::getSunLongitudeOfPerihelionInDays($day);
        return M_PI * ($ml - $lp) / 180;
    }

    public static function getSunEquationOfCenterInDays(int $day): float
    {
        $ta = self::getSunTrueAnomalyInDays($day);

        return 1.919 * sin($ta) + 0.02 * sin(2 * $ta);
    }

    /**
     * Get distance between two points on the Earth using the spherical law of cosines.
     * 
     * @param float $latitude1
     * @param float $longitude1
     * @param float $latitude2
     * @param float $longitude2
     * @param string|LengthUnit $measurement
     * 
     * @return float|int
     */
    public static function getDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2, string|LengthUnit $measurement = LengthUnit::KILLOMETERS): float|int
    {
        $theta = $longitude1 - $longitude2;
        $lambda = deg2rad($theta);

        $cos1 = cos(deg2rad($latitude1));
        $cos2 = cos(deg2rad($latitude2));

        $sin1 = sin(deg2rad($latitude1)) * sin(deg2rad($latitude2));
        $sin2 = cos($lambda);

        $miles = $sin1 + $cos1 * $cos2 * $sin2;
        $miles = acos($miles);
        $miles = rad2deg($miles);
        $miles = $miles * 60 * 1.1515;

        $factor = Length::getConversionFactor(LengthUnit::MILES, $measurement);

        return $miles * $factor;
    }

    /**
     * Get distance between two points on the Earth using the Haversine formula.
     * 
     * @param int|float $x1
     * @param int|float $y1
     * @param int|float $x2
     * @param int|float $y2
     * @param string  $measurement
     * 
     * @return float|int
     */
    public static function getDistanceByHaversine(int|float $x1, int|float $y1, int|float $x2, int|float $y2, string $measurement = LengthUnit::KILLOMETERS): float|int
    {
        $radius = 6371;
        $toRadian = M_PI / 180;

        $deltaLatitude = abs($x1 - $x2) * $toRadian;
        $deltaLongitude = abs($y1 - $y2) * $toRadian;
        $sinDeltaLat = sin($deltaLatitude / 2);
        $sinDeltaLongitude = sin($deltaLongitude / 2);

        $squareRoot = sqrt($sinDeltaLat ** 2 + cos($x1 * $toRadian) * cos($x2 * $toRadian) * $sinDeltaLongitude ** 2);
        $distance = 2 * $radius * asin($squareRoot);

        $factor = Length::getConversionFactor(LengthUnit::KILLOMETERS, $measurement);

        return $distance * $factor;
    }

    public static function distance2D(array $p1, array $p2): float
    {
        $pow1 = pow($p2[0] - $p1[0], 2);
        $pow2 = pow($p2[1] - $p1[1], 2);

        return sqrt($pow1 + $pow2);
    }

    public static function distance3D(array $p1, array $p2): float
    {
        $pow1 = pow($p2[0] - $p1[0], 2);
        $pow2 = pow($p2[1] - $p1[1], 2);
        $pow3 = pow($p2[2] - $p1[2], 2);

        return sqrt($pow1 + $pow2 + $pow3);
    }

    /**
     * Get the area of a triangle using Heron's formula.
     * 
     * @param float $a
     * @param float $b
     * @param float $c
     * 
     * @return float
     */
    public static function triangleAreaHeron(float $a, float $b, float $c): float
    {
        $s = ($a + $b + $c) / 2;

        return sqrt($s * ($s - $a) * ($s - $b) * ($s - $c));
    }

    /**
     * Get the area of a triangle given its base and height.
     * 
     * @param float $base
     * @param float $height
     * 
     * @return float
     */
    public static function triangleAreaBaseHeight(float $base, float $height): float
    {
        return 0.5 * $base * $height;
    }

    public static function circleArea(float $radius): float
    {
        return pi() * pow($radius, 2);
    }
}
