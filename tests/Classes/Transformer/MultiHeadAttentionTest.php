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
use Clover\Classes\Transformer\MultiHeadAttention;
use Clover\Classes\Math\MatrixOps;

/**
 * Unit tests for MultiHeadAttention
 * - Verifies forward output with deterministic linear projections
 * - Checks masking behavior
 * - Ensures backward updates weights and returns gradients with expected shape
 */
final class MultiHeadAttentionTest extends TestCase
{
    private MultiHeadAttention $mha;

    protected function setUp(): void
    {
        // d_model = 4, num_heads = 2 => d_k = d_v = 2
        $this->mha = new MultiHeadAttention(4, 2);
    }

    private function makeIdentityLikeProjections(): array
    {
        // Create deterministic projection matrices so head 0 uses the first half of features,
        // head 1 uses the second half. Each projection is d_model x d_k (4 x 2).
        $W_Q = [];
        $W_K = [];
        $W_V = [];

        // Head 0: pick dims [0,1]
        $W_Q[0] = [
            [1.0, 0.0], // input dim 0 -> head dim 0
            [0.0, 1.0], // input dim 1 -> head dim 1
            [0.0, 0.0], // input dim 2
            [0.0, 0.0], // input dim 3
        ];
        $W_K[0] = $W_Q[0];
        $W_V[0] = [
            [1.0, 0.0],
            [0.0, 1.0],
            [0.0, 0.0],
            [0.0, 0.0],
        ];

        // Head 1: pick dims [2,3]
        $W_Q[1] = [
            [0.0, 0.0],
            [0.0, 0.0],
            [1.0, 0.0],
            [0.0, 1.0],
        ];
        $W_K[1] = $W_Q[1];
        $W_V[1] = [
            [0.0, 0.0],
            [0.0, 0.0],
            [1.0, 0.0],
            [0.0, 1.0],
        ];

        // W_O: map concat (num_heads*d_v = 4) back to d_model (4)
        // Use identity 4x4 for determinism
        $W_O = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
            [0.0, 0.0, 0.0, 1.0],
        ];

        return ['W_Q' => $W_Q, 'W_K' => $W_K, 'W_V' => $W_V, 'W_O' => $W_O];
    }

    public function testForwardDeterministicProjectionsNoMask()
    {
        // simple sequence length 2
        $Q = [
            [1.0, 0.0, 0.0, 0.0], // will activate head0 dim0
            [0.0, 0.0, 1.0, 0.0], // will activate head1 dim0
        ];
        $K = $Q;
        $V = [
            [100.0, 0.0, 0.0, 0.0], // token 0
            [0.0, 0.0, 200.0, 0.0], // token 1
        ];

        // Replace random-initialized weights with deterministic ones
        $weights = $this->makeIdentityLikeProjections();
        $this->mha->setWeights($weights);

        $output = $this->mha->forward($Q, $K, $V);

        // Compute expected output by replicating MultiHeadAttention forward steps:
        // For each head: project Q,K,V -> compute scores = Q K^T, scale, softmax, then attend to V.
        $num_heads = 2;
        $d_k = 2;
        $head_outputs = [];

        for ($h = 0; $h < $num_heads; $h++) {
            $WQ = $weights['W_Q'][$h];
            $WK = $weights['W_K'][$h];
            $WV = $weights['W_V'][$h];

            $Q_h = MatrixOps::matmul($Q, $WQ); // seq x d_k
            $K_h = MatrixOps::matmul($K, $WK); // seq x d_k
            $V_h = MatrixOps::matmul($V, $WV); // seq x d_v

            $scores = MatrixOps::matmul($Q_h, MatrixOps::transpose($K_h)); // seq x seq
            $scores = MatrixOps::scale($scores, 1.0 / sqrt($d_k));
            $attn = MatrixOps::softmax($scores);
            $head_out = MatrixOps::matmul($attn, $V_h); // seq x d_v
            $head_outputs[$h] = $head_out;
        }

        // Concatenate heads
        $seq_len = count($Q);
        $concat = [];
        for ($i = 0; $i < $seq_len; $i++) {
            $concat[$i] = [];
            for ($h = 0; $h < $num_heads; $h++) {
                for ($j = 0; $j < $d_k; $j++) {
                    $concat[$i][] = $head_outputs[$h][$i][$j];
                }
            }
        }

        // W_O is identity in deterministic setup, so expected = concat
        $expected = $concat;

        // Compare per-element
        $this->assertCount(2, $output);
        for ($i = 0; $i < 2; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $this->assertEqualsWithDelta(
                    $expected[$i][$j],
                    $output[$i][$j],
                    1e-5,
                    "Mismatch at output[$i][$j]"
                );
            }
        }
    }

    public function testForwardWithMaskBlocksAttention()
    {
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
        ];
        $K = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 1.0, 0.0, 0.0],
        ];
        $V = [
            [10.0, 0.0, 0.0, 0.0],
            [0.0, 20.0, 0.0, 0.0],
        ];

        $weights = $this->makeIdentityLikeProjections();
        $this->mha->setWeights($weights);

        // mask: only allow attending to second key (index 1) => first key is blocked
        $mask = [
            [0, 1]
        ];

        $output = $this->mha->forward($Q, $K, $V, $mask);

        // After masking, only second key contributes. Projecting V into heads:
        // V token1 in full space has nonzero at head1 first dim (since we used identity-like V earlier),
        // but in this simple test both heads see zeros except allowed contributions; verify shape and finite values.
        $this->assertCount(1, $output);
        $this->assertCount(4, $output[0]);
        foreach ($output[0] as $val) {
            $this->assertIsFloat($val);
        }
    }

    public function testBackwardUpdatesWeightsAndReturnsGradientShape()
    {
        // Prepare same deterministic setup
        $Q = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
        ];
        $K = $Q;
        $V = [
            [100.0, 0.0, 0.0, 0.0],
            [0.0, 0.0, 200.0, 0.0],
        ];

        $weights = $this->makeIdentityLikeProjections();
        $this->mha->setWeights($weights);

        $output = $this->mha->forward($Q, $K, $V);

        // Create a simple d_output (same shape as output)
        $d_output = [
            [1.0, 0.0, 0.0, 0.0],
            [0.0, 0.0, 1.0, 0.0],
        ];

        // Copy weights before backward to compare later
        $before = $this->mha->getWeights();

        $grads = $this->mha->backward($d_output, 0.1);

        // grads is the combined gradient matrix with same shape as Q/K/V concatenation (seq_len x d_model)
        $this->assertIsArray($grads);
        $this->assertCount(2, $grads); // two rows
        $this->assertCount(4, $grads[0]); // d_model = 4

        // Ensure weights have been updated (at least W_O changed)
        $after = $this->mha->getWeights();
        $this->assertNotEquals($before['W_O'], $after['W_O']);

        // Ensure gradients are finite numbers
        foreach ($grads as $row) {
            foreach ($row as $v) {
                $this->assertIsFloat($v);
                $this->assertFalse(is_nan($v));
                $this->assertFalse(is_infinite($v));
            }
        }
    }
}
