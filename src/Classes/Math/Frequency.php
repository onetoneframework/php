<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use Clover\Classes\Math\Length;
use Clover\Enumeration\Math\Geometry as GeometryEnum;
use Clover\Enumeration\LengthUnit;

class Frequency
{
    /**
     * Cooley-Tukey radix-2 FFT on real-valued samples.
     * Input is zero-padded to the next power of 2 if necessary.
     *
     * @param array $samples Array of real-valued float samples
     * 
     * @return array{magnitude: array<float>,  phase: array<float>} ['magnitude' => float[], 'phase' => float[]]
     */
    public static function fft(array $samples): array
    {
        $n = count($samples);
        $m = 1;
        while ($m < $n) {
            $m <<= 1;
        }

        while (count($samples) < $m) {
            $samples[] = 0.0;
        }
        $n = $m;

        $real = array_fill(0, $n, 0.0);
        $imag = array_fill(0, $n, 0.0);
        $bits = (int) log($n, 2);

        for ($i = 0; $i < $n; $i++) {
            $rev = 0;
            for ($j = 0; $j < $bits; $j++) {
                $rev = ($rev << 1) | (($i >> $j) & 1);
            }
            $real[$rev] = (float) $samples[$i];
        }

        for ($size = 2; $size <= $n; $size *= 2) {
            $halfSize = $size >> 1;
            $angle = -2.0 * M_PI / $size;
            $wReal = cos($angle);
            $wImag = sin($angle);

            for ($start = 0; $start < $n; $start += $size) {
                $curReal = 1.0;
                $curImag = 0.0;
                for ($k = 0; $k < $halfSize; $k++) {
                    $evenIdx = $start + $k;
                    $oddIdx = $evenIdx + $halfSize;

                    $tReal = $curReal * $real[$oddIdx] - $curImag * $imag[$oddIdx];
                    $tImag = $curReal * $imag[$oddIdx] + $curImag * $real[$oddIdx];

                    $real[$oddIdx] = $real[$evenIdx] - $tReal;
                    $imag[$oddIdx] = $imag[$evenIdx] - $tImag;
                    $real[$evenIdx] += $tReal;
                    $imag[$evenIdx] += $tImag;

                    $newCurReal = $curReal * $wReal - $curImag * $wImag;
                    $curImag = $curReal * $wImag + $curImag * $wReal;
                    $curReal = $newCurReal;
                }
            }
        }

        $halfN = $n >> 1;
        $magnitude = [];
        $phase = [];
        for ($i = 0; $i < $halfN; $i++) {
            $magnitude[] = round(sqrt($real[$i] * $real[$i] + $imag[$i] * $imag[$i]) / $halfN, 6);
            $phase[] = round(atan2($imag[$i], $real[$i]), 6);
        }

        return ['magnitude' => $magnitude, 'phase' => $phase];
    }

    /**
     * Inverse FFT to reconstruct time-domain samples from frequency-domain data.
     *
     * @param array $magnitude Magnitude values (half-spectrum)
     * @param array $phase Phase values (half-spectrum)
     * @param int $outputLength Desired output sample count
     * @return array<float|int> Reconstructed real-valued samples
     */
    public static function ifft(array $magnitude, array $phase, int $outputLength): array
    {
        $halfN = count($magnitude);
        $n = $halfN << 1;

        $real = array_fill(0, $n, 0.0);
        $imag = array_fill(0, $n, 0.0);

        for ($i = 0; $i < $halfN; $i++) {
            $real[$i] = $magnitude[$i] * $halfN * cos($phase[$i]);
            $imag[$i] = $magnitude[$i] * $halfN * sin($phase[$i]);
            if ($i > 0) {
                $real[$n - $i] = $real[$i];
                $imag[$n - $i] = -$imag[$i];
            }
        }

        $bits = (int) log($n, 2);
        $rReal = array_fill(0, $n, 0.0);
        $rImag = array_fill(0, $n, 0.0);

        for ($i = 0; $i < $n; $i++) {
            $rev = 0;
            for ($j = 0; $j < $bits; $j++) {
                $rev = ($rev << 1) | (($i >> $j) & 1);
            }
            $rReal[$rev] = $real[$i];
            $rImag[$rev] = $imag[$i];
        }

        for ($size = 2; $size <= $n; $size *= 2) {
            $halfSize = $size >> 1;
            $angle = 2.0 * M_PI / $size;
            $wReal = cos($angle);
            $wImag = sin($angle);

            for ($start = 0; $start < $n; $start += $size) {
                $curReal = 1.0;
                $curImag = 0.0;
                for ($k = 0; $k < $halfSize; $k++) {
                    $evenIdx = $start + $k;
                    $oddIdx = $evenIdx + $halfSize;

                    $tReal = $curReal * $rReal[$oddIdx] - $curImag * $rImag[$oddIdx];
                    $tImag = $curReal * $rImag[$oddIdx] + $curImag * $rReal[$oddIdx];

                    $rReal[$oddIdx] = $rReal[$evenIdx] - $tReal;
                    $rImag[$oddIdx] = $rImag[$evenIdx] - $tImag;
                    $rReal[$evenIdx] += $tReal;
                    $rImag[$evenIdx] += $tImag;

                    $newCurReal = $curReal * $wReal - $curImag * $wImag;
                    $curImag = $curReal * $wImag + $curImag * $wReal;
                    $curReal = $newCurReal;
                }
            }
        }

        $result = [];
        for ($i = 0; $i < min($n, $outputLength); $i++) {
            $result[] = $rReal[$i] / $n;
        }
        return $result;
    }
}
