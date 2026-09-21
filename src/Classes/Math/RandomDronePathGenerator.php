<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;
/**
 * Class RandomDronePathGenerator
 *
 * A class to generate random drone paths and visualize them.
 */
class RandomDronePathGenerator
{
    /**
     * Generate a random 3D coordinate.
     *
     * @param int $min
     * @param int $max
     * 
     * @return array
     */
    public static function generateRandomCoordinate(int $min, int $max): array
    {
        return [
            (string) rand($min, $max),
            (string) rand($min, $max),
            (string) rand($min, $max),
        ];
    }

    /**
     * Generate an array of random stop coordinates.
     *
     * @param int $numStops
     * @param int $minCoordinate
     * @param int $maxCoordinate
     * 
     * @return array
     */
    public static function generateRandomStops(int $numStops, int $minCoordinate, int $maxCoordinate): array
    {
        $stops = [];
        for ($i = 0; $i < $numStops; $i++) {
            $stops[] = self::generateRandomCoordinate($minCoordinate, $maxCoordinate);
        }

        return $stops;
    }

    /**
     * Generate a random visit-order array.
     *
     * @param int $numStops
     * 
     * @return array
     */
    public static function generateRandomPathOrder(int $numStops): array
    {
        $pathOrder = [];
        for ($i = 0; $i < $numStops * 2; $i++) {
            $pathOrder[] = rand(0, $numStops - 1);
        }

        return $pathOrder;
    }

    /**
     * Generate a random map and path and output as an image.
     *
     * @param int $numStops
     * @param int $minCoordinate
     * @param int $maxCoordinate
     * @param int $imageWidth
     * @param int $imageHeight
     * @param string $imagePath
     * 
     * @return void
     */
    public static function visualizeRandomPath(int $numStops = 5, int $minCoordinate = -20, int $maxCoordinate = 20, int $imageWidth = 600, int $imageHeight = 600, string $imagePath = 'random_drone_path.png'): void
    {
        $start = self::generateRandomCoordinate($minCoordinate, $maxCoordinate);
        $stops = self::generateRandomStops($numStops, $minCoordinate, $maxCoordinate);
        $end = self::generateRandomCoordinate($minCoordinate, $maxCoordinate);
        $optimalPathOrder = self::generateRandomPathOrder($numStops);

        DronePathOptimizer::visualizePath($start, $stops, $end, $optimalPathOrder, $imagePath, $imageWidth, $imageHeight);
    }
}
