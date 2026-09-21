<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\Calculus;
use PHPUnit\Framework\TestCase;

class CalculusTest extends TestCase
{
    public function testNumericalDerivative(): void
    {
        // Derivative of x^2 is 2x. At x=3, it should be 6
        $function = function($x) {
            return $x * $x;
        };
        
        $derivative = Calculus::numericalDerivative($function, 3.0, 0.0001);
        $this->assertEqualsWithDelta(6.0, $derivative, 0.01);
    }

    public function testNumericalDerivativeException(): void
    {
        $function = function($x) {
            throw new \Exception("Error");
        };
        
        $derivative = Calculus::numericalDerivative($function, 3.0);
        $this->assertNull($derivative);
    }

    public function testNumericalIntegral(): void
    {
        // Integral of 2x dx from 0 to 3 is x^2 | = 9 - 0 = 9
        $function = function($x) {
            return 2 * $x;
        };
        
        $integral = Calculus::numericalIntegral($function, 0.0, 3.0, 1000);
        $this->assertEqualsWithDelta(9.0, $integral, 0.1);
    }

    public function testNumericalIntegralInvalidInterval(): void
    {
        $function = function($x) {
            return 2 * $x;
        };
        // a >= b
        $this->assertEquals(0, Calculus::numericalIntegral($function, 3.0, 2.0));
    }
}
