<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Math;

use Clover\Classes\Math\Matrix;
use PHPUnit\Framework\TestCase;

class MatrixTest extends TestCase
{
    public function testConstruct(): void
    {
        $data = [[1, 2], [3, 4]];
        $matrix = new Matrix($data);
        $this->assertEquals($data, $matrix->data);
        $this->assertEquals(2, $matrix->rows);
        $this->assertEquals(2, $matrix->cols);
    }

    public function testConstructInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Matrix([[1, 2], [3]]); // Inconsistent columns
    }

    public function testAdd(): void
    {
        $m1 = new Matrix([[1, 2], [3, 4]]);
        $m2 = new Matrix([[5, 6], [7, 8]]);
        $expected = [[6, 8], [10, 12]];

        $result = $m1->add($m2);
        $this->assertEquals($expected, $result->data);
    }

    public function testSubtract(): void
    {
        $m1 = new Matrix([[5, 6], [7, 8]]);
        $m2 = new Matrix([[1, 2], [3, 4]]);
        $expected = [[4, 4], [4, 4]];

        $result = $m1->subtract($m2);
        $this->assertEquals($expected, $result->data);
    }

    public function testMultiplyScalar(): void
    {
        $m = new Matrix([[1, 2], [3, 4]]);
        $expected = [[2, 4], [6, 8]];

        $result = $m->multiplyScalar(2);
        $this->assertEquals($expected, $result->data);
    }

    public function testMultiply(): void
    {
        // 2x3 matrix
        $m1 = new Matrix([
            [1, 2, 3],
            [4, 5, 6]
        ]);
        // 3x2 matrix
        $m2 = new Matrix([
            [7, 8],
            [9, 1],
            [2, 3]
        ]);
        
        // Result should be 2x2
        // [1*7 + 2*9 + 3*2, 1*8 + 2*1 + 3*3] = [7+18+6, 8+2+9] = [31, 19]
        // [4*7 + 5*9 + 6*2, 4*8 + 5*1 + 6*3] = [28+45+12, 32+5+18] = [85, 55]
        $expected = [
            [31, 19],
            [85, 55]
        ];

        $result = $m1->multiply($m2);
        $this->assertEquals($expected, $result->data);
    }

    public function testTranspose(): void
    {
        $m = new Matrix([
            [1, 2, 3],
            [4, 5, 6]
        ]);
        $expected = [
            [1, 4],
            [2, 5],
            [3, 6]
        ];

        $result = $m->transpose();
        $this->assertEquals($expected, $result->data);
    }

    public function testZeros(): void
    {
        $m = Matrix::zeros(2, 2);
        $this->assertEquals([[0.0, 0.0], [0.0, 0.0]], $m->data);
    }

    public function testFlatten(): void
    {
        $m = new Matrix([[1, 2], [3, 4]]);
        $this->assertEquals([1, 2, 3, 4], $m->flatten());
    }

    public function testMap(): void
    {
        $m = new Matrix([[1, 2], [3, 4]]);
        $result = $m->map(fn($v) => $v * 2);
        $this->assertEquals([[2.0, 4.0], [6.0, 8.0]], $result->data);
    }

    public function testGetRowCol(): void
    {
        $m = new Matrix([[1, 2], [3, 4]]);
        $this->assertEquals([1, 2], $m->getRow(0));
        $this->assertEquals([2, 4], $m->getCol(1));
        
        $this->assertNull($m->getRow(99));
        $this->assertNull($m->getCol(99));
    }

    public function testSubmatrix(): void
    {
        $m = new Matrix([
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9]
        ]);
        // Extract center [5]
        $sub = $m->submatrix(1, 1, 1, 1);
        $this->assertEquals([[5]], $sub->data);
        
        // Extract failure
        $this->assertNull($m->submatrix(0, 0, 5, 5));
    }

    public function testEquals(): void
    {
        $m1 = new Matrix([[1, 2], [3, 4]]);
        $m2 = new Matrix([[1, 2], [3, 4]]);
        $m3 = new Matrix([[1, 2], [3, 5]]);
        
        $this->assertTrue($m1->equals($m2));
        $this->assertFalse($m1->equals($m3));
    }
}
