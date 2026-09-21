<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\ConicSections;
use PHPUnit\Framework\TestCase;

class ConicSectionsTest extends TestCase
{
    public function testCircleEquation(): void
    {
        // x^2 + y^2 - r^2 = 0
        // for point on the circle (3, 4) with r = 5, origin (0, 0)
        $this->assertEqualsWithDelta(0.0, ConicSections::circleEquation(3, 4, 0, 0, 5), 0.0001);
        
        // Inside circle
        $this->assertLessThan(0, ConicSections::circleEquation(1, 1, 0, 0, 5));
        
        // Outside circle
        $this->assertGreaterThan(0, ConicSections::circleEquation(10, 10, 0, 0, 5));
    }

    public function testEllipseEquation(): void
    {
        // x^2/a^2 + y^2/b^2 - 1 = 0
        // for point (a, 0)
        $this->assertEqualsWithDelta(0.0, ConicSections::ellipseEquation(3, 0, 0, 0, 3, 2), 0.0001);
    }

    public function testParabolaEquationXAxis(): void
    {
        // y^2 - 4px = 0
        // for p=1, x=1, y=2 -> 4 - 4*1*1 = 0
        $this->assertEqualsWithDelta(0.0, ConicSections::parabolaEquationXAxis(2, 0, 1, 1, 0), 0.0001);
    }

    public function testHyperbolaEquationXAxis(): void
    {
        // x^2/a^2 - y^2/b^2 - 1 = 0
        // for point (a, 0)
        $this->assertEqualsWithDelta(0.0, ConicSections::hyperbolaEquationXAxis(3, 0, 0, 0, 3, 2), 0.0001);
    }
}
