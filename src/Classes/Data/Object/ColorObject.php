<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use Clover\Classes\Math\Basic;

class ColorObject
{
    /**
     * Converts RGB color values to HSL (Hue, Saturation, Lightness) format.
     *
     * @param float|int $red The red component of the color (0-255).
     * @param float|int $green The green component of the color (0-255).
     * @param float|int $blue The blue component of the color (0-255).
     * 
     * @return array An associative array with keys 'hue', 'saturation', 'lightness', and 'brightness' representing the HSL values.
     */
    public static function rgbToHSL(float|int $red, float|int $green, float|int $blue)
    {
        $r = $red / 255;
        $g = $green / 255;
        $b = $blue / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;
        $v = $max;

        if ($max === $min) {
            $hue = 0;
            $saturation = 0;
            $lightness = Basic::absRound($l * 100);
            $brightness = Basic::absRound($v * 100);
            return ['hue' => $hue, 'saturation' => $saturation, 'lightness' => $lightness, 'brightness' => $brightness];
        }

        $d = $max - $min;
        $s = $d / (($l <= 0.5) ? ($max + $min) : (2 - $max - $min));
        $h = (($max === $r) ? ($g - $b) / $d + ($g < $b ? 6 : 0) : (
            ($max === $g) ? (($b - $r) / $d + 2) : (($r - $g) / $d + 4)) / 6
        );

        $hue = Basic::absRound($h * 360);
        $saturation = Basic::absRound($s * 100);
        $lightness = Basic::absRound($l * 100);
        $brightness = Basic::absRound($v * 100);
        return ['hue' => $hue, 'saturation' => $saturation, 'lightness' => $lightness, 'brightness' => $brightness];
    }

    /**
     * Helper function for converting hue to RGB components.
     *
     * @param float $a An intermediate value used in the conversion process.
     * @param float $b An intermediate value used in the conversion process.
     * @param float $c The hue component of the color (0-1).
     * 
     * @return float The RGB component corresponding to the given hue.
     */
    public static function hueToRGB(float $a, float $b, float $c)
    {
        if ($c < 0) {
            $c++;
        }
        if ($c > 1) {
            $c--;
        }
        if ($c < 1 / 6) {
            return $a + ($b - $a) * 6 * $c;
        }
        if ($c < 1 / 2) {
            return $b;
        }
        if ($c < 2 / 3) {
            return $a + ($b - $a) * (2 / 3 - $c) * 6;
        }
        return $a;
    }

    /**
     * Converts HSL (Hue, Saturation, Lightness) color values to RGB format.
     *
     * @param float|int $hue The hue component of the color (0-360).
     * @param float|int $saturation The saturation component of the color (0-100).
     * @param float|int $lightness The lightness component of the color (0-100).
     * 
     * @return array An associative array with keys 'r', 'g', and 'b' representing the RGB values.
     */
    public static function hslToRGB(float|int $hue, float|int $saturation, float|int $lightness)
    {
        $h = $hue / 360;
        $s = $saturation / 100;
        $l = $lightness / 100;
        $q = $l < 0.5 ? $l * (1 + $s) : ($l + $s - $l * $s);
        $p = 2 * $l - $q;
        $red = Basic::absRound(self::hueToRGB($p, $q, $h + 1 / 3) * 255);
        $green = Basic::absRound(self::hueToRGB($p, $q, $h) * 255);
        $blue = Basic::absRound(self::hueToRGB($p, $q, $h - 1 / 3) * 255);
        return ['r' => $red, 'g' => $green, 'b' => $blue];
    }

    /**
     * Converts HSV (Hue, Saturation, Value) color values to RGB format.
     *
     * @param float|int $hue The hue component of the color (0-360).
     * @param float|int $saturation The saturation component of the color (0-100).
     * @param float|int $brightness The brightness component of the color (0-100).
     * 
     * @return array An associative array with keys 'r', 'g', and 'b' representing the RGB values.
     */
    public static function hsvToRGB(float|int $hue, float|int $saturation, float|int $brightness)
    {
        $h = $hue / 360;
        $s = $saturation / 100;
        $v = $brightness / 100;
        $r = 0;
        $g = 0;
        $b = 0;
        $i = floor($h * 6);
        $f = $h * 6 - $i;
        $p = $v * (1 - $s);
        $q = $v * (1 - $f * $s);
        $t = $v * (1 - (1 - $f) * $s);
        switch ($i % 6) {
            case 0:
                $r = $v;
                $g = $t;
                $b = $p;
                break;
            case 1:
                $r = $q;
                $g = $v;
                $b = $p;
                break;
            case 2:
                $r = $p;
                $g = $v;
                $b = $t;
                break;
            case 3:
                $r = $p;
                $g = $q;
                $b = $v;
                break;
            case 4:
                $r = $t;
                $g = $p;
                $b = $v;
                break;
            case 5:
                $r = $v;
                $g = $p;
                $b = $q;
                break;
        }

        $red = Basic::absRound($r * 255);
        $green = Basic::absRound($g * 255);
        $blue = Basic::absRound($b * 255);

        return ['r' => $red, 'g' => $green, 'b' => $blue];
    }
}
