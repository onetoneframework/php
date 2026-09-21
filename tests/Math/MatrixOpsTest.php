<?php

declare(strict_types=1);

namespace Clover\Tests\Math;
use Exception;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Math\MatrixOps;

final class MatrixOpsTest extends TestCase
{
	public function setUp(): void
	{
        MatrixOps::init();
	}

    public function testMatmul()
    {
        $A = [[1, 2], [3, 4]];
        $B = [[5, 6], [7, 8]];
        $expected = [[19, 22], [43, 50]];
        $this->assertEquals($expected, MatrixOps::matmul($A, $B));
    }

    public function testTranspose()
    {
        $A = [[1, 2, 3]];
        $expected = [[1], [2], [3]];
        $this->assertEquals($expected, MatrixOps::transpose($A));
    }

    public function testAdd()
    {
        $A = [[1, 2], [3, 4]];
        $B = [[5, 6], [7, 8]];
        $expected = [[6, 8], [10, 12]];
        $this->assertEquals($expected, MatrixOps::add($A, $B));
    }

    public function testSubtract()
    {
        $A = [[5, 7], [9, 11]];
        $B = [[1, 2], [3, 4]];
        $expected = [[4, 5], [6, 7]];
        $this->assertEquals($expected, MatrixOps::subtract($A, $B));
    }

    public function testScale()
    {
        $A = [[1, 2], [3, 4]];
        $expected = [[2, 4], [6, 8]];
        $this->assertEquals($expected, MatrixOps::scale($A, 2.0));
    }

    public function testMultiply()
    {
        $A = [[1, 2], [3, 4]];
        $B = [[2, 0], [1, 2]];
        $expected = [[2, 0], [3, 8]];
        $this->assertEquals($expected, MatrixOps::multiply($A, $B));
    }

    public function testSoftmax()
    {
        $X = [[1.0, 2.0, 3.0]];
        $result = MatrixOps::softmax($X);
        $sum = array_sum($result[0]);
        $this->assertEqualsWithDelta(1.0, $sum, 1e-6);
        $this->assertCount(3, $result[0]);
    }

    public function testSoftmaxBackward()
    {
        $X = [[1.0, 2.0, 3.0]];
        $sm = MatrixOps::softmax($X);
        $d_softmax = [[0.1, 0.2, 0.3]];
        $d_x = MatrixOps::softmaxBackward($d_softmax, $sm);
        $this->assertCount(3, $d_x[0]);
    }

    public function testXavierInitRange()
    {
        $rows = 8;
        $cols = 16;
        $mat = MatrixOps::xavierInit($rows, $cols);
        $limit = sqrt(6.0 / ($rows + $cols));

        foreach ($mat as $row) {
            foreach ($row as $val) {
                $this->assertTrue($val <= $limit && $val >= -$limit);
            }
        }
    }

    public function testClipGradient()
    {
        $grad = [[3.0, 4.0]];
        $clipped = MatrixOps::clipGradient($grad, 5.0);
        $norm = sqrt(3.0 * 3.0 + 4.0 * 4.0);
        $this->assertEqualsWithDelta(5.0, sqrt($clipped[0][0] ** 2 + $clipped[0][1] ** 2), 1e-6);
        $this->assertCount(2, $clipped[0]);
    }

    public function testGeluAndDerivative()
    {
        $x = 1.0;
        $y = MatrixOps::gelu($x);
        $dy = MatrixOps::geluDerivative($x);

        $this->assertIsFloat($y);
        $this->assertIsFloat($dy);
    }

    private function assertMatricesAlmostEqual(array $A, array $B, float $tol = 1e-6)
    {
        $this->assertCount(count($A), $B, "Row count differs");
        for ($i = 0; $i < count($A); $i++) {
            $this->assertCount(count($A[$i]), $B[$i], "Column count differs at row $i");
            for ($j = 0; $j < count($A[$i]); $j++) {
                $this->assertTrue(
                    abs($A[$i][$j] - $B[$i][$j]) <= $tol,
                    "Mismatch at [$i][$j]: {$A[$i][$j]} vs {$B[$i][$j]} (tol=$tol)"
                );
            }
        }
    }

    public function testMatmulSimple()
    {
        $A = [
            [1, 2, 3],
            [4, 5, 6],
        ];
        $B = [
            [7, 8],
            [9, 10],
            [11, 12],
        ];
        $expected = [
            [58, 64],
            [139, 154],
        ];
        $C = MatrixOps::matmul($A, $B);
        $this->assertEquals($expected, $C);
    }

    public function testMatmulDimensionMismatchThrows()
    {
        $this->expectException(Exception::class);
        $A = [[1, 2]];
        $B = [[1, 2, 3], [4, 5, 6], [7, 8, 9]];
        MatrixOps::matmul($A, $B);
    }

    public function testTransposeTwoDimention()
    {
        $A = [
            [1, 2, 3],
            [4, 5, 6],
        ];
        $expected = [
            [1, 4],
            [2, 5],
            [3, 6],
        ];
        $AT = MatrixOps::transpose($A);
        $this->assertEquals($expected, $AT);
    }

    public function testAddAndSubtract()
    {
        $A = [
            [1, 2],
            [3, 4],
        ];
        $B = [
            [5, 6],
            [7, 8],
        ];
        $sumExpected = [
            [6, 8],
            [10, 12],
        ];
        $diffExpected = [
            [-4, -4],
            [-4, -4],
        ];
        $this->assertEquals($sumExpected, MatrixOps::add($A, $B));
        $this->assertEquals($diffExpected, MatrixOps::subtract($A, $B));
    }

    public function testScaleAndElementwiseMultiply()
    {
        $A = [
            [1.5, -2.0],
            [0.0, 3.0],
        ];
        $s = 2.0;
        $scaled = MatrixOps::scale($A, $s);
        $this->assertEquals([[3.0, -4.0], [0.0, 6.0]], $scaled);

        $B = [
            [2.0, 0.5],
            [1.0, -1.0],
        ];
        $elemMulExpected = [
            [3.0, -1.0],
            [0.0, -3.0],
        ];
        $this->assertEquals($elemMulExpected, MatrixOps::multiply($A, $B));
    }

    public function testXavierInitShapeAndRange()
    {
        $rows = 64;
        $cols = 128;
        $W = MatrixOps::xavierInit($rows, $cols);
        $this->assertCount($rows, $W);
        $this->assertCount($cols, $W[0]);
        $limit = sqrt(6.0 / ($rows + $cols));
        // check values in range [-limit, +limit]
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $this->assertGreaterThanOrEqual(-$limit - 1e-9, $W[$i][$j]);
                $this->assertLessThanOrEqual($limit + 1e-9, $W[$i][$j]);
            }
        }
    }

    public function testSoftmaxRowwiseProbabilitiesAndStability()
    {
        // include large values to test numerical stability
        $X = [
            [1000, 1001, 999],
            [0.1, 0.2, 0.7],
        ];
        $S = MatrixOps::softmax($X);

        // each row sum ~1 and all elements between 0 and 1
        foreach ($S as $row) {
            $sum = array_sum($row);
            $this->assertEqualsWithDelta(1.0, $sum, 1e-8);
            foreach ($row as $val) {
                $this->assertGreaterThanOrEqual(0.0, $val);
                $this->assertLessThanOrEqual(1.0, $val);
            }
        }

        $maxIndex = array_search(max($X[0]), $X[0]);
        $maxProbIndex = array_search(max($S[0]), $S[0]);
        $this->assertEquals($maxIndex, $maxProbIndex, "Softmax peak mismatch");
    }

    public function testSoftmaxBackwardMatchesNumericalGradient()
    {
        // small test for gradient correctness using finite differences
        $X = [
            [1.2, -0.7, 0.3]
        ];
        $soft = MatrixOps::softmax($X); // 1x3
        $softRow = $soft[0];

        // choose upstream gradient (dL/dy) as simple vector
        $d_up = [[0.2, -0.1, 0.05]];
        $analytic = MatrixOps::softmaxBackward($d_up, $soft);

        // numerical gradient: dL/dx_i approx (L(x + eps*ei) - L(x - eps*ei)) / (2*eps)
        $eps = 1e-5;
        $numerical = [];
        for ($i = 0; $i < 3; $i++) {
            // perturb plus
            $X_plus = [$X[0]];
            $X_minus = [$X[0]];
            $X_plus[0][$i] += $eps;
            $X_minus[0][$i] -= $eps;

            $soft_plus = MatrixOps::softmax($X_plus)[0];
            $soft_minus = MatrixOps::softmax($X_minus)[0];

            // define loss L = sum_j d_up_j * soft_j
            $L_plus = 0.0;
            $L_minus = 0.0;
            for ($j = 0; $j < 3; $j++) {
                $L_plus += $d_up[0][$j] * $soft_plus[$j];
                $L_minus += $d_up[0][$j] * $soft_minus[$j];
            }
            $numerical[0][$i] = ($L_plus - $L_minus) / (2 * $eps);
        }

        $this->assertMatricesAlmostEqual($analytic, $numerical, 1e-5);
    }

    public function testClipGradientByGlobalNorm()
    {
        $grad = [
            [3.0, 4.0], // norm contribution 9 + 16 = 25
            [0.0, 0.0],
        ];
        // global norm = 5.0, with max_norm default 5.0 -> no scaling
        $clipped = MatrixOps::clipGradientByGlobalNorm($grad, 5.0);
        $this->assertEquals($grad, $clipped);

        // same grad but max_norm = 2.5 -> scaling factor 0.5
        $clipped2 = MatrixOps::clipGradientByGlobalNorm($grad, 2.5);
        $this->assertEquals([[1.5, 2.0], [0.0, 0.0]], $clipped2);
    }

}
