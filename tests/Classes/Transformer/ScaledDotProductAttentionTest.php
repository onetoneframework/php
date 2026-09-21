<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Transformer;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Transformer\ScaledDotProductAttention;
use Clover\Classes\Math\MatrixOps;

/**
 * Unit tests for ScaledDotProductAttention
 * - Validates forward attention output
 * - Checks masking logic
 * - Verifies gradient flow in backward pass
 */
final class ScaledDotProductAttentionTest extends TestCase
{
    private ScaledDotProductAttention $attention;

    protected function setUp(): void
    {
        $this->attention = new ScaledDotProductAttention(4); // d_k = 4
    }

    private function assertMatricesAlmostEqual(array $A, array $B, float $tol = 1e-6): void
    {
        $this->assertCount(count($A), $B);
        for ($i = 0; $i < count($A); $i++) {
            $this->assertCount(count($A[$i]), $B[$i]);
            for ($j = 0; $j < count($A[$i]); $j++) {
                $this->assertTrue(
                    abs($A[$i][$j] - $B[$i][$j]) <= $tol,
                    "Mismatch at [$i][$j]: {$A[$i][$j]} vs {$B[$i][$j]}"
                );
            }
        }
    }

    public function testForwardAttentionWithoutMask()
    {
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $K = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $V = [
            [10.0, 0.0],
            [0.0, 20.0],
        ];

        $output = $this->attention->forward($Q, $K, $V);

        // Compute expected values manually
        $scale = 1.0 / sqrt(4); // d_k = 4
        $dot_0 = [1.0 * $scale, 0.0];
        $dot_1 = [0.0, 1.0 * $scale];

        $softmax0 = MatrixOps::softmax([$dot_0])[0];
        $softmax1 = MatrixOps::softmax([$dot_1])[0];

        $expected0 = [
            $softmax0[0] * 10.0 + $softmax0[1] * 0.0,
            $softmax0[0] * 0.0 + $softmax0[1] * 20.0,
        ];
        $expected1 = [
            $softmax1[0] * 10.0 + $softmax1[1] * 0.0,
            $softmax1[0] * 0.0 + $softmax1[1] * 20.0,
        ];

        $this->assertEqualsWithDelta($expected0[0], $output[0][0], 1e-5);
        $this->assertEqualsWithDelta($expected0[1], $output[0][1], 1e-5);
        $this->assertEqualsWithDelta($expected1[0], $output[1][0], 1e-5);
        $this->assertEqualsWithDelta($expected1[1], $output[1][1], 1e-5);
    }

    public function testForwardAttentionWithMask()
    {
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
        ];
        $K = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $V = [
            [10.0, 0.0],
            [0.0, 20.0],
        ];
        $mask = [
            [1, 0], // Only attend to first key
        ];

        $output = $this->attention->forward($Q, $K, $V, $mask);

        $this->assertCount(1, $output);
        $this->assertEqualsWithDelta(10.0, $output[0][0], 1e-5);
        $this->assertEqualsWithDelta(0.0, $output[0][1], 1e-5);
    }

    public function testBackwardGradientsShape()
    {
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $K = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $V = [
            [10.0, 0.0],
            [0.0, 20.0],
        ];

        $output = $this->attention->forward($Q, $K, $V);

        // Simulate gradient from next layer
        $d_output = [
            [1.0, 0.0],
            [0.0, 1.0],
        ];

        $grads = $this->attention->backward($d_output);

        $this->assertArrayHasKey('d_Q', $grads);
        $this->assertArrayHasKey('d_K', $grads);
        $this->assertArrayHasKey('d_V', $grads);

        $this->assertCount(2, $grads['d_Q']);
        $this->assertCount(2, $grads['d_K']);
        $this->assertCount(2, $grads['d_V']);
    }

    public function testAttentionWeightsSumToOne()
    {
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
        ];
        $K = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $V = [
            [10.0, 0.0],
            [0.0, 20.0],
        ];

        $this->attention->forward($Q, $K, $V);
        $weights = (new \ReflectionClass($this->attention))->getProperty('cache');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $weights->setAccessible(true);
        }

        $cache = $weights->getValue($this->attention);
        $attention_weights = $cache['attention_weights'];

        foreach ($attention_weights as $row) {
            $sum = array_sum($row);
            $this->assertEqualsWithDelta(1.0, $sum, 1e-6);
        }
    }
}
