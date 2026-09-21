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

use InvalidArgumentException;

/**
 * Multi-Head Attention (Paper 3.2.2)
 * 
 * * MultiHead(Q, K, V) = Concat(head_1, ..., head_h)W^O
 * - where head_i = Attention(QW^Q_i, KW^K_i, VW^V_i)
 * 
 * - h: number of heads
 * - d_model: model dimension
 * - d_k = d_v = d_model / h
 */
class MultiHeadAttention
{
    private $d_model;
    private $num_heads;
    private $d_k;
    private $d_v;

    // Parameter: W^Q, W^K, W^V (per head), W^O
    private $W_Q;
    private $W_K;
    private $W_V;
    private $W_O;

    /** @var ScaledDotProductAttention[] $attention_heads */
    private $attention_heads = [];

    private $cache = [];

    public function __construct($d_model, $num_heads)
    {
        if ($d_model % $num_heads !== 0) {
            throw new InvalidArgumentException("d_model must be divisible by num_heads");
        }
        
        $this->d_model = $d_model;
        $this->num_heads = $num_heads;
        $this->d_k = intval($d_model / $num_heads);
        $this->d_v = intval($d_model / $num_heads);

        // projection matrices
        $this->W_Q = [];
        $this->W_K = [];
        $this->W_V = [];

        for ($h = 0; $h < $num_heads; $h++) {
            $this->W_Q[$h] = MatrixOps::xavierInit($d_model, $this->d_k);
            $this->W_K[$h] = MatrixOps::xavierInit($d_model, $this->d_k);
            $this->W_V[$h] = MatrixOps::xavierInit($d_model, $this->d_v);

            $this->attention_heads[$h] = new ScaledDotProductAttention($this->d_k);
        }

        // Output projection
        $this->W_O = MatrixOps::xavierInit($num_heads * $this->d_v, $d_model);
    }

    /**
     * Forward pass
     */
    public function forward($Q, $K, $V, $mask = null)
    {
        $seq_len = count($Q);
        $head_outputs = [];

        for ($h = 0; $h < $this->num_heads; $h++) {
            // Linear projections
            $Q_h = MatrixOps::matmul($Q, $this->W_Q[$h]);
            $K_h = MatrixOps::matmul($K, $this->W_K[$h]);
            $V_h = MatrixOps::matmul($V, $this->W_V[$h]);

            // Scaled dot-product attention
            $head_output = $this->attention_heads[$h]->forward($Q_h, $K_h, $V_h, $mask);
            $head_outputs[$h] = $head_output;
        }

        // Concatenate heads
        $concat = [];
        for ($i = 0; $i < $seq_len; $i++) {
            $concat[$i] = [];
            for ($h = 0; $h < $this->num_heads; $h++) {
                for ($j = 0; $j < $this->d_v; $j++) {
                    $concat[$i][] = $head_outputs[$h][$i][$j];
                }
            }
        }

        // Final linear projection
        $output = MatrixOps::matmul($concat, $this->W_O);

        // Cache for backward
        $this->cache = [
            'Q' => $Q,
            'K' => $K,
            'V' => $V,
            'head_outputs' => $head_outputs,
            'concat' => $concat
        ];

        return $output;
    }

    /**
     * Backward pass
     * Computes gradients and updates weights internally (simplified SGD)
     */
    public function backward($d_output, $learning_rate)
    {
        $Q = $this->cache['Q'];
        $K = $this->cache['K'];
        $V = $this->cache['V'];
        $concat = $this->cache['concat'];

        // Gradient through W_O
        $d_W_O = MatrixOps::matmul(MatrixOps::transpose($concat), $d_output);
        $d_W_O = MatrixOps::clipGradient($d_W_O);

        $d_concat = MatrixOps::matmul($d_output, MatrixOps::transpose($this->W_O));

        // Split gradient for each head
        $d_Q_total = MatrixOps::zeros(count($Q), $this->d_model);
        $d_K_total = MatrixOps::zeros(count($K), $this->d_model);
        $d_V_total = MatrixOps::zeros(count($V), $this->d_model);

        for ($h = 0; $h < $this->num_heads; $h++) {
            // Extract gradient for this head
            $d_head = [];
            $d_concat_count = count($d_concat);
            for ($i = 0; $i < $d_concat_count; $i++) {
                $d_head[$i] = [];

                for ($j = 0; $j < $this->d_v; $j++) {
                    $d_head[$i][$j] = $d_concat[$i][$h * $this->d_v + $j];
                }
            }

            // Backward through attention
            $grads = $this->attention_heads[$h]->backward($d_head);
            $d_Q_h = $grads['d_Q'];
            $d_K_h = $grads['d_K'];
            $d_V_h = $grads['d_V'];

            // Backward through projections
            $d_W_Q_h = MatrixOps::matmul(MatrixOps::transpose($Q), $d_Q_h);
            $d_W_K_h = MatrixOps::matmul(MatrixOps::transpose($K), $d_K_h);
            $d_W_V_h = MatrixOps::matmul(MatrixOps::transpose($V), $d_V_h);

            $d_W_Q_h = MatrixOps::clipGradient($d_W_Q_h);
            $d_W_K_h = MatrixOps::clipGradient($d_W_K_h);
            $d_W_V_h = MatrixOps::clipGradient($d_W_V_h);

            // Update weights
            $this->W_Q[$h] = MatrixOps::subtract($this->W_Q[$h], MatrixOps::scale($d_W_Q_h, $learning_rate));
            $this->W_K[$h] = MatrixOps::subtract($this->W_K[$h], MatrixOps::scale($d_W_K_h, $learning_rate));
            $this->W_V[$h] = MatrixOps::subtract($this->W_V[$h], MatrixOps::scale($d_W_V_h, $learning_rate));

            // Accumulate gradients for Q, K, V
            $d_Q_from_h = MatrixOps::matmul($d_Q_h, MatrixOps::transpose($this->W_Q[$h]));
            $d_K_from_h = MatrixOps::matmul($d_K_h, MatrixOps::transpose($this->W_K[$h]));
            $d_V_from_h = MatrixOps::matmul($d_V_h, MatrixOps::transpose($this->W_V[$h]));

            $d_Q_total = MatrixOps::add($d_Q_total, $d_Q_from_h);
            $d_K_total = MatrixOps::add($d_K_total, $d_K_from_h);
            $d_V_total = MatrixOps::add($d_V_total, $d_V_from_h);
        }

        // Update W_O
        $this->W_O = MatrixOps::subtract($this->W_O, MatrixOps::scale($d_W_O, $learning_rate));

        // Return combined gradients
        return MatrixOps::add(MatrixOps::add($d_Q_total, $d_K_total), $d_V_total);
    }

    public function getWeights()
    {
        return [
            'W_Q' => $this->W_Q,
            'W_K' => $this->W_K,
            'W_V' => $this->W_V,
            'W_O' => $this->W_O
        ];
    }

    public function setWeights($weights)
    {
        $this->W_Q = $weights['W_Q'];
        $this->W_K = $weights['W_K'];
        $this->W_V = $weights['W_V'];
        $this->W_O = $weights['W_O'];
    }
}
