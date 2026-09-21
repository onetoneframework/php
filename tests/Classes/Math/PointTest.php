<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Point;
use PHPUnit\Framework\TestCase;

class PointTest extends TestCase
{
    public function testRotateX(): void
    {
        $p = new Point(1.0, 2.0, 3.0);
        $angle = M_PI / 2; // 90 degrees

        $rotated = $p->rotateX($p, $angle);

        // At 90 degrees around X:
        // x' = x = 1
        // y' = y * cos(90) - z * sin(90) = 2 * 0 - 3 * 1 = -3
        // z' = y * sin(90) + z * cos(90) = 2 * 1 + 3 * 0 = 2

        $reflection = new \ReflectionClass($rotated);
        $xProp = $reflection->getProperty('x');
        $yProp = $reflection->getProperty('y');
        $zProp = $reflection->getProperty('z');
        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $xProp->setAccessible(true);
            // @phpstan-ignore-next-line
            $yProp->setAccessible(true);
            // @phpstan-ignore-next-line
            $zProp->setAccessible(true);
        }

        $this->assertEqualsWithDelta(1.0, $xProp->getValue($rotated), 0.0001);
        $this->assertEqualsWithDelta(-3.0, $yProp->getValue($rotated), 0.0001);
        $this->assertEqualsWithDelta(2.0, $zProp->getValue($rotated), 0.0001);
    }

    public function testRotateY(): void
    {
        $p = new Point(1.0, 2.0, 3.0);
        $angle = M_PI / 2; // 90 degrees

        $rotated = $p->rotateY($p, $angle);

        // At 90 degrees around Y:
        // x' = x * cos(90) + z * sin(90) = 1 * 0 + 3 * 1 = 3
        // z' = -x * sin(90) + z * cos(90) = -1 * 1 + 3 * 0 = -1
        // y' = y = 2

        $reflection = new \ReflectionClass($rotated);
        $xProp = $reflection->getProperty('x');
        $yProp = $reflection->getProperty('y');
        $zProp = $reflection->getProperty('z');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $xProp->setAccessible(true);
            // @phpstan-ignore-next-line
            $yProp->setAccessible(true);
            // @phpstan-ignore-next-line
            $zProp->setAccessible(true);
        }

        $this->assertEqualsWithDelta(3.0, $xProp->getValue($rotated), 0.0001);
        $this->assertEqualsWithDelta(2.0, $yProp->getValue($rotated), 0.0001);
        $this->assertEqualsWithDelta(-1.0, $zProp->getValue($rotated), 0.0001);
    }
}
