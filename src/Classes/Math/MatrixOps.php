<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Classes\Math\MathOps\Driver\MatchOpsInterface;

class MatrixOps implements MatchOpsInterface
{
    protected static $_driver = null;

    public static function init($config = []): void
    {
        $className = '\\Clover\\Classes\\Math\\MathOps\\Driver\\PHPMatchOpts';
        self::$_driver = $className::getInstance($config);
    }

    public static function matmul($A, $B): mixed
    {
        return self::$_driver::matmul($A, $B);
    }

    public static function transpose($A): mixed
    {
        return self::$_driver::transpose($A);
    }

    public static function softmax($X): mixed
    {
        return self::$_driver::softmax($X);
    }

    public static function softmaxBackward($d_softmax, $softmax_output): mixed
    {
        return self::$_driver::softmaxBackward($d_softmax, $softmax_output);
    }

    public static function subtract($A, $B): mixed
    {
        return self::$_driver::subtract($A, $B);
    }

    public static function add($A, $B): mixed
    {
        return self::$_driver::add($A, $B);
    }

    public static function scale($A, $s): mixed
    {
        return self::$_driver::scale($A, $s);
    }

    public static function xavierInit($rows, $cols): mixed
    {
        return self::$_driver::xavierInit($rows, $cols);
    }

    public static function clipGradient($grad, $max_norm = 5.0): mixed
    {
        return self::$_driver::clipGradient($grad, $max_norm);
    }

    public static function multiply($A, $B): mixed
    {
        return self::$_driver::multiply($A, $B);
    }

    public static function gelu($x): mixed
    {
        return self::$_driver::gelu($x);
    }

    public static function geluDerivative($x): mixed
    {
        return self::$_driver::geluDerivative($x);
    }

    public static function updateWeights(&$w, $lr, $direction = -1, $clip = 1.0): mixed
    {
        return self::$_driver::updateWeights($w, $lr, $direction, $clip);
    }

    public static function zeros($A, $B): mixed
    {
        return self::$_driver::zeros($A, $B);
    }

    public static function max($A): mixed
    {
        return self::$_driver::max($A);
    }

    public static function substractExp($A, $max): mixed
    {
        return self::$_driver::substractExp($A, $max);
    }

    public static function sum($A): mixed
    {
        return self::$_driver::sum($A);
    }

    public static function divide($A, $B): mixed
    {
        return self::$_driver::divide($A, $B);
    }

    public static function exp($A): mixed
    {
        return self::$_driver::exp($A);
    }

    public static function adamUpdate($param, $grad, $m, $v, $t, $lr_t, $beta1, $beta2, $epsilon): mixed
    {
        return self::$_driver::adamUpdate($param, $grad, $m, $v, $t, $lr_t, $beta1, $beta2, $epsilon);
    }

    public static function addVector($vecA, $vecB): mixed
    {
        return self::$_driver::addVector($vecA, $vecB);
    }

    public static function scaleVector($vec, $scalar): mixed
    {
        return self::$_driver::scaleVector($vec, $scalar);
    }

    public static function clipVector($vec, $clip): mixed
    {
        return self::$_driver::clipVector($vec, $clip);
    }

    public static function adamUpdateRow($param_row, $grad_row, $m_row, $v_row, $t, $lr_t, $beta1, $beta2, $epsilon): mixed
    {
        return self::$_driver::adamUpdateRow($param_row, $grad_row, $m_row, $v_row, $t, $lr_t, $beta1, $beta2, $epsilon);
    }

    public static function getRow($matrix, $index): mixed
    {
        return self::$_driver::getRow($matrix, $index);
    }

    public static function logSoftmax($logits): mixed
    {
        return self::$_driver::logSoftmax($logits);
    }

    public static function crossEntropyLoss($log_probs, $targets, $pad_id): mixed
    {
        return self::$_driver::crossEntropyLoss($log_probs, $targets, $pad_id);
    }

    public static function maskPadding($matrix, $targets, $pad_id, $mask_value): mixed
    {
        return self::$_driver::maskPadding($matrix, $targets, $pad_id, $mask_value);
    }

    public static function buildOneHot($targets, $vocab_size): mixed
    {
        return self::$_driver::buildOneHot($targets, $vocab_size);
    }

    public static function clipGradientByGlobalNorm($A, $max_norm): mixed
    {
        return self::$_driver::clipGradientByGlobalNorm($A, $max_norm);
    }

    public static function createCausalMask(int $seq_len): mixed
    {
        return self::$_driver::createCausalMask($seq_len);
    }

    public static function createPositionalEncoding(int $max_len, int $d_model): mixed
    {
        return self::$_driver::createPositionalEncoding($max_len, $d_model);
    }

    public static function logsumexp(float $maxVal, float $sumExp, float $eps = 1e-12): mixed
    {
        return self::$_driver::logsumexp($maxVal, $sumExp, $eps);
    }

}
