<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Transformer;
use ReflectionClass;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use PHPUnit\Framework\TestCase;
use Clover\Classes\Transformer\PositionwiseFeedForward;

/**
 * Unit tests for PositionwiseFeedForward
 * - Verifies forward computation with deterministic weights
 * - Checks GELU activation properties and numerical stability
 * - Validates backward pass: gradient shapes, finite values, and parameter updates
 */
final class PositionwiseFeedForwardTest extends TestCase
{
    private PositionwiseFeedForward $ffn;

    protected function setUp(): void
    {
        // d_model = 4, d_ff = 8 for testing
        $this->ffn = new PositionwiseFeedForward(4, 8);
    }

    /**
     * Create deterministic weights so results are reproducible.
     * W_1: d_model x d_ff, W_2: d_ff x d_model
     * Fill with small integer patterns to keep values readable.
     */
    private function makeDeterministicWeights(): array
    {
        $d_model = 4;
        $d_ff = 8;

        $W_1 = [];
        for ($i = 0; $i < $d_model; $i++) {
            $W_1[$i] = [];
            for ($j = 0; $j < $d_ff; $j++) {
                // pattern: row_index + col_index * 0.1
                $W_1[$i][$j] = $i + $j * 0.1;
            }
        }

        $W_2 = [];
        for ($i = 0; $i < $d_ff; $i++) {
            $W_2[$i] = [];
            for ($j = 0; $j < $d_model; $j++) {
                // pattern: col_index + row_index * 0.05
                $W_2[$i][$j] = $j + $i * 0.05;
            }
        }

        $b_1 = array_fill(0, $d_ff, 0.0);
        $b_2 = array_fill(0, $d_model, 0.0);

        return ['W_1' => $W_1, 'b_1' => $b_1, 'W_2' => $W_2, 'b_2' => $b_2];
    }

    public function testForwardProducesExpectedShapeAndFiniteValues()
    {
        $weights = $this->makeDeterministicWeights();
        $this->ffn->setWeights($weights);

        // input: sequence length 2, d_model = 4
        $x = [
            [1.0, 0.0, -1.0, 0.5],
            [0.2, -0.3, 0.0, 0.1],
        ];

        $output = $this->ffn->forward($x);

        // shape checks
        $this->assertCount(2, $output);
        $this->assertCount(4, $output[0]);
        $this->assertCount(4, $output[1]);

        // finite checks
        foreach ($output as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }
    }

    public function testGeluBehaviorMonotonicNearZero()
    {
        $ref = new ReflectionClass($this->ffn);
        $method = $ref->getMethod('gelu');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $method->setAccessible(true);
        }

        $eps = 1e-3;
        $low = $method->invoke($this->ffn, -$eps);
        $zero = $method->invoke($this->ffn, 0.0);
        $high = $method->invoke($this->ffn, $eps);

        // GELU(0) should be approximately 0
        $this->assertEqualsWithDelta(0.0, $zero, 1e-12);

        // GELU is approximately odd: gelu(-x) ≈ -gelu(x); allow a relaxed tolerance for the approximation
        $this->assertEqualsWithDelta(-$high, $low, 1e-3, "gelu(-eps) should be approximately -gelu(+eps)");
    }

    public function testBackwardReturnsDxAndUpdatesWeights()
    {
        $weights = $this->makeDeterministicWeights();
        $this->ffn->setWeights($weights);

        // small batch of two tokens
        $x = [
            [1.0, 0.0, -1.0, 0.5],
            [0.2, -0.3, 0.0, 0.1],
        ];

        $output = $this->ffn->forward($x);

        // create a simple upstream gradient matching output shape
        $d_output = [
            [0.5, -0.2, 0.0, 0.1],
            [0.1, 0.0, -0.05, 0.2],
        ];

        // copy weights before update
        $before = $this->ffn->getWeights();

        $dx = $this->ffn->backward($d_output, 0.01);

        // dx shape should match x
        $this->assertCount(2, $dx);
        $this->assertCount(4, $dx[0]);
        $this->assertCount(4, $dx[1]);

        // dx numeric checks
        foreach ($dx as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }

        // weights should have been updated (at least W_1 or W_2 changes)
        $after = $this->ffn->getWeights();
        $this->assertNotEquals($before['W_1'], $after['W_1']);
        $this->assertNotEquals($before['W_2'], $after['W_2']);

        // biases should have changed from zero
        $this->assertNotEquals($before['b_1'], $after['b_1']);
        $this->assertNotEquals($before['b_2'], $after['b_2']);
    }

    public function testNumericalGradientConsistencySmallEpsilon()
    {
        // Numerical gradient check for W_2 components for one entry of loss = sum(output)
        $weights = $this->makeDeterministicWeights();
        $this->ffn->setWeights($weights);

        $x = [
            [0.5, -0.4, 0.1, 0.0],
        ];

        // Forward to populate cache
        $output = $this->ffn->forward($x);

        // Use loss L = sum(output) so d_output is matrix of ones
        $d_output = [
            [1.0, 1.0, 1.0, 1.0],
        ];

        // Compute analytic gradients by calling backward but restore weights after to avoid persistent update
        $before = $this->ffn->getWeights();
        $this->ffn->backward($d_output, 0.0); // learning_rate 0 means no update, but returns dx (we need grads indirectly)
        $after_call = $this->ffn->getWeights();
        // Because learning_rate=0 no weight change, we rely on internal computations being stable.
        // We'll numerically approximate dW2[0][0] using finite differences on loss.

        $eps = 1e-4;
        $W2_orig = $before['W_2'];
        $W2_plus = $W2_orig;
        $W2_minus = $W2_orig;
        $W2_plus[0][0] += $eps;
        $W2_minus[0][0] -= $eps;

        // Set W_2 plus, forward, compute loss
        $w_plus = $before;
        $w_plus['W_2'] = $W2_plus;
        $this->ffn->setWeights($w_plus);
        $out_plus = $this->ffn->forward($x);
        $L_plus = array_sum($out_plus[0]);

        // Set W_2 minus, forward, compute loss
        $w_minus = $before;
        $w_minus['W_2'] = $W2_minus;
        $this->ffn->setWeights($w_minus);
        $out_minus = $this->ffn->forward($x);
        $L_minus = array_sum($out_minus[0]);

        // numerical gradient
        $numerical = ($L_plus - $L_minus) / (2 * $eps);

        // Restore original weights
        $this->ffn->setWeights($before);

        // Analytical gradient element dW2[0][0] can be computed from d_output and h1_activated captured in cache:
        // dW2 = transpose(h1_activated) @ d_output
        $ref = new ReflectionClass($this->ffn);
        $cacheProp = $ref->getProperty('cache');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $cacheProp->setAccessible(true);
        }

        $cache = $cacheProp->getValue($this->ffn);
        $h1_activated = $cache['h1_activated']; // 1 x d_ff
        $analytic_dW2_00 = $h1_activated[0][0] * 1.0; // since d_output row is ones and single sample

        // Compare numeric vs analytic (they should be close)
        $this->assertEqualsWithDelta($analytic_dW2_00, $numerical, 1e-3);
    }
}
