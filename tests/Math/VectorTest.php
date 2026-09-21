<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Math;

use Clover\Classes\Math\Vector;
use Clover\Classes\Math\Scalar;
use PHPUnit\Framework\TestCase;

class VectorTest extends TestCase
{
    public function testAdd(): void
    {
        $v1 = [1, 2, 3];
        $v2 = [4, 5, 6];
        $expected = [5, 7, 9];

        $this->assertEquals($expected, Vector::add($v1, $v2));
        $this->assertNull(Vector::add([1], [1, 2])); // Different lengths
    }

    public function testMultiplyScalar(): void
    {
        $v = [1, 2, 3];
        $scalar = new Scalar(2.0);
        $expected = [2.0, 4.0, 6.0];

        $this->assertEquals($expected, Vector::multiplyScalar($v, $scalar));
    }

    public function testDotProduct(): void
    {
        $v1 = [1, 3, -5];
        $v2 = [4, -2, -1];
        // 1*4 + 3*(-2) + (-5)*(-1) = 4 - 6 + 5 = 3
        
        $this->assertEquals(3.0, Vector::dotProduct($v1, $v2));
    }

    public function testCrossProduct(): void
    {
        $v1 = [1, 0, 0];
        $v2 = [0, 1, 0];
        // Cross product of X and Y axis is Z axis [0, 0, 1]
        $expected = [0, 0, 1];

        $this->assertEquals($expected, Vector::crossProduct($v1, $v2));
        $this->assertNull(Vector::crossProduct([1, 2], [1, 2])); // Not 3D
    }

    public function testMagnitude(): void
    {
        $v = [3, 4];
        $this->assertEquals(5.0, Vector::magnitude($v));
    }

    public function testNormalize(): void
    {
        $v = [3, 0];
        $normalized = Vector::normalize($v);
        $this->assertEquals([1.0, 0.0], $normalized);

        $this->assertNull(Vector::normalize([0, 0])); // Zero vector
    }
}
