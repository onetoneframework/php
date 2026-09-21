<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Math\Geometry;
use Clover\Enumeration\LengthUnit;

use function sprintf;

/**
 * Class CoordinateObject
 *
 * Represents a geographical coordinate with latitude and longitude.
 */
#[\AllowDynamicProperties]
class CoordinateObject
{
    /**
     * The latitude of the coordinate.
     *
     * @var float
     */
    private float $latitude;

    /**
     * The longitude of the coordinate.
     *
     * @var float
     */
    private float $longitude;

    /**
     * Constructor for CoordinateObject.
     *
     * @param float $latitude The latitude of the coordinate.
     * @param float $longitude The longitude of the coordinate.
     */
    public function __construct(float $latitude, float $longitude)
    {
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    /**
     * Gets the latitude of the coordinate.
     *
     * @return float The latitude.
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Gets the longitude of the coordinate.
     *
     * @return float The longitude.
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Sets the latitude of the coordinate.
     *
     * @param float $latitude The latitude to set.
     * 
     * @return void
     */
    public function setLatitude(float $latitude): void
    {
        $this->latitude = $latitude;
    }

    /**
     * Sets the longitude of the coordinate.
     *
     * @param float $longitude The longitude to set.
     * 
     * @return void
     */
    public function setLongitude(float $longitude): void
    {
        $this->latitude = $longitude;
    }

    /**
     * Calculates the distance to another CoordinateObject using the Haversine formula.
     *
     * @param CoordinateObject $destination The destination coordinate.
     * @param string $measurement The measurement unit (default is kilometers).
     * 
     * @return float|int The distance between the two coordinates.
     */
    public function getDistanceByHaversine(CoordinateObject $destination, string $measurement = LengthUnit::KILLOMETERS): float|int
    {
        return Geometry::getDistanceByHaversine($this->latitude, $this->longitude, $destination->latitude, $destination->longitude, $measurement);
    }

    /**
     * Calculates the distance to another CoordinateObject using the specified measurement unit.
     *
     * @param CoordinateObject $destination The destination coordinate.
     * @param string $measurement The measurement unit (default is kilometers).
     * 
     * @return float|int The distance between the two coordinates.
     */
    public function getDistance(CoordinateObject $destination, string $measurement = LengthUnit::KILLOMETERS): float|int
    {
        return Geometry::getDistance($this->latitude, $this->longitude, $destination->latitude, $destination->longitude, $measurement);
    }

    /**
     * Returns a string representation of the coordinate.
     *
     * @return string The coordinate in "latitude, longitude" format.
     */
    public function __toString(): string
    {
        return sprintf("%f, %f", $this->latitude, $this->longitude);
    }

    /**
     * Convert coordinates to an array
     * 
     * @return array{latitude: float, longitude: float}
     */
    public function toArray(): array
    {
        return ['latitude' => $this->latitude, 'longitude' => $this->longitude];
    }
}
