<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Interpreter\Stdlib;

class ConsoleBinding
{
    public function printLine(mixed ...$args): void
    {
        echo implode('', array_map('strval', $args)) . PHP_EOL;
    }

    public function print(mixed ...$args): void
    {
        echo implode('', array_map('strval', $args));
    }

    public function sleep(int $ms): void
    {
        usleep($ms * 1000);
    }
}

class MathBinding
{
    public float $PI = M_PI;
    public float $E = M_E;

    public function abs(float $x): float
    {
        return abs($x);
    }
    public function floor(float $x): int
    {
        return (int) floor($x);
    }
    public function ceil(float $x): int
    {
        return (int) ceil($x);
    }
    public function round(float $x): int
    {
        return (int) round($x);
    }
    public function sqrt(float $x): float
    {
        return sqrt($x);
    }
    public function pow(float $x, float $y): float
    {
        return $x ** $y;
    }
    public function log(float $x): float
    {
        return log($x);
    }
    public function log10(float $x): float
    {
        return log10($x);
    }
    public function exp(float $x): float
    {
        return exp($x);
    }
    public function sin(float $x): float
    {
        return sin($x);
    }
    public function cos(float $x): float
    {
        return cos($x);
    }
    public function tan(float $x): float
    {
        return tan($x);
    }
    public function random(): float
    {
        return mt_rand() / mt_getrandmax();
    }
    public function min(float ...$args): float
    {
        return min($args);
    }
    public function max(float ...$args): float
    {
        return max($args);
    }
}
