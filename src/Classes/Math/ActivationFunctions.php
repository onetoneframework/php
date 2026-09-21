<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use const M_E;
use function is_array;
use function count;

/**
 * Class ActivationFunctions
 *
 * A collection of activation functions and related utilities for machine learning and neural networks.
 */
class ActivationFunctions
{
    /**
     * Computes the logarithm of a value with a: float specified base.
     *
     * @param float $v The value to compute the logarithm for.
     * @param float $base The base of the logarithm. Default is Euler's number (e).
     * 
     * @return float The logarithm of the value with the specified base.
     */
    public static function log(float $v, float $base = M_E): float
    {
        return log($v, $base);
    }

    /**
     * Computes the log-sum-exp of an array of values.
     *
     * @param array $values The array of values.
     * 
     * @return float The log-sum-exp of the values.
     */
    public static function logsumexp(array $values): float
    {
        return log(array_sum(array_map('exp', $values)));
    }

    /**
     * Error function approximation.
     *
     * @param float $x The input value.
     * 
     * @return float The approximate value of the error function at x.
     */
    public static function erf(float $x): float
    {
        $t = 1 / (1 + 0.5 * abs($x));
        $ans = 1 - $t * exp(-pow($x, 2) - 1.26551223 + $t * (1.00002368 + $t * (0.37409196 + $t * (0.09678418 + $t * (-0.18628806 + $t * (0.27886807 + $t * (-1.13520398 + $t * (1.48851587 + $t * (-0.82215223 + $t * 0.17087277)))))))));
        return ($x >= 0) ? $ans : -$ans;
    }

    /**
     * Hyperbolic tangent activation function.
     *
     * @param float|array $x The input value or array of values.
     * 
     * @return float|array The hyperbolic tangent of the input.
     */
    public static function tanhActivation(float|array $x): array|float
    {
        return is_array($x) ? array_map('tanh', $x) : tanh($x);
    }

    /**
     * Adds two vectors element-wise.
     *
     * @param array $v1 The first vector.
     * @param array $v2 The second vector.
     * 
     * @return array The element-wise sum of the two vectors.
     */
    public static function addVectors(array $v1, array $v2): array
    {
        return array_map(function ($a, $b) {
            return is_array($a) ? self::addVectors($a, $b) : $a + $b;
        }, $v1, $v2);
    }

    /**
     * Multiplies two vectors element-wise.
     *
     * @param array $v1 The first vector.
     * @param array $v2 The second vector.
     * 
     * @return array The element-wise product of the two vectors.
     */
    public static function multiplyElements(array $v1, array $v2): array
    {
        return array_map(function ($a, $b) {
            return is_array($a) ? self::multiplyElements($a, $b) : $a * $b;
        }, $v1, $v2);
    }

    /**
     * Computes the derivative of a specified activation function at a given point.
     *
     * @param string $functionName The name of the activation function.
     * @param float $x The input value.
     * @param mixed ...$args Additional arguments for certain activation functions.
     * 
     * @return float|null The derivative of the activation function at x, or null if the function is not recognized.
     */
    public static function derivative(string $functionName, float $x, ...$args): ?float
    {
        switch ($functionName) {
            case 'sigmoid':
                $sigmoidX = self::sigmoid($x);
                return self::sigmoidDerivative($sigmoidX);
            case 'tanh':
                return self::hyperbolicTangentDerivative($x);
            case 'relu':
                return self::reluDerivative($x);
            case 'elu':
                return self::eluDerivative($x, ...$args);
            case 'selu':
                return self::seluDerivative($x, ...$args);
            case 'swish':
                return self::swishDerivative($x);
            case 'gelu':
                return self::geluDerivative($x);
            default:
                return null;
        }
    }

    /**
     * Computes the partial derivative of a multivariable function with respect to one variable using finite differences.
     *
     * @param callable $function The multivariable function.
     * @param array $x The point at which to compute the derivative.
     * @param int $index The index of the variable with respect to which to differentiate.
     * @param float $h A small value for finite difference approximation.
     * 
     * @return float|null The partial derivative at the specified index, or null if computation fails.
     */
    public static function partialDerivative(callable $function, array $x, int $index, float $h = 0.0001): ?float
    {
        $originalValue = $x[$index] ?? null;
        if ($originalValue === null) {
            return null;
        }

        $xPlusH = $x;
        $xPlusH[$index] = $originalValue + $h;

        $xMinusH = $x;
        $xMinusH[$index] = $originalValue - $h;

        try {
            return ($function($xPlusH) - $function($xMinusH)) / (2 * $h);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Performs backpropagation to update weights based on output layer activations and target outputs.
     *
     * @param array $outputLayerActivations The activations of the output layer.
     * @param array $targetOutputs The target output values.
     * @param array $layerInputs The inputs to the layer.
     * @param array $weights The current weights of the layer.
     * @param float $learningRate The learning rate for weight updates.
     * 
     * @return array The updated weights after backpropagation.
     */
    public static function backPropagtion(array $outputLayerActivations, array $targetOutputs, array $layerInputs, array $weights, float $learningRate): array
    {
        $outputErrors = array_map(function ($output, $target) {
            return $target - $output;
        }, $outputLayerActivations, $targetOutputs);

        $updatedWeights = $weights;

        return $updatedWeights;
    }

    /**
     * Computes the cross-entropy loss between predictions and target values.
     *
     * @param array $predictions The predicted probabilities.
     * @param array $targets The target values (one-hot encoded).
     * @param float $epsilon A small value to avoid log(0).
     * 
     * @return float|null The cross-entropy loss, or null if input sizes do not match.
     */
    public static function crossEntropy(array $predictions, array $targets, float $epsilon = 1e-15): ?float
    {
        if (count($predictions) !== count($targets)) {
            return null;
        }

        $sum = 0;
        for ($i = 0; $i < count($predictions); $i++) {
            $p = max(min($predictions[$i], 1 - $epsilon), $epsilon);
            $t = $targets[$i];
            $sum -= $t * log($p);
        }

        return $sum;
    }

    /**
     * Computes the softmax of an array of values.
     *
     * @param array $v The input array.
     * 
     * @return array The softmax-transformed array.
     */
    public static function softmax(array $v): array
    {
        $v = array_map('floatval', $v);
        $expValues = array_map('exp', $v);
        $sumExp = array_sum($expValues);

        return array_map(function ($value) use ($sumExp) {
            return $value / $sumExp;
        }, $expValues);
    }

    /**
     * Sigmoid activation function.
     *
     * @param float $x The input value.
     * 
     * @return float The sigmoid of the input.
     */
    public static function sigmoid(float $x): float|int
    {
        return 1 / (1 + exp(-$x));
    }

    /**
     * Derivative of the sigmoid function.
     *
     * @param float|int $x The input value (sigmoid output).
     * 
     * @return float|int The derivative of the sigmoid at the input.
     */
    public static function sigmoidDerivative(float|int $x): float|int
    {
        return $x * (1 - $x);
    }

    /**
     * Hyperbolic tangent activation function.
     *
     * @param float|int $x The input value.
     * 
     * @return float|int The hyperbolic tangent of the input.
     */
    public static function hyperbolicTangent(float|int $x): float|int
    {
        if (function_exists('tanh')) {
            return tanh($x);
        }

        return (exp($x) - exp(-$x)) / (exp($x) + exp(-$x));
    }

    /**
     * Derivative of the hyperbolic tangent function.
     *
     * @param float $x The input value.
     * 
     * @return float The derivative of the hyperbolic tangent at the input.
     */
    public static function hyperbolicTangentDerivative(float $x): float
    {
        $tanhX = self::hyperbolicTangent($x);
        return 1 - pow($tanhX, 2);
    }

    /**
     * Scaled Exponential Linear Unit
     * 
     * @param float $x
     * @param float $alpha
     * @param float $scale
     * 
     * @return float
     */
    public static function selu(float $x, float $alpha = 1.67326, float $scale = 1.0507): float
    {
        if ($x > 0) {
            return $scale * $x;
        }

        return $scale * $alpha * (exp($x) - 1);
    }

    /**
     * Derivative of the Scaled Exponential Linear Unit
     * 
     * @param float $x
     * @param float $alpha
     * @param float $scale
     * 
     * @return float
     */
    public static function seluDerivative(float $x, float $alpha = 1.67326, float $scale = 1.0507): float
    {
        if ($x > 0) {
            return $scale;
        }

        return $scale * $alpha * exp($x);
    }

    /**
     * Exponential Linear Unit
     * 
     * @param float $x
     * @param float $alpha
     * 
     * @return float
     */
    public static function elu(float $x, float $alpha = 1.0): float
    {
        if ($x > 0) {
            return $x;
        }

        return $alpha * (exp($x) - 1);
    }

    /**
     * Derivative of the Exponential Linear Unit
     * 
     * @param float $x
     * @param float $alpha
     * 
     * @return float
     */
    public static function eluDerivative(float $x, float $alpha = 1.0): float
    {
        if ($x > 0) {
            return 1;
        }

        return $alpha * exp($x);
    }

    /**
     * Maxout activation function
     * 
     * @param array $inputs
     * 
     * @return float|null
     */
    public static function maxout(array $inputs): ?float
    {
        if (empty($inputs)) {
            return null;
        }

        return max($inputs);
    }

    /**
     * Sigmoid-Weighted Linear Unit
     * 
     * @param float $x
     * @param float $beta
     * 
     * @return float
     */
    public static function swish(float $x, float $beta = 1.0): float
    {
        return $x * self::sigmoid($beta * $x);
    }

    /**
     * Derivative of the Sigmoid-Weighted Linear Unit
     * 
     * @param float $x
     * @param float $beta
     * 
     * @return float
     */
    public static function swishDerivative(float $x, float $beta = 1.0): float
    {
        $sigmoidBetaX = self::sigmoid($beta * $x);
        return $sigmoidBetaX + $beta * $x * $sigmoidBetaX * (1 - $sigmoidBetaX);
    }

    /**
     * Rectified Linear Unit
     * 
     * @param float $x
     * 
     * @return float|int
     */
    public static function relu(float $x): float
    {
        return max(0, $x);
    }

    /**
     * Derivative of the Rectified Linear Unit
     * 
     * @param float $x
     * 
     * @return float
     */
    public static function reluDerivative(float $x): float
    {
        return ($x > 0) ? 1 : 0;
    }

    /**
     * Gaussian Error Linear Unit
     * 
     * @param float $x
     * 
     * @return float
     */
    public static function gelu(float $x): float
    {
        return 0.5 * $x * (1 + self::erf($x / sqrt(2)));
    }

    /**
     * Derivative of the Gaussian Error Linear Unit
     * 
     * @param float $x
     * 
     * @return float
     */
    public static function geluDerivative(float $x): float
    {
        return 0.5 * (1 + self::erf($x / sqrt(2))) + ($x / sqrt(2 * M_PI)) * exp(-0.5 * $x * $x);
    }

    /**
     * Matrix multiplication
     * 
     * @param array $A
     * @param array $B
     * 
     * @return array
     */
    public static function matmul(array $A, array $B): array
    {
        $res = [];
        for ($i = 0; $i < count($A); $i++) {
            for ($j = 0; $j < count($B[0]); $j++) {
                $sum = 0;
                for ($k = 0; $k < count($B); $k++) {
                    $sum += $A[$i][$k] * $B[$k][$j];
                }
                $res[$i][$j] = $sum;
            }
        }

        return $res;
    }

    /**
     * Generates a random matrix
     * 
     * @param int $rows
     * @param int $cols
     * 
     * @return array
     */
    public static function randomMatrix(int $rows, int $cols): array
    {
        $m = [];
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $m[$i][$j] = mt_rand() / mt_getrandmax();
            }
        }

        return $m;
    }

    /**
     * Single attention head
     * 
     * @param array $X Input matrix (T x d)
     * @param int $d Dimension of the model
     * 
     * @return array Output matrix (T x d)
     */
    public static function attentionHead(array $X, int $d): array
    {
        $W_Q = self::randomMatrix($d, $d);
        $W_K = self::randomMatrix($d, $d);
        $W_V = self::randomMatrix($d, $d);
        $Q = self::matmul($X, $W_Q);
        $K = self::matmul($X, $W_K);
        $V = self::matmul($X, $W_V);
        $T = count($X);

        $outputs = [];
        for ($i = 0; $i < $T; $i++) {
            $scores = [];
            for ($j = 0; $j < $T; $j++) {
                $dot = 0;
                for ($k = 0; $k < $d; $k++) {
                    $dot += $Q[$i][$k] * $K[$j][$k];
                }
                $scores[$j] = $dot / sqrt($d);
            }

            $weights = self::softmax($scores);
            $output = array_fill(0, $d, 0.0);
            for ($j = 0; $j < $T; $j++) {
                for ($k = 0; $k < $d; $k++) {
                    $output[$k] += $weights[$j] * $V[$j][$k];
                }
            }

            $outputs[$i] = $output;
        }

        return $outputs;
    }

    /**
     * Adds positional encoding to the input matrix
     * 
     * @param array $X Input matrix (T x d)
     * 
     * @return void
     */
    public static function addPositionalEncoding(array &$X): void
    {
        $T = count($X);
        $d = count($X[0]);
        for ($pos = 0; $pos < $T; $pos++) {
            for ($i = 0; $i < $d; $i++) {
                if ($i % 2 == 0) {
                    $X[$pos][$i] += sin($pos / pow(10000, $i / $d));
                } else {
                    $X[$pos][$i] += cos($pos / pow(10000, ($i - 1) / $d));
                }
            }
        }
    }

    /**
     * Final linear layer followed by softmax
     * 
     * @param array $vector Input vector
     * @param array $output_weights Output weights matrix
     * @param array $output_bias Output bias vector
     * 
     * @return array Softmax probabilities
     */
    public static function finalLinearSoftmax(array $vector, array $output_weights, array $output_bias): array
    {
        $logits = [];
        for ($i = 0; $i < count($output_weights); $i++) {
            $sum = $output_bias[$i];
            for ($j = 0; $j < count($vector); $j++) {
                $sum += $vector[$j] * $output_weights[$i][$j];
            }
            $logits[$i] = $sum;
        }

        $max = max($logits);
        $exp = array_map(fn($x) => exp($x - $max), $logits);
        $sum = array_sum($exp);
        $probs = array_map(fn($x) => $x / $sum, $exp);

        return $probs;
    }

    /**
     * Computes the cosine similarity between two vectors
     * 
     * @param array $vec1
     * @param array $vec2
     * 
     * @return float|int
     */
    public static function cosineSimilarity(array $vec1, array $vec2): float|int
    {
        $dot = array_sum(array_map(fn($a, $b) => $a * $b, $vec1, $vec2));
        $norm1 = sqrt(array_sum(array_map(fn($x) => $x * $x, $vec1)));
        $norm2 = sqrt(array_sum(array_map(fn($x) => $x * $x, $vec2)));
        return $dot / ($norm1 * $norm2 + 1e-8);
    }

    /**
     * Layer normalization
     * 
     * @param array $X Input matrix (T x d)
     * 
     * @return array Normalized matrix (T x d)
     */
    public static function layerNorm(array $X): array
    {
        $T = count($X);
        $d = count($X[0]);
        $epsilon = 1e-6;
        $normalized = [];

        foreach ($X as $vec) {
            $mean = array_sum($vec) / $d;
            $var = 0.0;
            foreach ($vec as $v) {
                $var += ($v - $mean) ** 2;
            }
            $var /= $d;
            $std = sqrt($var + $epsilon);

            $norm = [];
            foreach ($vec as $v) {
                $norm[] = ($v - $mean) / $std;  // gamma=1, beta=0
            }
            $normalized[] = $norm;
        }

        return $normalized;
    }

    /**
     * Feedforward layer with ReLU activation
     * 
     * @param array $inputVec Input vector
     * @param array $W1 Weights for first layer
     * @param array $b1 Biases for first layer
     * @param array $W2 Weights for second layer
     * @param array $b2 Biases for second layer
     * 
     * @return array Output vector
     */
    public static function feedforwardLayer(array $inputVec, array $W1, array $b1, array $W2, array $b2): array
    {
        $hidden = [];
        for ($i = 0; $i < count($W1); $i++) {
            $sum = $b1[$i];
            for ($j = 0; $j < count($inputVec); $j++) {
                $sum += $inputVec[$j] * $W1[$i][$j];
            }
            $hidden[$i] = self::relu($sum);
        }

        $output = [];
        for ($i = 0; $i < count($W2); $i++) {
            $sum = $b2[$i];
            for ($j = 0; $j < count($hidden); $j++) {
                $sum += $hidden[$j] * $W2[$i][$j];
            }
            $output[$i] = $sum;
        }

        return $output;
    }

    /**
     * Computes the dot product of two vectors
     * 
     * @param array $a
     * @param array $b
     * 
     * @return float|int
     */
    public static function dotProduct(array $a, array $b): float|int
    {
        $sum = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $sum += $a[$i] * $b[$i];
        }
        return $sum;
    }

    /**
     * Cross-attention mechanism
     * 
     * @param array $query Query vector
     * @param array $keys Array of key vectors
     * @param array $values Array of value vectors
     * 
     * @return array Tuple of output vector and attention weights
     */
    public static function crossAttention(array $query, array $keys, array $values): array
    {
        $scores = [];
        foreach ($keys as $key) {
            $scores[] = self::dotProduct($query, $key);
        }
        $weights = self::softmax($scores);

        $output = array_fill(0, count($values[0]), 0.0);
        for ($i = 0; $i < count($values); $i++) {
            for ($j = 0; $j < count($output); $j++) {
                $output[$j] += $weights[$i] * $values[$i][$j];
            }
        }

        return [$output, $weights];
    }
}
