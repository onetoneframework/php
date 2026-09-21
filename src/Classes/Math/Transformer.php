<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function count;
use function array_slice;

/**
 * Class Transformer
 *
 * A simplified transformer model for text generation.
 */
class Transformer
{
    private array $vocab;
    private int $d_model;
    private array $word2vec;

    /**
     * Transformer constructor.
     *
     * @param array $vocab
     * @param int $d_model
     */
    public function __construct(array $vocab, int $d_model = 16)
    {
        $this->vocab = $vocab;
        $this->d_model = $d_model;
        $this->initEmbeddings();
    }

    /**
     * Initialize word embeddings.
     * 
     * @return void
     */
    private function initEmbeddings(): void
    {
        foreach ($this->vocab as $word) {
            $vec = [];
            for ($i = 0; $i < $this->d_model; $i++) {
                $vec[] = sin(crc32($word.$i) % 100 / 100);
            }
            $this->word2vec[$word] = $vec;
        }
    }

    private function getEmbedding(string $word): array
    {
        return $this->word2vec[$word] ?? array_fill(0, $this->d_model, 0);
    }

    private function layerNorm(array $x, array $residual): array
    {
        $output = [];
        foreach ($x as $i => $vec) {
            $merged = array_map(fn($a, $b) => $a + $b, $vec, $residual[$i]);
            $mean = array_sum($merged) / count($merged);
            $var = array_sum(array_map(fn($v) => pow($v - $mean, 2), $merged)) / count($merged);
            $norm = array_map(fn($v) => ($v - $mean) / sqrt($var + 1e-6), $merged);
            $output[] = $norm;
        }
        
        return $output;
    }

    private function selfAttention(array $x): array
    {
        $len = count($x);
        $d = count($x[0]);
        $output = [];
        for ($i = 0; $i < $len; $i++) {
            $attn_sum = array_fill(0, $d, 0);
            $weight_sum = 0;
            for ($j = 0; $j < $len; $j++) {
                $score = array_sum(array_map(fn($a, $b) => $a * $b, $x[$i], $x[$j]));
                $weight = exp($score);
                $attn_sum = array_map(fn($a, $b) => $a + $weight * $b, $attn_sum, $x[$j]);
                $weight_sum += $weight;
            }
            $output[] = array_map(fn($v) => $v / $weight_sum, $attn_sum);
        }
        return $output;
    }

    private function feedForward(array $x): array
    {
        $output = [];
        foreach ($x as $vec) {
            $hidden = array_map(fn($v) => tanh($v * 0.1 + 0.05), $vec);
            $proj = array_map(fn($v) => $v * 0.3, $hidden);
            $output[] = $proj;
        }
        return $output;
    }

    private function decoderBlock(array $x): array
    {
        $attn = $this->selfAttention($x);
        $x = $this->layerNorm($x, $attn);
        $ff = $this->feedForward($x);
        return $this->layerNorm($x, $ff);
    }

    private function softmax(array $logits): array
    {
        $max = max($logits);
        $exp = array_map(fn($x) => exp($x - $max), $logits);
        $sum = array_sum($exp);
        return array_map(fn($x) => $x / $sum, $exp);
    }

    private function sampleNextToken(array $vec): string
    {
        $logits = [];
        foreach ($this->vocab as $word) {
            $embed = $this->getEmbedding($word);
            $score = array_sum(array_map(fn($a, $b) => $a * $b, $vec, $embed));
            $logits[$word] = $score;
        }
        $probs = $this->softmax($logits);
        arsort($probs);
        $top = array_slice($probs, 0, 5, true);
        $words = array_keys($top);
        return $words[array_rand($words)];
    }

    public function generate(string $prompt, int $maxLength = 20): string
    {
        $tokens = explode(" ", $prompt);
        $output = $tokens;
        for ($i = 0; $i < $maxLength; $i++) {
            $x = array_map(fn($t) => $this->getEmbedding($t), $output);
            $x = $this->decoderBlock($x);
            $next = $this->sampleNextToken(end($x));
            if ($next === "<EOS>" || count($output) >= 50)
                break;
            $output[] = $next;
        }
        return implode(" ", $output);
    }
}