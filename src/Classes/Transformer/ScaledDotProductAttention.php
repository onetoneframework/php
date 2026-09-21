<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Transformer;

use Clover\Classes\Math\MatrixOps;

/**
 * Scaled Dot-Product Attention (Paper 3.2.1)
 * 
 * Attention(Q, K, V) = softmax(QK^T / √d_k) V
 * 
 * - Q: Queries (seq_len x d_k)
 * - K: Keys (seq_len x d_k)
 * - V: Values (seq_len x d_v)
 * - d_k: Key vector
 */
class ScaledDotProductAttention
{
    private $d_k;
    private $cache = [];

    public function __construct($d_k)
    {
        $this->d_k = $d_k;
    }

    /**
     * Forward pass
     * 
     * @param array $Q Queries
     * @param array $K Keys
     * @param array $V Values
     * @param array|null $mask Attention mask (causal mask for decoder)
     * @return array Attention output
     */
    public function forward($Q, $K, $V, $mask = null)
    {
        // 1. Compute scores: QK^T
        $K_T = MatrixOps::transpose($K);
        $scores = MatrixOps::matmul($Q, $K_T);

        // 2. Scale by √d_k
        $scale = 1.0 / sqrt($this->d_k);
        $scores = MatrixOps::scale($scores, $scale);

        // 3. Apply mask (if provided)
        if ($mask !== null) {
            $scores_count = count($scores);

            for ($i = 0; $i < $scores_count; $i++) {
                $scores_i_count = count($scores[$i]);

                for ($j = 0; $j < $scores_i_count; $j++) {
                    if ($mask[$i][$j] == 0) {
                        $scores[$i][$j] = -1e9; // Large negative number
                    }
                }
            }
        }

        // 4. Apply softmax
        $attention_weights = MatrixOps::softmax($scores);

        // 5. Multiply by V
        $output = MatrixOps::matmul($attention_weights, $V);

        // Cache for backward pass
        $this->cache = [
            'Q' => $Q,
            'K' => $K,
            'V' => $V,
            'scores' => $scores,
            'attention_weights' => $attention_weights,
            'mask' => $mask
        ];

        return $output;
    }

    /**
     * Backward pass
     * 
     * @param array $d_output Gradient from next layer
     * @return array Gradients for Q, K, V
     */
    public function backward($d_output)
    {
        $Q = $this->cache['Q'];
        $K = $this->cache['K'];
        $V = $this->cache['V'];
        $attention_weights = $this->cache['attention_weights'];

        // d_output: gradient of loss w.r.t output
        // output = attention_weights @ V

        // 1. Gradient w.r.t V
        $d_V = MatrixOps::matmul(MatrixOps::transpose($attention_weights), $d_output);

        // 2. Gradient w.r.t attention_weights
        $d_attention_weights = MatrixOps::matmul($d_output, MatrixOps::transpose($V));

        // 3. Gradient through softmax
        $d_scores = MatrixOps::softmaxBackward($d_attention_weights, $attention_weights);

        // 4. Gradient through scaling
        $scale = 1.0 / sqrt($this->d_k);
        $d_scores = MatrixOps::scale($d_scores, $scale);

        // 5. Gradient w.r.t Q and K (through QK^T)
        $d_Q = MatrixOps::matmul($d_scores, $K);
        $d_K = MatrixOps::matmul(MatrixOps::transpose($d_scores), $Q);

        return ['d_Q' => $d_Q, 'd_K' => $d_K, 'd_V' => $d_V];
    }
}
