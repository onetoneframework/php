<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Geometry;
use Clover\Enumeration\LengthUnit;
use PHPUnit\Framework\TestCase;

class GeometryTest extends TestCase
{
    public function testDistance2D(): void
    {
        $distance = Geometry::distance2D([0, 0], [3, 4]);
        $this->assertEquals(5.0, $distance);
    }

    public function testDistance3D(): void
    {
        $distance = Geometry::distance3D([0, 0, 0], [1, 2, 2]);
        $this->assertEquals(3.0, $distance);
    }

    public function testTriangleAreaHeron(): void
    {
        $area = Geometry::triangleAreaHeron(3.0, 4.0, 5.0);
        $this->assertEquals(6.0, $area);
    }

    public function testCircleArea(): void
    {
        $area = Geometry::circleArea(10.0);
        $this->assertEqualsWithDelta(314.159, $area, 0.001);
    }

    public function testGetDistance(): void
    {
        // Simple test to ensure the method executes and returns a float. Distance from (0,0) to (0,0) is 0.
        $dist = Geometry::getDistance(0.0, 0.0, 0.0, 0.0, LengthUnit::KILLOMETERS);
        $this->assertEquals(0.0, $dist);
    }

    public function testGetDistanceByHaversine(): void
    {
        $dist = Geometry::getDistanceByHaversine(0.0, 0.0, 0.0, 0.0, LengthUnit::KILLOMETERS);
        $this->assertEquals(0.0, $dist);
    }
}
