<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math\MathOps\Driver;

use Exception;

class PHPMatchOpts extends BaseMatchOpts
{
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

    public static function transpose($A)
    {
        if (empty($A)) {
            return [];
        }

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

    public static function softmax($X)
    {
        $result = [];

        foreach ($X as $row) {
            // Numerical stability: subtract max
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

    public static function softmaxBackward($d_softmax, $softmax_output)
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

    public static function subtract($A, $B)
    {
        if (empty($A)) {
            return [];
        }
        if (empty($B)) {
            return $A;
        }

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

    public static function add($A, $B)
    {
        if (empty($A)) {
            return $B;
        }

        if (empty($B)) {
            return $A;
        }

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

    public static function scale($A, $s)
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

    public static function xavierInit($rows, $cols)
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

    public static function updateWeights(&$w, $lr, $direction = -1, $clip = 1.0)
    {
        for ($i = 0; $i < count($w); $i++) {
            for ($j = 0; $j < count($w[0]); $j++) {
                // Random gradient with clipping
                $grad = (mt_rand() / mt_getrandmax()) * 0.001;
                $grad = max(-$clip, min($clip, $grad));
                $w[$i][$j] += $direction * $lr * $grad;

                // Weight clipping to prevent explosion
                $w[$i][$j] = max(-10, min(10, $w[$i][$j]));
            }
        }
    }

    public static function clipGradient($grad, $max_norm = 5.0)
    {
        $norm = 0.0;

        foreach ($grad as $row) {
            foreach ($row as $val) {
                $norm += $val * $val;
            }
        }
        $norm = sqrt($norm);

        if ($norm > $max_norm) {
            return self::scale($grad, $max_norm / $norm);
        }
        return $grad;
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

    public static function multiply($A, $B)
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

    public static function zeros($A, $B)
    {
        return array_fill(0, $A, array_fill(0, $B, 0.0));
    }

    public static function max($A)
    {
        return max($A);
    }

    public static function substractExp($A, $max)
    {
        return array_map(fn($l) => exp(min($l - $max, 20)), $A);
    }

    public static function sum($A)
    {
        return array_sum($A);
    }

    public static function divide($A, $B)
    {
        return array_map(fn($l) => $l / $B, $A);
    }

    public static function exp($A)
    {
        $m = count($A);
        $n = count($A[0]);
        for ($i = 0; $i < $m; $i++) {
            for ($j = 0; $j < $n; $j++)
                $A[$i][$j] = exp($A[$i][$j]);
        }
        return $A;
    }

    public static function logSoftmax($logits)
    {
        $m = count($logits);
        $n = count($logits[0]);
        $log_probs = [];
        for ($t = 0; $t < $m; $t++) {
            $row = $logits[$t];
            $maxVal = max($row);
            $sumExp = 0.0;
            for ($j = 0; $j < $n; $j++)
                $sumExp += exp($row[$j] - $maxVal);
            $logsumexp = $maxVal + log(max($sumExp, 1e-12));
            $log_probs[$t] = [];
            for ($j = 0; $j < $n; $j++)
                $log_probs[$t][$j] = $row[$j] - $logsumexp;
        }
        return $log_probs;
    }

    public static function crossEntropyLoss($log_probs, $targets, $pad_id)
    {
        $loss = 0.0;
        $valid_tokens = 0;
        $T = count($targets);
        for ($t = 0; $t < $T; $t++) {
            $target_id = $targets[$t];
            if ($target_id === $pad_id)
                continue;
            $loss -= $log_probs[$t][$target_id] ?? log(1e-12);
            $valid_tokens++;
        }
        return $valid_tokens > 0 ? $loss / $valid_tokens : 0.0;
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

    public static function maskPadding($matrix, $targets, $pad_id, $mask_value)
    {
        $T = count($targets);
        $n = count($matrix[0]);
        for ($t = 0; $t < $T; $t++) {
            if ($targets[$t] === $pad_id) {
                for ($j = 0; $j < $n; $j++)
                    $matrix[$t][$j] = $mask_value;
            }
        }
        return $matrix;
    }

    public static function adamUpdate($param, $grad, $m, $v, $t, $lr_t, $beta1, $beta2, $epsilon)
    {
        $rows = count($param);
        $cols = count($param[0]);
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $g = $grad[$i][$j];
                $m[$i][$j] = $beta1 * $m[$i][$j] + (1 - $beta1) * $g;
                $v[$i][$j] = $beta2 * $v[$i][$j] + (1 - $beta2) * ($g * $g);
                $m_hat = $m[$i][$j] / (1 - pow($beta1, $t));
                $v_hat = $v[$i][$j] / (1 - pow($beta2, $t));
                $param[$i][$j] -= $lr_t * $m_hat / (sqrt($v_hat) + $epsilon);
            }
        }
        return [$param, $m, $v];
    }
    public static function getRow($matrix, $index)
    {
        return $matrix[$index];
    }

    public static function addVector($vecA, $vecB)
    {
        $n = count($vecA);
        for ($i = 0; $i < $n; $i++)
            $vecA[$i] += $vecB[$i];
        return $vecA;
    }

    public static function scaleVector($vec, $scalar)
    {
        $n = count($vec);
        for ($i = 0; $i < $n; $i++)
            $vec[$i] *= $scalar;
        return $vec;
    }

    public static function clipVector($vec, $clip)
    {
        $n = count($vec);
        for ($i = 0; $i < $n; $i++)
            $vec[$i] = max(min($vec[$i], $clip), -$clip);
        return $vec;
    }

    public static function adamUpdateRow($param_row, $grad_row, $m_row, $v_row, $t, $lr_t, $beta1, $beta2, $epsilon)
    {
        $n = count($param_row);
        for ($j = 0; $j < $n; $j++) {
            $g = $grad_row[$j];
            $m_row[$j] = $beta1 * $m_row[$j] + (1 - $beta1) * $g;
            $v_row[$j] = $beta2 * $v_row[$j] + (1 - $beta2) * ($g * $g);
            $m_hat = $m_row[$j] / (1 - pow($beta1, $t));
            $v_hat = $v_row[$j] / (1 - pow($beta2, $t));
            $param_row[$j] -= $lr_t * $m_hat / (sqrt($v_hat) + $epsilon);
        }
        return [$param_row, $m_row, $v_row];
    }

    public static function createCausalMask(int $seq_len)
    {
        $mask = [];
        for ($i = 0; $i < $seq_len; $i++) {
            $mask[$i] = [];
            for ($j = 0; $j < $seq_len; $j++) {
                $mask[$i][$j] = ($j <= $i) ? 1 : 0;
            }
        }
        return $mask;
    }

    public static function createPositionalEncoding(int $max_len, int $d_model)
    {
        $pe = [];

        for ($pos = 0; $pos < $max_len; $pos++) {
            $pe[$pos] = [];
            for ($i = 0; $i < $d_model; $i++) {
                $div_term = pow(10000, (2 * intval($i / 2)) / $d_model);
                $angle = $pos / $div_term;

                if ($i % 2 == 0) {
                    $pe[$pos][$i] = sin($angle);
                } else {
                    $pe[$pos][$i] = cos($angle);
                }
            }
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

    public static function logsumexp(float $maxVal, float $sumExp, float $eps = 1e-12)
    {
        return $maxVal + log(max($sumExp, $eps));
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