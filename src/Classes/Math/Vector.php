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

/**
 * Class Vector
 *
 * A utility class for vector-related mathematical operations.
 */
class Vector
{
    /**
     * Add two vectors.
     *
     * @param array $v1
     * @param array $v2
     * 
     * @return array|null
     */
    public static function add(array $v1, array $v2): ?array
    {
        if (count($v1) !== count($v2)) {
            return null;
        }
        
        return array_map(function ($a, $b) {
            return $a + $b;
        }, $v1, $v2);
    }

    /**
     * Subtract one vector from another.
     *
     * @param array $v1
     * @param array $v2
     * 
     * @return array|null
     */
    public static function subtract(array $v1, array $v2): ?array
    {
        if (count($v1) !== count($v2)) {
            return null;
        }
        return array_map(function ($a, $b) {
            return $a - $b;
        }, $v1, $v2);
    }

    /**
     * Multiply a vector by a scalar.
     *
     * @param array $vector
     * @param Scalar $scalar
     * 
     * @return array
     */
    public static function multiplyScalar(array $vector, Scalar $scalar): array
    {
        $value = $scalar->value;

        return array_map(function ($v) use ($value): float|int {
            return $v * $value;
        }, $vector);
    }

    /**
     * Calculate the dot product of two vectors.
     *
     * @param array $v1
     * @param array $v2
     * 
     * @return float|null
     */
    public static function dotProduct(array $v1, array $v2): ?float
    {
        if (count($v1) !== count($v2)) {
            return null;
        }

        $product = 0;
        foreach (array_map(null, $v1, $v2) as [$a, $b]) {
            $product += $a * $b;
        }

        return $product;
    }

    /**
     * Calculate the cross product of two 3D vectors.
     *
     * @param array $v1
     * @param array $v2
     * 
     * @return array<float|int>|null
     */
    public static function crossProduct(array $v1, array $v2): ?array
    {
        if (count($v1) !== 3 || count($v2) !== 3) {
            return null;
        }

        return [
            $v1[1] * $v2[2] - $v1[2] * $v2[1],
            $v1[2] * $v2[0] - $v1[0] * $v2[2],
            $v1[0] * $v2[1] - $v1[1] * $v2[0],
        ];
    }

    /**
     * Calculate the magnitude of a vector.
     *
     * @param array $vector
     * 
     * @return float
     */
    public static function magnitude(array $vector): float
    {
        $sumOfSquares = array_sum(array_map(function ($v) {
            return pow($v, 2);
        }, $vector));

        return sqrt($sumOfSquares);
    }

    /**
     * Normalize a vector to unit length.
     *
     * @param array $vector
     * 
     * @return array|null
     */
    public static function normalize(array $vector): ?array
    {
        $magnitude = self::magnitude($vector);
        if ($magnitude === 0.0) {
            return null;
        }

        return array_map(function ($v) use ($magnitude) {
            return $v / $magnitude;
        }, $vector);
    }

    /**
     * Perform an inverse Hadamard transform on a 2x2 block of data.
     *
     * @param array $dc The input 2x2 block of data in a flat array format (4 elements).
     * @param int $qpC The quantization parameter for the chroma component.
     * @return array The resulting 2x2 block after the inverse Hadamard transform, in a flat array format (4 elements).
     */
    public static function inverseHadamard2x2(array $dc, int $qpC): array
    {
        $d00 = $dc[0] + $dc[1] + $dc[2] + $dc[3];
        $d01 = $dc[0] - $dc[1] + $dc[2] - $dc[3];
        $d10 = $dc[0] + $dc[1] - $dc[2] - $dc[3];
        $d11 = $dc[0] - $dc[1] - $dc[2] + $dc[3];

        $vMat = [
            [10, 16, 13],
            [11, 18, 14],
            [13, 20, 16],
            [14, 23, 18],
            [16, 25, 20],
            [18, 29, 23],
        ];
        $qpPer = intdiv($qpC, 6);
        $qpRem = $qpC % 6;
        $scale = $vMat[$qpRem][0];

        $s = function ($v) use ($scale, $qpPer) {
            if ($qpPer >= 1) {
                return ($v * $scale) << ($qpPer - 1);
            }
            return ($v * $scale + 1) >> 1;
        };

        return [$s($d00), $s($d01), $s($d10), $s($d11)];
    }

    /**
     * Predict intra 4x4 block values based on neighboring pixels and the specified mode.
     *
     * @param array $plane The 2D array representing the pixel plane.
     * @param int $bx The x-coordinate of the block's top-left corner.
     * @param int $by The y-coordinate of the block's top-left corner.
     * @param int $mode The prediction mode (0: vertical, 1: horizontal, 2: DC, 3: diagonal).
     * @param int $planeW The width of the pixel plane.
     * @param int $planeH The height of the pixel plane.
     */
    public static function predictIntra4x4(array &$plane, int $bx, int $by, int $mode, int $planeW, int $planeH): void
    {
        $p = [];

        for ($i = -1; $i < 8; $i++) {
            $px = $bx + $i;
            $py = $by - 1;
            $p['t' . $i] = ($py >= 0 && $px >= 0 && $px < $planeW) ? $plane[$py][$px] : 128;
        }

        for ($i = 0; $i < 4; $i++) {
            $px = $bx - 1;
            $py = $by + $i;
            $p['l' . $i] = ($px >= 0 && $py < $planeH) ? $plane[$py][$px] : 128;
        }
        $p['tl'] = ($by > 0 && $bx > 0) ? $plane[$by - 1][$bx - 1] : 128;

        $pred = array_fill(0, 4, array_fill(0, 4, 128));

        switch ($mode) {
            case 0:
                for ($y = 0; $y < 4; $y++) {
                    for ($x = 0; $x < 4; $x++) {
                        $pred[$y][$x] = $p['t' . $x];
                    }
                }
                break;
            case 1:
                for ($y = 0; $y < 4; $y++) {
                    for ($x = 0; $x < 4; $x++) {
                        $pred[$y][$x] = $p['l' . $y];
                    }
                }
                break;
            case 2:
                $sum = 0;
                $count = 0;
                if ($by > 0) {
                    for ($x = 0; $x < 4; $x++) {
                        $sum += $p['t' . $x];
                        $count++;
                    }
                }

                if ($bx > 0) {
                    for ($y = 0; $y < 4; $y++) {
                        $sum += $p['l' . $y];
                        $count++;
                    }
                }
                $dc = ($count > 0) ? ($sum + ($count >> 1)) / $count : 128;
                $pred = array_fill(0, 4, array_fill(0, 4, (int) $dc));
                break;
            default:
                break;
        }

        for ($y = 0; $y < 4; $y++) {
            for ($x = 0; $x < 4; $x++) {
                if ($by + $y < $planeH && $bx + $x < $planeW) {
                    $plane[$by + $y][$bx + $x] = $pred[$y][$x];
                }
            }
        }
    }

    /**
     * Perform an inverse transform on a 4x4 block of data.
     *
     * @param array $d The input 4x4 block of data.
     * @return array The resulting 4x4 block after the inverse transform.
     */
    public static function inverseTransform4x4(array $d): array
    {
        $r = array_fill(0, 4, array_fill(0, 4, 0));
        $e = array_fill(0, 4, array_fill(0, 4, 0));

        for ($i = 0; $i < 4; $i++) {
            $e[$i][0] = $d[$i][0] + $d[$i][2];
            $e[$i][1] = $d[$i][0] - $d[$i][2];
            $e[$i][2] = ($d[$i][1] >> 1) - $d[$i][3];
            $e[$i][3] = $d[$i][1] + ($d[$i][3] >> 1);
        }

        $f = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $f[$i][0] = $e[$i][0] + $e[$i][3];
            $f[$i][1] = $e[$i][1] + $e[$i][2];
            $f[$i][2] = $e[$i][1] - $e[$i][2];
            $f[$i][3] = $e[$i][0] - $e[$i][3];
        }

        for ($j = 0; $j < 4; $j++) {
            $g0 = $f[0][$j] + $f[2][$j];
            $g1 = $f[0][$j] - $f[2][$j];
            $g2 = ($f[1][$j] >> 1) - $f[3][$j];
            $g3 = $f[1][$j] + ($f[3][$j] >> 1);
            $r[0][$j] = ($g0 + $g3 + 32) >> 6;
            $r[1][$j] = ($g1 + $g2 + 32) >> 6;
            $r[2][$j] = ($g1 - $g2 + 32) >> 6;
            $r[3][$j] = ($g0 - $g3 + 32) >> 6;
        }

        return $r;
    }

    /**
     * Predict intra 8x8 chroma block values based on neighboring pixels and the specified mode.
     *
     * @param array $plane The 2D array representing the pixel plane.
     * @param int $mbX The x-coordinate of the macroblock's top-left corner (in units of 16 pixels).
     * @param int $mbY The y-coordinate of the macroblock's top-left corner (in units of 16 pixels).
     * @param int $mode The prediction mode (0: DC, 1: vertical, 2: horizontal, 3: diagonal).
     * @param int $planeW The width of the pixel plane.
     * @param int $planeH The height of the pixel plane.
     */
    public static function predictChroma(array &$plane, int $mbX, int $mbY, int $mode, int $planeW, int $planeH): void
    {
        $ox = $mbX * 8;
        $oy = $mbY * 8;
        $pred = array_fill(0, 8, array_fill(0, 8, 128));

        switch ($mode) {
            case 0:
                $sum = [0, 0, 0, 0];
                $cnt = [0, 0, 0, 0];
                for ($blk = 0; $blk < 4; $blk++) {
                    $bx = ($blk & 1) * 4;
                    $by = ($blk >> 1) * 4;
                    if ($oy > 0) {
                        for ($x = 0; $x < 4; $x++) {
                            $px = $ox + $bx + $x;
                            if ($px < $planeW) {
                                $sum[$blk] += $plane[$oy - 1][$px];
                                $cnt[$blk]++;
                            }
                        }
                    }

                    if ($ox > 0) {
                        for ($y = 0; $y < 4; $y++) {
                            $py = $oy + $by + $y;
                            if ($py < $planeH) {
                                $sum[$blk] += $plane[$py][$ox - 1];
                                $cnt[$blk]++;
                            }
                        }
                    }
                }
                for ($blk = 0; $blk < 4; $blk++) {
                    $bx = ($blk & 1) * 4;
                    $by = ($blk >> 1) * 4;
                    $dc = ($cnt[$blk] > 0) ? intdiv($sum[$blk] + ($cnt[$blk] >> 1), $cnt[$blk]) : 128;
                    for ($y = 0; $y < 4; $y++) {
                        for ($x = 0; $x < 4; $x++) {
                            $pred[$by + $y][$bx + $x] = $dc;
                        }
                    }
                }
                break;
            case 1:
                if ($ox > 0) {
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = ($oy + $y < $planeH) ? $plane[$oy + $y][$ox - 1] : 128;
                        }
                    }
                }
                break;
            case 2:
                if ($oy > 0) {
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = ($ox + $x < $planeW) ? $plane[$oy - 1][$ox + $x] : 128;
                        }
                    }
                }
                break;
            case 3:
                if ($oy > 0 && $ox > 0) {
                    $h = 0;
                    $v = 0;
                    for ($i = 0; $i < 4; $i++) {
                        $h += ($i + 1) * (
                            (($ox + 4 + $i < $planeW) ? $plane[$oy - 1][$ox + 4 + $i] : 128) -
                            (($ox + 2 - $i >= 0) ? $plane[$oy - 1][$ox + 2 - $i] : 128)
                        );
                        $v += ($i + 1) * (
                            (($oy + 4 + $i < $planeH) ? $plane[$oy + 4 + $i][$ox - 1] : 128) -
                            (($oy + 2 - $i >= 0) ? $plane[$oy + 2 - $i][$ox - 1] : 128)
                        );
                    }

                    $a = 16 * ($plane[$oy - 1][min($ox + 7, $planeW - 1)] + $plane[min($oy + 7, $planeH - 1)][$ox - 1]);
                    $b = (17 * $h + 16) >> 5;
                    $c = (17 * $v + 16) >> 5;
                    for ($y = 0; $y < 8; $y++) {
                        for ($x = 0; $x < 8; $x++) {
                            $pred[$y][$x] = max(0, min(255, ($a + $b * ($x - 3) + $c * ($y - 3) + 16) >> 5));
                        }
                    }
                }
                break;
        }

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                if ($oy + $y < $planeH && $ox + $x < $planeW) {
                    $plane[$oy + $y][$ox + $x] = $pred[$y][$x];
                }
            }
        }
    }

    /**
     * Predict intra 16x16 block values based on neighboring pixels and the specified mode.
     *
     * @param array $plane The 2D array representing the pixel plane.
     * @param int $mbX The x-coordinate of the macroblock's top-left corner (in units of 16 pixels).
     * @param int $mbY The y-coordinate of the macroblock's top-left corner (in units of 16 pixels).
     * @param int $mode The prediction mode (0: vertical, 1: horizontal, 2: DC, 3: diagonal).
     * @param int $planeW The width of the pixel plane.
     * @param int $planeH The height of the pixel plane.
     */
    public static function predictIntra16x16(array &$plane, int $mbX, int $mbY, int $mode, int $planeW, int $planeH): void
    {
        $ox = $mbX * 16;
        $oy = $mbY * 16;
        $pred = array_fill(0, 16, array_fill(0, 16, 128));

        switch ($mode) {
            case 0:
                if ($oy > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = ($oy - 1 >= 0 && $ox + $x < $planeW) ? $plane[$oy - 1][$ox + $x] : 128;
                        }
                    }
                }
                break;
            case 1:
                if ($ox > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = ($ox - 1 >= 0 && $oy + $y < $planeH) ? $plane[$oy + $y][$ox - 1] : 128;
                        }
                    }
                }
                break;
            case 2:
                $sum = 0;
                $count = 0;
                if ($oy > 0) {
                    for ($x = 0; $x < 16; $x++) {
                        $px = $ox + $x;
                        if ($px < $planeW) {
                            $sum += $plane[$oy - 1][$px];
                            $count++;
                        }
                    }
                }

                if ($ox > 0) {
                    for ($y = 0; $y < 16; $y++) {
                        $py = $oy + $y;
                        if ($py < $planeH) {
                            $sum += $plane[$py][$ox - 1];
                            $count++;
                        }
                    }
                }
                $dc = ($count > 0) ? intdiv($sum + ($count >> 1), $count) : 128;
                $pred = array_fill(0, 16, array_fill(0, 16, $dc));
                break;
            case 3:
                $hasTop = ($oy > 0);
                $hasLeft = ($ox > 0);
                if ($hasTop && $hasLeft) {
                    $h = 0;
                    $v = 0;
                    for ($i = 0; $i < 8; $i++) {
                        $h += ($i + 1) * (
                            (($ox + 8 + $i < $planeW) ? $plane[$oy - 1][$ox + 8 + $i] : 128) -
                            (($ox + 6 - $i >= 0) ? $plane[$oy - 1][$ox + 6 - $i] : 128)
                        );
                        $v += ($i + 1) * (
                            (($oy + 8 + $i < $planeH) ? $plane[$oy + 8 + $i][$ox - 1] : 128) -
                            (($oy + 6 - $i >= 0) ? $plane[$oy + 6 - $i][$ox - 1] : 128)
                        );
                    }

                    $a = 16 * ($plane[$oy - 1][$ox + 15 < $planeW ? $ox + 15 : $planeW - 1] + $plane[($oy + 15 < $planeH ? $oy + 15 : $planeH - 1)][$ox - 1]);
                    $b = (5 * $h + 32) >> 6;
                    $c = (5 * $v + 32) >> 6;
                    for ($y = 0; $y < 16; $y++) {
                        for ($x = 0; $x < 16; $x++) {
                            $pred[$y][$x] = max(0, min(255, ($a + $b * ($x - 7) + $c * ($y - 7) + 16) >> 5));
                        }
                    }
                }
                break;
        }

        for ($y = 0; $y < 16; $y++) {
            for ($x = 0; $x < 16; $x++) {
                if ($oy + $y < $planeH && $ox + $x < $planeW) {
                    $plane[$oy + $y][$ox + $x] = $pred[$y][$x];
                }
            }
        }
    }

    /**
     * Perform an inverse transform on a 4x4 block of data using the Hadamard transform.
     *
     * @param array $dc The input 4x4 block of data in a flat array format (16 elements).
     * @param int $qpY The quantization parameter for the luma component.
     * @return array The resulting 4x4 block after the inverse Hadamard transform, in a flat array format (16 elements).
     */
    public static function inverseHadamard4x4(array $dc, int $qpY): array
    {
        $d = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $d[$i][$j] = $dc[$i * 4 + $j];
            }
        }

        $e = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $e[$i][0] = $d[$i][0] + $d[$i][2];
            $e[$i][1] = $d[$i][0] - $d[$i][2];
            $e[$i][2] = $d[$i][1] - $d[$i][3];
            $e[$i][3] = $d[$i][1] + $d[$i][3];
        }

        $f = array_fill(0, 4, array_fill(0, 4, 0));
        for ($i = 0; $i < 4; $i++) {
            $f[$i][0] = $e[$i][0] + $e[$i][3];
            $f[$i][1] = $e[$i][1] + $e[$i][2];
            $f[$i][2] = $e[$i][1] - $e[$i][2];
            $f[$i][3] = $e[$i][0] - $e[$i][3];
        }

        $g = array_fill(0, 4, array_fill(0, 4, 0));
        for ($j = 0; $j < 4; $j++) {
            $g[0][$j] = $f[0][$j] + $f[2][$j];
            $g[1][$j] = $f[0][$j] - $f[2][$j];
            $g[2][$j] = $f[1][$j] - $f[3][$j];
            $g[3][$j] = $f[1][$j] + $f[3][$j];
        }

        $h = array_fill(0, 4, array_fill(0, 4, 0));
        for ($j = 0; $j < 4; $j++) {
            $h[0][$j] = $g[0][$j] + $g[3][$j];
            $h[1][$j] = $g[1][$j] + $g[2][$j];
            $h[2][$j] = $g[1][$j] - $g[2][$j];
            $h[3][$j] = $g[0][$j] - $g[3][$j];
        }

        $vMat = [
            [10, 16, 13],
            [11, 18, 14],
            [13, 20, 16],
            [14, 23, 18],
            [16, 25, 20],
            [18, 29, 23],
        ];
        $qpPer = intdiv($qpY, 6);
        $qpRem = $qpY % 6;
        $scale = $vMat[$qpRem][0];

        $result = [];
        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                if ($qpPer >= 2) {
                    $result[$i * 4 + $j] = ($h[$i][$j] * $scale) << ($qpPer - 2);
                } else {
                    $result[$i * 4 + $j] = ($h[$i][$j] * $scale + (1 << (1 - $qpPer))) >> (2 - $qpPer);
                }
            }
        }

        return $result;
    }
}
