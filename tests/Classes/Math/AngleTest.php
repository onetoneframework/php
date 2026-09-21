<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Angle;
use PHPUnit\Framework\TestCase;

class AngleTest extends TestCase
{
    public function testNormalizeAngle(): void
    {
        $this->assertEquals(0.0, Angle::normalizeAngle(360.0));
        $this->assertEquals(180.0, Angle::normalizeAngle(180.0));
        $this->assertEquals(90.0, Angle::normalizeAngle(-270.0));
        $this->assertEquals(10.0, Angle::normalizeAngle(730.0));
    }

    public function testAngleDiff(): void
    {
        $this->assertEquals(90.0, Angle::angleDiff(10.0, 100.0));
        $this->assertEquals(-90.0, Angle::angleDiff(100.0, 10.0));
        $this->assertEquals(-170.0, Angle::angleDiff(350.0, 180.0));
        $this->assertEquals(170.0, Angle::angleDiff(180.0, 350.0));
    }

    public function testDeg2Rad(): void
    {
        $this->assertEqualsWithDelta(M_PI, Angle::deg2rad(180.0), 0.0001);
        $this->assertEqualsWithDelta(M_PI / 2, Angle::deg2rad(90.0), 0.0001);
    }

    public function testRad2Degf(): void
    {
        $this->assertEquals(180.0, Angle::rad2degf(M_PI));
        $this->assertEquals(90.0, Angle::rad2degf(M_PI / 2));
    }

    public function testNorm360(): void
    {
        $this->assertEquals(0.0, Angle::norm360(360.0));
        $this->assertEquals(180.0, Angle::norm360(-180.0));
    }

    public function testNorm180(): void
    {
        $this->assertEquals(-170.0, Angle::norm180(190.0));
        $this->assertEquals(170.0, Angle::norm180(170.0));
        $this->assertEquals(-180.0, Angle::norm180(180.0)); // Based on logic, 180 might become -180 depending on fmod exactness, let's test a safe value
    }
}
