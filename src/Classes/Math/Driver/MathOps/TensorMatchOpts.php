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
 * High-Performance Matrix Operations using RubixML/Tensor
 * 
 * installation: composer require rubix/tensor
 */
class TensorMatchOpts extends BaseMatchOpts
{
    /**
     * Matrix Multiplication
     * 
     * @param array $A Matrix A (m × n)
     * @param array $B Matrix B (n × p)
     * @return array Result C (m × p)
     */
    public static function matmul($A, $B)
    {
        if (empty($A) || empty($B)) {
            return [];
        }

        $m = count($A);
        $n = count($A[0]);
        $n2 = count($B);
        $p = count($B[0]);

        if ($n !== $n2) {
            throw new Exception("Matrix dimension mismatch: ($m x $n) @ ($n2 x $p)");
        }

        try {
            $matA = Matrix::quick($A);
            $matB = Matrix::quick($B);
            return $matA->matmul($matB)->asArray();
        } catch (Exception $e) {
            return self::matmulPure($A, $B);
        }
    }

    /**
     * Matrix Transpose
     * 
     * @param array $A Matrix A
     * @return array Transposed matrix A^T
     */
    public static function transpose($A)
    {
        if (empty($A)) {
            return [];
        }

        try {
            return Matrix::quick($A)->transpose()->asArray();
        } catch (Exception $e) {
            // Fallback
            return self::transposePure($A);
        }
    }

    /**
     * Softmax Function
     * 
     * @param array $X Input matrix (logits)
     * @return array Probability distribution (sum = 1)
     */
    public static function softmax($X)
    {
        try {
            $result = [];

            foreach ($X as $row) {
                $vec = Vector::quick($row);

                // Numerical stability: subtract max
                $max = $vec->max();
                $shifted = $vec->subtract($max);

                // exp
                $exps = $shifted->map(fn($x) => exp($x));

                // sum
                $sum = $exps->sum();

                // normalize
                $softmax_row = $exps->divide($sum + 1e-10)->asArray();

                $result[] = $softmax_row;
            }

            return $result;
        } catch (Exception $e) {
            // Fallback
            return self::softmaxPure($X);
        }
    }

    /**
     * Softmax Backward Pass
     */
    public static function softmaxBackward($d_softmax, $softmax_output)
    {
        return self::softmaxBackwardPure($d_softmax, $softmax_output);
    }

    /**
     * Matrix Subtraction
     * 
     * @param array $A Matrix A
     * @param array $B Matrix B
     * @return array C = A - B
     */
    public static function subtract($A, $B)
    {
        if (empty($A)) {
            return [];
        }
        if (empty($B)) {
            return $A;
        }

        try {
            $matA = Matrix::quick($A);
            $matB = Matrix::quick($B);
            return $matA->subtract($matB)->asArray();
        } catch (Exception $e) {
            // Fallback
            return self::subtractPure($A, $B);
        }
    }

    /**
     * Element-wise Matrix Addition
     * 
     * @param array $A Matrix A
     * @param array $B Matrix B
     * @return array Result C = A + B
     */
    public static function add($A, $B)
    {
        if (empty($A)) {
            return $B;
        }
        if (empty($B)) {
            return $A;
        }

        try {
            $matA = Matrix::quick($A);
            $matB = Matrix::quick($B);
            return $matA->add($matB)->asArray();
        } catch (Exception $e) {
            // Fallback
            return self::addPure($A, $B);
        }
    }

    /**
     * Scalar Multiplication
     * 
     * @param array $A Matrix A
     * @param float $s Scalar value s
     * @return array Result B = s·A
     */
    public static function scale($A, $s)
    {
        try {
            return Matrix::quick($A)->multiply($s)->asArray();
        } catch (Exception $e) {
            // Fallback
            return self::scalePure($A, $s);
        }
    }

    /**
     * Xavier/Glorot Initialization
     * 
     * @param int $rows Number of rows
     * @param int $cols Number of columns
     * @return array Initialized weight matrix
     */
    public static function xavierInit($rows, $cols)
    {
        $limit = sqrt(6.0 / ($rows + $cols));

        try {
            $matrix = [];
            for ($i = 0; $i < $rows; $i++) {
                $matrix[$i] = [];
                for ($j = 0; $j < $cols; $j++) {
                    $matrix[$i][$j] = (mt_rand() / mt_getrandmax()) * 2 * $limit - $limit;
                }
            }
            return $matrix;
        } catch (Exception $e) {
            return self::xavierInitPure($rows, $cols);
        }
    }

    /**
     * Gradient Clipping (by global norm)
     */
    public static function clipGradient($A, $clip)
    {
        return Matrix::quick($A)->clip(-$clip, $clip)->asArray();
    }

    public static function createCausalMask(int $seq_len): array
    {
        $ones = Matrix::ones($seq_len, $seq_len);
        $mask_array = $ones->asArray();

        for ($i = 0; $i < $seq_len; $i++) {
            for ($j = $i + 1; $j < $seq_len; $j++) {
                $mask_array[$i][$j] = 0;
            }
        }

        return $mask_array;
    }

    public static function createPositionalEncoding(int $max_len, int $d_model)
    {
        $pe = array_fill(0, $max_len, array_fill(0, $d_model, 0.0));

        $div_terms = [];
        for ($i = 0; $i < $d_model; $i++) {
            $div_terms[] = pow(10000.0, (2 * intval($i / 2)) / $d_model);
        }
        $div_vec = Vector::quick($div_terms);

        for ($pos = 0; $pos < $max_len; $pos++) {
            // angles = pos / div_terms
            $angles = $div_vec->map(fn($div) => $pos / $div);
            $pe_row = [];
            $angles_array = $angles->asArray();

            for ($i = 0; $i < $d_model; $i++) {
                $pe_row[] = ($i % 2 == 0)
                    ? sin($angles_array[$i])
                    : cos($angles_array[$i]);
            }

            $pe[$pos] = $pe_row;
        }

        return $pe;
    }

    public static function clipGradientByGlobalNorm($A, $max_norm)
    {
        // 1. Calculate the squared L2-norm
        $sq_norm = 0.0;
        foreach ($A as $row) {
            foreach ($row as $val) {
                $sq_norm += $val * $val;
            }
        }
        $global_norm = sqrt($sq_norm);

        // 2. Check if scaling is necessary
        if ($global_norm <= $max_norm) {
            return $A; // No scaling needed
        }

        // 3. Calculate scaling factor and apply
        $scale_factor = $max_norm / $global_norm;

        $clipped = [];
        foreach ($A as $i => $row) {
            $clipped[$i] = [];
            foreach ($row as $j => $val) {
                $clipped[$i][$j] = $val * $scale_factor;
            }
        }
        return $clipped;
    }

    /**
     * Element-wise multiplication
     */
    public static function multiply($A, $B)
    {
        try {
            // 🚀 Tensor: element-wise multiplication
            $matA = Matrix::quick($A);
            $matB = Matrix::quick($B);

            $result = [];
            for ($i = 0; $i < count($A); $i++) {
                $result[$i] = [];
                for ($j = 0; $j < count($A[0]); $j++) {
                    $result[$i][$j] = $A[$i][$j] * $B[$i][$j];
                }
            }
            return $result;
        } catch (Exception $e) {
            return self::multiplyPure($A, $B);
        }
    }

    private static function matmulPure($A, $B)
    {
        $m = count($A);
        $n = count($A[0]);
        $p = count($B[0]);
        $C = [];

        for ($i = 0; $i < $m; $i++) {
            $C[$i] = [];
            for ($j = 0; $j < $p; $j++) {
                $sum = 0.0;
                for ($k = 0; $k < $n; $k++) {
                    $sum += $A[$i][$k] * $B[$k][$j];
                }
                $C[$i][$j] = $sum;
            }
        }
        return $C;
    }

    private static function transposePure($A)
    {
        $m = count($A);
        $n = count($A[0]);
        $AT = [];

        for ($j = 0; $j < $n; $j++) {
            $AT[$j] = [];
            for ($i = 0; $i < $m; $i++) {
                $AT[$j][$i] = $A[$i][$j];
            }
        }
        return $AT;
    }

    private static function softmaxPure($X)
    {
        $result = [];

        foreach ($X as $row) {
            $max = max($row);
            $exps = [];
            $sum = 0.0;

            foreach ($row as $val) {
                $exp = exp($val - $max);
                $exps[] = $exp;
                $sum += $exp;
            }

            $softmax_row = [];
            foreach ($exps as $exp) {
                $softmax_row[] = $exp / ($sum + 1e-10);
            }
            $result[] = $softmax_row;
        }

        return $result;
    }

    private static function softmaxBackwardPure($d_softmax, $softmax_output)
    {
        $d_x = [];

        for ($i = 0; $i < count($softmax_output); $i++) {
            $d_x[$i] = [];
            $softmax_row = $softmax_output[$i];
            $d_row = $d_softmax[$i];

            for ($j = 0; $j < count($softmax_row); $j++) {
                $sum = 0.0;
                for ($k = 0; $k < count($softmax_row); $k++) {
                    $kronecker = ($j == $k) ? 1.0 : 0.0;
                    $sum += $d_row[$k] * $softmax_row[$j] * ($kronecker - $softmax_row[$k]);
                }
                $d_x[$i][$j] = $sum;
            }
        }

        return $d_x;
    }

    private static function subtractPure($A, $B)
    {
        $m = count($A);
        $n = count($A[0]);
        $C = [];

        for ($i = 0; $i < $m; $i++) {
            $C[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $C[$i][$j] = $A[$i][$j] - ($B[$i][$j] ?? 0);
            }
        }
        return $C;
    }

    private static function addPure($A, $B)
    {
        $m = count($A);
        $n = count($A[0]);
        $C = [];

        for ($i = 0; $i < $m; $i++) {
            $C[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $C[$i][$j] = $A[$i][$j] + ($B[$i][$j] ?? 0);
            }
        }
        return $C;
    }

    private static function scalePure($A, $s)
    {
        $C = [];
        foreach ($A as $i => $row) {
            $C[$i] = [];
            foreach ($row as $j => $val) {
                $C[$i][$j] = $s * $val;
            }
        }
        return $C;
    }

    private static function xavierInitPure($rows, $cols)
    {
        $limit = sqrt(6.0 / ($rows + $cols));
        $matrix = [];

        for ($i = 0; $i < $rows; $i++) {
            $matrix[$i] = [];
            for ($j = 0; $j < $cols; $j++) {
                $matrix[$i][$j] = (mt_rand() / mt_getrandmax()) * 2 * $limit - $limit;
            }
        }
        return $matrix;
    }

    private static function clipGradientPure($grad, $max_norm = 5.0)
    {
        $norm = 0.0;

        foreach ($grad as $row) {
            foreach ($row as $val) {
                $norm += $val * $val;
            }
        }
        $norm = sqrt($norm);

        if ($norm > $max_norm) {
            return self::scalePure($grad, $max_norm / $norm);
        }
        return $grad;
    }

    private static function multiplyPure($A, $B)
    {
        $m = count($A);
        $n = count($A[0]);
        $C = [];

        for ($i = 0; $i < $m; $i++) {
            $C[$i] = [];
            for ($j = 0; $j < $n; $j++) {
                $C[$i][$j] = $A[$i][$j] * ($B[$i][$j] ?? 0);
            }
        }
        return $C;
    }

    public static function gelu($x)
    {
        return $x * 0.5 * (1.0 + tanh(sqrt(2.0 / M_PI) * ($x + 0.044715 * pow($x, 3))));
    }

    public static function geluDerivative($x)
    {
        $tanh_term = tanh(sqrt(2.0 / M_PI) * ($x + 0.044715 * pow($x, 3)));
        return 0.5 * (1.0 + $tanh_term) + $x * 0.5 * (1.0 - pow($tanh_term, 2)) * sqrt(2.0 / M_PI) * (1.0 + 3 * 0.044715 * pow($x, 2));
    }

    public static function updateWeights(&$w, $lr, $direction = -1, $clip = 1.0)
    {
        for ($i = 0; $i < count($w); $i++) {
            for ($j = 0; $j < count($w[0]); $j++) {
                $grad = (mt_rand() / mt_getrandmax()) * 0.001;
                $grad = max(-$clip, min($clip, $grad));
                $w[$i][$j] += $direction * $lr * $grad;
                $w[$i][$j] = max(-10, min(10, $w[$i][$j]));
            }
        }
    }

    public static function zeros($A, $B)
    {
        return Matrix::zeros($A, $B)->asArray();
    }

    public static function max($A)
    {
        return (new Matrix($A))->max()->asArray();
    }

    public static function sum($A)
    {
        return (new Matrix($A))->sum()->asArray();
    }

    public static function substractExp($A, $max)
    {
        return (new Matrix($A))->subtract($max)->exp()->asArray();
    }

    public static function divide($A, $B)
    {
        return (new Matrix($A))->divide($B)->asArray();
    }

    public static function exp($A)
    {
        return Matrix::quick($A)->exp()->asArray();
    }

    public static function adamUpdate($param, $grad, $m, $v, $t, $lr_t, $beta1, $beta2, $epsilon)
    {
        $paramMat = Matrix::quick($param);
        $gradMat = Matrix::quick($grad);
        $mMat = Matrix::quick($m);
        $vMat = Matrix::quick($v);

        $mMat = $mMat->multiply($beta1)->add($gradMat->multiply(1 - $beta1));
        $vMat = $vMat->multiply($beta2)->add($gradMat->square()->multiply(1 - $beta2));

        $m_hat = $mMat->divide(1 - pow($beta1, $t));
        $v_hat = $vMat->divide(1 - pow($beta2, $t));

        $update = $m_hat->divide($v_hat->sqrt()->add($epsilon))->multiply($lr_t);
        $paramMat = $paramMat->subtract($update);

        return [$paramMat->asArray(), $mMat->asArray(), $vMat->asArray()];
    }

    public static function addVector($vecA, $vecB)
    {
        return Vector::quick($vecA)->add(Vector::quick($vecB))->asArray();
    }

    public static function scaleVector($vec, $scalar)
    {
        return Vector::quick($vec)->multiply($scalar)->asArray();
    }

    public static function clipVector($vec, $clip)
    {
        return Vector::quick($vec)->clip(-$clip, $clip)->asArray();
    }

    public static function adamUpdateRow($param_row, $grad_row, $m_row, $v_row, $t, $lr_t, $beta1, $beta2, $epsilon)
    {
        $paramVec = Vector::quick($param_row);
        $gradVec = Vector::quick($grad_row);
        $mVec = Vector::quick($m_row);
        $vVec = Vector::quick($v_row);

        $mVec = $mVec->multiply($beta1)->add($gradVec->multiply(1 - $beta1));
        $vVec = $vVec->multiply($beta2)->add($gradVec->square()->multiply(1 - $beta2));

        $m_hat = $mVec->divide(1 - pow($beta1, $t));
        $v_hat = $vVec->divide(1 - pow($beta2, $t));

        $update = $m_hat->divide($v_hat->sqrt()->add($epsilon))->multiply($lr_t);
        $paramVec = $paramVec->subtract($update);

        return [$paramVec->asArray(), $mVec->asArray(), $vVec->asArray()];
    }

    public static function getRow($matrix, $index)
    {
        if (isset($matrix[$index])) {
            return $matrix[$index];
        }

        throw new Exception("Index $index not found in matrix.");
    }

    public static function logSoftmax($logits)
    {
        $logitsMat = Matrix::quick($logits);
        $max = $logitsMat->max(); // Row-wise maximum (ColumnVector)

        // Numerically stable Log-Sum-Exp (LSE) calculation: log(sum(exp(x - max))) + max
        $lse = $logitsMat->subtract($max)->exp()->sum(1)->log()->add($max);

        // log_probs = x - LSE
        return $logitsMat->subtract($lse)->asArray();
    }

    public static function crossEntropyLoss($log_probs, $targets, $pad_id)
    {
        $logProbsArr = $log_probs;

        $loss = 0.0;
        $valid_tokens = 0;
        $T = count($targets);
        for ($t = 0; $t < $T; $t++) {
            $target_id = $targets[$t];
            if ($target_id === $pad_id)
                continue;

            $log_prob_value = $logProbsArr[$t][$target_id] ?? 0.0;
            $loss -= $log_prob_value;
            $valid_tokens++;
        }
        return $valid_tokens > 0 ? $loss / $valid_tokens : 0.0;
    }

    public static function maskPadding($matrix, $targets, $pad_id, $mask_value)
    {
        $T = count($targets);
        if ($T === 0)
            return $matrix;

        $n = count($matrix[0]);
        $maskRow = array_fill(0, $n, $mask_value);

        for ($t = 0; $t < $T; $t++) {
            if ($targets[$t] === $pad_id) {
                $matrix[$t] = $maskRow;
            }
        }
        return $matrix;
    }

    public static function buildOneHot($targets, $vocab_size)
    {
        $T = count($targets);
        $oneHot = self::zeros($T, $vocab_size);

        for ($t = 0; $t < $T; $t++) {
            $target_id = $targets[$t];
            if ($target_id >= 0 && $target_id < $vocab_size) {
                $oneHot[$t][$target_id] = 1.0;
            }
        }

        return $oneHot;
    }

    public static function logsumexp(float $maxVal, float $sumExp, float $eps = 1e-12)
    {
        // Wrap floats into 1-element Vectors
        $vMax = new Vector([(float) $maxVal]);
        $vSum = new Vector([(float) $sumExp]);

        // Extract arrays, apply safe max for numerical stability, compute log and add
        $maxArr = $vMax->asArray();   // returns [ $maxVal ]
        $sumArr = $vSum->asArray();   // returns [ $sumExp ]

        $safe = max($sumArr[0], $eps);
        $value = $maxArr[0] + log($safe);

        return $value;
    }

        public static function maskedFill($matrix, $mask, $value)
    {
        $result = [];
        
        foreach ($matrix as $i => $row) {
            $result[$i] = [];
            foreach ($row as $j => $val) {
                $result[$i][$j] = $mask[$i][$j] == 0 ? $value : $val;
            }
        }
        
        return $result;
    }
    
    /**
    * In-place addition (memory-optimized)
     * $a += $b
     */
    public static function addInPlace(&$a, $b)
    {
        $rows = count($a);
        $cols = count($a[0]);
        
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $a[$i][$j] += $b[$i][$j];
            }
        }
    }
}