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
use Clover\Classes\Transformer\TransformerEncoderLayer;

/**
 * Unit tests for TransformerEncoderLayer
 * - Verifies forward pass shape and numerical stability
 * - Ensures mask affects attention outputs (basic check)
 * - Validates backward pass returns gradients with correct shape and updates parameters
 */
final class TransformerEncoderLayerTest extends TestCase
{
    private TransformerEncoderLayer $layer;

    protected function setUp(): void
    {
        // Use small model for tests: d_model=4, num_heads=2, d_ff=8
        $this->layer = new TransformerEncoderLayer(4, 2, 8);
    }

    private function makeDeterministicWeights(): array
    {
        // Build a deterministic, identity-like weight set for contained modules:
        // - MultiHeadAttention weights: W_Q/W_K/W_V per head and W_O
        // - FeedForward: W_1, b_1, W_2, b_2
        // - LayerNorms: gamma and beta
        $d_model = 4;
        $num_heads = 2;
        $d_k = intval($d_model / $num_heads); // 2
        $d_v = $d_k;
        $d_ff = 8;

        // MultiHeadAttention weights
        $W_Q = [];
        $W_K = [];
        $W_V = [];
        for ($h = 0; $h < $num_heads; $h++) {
            // simple selector projections that split the model dimension between heads
            if ($h === 0) {
                $W_Q[$h] = [
                    [1.0, 0.0],
                    [0.0, 1.0],
                    [0.0, 0.0],
                    [0.0, 0.0],
                ];
                $W_K[$h] = $W_Q[$h];
                $W_V[$h] = [
                    [1.0, 0.0],
                    [0.0, 1.0],
                    [0.0, 0.0],
                    [0.0, 0.0],
                ];
            } else {
                $W_Q[$h] = [
                    [0.0, 0.0],
                    [0.0, 0.0],
                    [1.0, 0.0],
                    [0.0, 1.0],
                ];
                $W_K[$h] = $W_Q[$h];
                $W_V[$h] = [
                    [0.0, 0.0],
                    [0.0, 0.0],
                    [1.0, 0.0],
                    [0.0, 1.0],
                ];
            }
        }
        // W_O identity (4x4) mapping concat back to model dim
        $W_O = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
            [0.0, 0.0, 0.0, 1.0],
        ];

        $mha_weights = ['W_Q' => $W_Q, 'W_K' => $W_K, 'W_V' => $W_V, 'W_O' => $W_O];

        // Feed-forward deterministic weights
        $W_1 = [];
        for ($i = 0; $i < $d_model; $i++) {
            $W_1[$i] = [];
            for ($j = 0; $j < $d_ff; $j++) {
                $W_1[$i][$j] = ($i + 1) * 0.1 + $j * 0.01;
            }
        }
        $b_1 = array_fill(0, $d_ff, 0.0);

        $W_2 = [];
        for ($i = 0; $i < $d_ff; $i++) {
            $W_2[$i] = [];
            for ($j = 0; $j < $d_model; $j++) {
                $W_2[$i][$j] = ($j + 1) * 0.05 + $i * 0.001;
            }
        }
        $b_2 = array_fill(0, $d_model, 0.0);

        $ff_weights = ['W_1' => $W_1, 'b_1' => $b_1, 'W_2' => $W_2, 'b_2' => $b_2];

        // LayerNorm weights
        $ln_weights = ['gamma' => array_fill(0, $d_model, 1.0), 'beta' => array_fill(0, $d_model, 0.0)];

        return [
            'multi_head_attention' => $mha_weights,
            'feed_forward' => $ff_weights,
            'layer_norm_1' => $ln_weights,
            'layer_norm_2' => $ln_weights,
        ];
    }

    public function testForwardProducesCorrectShapeAndFiniteValues()
    {
        $weights = $this->makeDeterministicWeights();
        $this->layer->setWeights($weights);

        // Input: sequence length 2, d_model = 4
        $x = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
        ];

        $out = $this->layer->forward($x, null);

        // output shape should be seq_len x d_model
        $this->assertCount(2, $out);
        $this->assertCount(4, $out[0]);

        // values finite
        foreach ($out as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }
    }

    public function testMaskAffectsAttention()
    {
        $weights = $this->makeDeterministicWeights();
        $this->layer->setWeights($weights);

        $x = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
        ];

        // No mask
        $out_no_mask = $this->layer->forward($x, null);

        // Causal mask that blocks attention to the second token for the first query
        $mask = [
            [1, 0],
            [1, 1],
        ];

        $out_with_mask = $this->layer->forward($x, $mask);

        // Outputs should differ when mask is applied (at least one element differs)
        $this->assertNotEquals($out_no_mask, $out_with_mask);
    }

    public function testBackwardReturnsGradientAndUpdatesParameters()
    {
        $weights = $this->makeDeterministicWeights();
        $this->layer->setWeights($weights);

        $x = [
            [0.5, -0.2, 0.1, 0.0],
            [0.0, 1.0, -0.5, 0.2],
        ];

        $out = $this->layer->forward($x, null);

        // simple upstream gradient (same shape)
        $d_output = [
            [0.1, 0.0, -0.05, 0.2],
            [0.0, 0.2, 0.1, -0.1],
        ];

        $before = $this->layer->getWeights();

        $d_x = $this->layer->backward($d_output, 0.01);

        // d_x shape equals input shape
        $this->assertCount(2, $d_x);
        $this->assertCount(4, $d_x[0]);

        // Ensure parameters updated: at least feed-forward or layernorm weights change
        $after = $this->layer->getWeights();
        $this->assertNotEquals($before['feed_forward']['W_1'], $after['feed_forward']['W_1']);
        $this->assertNotEquals($before['layer_norm_1'], $after['layer_norm_1']);
    }

    public function testGradientPropagationSanity()
    {
        $weights = $this->makeDeterministicWeights();
        $this->layer->setWeights($weights);

        $x = [
            [0.2, 0.1, -0.1, 0.0],
        ];

        $out = $this->layer->forward($x, null);

        // Upstream gradient of ones
        $d_output = [
            [1.0, 1.0, 1.0, 1.0],
        ];

        $d_x = $this->layer->backward($d_output, 0.0); // lr 0 to avoid parameter updates for this sanity check

        // d_x should be finite and of correct shape
        $this->assertCount(1, $d_x);
        $this->assertCount(4, $d_x[0]);
        foreach ($d_x[0] as $v) {
            $this->assertIsFloat($v);
            $this->assertFalse(is_nan($v));
            $this->assertFalse(is_infinite($v));
        }
    }
}
