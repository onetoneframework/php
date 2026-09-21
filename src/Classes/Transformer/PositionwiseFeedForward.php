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
 * Position-wise Feed-Forward Networks (3.3)
 * 
 * FFN(x) = max(0, xW_1 + b_1)W_2 + b_2
 * 
 * * This implementation uses GELU instead of ReLU for smoother activation.
 */
class PositionwiseFeedForward
{
    private $d_model;
    private $d_ff;

    private $W_1;
    private $b_1;
    private $W_2;
    private $b_2;

    private $cache = [];

    public function __construct($d_model, $d_ff)
    {
        $this->d_model = $d_model;
        $this->d_ff = $d_ff;

        $this->W_1 = MatrixOps::xavierInit($d_model, $d_ff);
        $this->b_1 = array_fill(0, $d_ff, 0.0);
        $this->W_2 = MatrixOps::xavierInit($d_ff, $d_model);
        $this->b_2 = array_fill(0, $d_model, 0.0);
    }

    /**
     * GELU(x) = x * Φ(x)
     * where Φ(x) is the cumulative distribution function of the standard Gaussian
     * 
     * GELU(x) ≈ 0.5x(1 + tanh[√(2/π)(x + 0.044715x³)])
     */
    private function gelu($x)
    {
        return $x * 0.5 * (1.0 + tanh(sqrt(2.0 / M_PI) * ($x + 0.044715 * pow($x, 3))));
    }

    /**
     * Derivative of GELU
     */
    private function geluDerivative($x)
    {
        $tanh_arg = sqrt(2.0 / M_PI) * ($x + 0.044715 * pow($x, 3));
        $tanh_val = tanh($tanh_arg);
        $sech2 = 1.0 - pow($tanh_val, 2);

        $cdf = 0.5 * (1.0 + $tanh_val);
        $pdf = 0.5 * $sech2 * sqrt(2.0 / M_PI) * (1.0 + 3 * 0.044715 * pow($x, 2));

        return $cdf + $x * $pdf;
    }

    /**
     * Forward pass
     */
    public function forward($x)
    {
        // First linear layer: x @ W_1 + b_1
        $h1 = MatrixOps::matmul($x, $this->W_1);
        $h1_count = count($h1);
        for ($i = 0; $i < $h1_count; $i++) {
            $h1_i_count = count($h1[$i]);

            for ($j = 0; $j < $h1_i_count; $j++) {
                $h1[$i][$j] += $this->b_1[$j];
            }
        }

        // Activation: GELU
        $h1_activated = [];
        foreach ($h1 as $row) {
            $activated_row = [];

            foreach ($row as $val) {
                $activated_row[] = $this->gelu($val);
            }

            $h1_activated[] = $activated_row;
        }

        // Second linear layer: h1_activated @ W_2 + b_2
        $output = MatrixOps::matmul($h1_activated, $this->W_2);
        $output_count = count($output);
        for ($i = 0; $i < $output_count; $i++) {
            $output_i_count = count($output[$i]);

            for ($j = 0; $j < $output_i_count; $j++) {
                $output[$i][$j] += $this->b_2[$j];
            }
        }

        // Cache for backward
        $this->cache = [
            'x' => $x,
            'h1' => $h1,
            'h1_activated' => $h1_activated
        ];

        return $output;
    }

    /**
     * Backward pass
     */
    public function backward($d_output, $learning_rate)
    {
        $x = $this->cache['x'];
        $h1 = $this->cache['h1'];
        $h1_activated = $this->cache['h1_activated'];

        // Gradient for b_2
        $d_b_2 = array_fill(0, $this->d_model, 0.0);
        foreach ($d_output as $row) {
            $row_count = count($row);

            for ($j = 0; $j < $row_count; $j++) {
                $d_b_2[$j] += $row[$j];
            }
        }

        // Gradient for W_2
        $d_W_2 = MatrixOps::matmul(MatrixOps::transpose($h1_activated), $d_output);
        $d_W_2 = MatrixOps::clipGradient($d_W_2);

        // Gradient for h1_activated
        $d_h1_activated = MatrixOps::matmul($d_output, MatrixOps::transpose($this->W_2));
        $d_h1_activated_count = count($d_h1_activated);
        // Gradient through GELU
        $d_h1 = [];
        for ($i = 0; $i < $d_h1_activated_count; $i++) {
            $d_h1[$i] = [];
            $d_h1_activated_i = $d_h1_activated[$i];
            $d_h1_activated_i_count = count($d_h1_activated_i);

            for ($j = 0; $j < $d_h1_activated_i_count; $j++) {
                $gelu_grad = $this->geluDerivative($h1[$i][$j]);
                $d_h1[$i][$j] = $d_h1_activated_i[$j] * $gelu_grad;
            }
        }

        // Gradient for b_1
        $d_b_1 = array_fill(0, $this->d_ff, 0.0);
        foreach ($d_h1 as $row) {
            $row_count = count($row);
            for ($j = 0; $j < $row_count; $j++) {
                $d_b_1[$j] += $row[$j];
            }
        }

        // Gradient for W_1
        $d_W_1 = MatrixOps::matmul(MatrixOps::transpose($x), $d_h1);
        $d_W_1 = MatrixOps::clipGradient($d_W_1);

        // Gradient for x
        $d_x = MatrixOps::matmul($d_h1, MatrixOps::transpose($this->W_1));

        // Update weights
        $this->W_1 = MatrixOps::subtract($this->W_1, MatrixOps::scale($d_W_1, $learning_rate));
        $this->W_2 = MatrixOps::subtract($this->W_2, MatrixOps::scale($d_W_2, $learning_rate));

        for ($j = 0; $j < $this->d_ff; $j++) {
            $this->b_1[$j] -= $learning_rate * $d_b_1[$j];
        }

        for ($j = 0; $j < $this->d_model; $j++) {
            $this->b_2[$j] -= $learning_rate * $d_b_2[$j];
        }

        return $d_x;
    }

    public function getWeights()
    {
        return [
            'W_1' => $this->W_1,
            'b_1' => $this->b_1,
            'W_2' => $this->W_2,
            'b_2' => $this->b_2
        ];
    }

    public function setWeights($weights)
    {
        $this->W_1 = $weights['W_1'];
        $this->b_1 = $weights['b_1'];
        $this->W_2 = $weights['W_2'];
        $this->b_2 = $weights['b_2'];
    }
}
