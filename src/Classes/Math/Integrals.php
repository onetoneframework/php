<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

class Integrals
{
    public static function powerRuleIndefiniteIntegral(float $x, int $n): string
    {
        return "(1/(".($n + 1).")) * x^(".($n + 1).") + C";
    }

    public static function numericalDefiniteIntegral(callable $function, float $a, float $b, int $n = 1000): float
    {
        $width = ($b - $a) / $n;
        $integral = 0;
        for ($i = 0; $i < $n; $i++) {
            $integral += $function($a + $i * $width) * $width;
        }
        
        return $integral;
    }
}