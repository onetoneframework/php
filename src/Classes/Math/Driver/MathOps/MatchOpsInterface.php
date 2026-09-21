<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math\MathOps\Driver;

use Tensor\Matrix;
use Tensor\Vector;
use Exception;

/**
 * Full Transformer Implementation (Faithful to the paper)
 * * A precise implementation of "Attention Is All You Need" (Vaswani et al., 2017)
 * * - Full Multi-Head Attention
 * - Full Position-wise Feed-Forward Networks  
 * - Layer Normalization (Correct formula)
 * - Residual Connections
 * - Positional Encoding (sin/cos)
 * - Full Backpropagation (All layers)
 * - Character-level Tokenizer (Generalizable)
 * * Note: Training in pure PHP will be extremely slow.
 */
interface MatchOpsInterface
{
    /**
     * Matrix Multiplication
     * 
     * Mathematical Formula:
     * - C = A × B
     * - C_{ij} = \sum_{k=1}^{n} A_{ik} \cdot B_{kj}
     * 
     * where:
     * - A is an m×n matrix
     * - B is an n×p matrix
     * - C is the resulting m×p matrix
     * 
     * @param array $A Matrix A (m × n)
     * @param array $B Matrix B (n × p)
     * @return array Result C (m × p)
     */
    public static function matmul($A, $B);

    /**
     * Matrix Transpose
     * 
     * Mathematical Formula:
     * - B = A^T
     * - B_{ij} = A_{ji}
     * 
     * Transforms an m×n matrix into an n×m matrix
     * 
     * @param array $A Matrix A
     * @return array Transposed matrix A^T
     */
    public static function transpose($A);

    /**
     * Softmax Function
     * 
     * Mathematical Formula:
     * - \text{softmax}(x_i) = \frac{e^{x_i}}{\sum_{j=1}^{n} e^{x_j}}
     * - Softmax: softmax(x_i) = exp(x_i) / Σ exp(x_j)
     * 
     * With numerical stability (subtract max):
     * - \text{softmax}(x_i) = \frac{e^{x_i - \max(x)}}{\sum_{j=1}^{n} e^{x_j - \max(x)}}
     * 
     * Properties:
     * - Output range: (0, 1)
     * - Sum of outputs = 1
     * - Converts logits to probability distribution
     * 
     * @param array $X Input matrix (logits)
     * @return array Probability distribution (sum = 1)
     */
    public static function softmax($X);

    /**
     * Softmax Backward Pass
     * d_softmax: gradient from the upper layer
     * softmax_output: softmax output from the forward pass
     * * Formula: d_x[i] = Σ_j (d_softmax[j] * softmax[i] * (δ_ij - softmax[j]))
     */
    public static function softmaxBackward($d_softmax, $softmax_output);

    /**
     * Matrix subtraction: C = A - B (element-wise)
     */
    public static function subtract($A, $B);

    /**
     * Element-wise Matrix Addition
     * 
     * Mathematical Formula:
     * - C = A + B
     * - C_{ij} = A_{ij} + B_{ij}
     * 
     * Used in residual connections:
     * - \text{output} = x + \text{SubLayer}(x)
     * 
     * @param array $A Matrix A
     * @param array $B Matrix B
     * @return array Result C = A + B
     */
    public static function add($A, $B);

    /**
     * Scalar Multiplication
     * 
     * Mathematical Formula:
     * B = s \cdot A
     * B_{ij} = s \cdot A_{ij}
     * 
     * Used in attention scaling:
     * \text{scores} = \frac{QK^T}{\sqrt{d_k}}
     * 
     * @param array $A Matrix A
     * @param float $s Scalar value s
     * @return array Result B = s·A
     */
    public static function scale($A, $s);

    /**
     * Xavier/Glorot Initialization
     * 
     * Paper: "Understanding the difficulty of training deep feedforward neural networks"
     * 
     * Mathematical Formula:
     * W_{ij} \sim \mathcal{U}(-\sqrt{\frac{6}{n_{in} + n_{out}}}, \sqrt{\frac{6}{n_{in} + n_{out}}})
     * 
     * where:
     * - n_{in} = number of input units
     * - n_{out} = number of output units
     * - \mathcal{U}(a, b) = uniform distribution between a and b
     * 
     * Purpose: Maintains variance of activations across layers
     * 
     * @param int $rows Number of rows (input dimension)
     * @param int $cols Number of columns (output dimension)
     * @return array Initialized weight matrix
     */
    public static function xavierInit($rows, $cols);

    /**
     * Gradient Clipping (by global norm)
     * Used to prevent gradient exploding.
     */
    public static function clipGradient($grad, $max_norm = 5.0);

    public static function multiply($A, $B);

    /**
     * GELU activation (approximation)
     * @param mixed $x
     * @return float|int
     */
    public static function gelu($x);

    /**
     * Simplified GELU derivative
     * @param mixed $x
     * @return float|int
     */
    public static function geluDerivative($x);

    /**
     * Weight Update - Simplified version
     * 
     * Mathematical Formula (Standard SGD):
     * W_{new} = W_{old} - \eta \cdot \nabla L
     * 
     * where:
     * - \eta = learning rate
     * - \nabla L = gradient of loss with respect to W
     * 
     * Simplified version (evolutionary):
     * W_{new} = W_{old} + \text{direction} \cdot \eta \cdot \text{noise}
     * 
     * @param array $w Weights (modified in place)
     * @param float $lr Learning rate η
     * @param int $direction Update direction (-1 or 1)
     */
    public static function updateWeights(&$w, $lr, $direction = -1, $clip = 1.0);

    public static function zeros($A, $B);

    public static function max($A);

    public static function substractExp($A, $max);

    /**
     * Positional Encoding (Paper 3.5)
     * * PE_(pos,2i) = sin(pos / 10000^(2i/d_model))
     * PE_(pos,2i+1) = cos(pos / 10000^(2i/d_model))
     */
    public static function createPositionalEncoding(int $max_len, int $d_model);

    /**
     * Causal mask for auto-regressive generation
     */
    public static function createCausalMask(int $seq_len);
}