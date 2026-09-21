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
 * Layer Normalization
 * 
 * LayerNorm(x) = γ ⊙ (x - μ) / √(σ² + ε) + β
 * 
 * where:
 * - μ: mean over features
 * - σ²: variance over features
 * - γ, β: learnable parameters
 * - ε: small constant for numerical stability
 */
class LayerNormalization
{
    private readonly int $d_model;
    private array $gamma; // γ
    private array $beta;  // β
    private float $eps = 1e-6;

    private array $cache = [];

    public function __construct(int $d_model)
    {
        $this->d_model = $d_model;
        $this->gamma = array_fill(0, $d_model, 1.0);
        $this->beta = array_fill(0, $d_model, 0.0);
    }

    /**
     * Forward pass
     */
    public function forward(array $x): array
    {
        $normalized = [];
        $stats = [];

        foreach ($x as $i => $row) {
            // Compute mean
            $mean = array_sum($row) / $this->d_model;

            // Compute variance
            $variance = 0.0;
            foreach ($row as $val) {
                $variance += pow($val - $mean, 2);
            }
            $variance /= $this->d_model;

            // Compute standard deviation
            $std = sqrt($variance + $this->eps);

            // Normalize
            $norm_row = [];
            $x_hat_row = [];
            for ($j = 0; $j < $this->d_model; $j++) {
                $x_hat = ($row[$j] - $mean) / $std;
                $x_hat_row[$j] = $x_hat;
                $norm_row[$j] = $this->gamma[$j] * $x_hat + $this->beta[$j];
            }
            $normalized[] = $norm_row;

            // Cache statistics
            $stats[$i] = [
                'mean' => $mean,
                'std' => $std,
                'x_hat' => $x_hat_row,
                'x_row' => $row
            ];
        }

        $this->cache = ['x' => $x, 'stats' => $stats];
        return $normalized;
    }

    /**
     * Backward pass (Mathematically Correct)
     */
    public function backward(array $d_output, float $learning_rate): array
    {
        $stats = $this->cache['stats'];

        $d_gamma = array_fill(0, $this->d_model, 0.0);
        $d_beta = array_fill(0, $this->d_model, 0.0);
        $d_x = [];

        foreach ($d_output as $i => $d_row) {
            $stats_i = $stats[$i];
            $mean = $stats_i['mean'];
            $std = $stats_i['std'];
            $x_hat = $stats_i['x_hat'];

            $N = $this->d_model;

            // Accumulate gradients for gamma and beta
            for ($j = 0; $j < $N; $j++) {
                $d_row_j = $d_row[$j];

                $d_gamma[$j] += $d_row_j * $x_hat[$j];
                $d_beta[$j] += $d_row_j;
            }

            // Gradient for x (Full LayerNorm backward pass)
            // dL/dx_i = (1/std) * [ (dL/dy_i*gamma_i) - (1/N) * sum(dL/dy_j*gamma_j) - (x_hat_i/N) * sum(dL/dy_j*gamma_j*x_hat_j) ]

            $d_norm_sum = 0.0;
            $d_norm_x_hat_sum = 0.0;
            $d_norm = [];

            for ($j = 0; $j < $N; $j++) {
                $d_norm_val = $d_row[$j] * $this->gamma[$j];
                $d_norm[$j] = $d_norm_val;
                $d_norm_sum += $d_norm_val;
                $d_norm_x_hat_sum += $d_norm_val * $x_hat[$j];
            }

            $d_x_row = [];
            for ($j = 0; $j < $N; $j++) {
                $d_x_row[$j] = (1.0 / $std) * ($d_norm[$j] - ($d_norm_sum / $N) - ($x_hat[$j] * $d_norm_x_hat_sum / $N));
            }
            $d_x[] = $d_x_row;
        }

        // Update gamma and beta
        for ($j = 0; $j < $this->d_model; $j++) {
            $this->gamma[$j] -= $learning_rate * $d_gamma[$j];
            $this->beta[$j] -= $learning_rate * $d_beta[$j];
        }

        return $d_x;
    }

    public function getWeights(): array
    {
        return ['gamma' => $this->gamma, 'beta' => $this->beta];
    }

    public function setWeights(array $weights): void
    {
        $this->gamma = $weights['gamma'];
        $this->beta = $weights['beta'];
    }
}