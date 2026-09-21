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
 * Transformer Encoder Layer (3.1)
 * 
 * Structor:
 * 1. Multi-Head Self-Attention
 * 2. Add & Norm (Residual + Layer Norm)
 * 3. Position-wise Feed-Forward
 * 4. Add & Norm (Residual + Layer Norm)
 */
class TransformerEncoderLayer
{
    private readonly int $d_model;
    private MultiHeadAttention $multi_head_attention;
    private PositionwiseFeedForward $feed_forward;
    private LayerNormalization $layer_norm_1;
    private LayerNormalization $layer_norm_2;

    private array $cache = [];

    public function __construct(int $d_model, int $num_heads, int $d_ff)
    {
        $this->d_model = $d_model;
        $this->multi_head_attention = new MultiHeadAttention($d_model, $num_heads);
        $this->feed_forward = new PositionwiseFeedForward($d_model, $d_ff);
        $this->layer_norm_1 = new LayerNormalization($d_model);
        $this->layer_norm_2 = new LayerNormalization($d_model);
    }

    /**
     * Forward pass
     */
    public function forward(array $x, ?array $mask = null): array
    {
        // 1. Multi-Head Self-Attention
        $attn_output = $this->multi_head_attention->forward($x, $x, $x, $mask);

        // 2. Add & Norm
        $x1 = MatrixOps::add($x, $attn_output); // Residual
        $x1 = $this->layer_norm_1->forward($x1); // Layer Norm

        // 3. Position-wise Feed-Forward
        $ff_output = $this->feed_forward->forward($x1);

        // 4. Add & Norm
        $x2 = MatrixOps::add($x1, $ff_output); // Residual
        $output = $this->layer_norm_2->forward($x2); // Layer Norm

        // Cache for backward
        $this->cache = [
            'x' => $x,
            'attn_output' => $attn_output,
            'x1' => $x1,
            'ff_output' => $ff_output,
            'x2' => $x2
        ];

        return $output;
    }

    /**
     * Backward pass
     */
    public function backward(array $d_output, float $learning_rate): array
    {
        // Backward through layer norm 2
        $d_x2 = $this->layer_norm_2->backward($d_output, $learning_rate);

        // Backward through residual (split gradient)
        $d_ff_output = $d_x2;
        $d_x1_from_residual = $d_x2;

        // Backward through feed-forward
        $d_x1_from_ff = $this->feed_forward->backward($d_ff_output, $learning_rate);

        // Combine gradients
        $d_x1 = MatrixOps::add($d_x1_from_residual, $d_x1_from_ff);

        // Backward through layer norm 1
        $d_x1 = $this->layer_norm_1->backward($d_x1, $learning_rate);

        // Backward through residual (split gradient)
        $d_attn_output = $d_x1;
        $d_x_from_residual = $d_x1;

        // Backward through multi-head attention
        $d_x_from_attn = $this->multi_head_attention->backward($d_attn_output, $learning_rate);

        // Combine gradients
        $d_x = MatrixOps::add($d_x_from_residual, $d_x_from_attn);

        return $d_x;
    }

    public function getWeights(): array
    {
        return [
            'multi_head_attention' => $this->multi_head_attention->getWeights(),
            'feed_forward' => $this->feed_forward->getWeights(),
            'layer_norm_1' => $this->layer_norm_1->getWeights(),
            'layer_norm_2' => $this->layer_norm_2->getWeights()
        ];
    }

    public function setWeights(array $weights): void
    {
        $this->multi_head_attention->setWeights($weights['multi_head_attention']);
        $this->feed_forward->setWeights($weights['feed_forward']);
        $this->layer_norm_1->setWeights($weights['layer_norm_1']);
        $this->layer_norm_2->setWeights($weights['layer_norm_2']);
    }
}