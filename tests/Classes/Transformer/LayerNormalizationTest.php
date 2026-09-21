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
use Clover\Classes\Transformer\LayerNormalization;
use ReflectionClass;

/**
 * Unit tests for LayerNormalization
 * 
 * Verifies:
 * - Forward normalization produces mean ≈ 0 and variance ≈ 1
 * - Backward pass correctly updates gamma and beta
 * - Weight getter/setter work as expected
 */
final class LayerNormalizationTest extends TestCase
{
    private LayerNormalization $ln;

    protected function setUp(): void
    {
        // d_model = 4 for tests
        $this->ln = new LayerNormalization(4);
    }


    public function testForwardNormalization(): void
    {
        $layerNorm = new LayerNormalization(4);

        $x = [
            [1.0, 2.0, 3.0, 4.0],
            [2.0, 4.0, 6.0, 8.0],
        ];

        $output = $layerNorm->forward($x);

        // Each row should have mean ≈ 0 and variance ≈ 1
        foreach ($output as $row) {
            $mean = array_sum($row) / count($row);
            $variance = 0.0;
            foreach ($row as $val) {
                $variance += pow($val - $mean, 2);
            }
            $variance /= count($row);

            $this->assertTrue(abs($mean) < 1e-6, "Mean should be approximately 0, got $mean");
            $this->assertTrue(abs($variance - 1.0) < 1e-5, "Variance should be approximately 1, got $variance");
        }
    }

    public function testBackwardUpdatesWeights(): void
    {
        $layerNorm = new LayerNormalization(4);

        $x = [
            [1.0, 2.0, 3.0, 4.0],
        ];

        $out = $layerNorm->forward($x);

        // Gradient from loss (simple case: all ones)
        $d_output = [[1.0, 1.0, 1.0, 1.0]];

        $old_weights = $layerNorm->getWeights();
        $layerNorm->backward($d_output, 0.01);
        $new_weights = $layerNorm->getWeights();

        // Ensure parameters have been updated
        $this->assertNotEquals($old_weights['gamma'], $new_weights['gamma'], "Gamma must be updated after backward pass");
        $this->assertNotEquals($old_weights['beta'], $new_weights['beta'], "Beta must be updated after backward pass");
    }

    public function testSetAndGetWeights(): void
    {
        $layerNorm = new LayerNormalization(3);

        $weights = [
            'gamma' => [1.1, 0.9, 1.05],
            'beta' => [0.2, -0.1, 0.3],
        ];

        $layerNorm->setWeights($weights);
        $retrieved = $layerNorm->getWeights();

        $this->assertSame($weights, $retrieved, "Weights retrieved must match the set values");
    }


    private function getCacheStats(): array
    {
        $ref = new ReflectionClass($this->ln);
        $prop = $ref->getProperty('cache');

        if (version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $prop->setAccessible(true);
        }

        return $prop->getValue($this->ln);
    }

    public function testForwardZeroMeanUnitVarianceBeforeScale()
    {
        $x = [
            [1.0, 2.0, 3.0, 4.0],
            [0.5, -0.5, 2.0, -1.0],
        ];

        $out = $this->ln->forward($x);

        // Access cached x_hat and std to verify normalization properties
        $cache = $this->getCacheStats();
        $stats = $cache['stats'];

        foreach ($stats as $i => $s) {
            $x_hat = $s['x_hat'];
            $mean = array_sum($x_hat) / count($x_hat);
            $variance = 0.0;
            foreach ($x_hat as $v) {
                $variance += pow($v - $mean, 2);
            }
            $variance /= count($x_hat);

            // x_hat should have mean approximately 0 and variance approximately 1
            $this->assertEqualsWithDelta(0.0, $mean, 1e-9);
            $this->assertEqualsWithDelta(1.0, $variance, 1e-6);
        }

        // Output should be floats and finite
        foreach ($out as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }
    }

    public function testGammaBetaAffectOutput()
    {
        $x = [
            [1.0, 2.0, 3.0, 4.0],
        ];

        // Default gamma=1, beta=0
        $out_default = $this->ln->forward($x);

        // Set gamma to [2,2,2,2] and beta to [1,1,1,1]
        $weights = $this->ln->getWeights();
        $weights['gamma'] = array_fill(0, 4, 2.0);
        $weights['beta'] = array_fill(0, 4, 1.0);
        $this->ln->setWeights($weights);

        $out_scaled = $this->ln->forward($x);

        // Each element in out_scaled should equal 2 * out_default + 1 (elementwise)
        for ($j = 0; $j < 4; $j++) {
            $this->assertEqualsWithDelta(2.0 * $out_default[0][$j] + 1.0, $out_scaled[0][$j], 1e-9);
        }
    }

    public function testBackwardReturnsDxAndUpdatesGammaBeta()
    {
        $x = [
            [0.1, -0.2, 0.3, -0.4],
            [1.0, 0.0, -1.0, 0.5],
        ];

        $this->ln->forward($x);

        // simple upstream gradient (same shape)
        $d_output = [
            [0.5, -0.1, 0.0, 0.2],
            [0.1, 0.0, -0.05, 0.3],
        ];

        $before = $this->ln->getWeights();

        $d_x = $this->ln->backward($d_output, 0.01);

        // dx shape should match input
        $this->assertCount(2, $d_x);
        $this->assertCount(4, $d_x[0]);
        $this->assertCount(4, $d_x[1]);

        // dx numeric checks
        foreach ($d_x as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }

        // gamma and beta should have been updated
        $after = $this->ln->getWeights();
        $this->assertNotEquals($before['gamma'], $after['gamma']);
        $this->assertNotEquals($before['beta'], $after['beta']);
    }

    public function testNumericalGradientGammaBeta()
    {
        $x = [
            [0.2, -0.1, 0.3, 0.0],
        ];

        // Forward to populate cache
        $this->ln->forward($x);

        // Loss L = sum(out) -> d_output is ones
        $d_output = [
            [1.0, 1.0, 1.0, 1.0],
        ];

        // Analytical gradients for gamma and beta can be obtained by calling backward with lr=0
        // but backward updates gamma/beta; instead use finite difference on loss w.r.t gamma/beta

        $orig = $this->ln->getWeights();
        $eps = 1e-5;

        // Numerical gradient for gamma[0]
        $w_plus = $orig;
        $w_minus = $orig;
        $w_plus['gamma'][0] += $eps;
        $w_minus['gamma'][0] -= $eps;

        $this->ln->setWeights($w_plus);
        $out_plus = $this->ln->forward($x);
        $L_plus = array_sum($out_plus[0]);

        $this->ln->setWeights($w_minus);
        $out_minus = $this->ln->forward($x);
        $L_minus = array_sum($out_minus[0]);

        $numerical_gamma0 = ($L_plus - $L_minus) / (2 * $eps);

        // Restore original weights
        $this->ln->setWeights($orig);

        // Now compute analytic gradient for gamma[0] from backward partials:
        // dL/dgamma = sum_j d_output_j * x_hat_j ; with d_output all ones here
        $this->ln->forward($x);
        $cache = $this->getCacheStats();
        $x_hat = $cache['stats'][0]['x_hat'];
        $analytic_gamma0 = $x_hat[0] * 1.0; // because d_output is ones and single sample

        // Compare with relaxed tolerance
        $this->assertEqualsWithDelta($analytic_gamma0, $numerical_gamma0, 1e-3);
    }
}
