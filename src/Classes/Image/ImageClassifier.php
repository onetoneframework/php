<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Image;

use Clover\Classes\OperationSystem;
use RuntimeException;
use function is_int;
use function is_float;
use function is_array;
use function array_slice;
use function count;

/**
 * ImageClassifier
 *
 * A comprehensive pure-PHP image processing and edge-detection toolkit.
 *
 * All methods accept / return a "pixel array":
 *   - Grayscale: 2D int[][] where each value is 0-255
 *   - Color:     2D array[][][] where each pixel is [R, G, B] (0-255 each)
 *
 * GD helpers (fromGd / toGd) bridge between GdImage and pixel arrays.
 *
 * Pipeline overview
 * -----------------
 *  loadFromFile()  → fromGd()
 *  → grayscale()   → gaussianBlur() / medianFilter() / bilateralFilter()
 *  → canny()  /  prewitt()  /  sobelEdges()  /  log_()
 *  → otsuThreshold()  /  adaptiveThreshold()
 *  → morphDilate()  /  morphErode()  /  morphOpen()  /  morphClose()
 *  → connectedComponents()  /  boundingBoxes()
 *  → harrisCorners()
 *  → histogramEqualize()  /  sharpen()
 *  → toGd()  →  saveToFile()
 */
class ImageClassifier
{
    // -------------------------------------------------------------------------
    // Constants
    // -------------------------------------------------------------------------

    public const INTERP_NEAREST = 'nearest';
    public const INTERP_BILINEAR = 'bilinear';

    // -------------------------------------------------------------------------
    // GD Helpers
    // -------------------------------------------------------------------------

    /**
     * Load an image from disk and return its pixel array (RGB).
     *
     * Supported formats: JPEG, PNG, GIF, WebP, BMP.
     *
     * @param  string $path Absolute or relative file path.
     * @return array        3-D array [y][x] => [R, G, B].
     * @throws RuntimeException If the file cannot be read or decoded.
     */
    public static function loadFromFile(string $path): array
    {
        if (!is_readable($path)) {
            throw new RuntimeException("Cannot read file: {$path}");
        }

        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException("Not a recognised image: {$path}");
        }

        $gd = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_GIF => @imagecreatefromgif($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_BMP => @imagecreatefrombmp($path),
            default => throw new RuntimeException("Unsupported image type: {$info[2]}")
        };

        if (!$gd instanceof \GdImage) {
            throw new RuntimeException("GD failed to load: {$path}");
        }

        // Ensure true-color so imagecolorat returns packed RGB
        if (!imageistruecolor($gd)) {
            $tc = imagecreatetruecolor(imagesx($gd), imagesy($gd));
            imagecopy($tc, $gd, 0, 0, 0, 0, imagesx($gd), imagesy($gd));

            if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
                // @phpstan-ignore-next-line
                imagedestroy($gd);
            }
            $gd = $tc;
        }

        return self::fromGd($gd);
    }

    /**
     * Convert a GdImage to an RGB pixel array.
     *
     * @param  \GdImage $gd
     * @return array  [y][x] => [R, G, B]
     */
    public static function fromGd(\GdImage $gd): array
    {
        $w = imagesx($gd);
        $h = imagesy($gd);
        $img = [];

        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $packed = imagecolorat($gd, $x, $y);
                $row[] = [
                    ($packed >> 16) & 0xFF,
                    ($packed >> 8) & 0xFF,
                    $packed & 0xFF,
                ];
            }
            $img[] = $row;
        }

        return $img;
    }

    /**
     * Convert a grayscale or RGB pixel array back to a GdImage.
     *
     * @param  array $img  2-D grayscale (int) or RGB ([R,G,B]) pixel array.
     * @return \GdImage
     * @throws RuntimeException
     */
    public static function toGd(array $img): \GdImage
    {
        $h = count($img);
        $w = $h > 0 ? count($img[0]) : 0;

        if ($h === 0 || $w === 0) {
            throw new RuntimeException('Cannot create a GdImage from an empty pixel array.');
        }

        $gd = imagecreatetruecolor($w, $h);

        if (!$gd instanceof \GdImage) {
            throw new RuntimeException('imagecreatetruecolor() failed.');
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $p = $img[$y][$x];
                if (is_array($p)) {
                    [$r, $g, $b] = $p;
                } else {
                    $v = self::clamp((int) $p, 0, 255);
                    $r = $g = $b = $v;
                }
                $color = imagecolorallocate($gd, $r, $g, $b);
                imagesetpixel($gd, $x, $y, $color);
            }
        }

        return $gd;
    }

    /**
     * Save a pixel array to disk.
     *
     * @param  array  $img     Grayscale or RGB pixel array.
     * @param  string $path    Destination file path (extension determines format).
     * @param  int    $quality JPEG quality 0-100 (ignored for PNG / GIF).
     * @throws RuntimeException
     */
    public static function saveToFile(array $img, string $path, int $quality = 90): void
    {
        $gd = self::toGd($img);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $ok = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($gd, $path, $quality),
            'png' => imagepng($gd, $path),
            'gif' => imagegif($gd, $path),
            'webp' => imagewebp($gd, $path, $quality),
            'bmp' => imagebmp($gd, $path),
            default => throw new RuntimeException("Unsupported extension: {$ext}")
        };

        if (OperationSystem::comparePHPVersion('8.5.0', '<')) {
            // @phpstan-ignore-next-line
            imagedestroy($gd);
        }

        if (!$ok) {
            throw new RuntimeException("Failed to write image: {$path}");
        }
    }

    // -------------------------------------------------------------------------
    // Color Space Conversions
    // -------------------------------------------------------------------------

    /**
     * Convert an RGB pixel array to grayscale (luminance).
     *
     * Accepts pixels that are already a scalar (pass-through).
     *
     * @param  array $img  RGB or grayscale pixel array.
     * @return array       2-D int[][] grayscale array.
     */
    public static function grayscale(array $img): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $p = $img[$y][$x];

                if (is_int($p) || is_float($p)) {
                    $out[$y][$x] = self::clamp((int) round($p), 0, 255);
                } else {
                    $r = (int) ($p[0] ?? 0);
                    $g = (int) ($p[1] ?? 0);
                    $b = (int) ($p[2] ?? 0);
                    // ITU-R BT.601 luma coefficients
                    $out[$y][$x] = self::clamp((int) round(0.299 * $r + 0.587 * $g + 0.114 * $b), 0, 255);
                }
            }
        }

        return $out;
    }

    /**
     * Convert an RGB pixel array to HSV.
     *
     * @param  array $img  RGB pixel array.
     * @return array       [y][x] => [H(0-360), S(0-1), V(0-1)]
     */
    public static function rgbToHsv(array $img): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w, [0.0, 0.0, 0.0]);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $p = $img[$y][$x];
                $r = ($p[0] ?? 0) / 255.0;
                $g = ($p[1] ?? 0) / 255.0;
                $b = ($p[2] ?? 0) / 255.0;
                $mx = max($r, $g, $b);
                $mn = min($r, $g, $b);
                $d = $mx - $mn;
                $v = $mx;
                $s = $mx > 0.0 ? $d / $mx : 0.0;

                if ($d < 1e-10) {
                    $hue = 0.0;
                } elseif ($mx === $r) {
                    $hue = fmod(($g - $b) / $d, 6.0) * 60.0;
                } elseif ($mx === $g) {
                    $hue = (($b - $r) / $d + 2.0) * 60.0;
                } else {
                    $hue = (($r - $g) / $d + 4.0) * 60.0;
                }

                if ($hue < 0.0) {
                    $hue += 360.0;
                }

                $out[$y][$x] = [$hue, $s, $v];
            }
        }

        return $out;
    }

    /**
     * Convert an HSV pixel array back to RGB.
     *
     * @param  array $img  [y][x] => [H(0-360), S(0-1), V(0-1)]
     * @return array       RGB pixel array.
     */
    public static function hsvToRgb(array $img): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w, [0, 0, 0]);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                [$hue, $s, $v] = $img[$y][$x];
                $c = $v * $s;
                $xv = $c * (1 - abs(fmod($hue / 60.0, 2) - 1));
                $m = $v - $c;
                $sector = (int) floor($hue / 60.0) % 6;

                [$r1, $g1, $b1] = match ($sector) {
                    0 => [$c, $xv, 0.0],
                    1 => [$xv, $c, 0.0],
                    2 => [0.0, $c, $xv],
                    3 => [0.0, $xv, $c],
                    4 => [$xv, 0.0, $c],
                    default => [$c, 0.0, $xv],
                };

                $out[$y][$x] = [
                    self::clamp((int) round(($r1 + $m) * 255), 0, 255),
                    self::clamp((int) round(($g1 + $m) * 255), 0, 255),
                    self::clamp((int) round(($b1 + $m) * 255), 0, 255),
                ];
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Noise Reduction / Smoothing
    // -------------------------------------------------------------------------

    /**
     * Gaussian blur (σ-driven kernel size).
     *
     * @param  array $img    Grayscale pixel array.
     * @param  float $sigma  Standard deviation (>= 0.1).
     * @return array         Blurred grayscale array.
     */
    public static function gaussianBlur(array $img, float $sigma = 1.4): array
    {
        return self::convolve($img, self::makeGaussianKernel($sigma));
    }

    /**
     * Median filter — robust salt-and-pepper noise removal.
     *
     * @param  array $img      Grayscale pixel array.
     * @param  int   $radius   Neighbourhood radius (kernel size = 2r+1).
     * @return array
     */
    public static function medianFilter(array $img, int $radius = 1): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);
        $size = 2 * $radius + 1;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $vals = [];
                for ($ky = 0; $ky < $size; $ky++) {
                    for ($kx = 0; $kx < $size; $kx++) {
                        $iy = self::clamp($y + $ky - $radius, 0, $h - 1);
                        $ix = self::clamp($x + $kx - $radius, 0, $w - 1);
                        $vals[] = $img[$iy][$ix];
                    }
                }
                sort($vals, SORT_NUMERIC);
                $out[$y][$x] = (int) $vals[(int) floor(count($vals) / 2)];
            }
        }

        return $out;
    }

    /**
     * Bilateral filter — edge-preserving smoothing.
     *
     * Smooths flat regions while preserving edges by weighting neighbours by
     * both spatial distance and intensity similarity.
     *
     * @param  array $img        Grayscale pixel array.
     * @param  int   $radius     Spatial kernel radius.
     * @param  float $sigmaS     Spatial sigma.
     * @param  float $sigmaR     Range (intensity) sigma.
     * @return array
     */
    public static function bilateralFilter(array $img, int $radius = 5, float $sigmaS = 3.0, float $sigmaR = 50.0): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);
        $twoSs2 = 2.0 * $sigmaS * $sigmaS;
        $twoSr2 = 2.0 * $sigmaR * $sigmaR;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $iCenter = $img[$y][$x];
                $wSum = 0.0;
                $vSum = 0.0;

                for ($ky = -$radius; $ky <= $radius; $ky++) {
                    for ($kx = -$radius; $kx <= $radius; $kx++) {
                        $iy = self::clamp($y + $ky, 0, (int) ($h - 1));
                        $ix = self::clamp($x + $kx, 0, (int) ($w - 1));
                        $iN = $img[$iy][$ix];
                        $wS = exp(-($kx * $kx + $ky * $ky) / $twoSs2);
                        $wR = exp(-(($iN - $iCenter) ** 2) / $twoSr2);
                        $w = $wS * $wR;
                        $wSum += $w;
                        $vSum += $w * $iN;
                    }
                }

                $out[$y][$x] = self::clamp((int) round($vSum / $wSum), 0, 255);
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Edge Detection
    // -------------------------------------------------------------------------

    /**
     * Full Canny edge-detection pipeline.
     *
     * Steps: grayscale → gaussian blur → Sobel → NMS → double-threshold → hysteresis.
     *
     * @param  array $img    RGB or grayscale pixel array.
     * @param  int   $low    Low hysteresis threshold  (0-255).
     * @param  int   $high   High hysteresis threshold (0-255).
     * @param  float $sigma  Gaussian blur sigma.
     * @return array         Binary edge map (0 or 255).
     */
    public static function canny(array $img, int $low = 50, int $high = 150, float $sigma = 1.4): array
    {
        $gray = is_array($img[0][0]) ? self::grayscale($img) : $img;
        $blur = self::gaussianBlur($gray, $sigma);
        [$gx, $gy] = self::sobelGradients($blur);
        [$mag, $dir] = self::gradientMagDir($gx, $gy);
        $nms = self::nonMaxSuppression($mag, $dir);
        $dt = self::doubleThreshold($nms, $low, $high);
        return self::hysteresis($dt);
    }

    /**
     * Sobel edge magnitude map (single output, not the full Canny pipeline).
     *
     * @param  array $img    Grayscale pixel array.
     * @return array         Float magnitude array (unnormalised).
     */
    public static function sobelEdges(array $img): array
    {
        [$gx, $gy] = self::sobelGradients($img);
        [$mag] = self::gradientMagDir($gx, $gy);
        return self::normalise($mag);
    }

    /**
     * Prewitt edge detector.
     *
     * Lighter alternative to Sobel (uniform neighbourhood weighting).
     *
     * @param  array $img  Grayscale pixel array.
     * @return array       Normalised magnitude array (0-255 int).
     */
    public static function prewitt(array $img): array
    {
        $kx = [
            [-1, 0, 1],
            [-1, 0, 1],
            [-1, 0, 1],
        ];
        $ky = [
            [-1, -1, -1],
            [0, 0, 0],
            [1, 1, 1],
        ];
        $gx = self::convolve($img, $kx);
        $gy = self::convolve($img, $ky);
        [$mag] = self::gradientMagDir($gx, $gy);
        return self::normalise($mag);
    }

    /**
     * Laplacian-of-Gaussian (LoG) edge detector.
     *
     * Blurs the image with Gaussian then applies a Laplacian kernel to detect
     * zero-crossings / edges. Returns absolute-value normalised result.
     *
     * @param  array $img    Grayscale pixel array.
     * @param  float $sigma  Gaussian sigma before Laplacian.
     * @return array         Normalised edge map (0-255 int).
     */
    public static function laplacianOfGaussian(array $img, float $sigma = 1.4): array
    {
        $blurred = self::gaussianBlur($img, $sigma);

        // 5×5 LoG kernel (approximation)
        $kernel = [
            [0, 0, -1, 0, 0],
            [0, -1, -2, -1, 0],
            [-1, -2, 16, -2, -1],
            [0, -1, -2, -1, 0],
            [0, 0, -1, 0, 0],
        ];

        $raw = self::convolve($blurred, $kernel);

        // Take absolute value then normalise to 0-255
        [$h, $w] = self::dims($raw);
        $abs = self::zeros($h, $w, 0.0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $abs[$y][$x] = abs($raw[$y][$x]);
            }
        }

        return self::normalise($abs);
    }

    /**
     * Simple Laplacian edge sharpener / detector (3×3 kernel).
     *
     * @param  array $img  Grayscale pixel array.
     * @return array       Normalised edge map (0-255 int).
     */
    public static function laplacian(array $img): array
    {
        $kernel = [
            [0, -1, 0],
            [-1, 4, -1],
            [0, -1, 0],
        ];

        $raw = self::convolve($img, $kernel);
        [$h, $w] = self::dims($raw);
        $abs = self::zeros($h, $w, 0.0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $abs[$y][$x] = abs($raw[$y][$x]);
            }
        }

        return self::normalise($abs);
    }

    // -------------------------------------------------------------------------
    // Thresholding
    // -------------------------------------------------------------------------

    /**
     * Otsu's global thresholding — automatically selects the optimal threshold
     * by maximising inter-class variance of the bimodal histogram.
     *
     * @param  array $img  Grayscale pixel array.
     * @return array       Binary map: 0 (background) or 255 (foreground).
     */
    public static function otsuThreshold(array $img): array
    {
        $threshold = self::computeOtsuThreshold($img);
        return self::applyThreshold($img, $threshold);
    }

    /**
     * Compute Otsu's optimal threshold value without applying it.
     *
     * @param  array $img  Grayscale pixel array.
     * @return int         Threshold value in [0, 255].
     */
    public static function computeOtsuThreshold(array $img): int
    {
        [$h, $w] = self::dims($img);
        $total = $h * $w;
        $hist = array_fill(0, 256, 0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $hist[$img[$y][$x]]++;
            }
        }

        $sum = 0;
        for ($i = 0; $i < 256; $i++) {
            $sum += $i * $hist[$i];
        }

        $sumB = 0.0;
        $wB = 0;
        $best = 0.0;
        $threshold = 0;

        for ($t = 0; $t < 256; $t++) {
            $wB += $hist[$t];
            if ($wB === 0) {
                continue;
            }

            $wF = $total - $wB;
            if ($wF === 0) {
                break;
            }

            $sumB += (float) ($t * $hist[$t]);
            $mB = $sumB / $wB;
            $mF = ($sum - $sumB) / $wF;
            $between = (float) $wB * $wF * ($mB - $mF) ** 2;

            if ($between > $best) {
                $best = $between;
                $threshold = $t;
            }
        }

        return $threshold;
    }

    /**
     * Apply a fixed threshold to a grayscale image.
     *
     * @param  array $img        Grayscale pixel array.
     * @param  int   $threshold  Value in [0, 255].
     * @param  bool  $invert     If true, pixels below threshold become 255.
     * @return array             Binary map: 0 or 255.
     */
    public static function applyThreshold(array $img, int $threshold, bool $invert = false): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $above = $img[$y][$x] >= $threshold;
                $out[$y][$x] = ($invert ? !$above : $above) ? 255 : 0;
            }
        }

        return $out;
    }

    /**
     * Adaptive (local) thresholding using the mean of a local window.
     *
     * Handles uneven illumination better than global Otsu.
     *
     * @param  array $img    Grayscale pixel array.
     * @param  int   $block  Side length of the local window (must be odd).
     * @param  int   $c      Constant subtracted from mean.
     * @return array         Binary map: 0 or 255.
     */
    public static function adaptiveThreshold(array $img, int $block = 11, int $c = 2): array
    {
        if ($block % 2 === 0) {
            $block++; // ensure odd
        }

        [$h, $w] = self::dims($img);
        $half = (int) floor($block / 2);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $sum = 0;
                $count = 0;

                for ($ky = -$half; $ky <= $half; $ky++) {
                    for ($kx = -$half; $kx <= $half; $kx++) {
                        $iy = self::clamp($y + $ky, 0, $h - 1);
                        $ix = self::clamp($x + $kx, 0, $w - 1);
                        $sum += $img[$iy][$ix];
                        $count++;
                    }
                }

                $mean = $sum / $count - $c;
                $out[$y][$x] = $img[$y][$x] >= $mean ? 255 : 0;
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Morphological Operations
    // -------------------------------------------------------------------------

    /**
     * Morphological dilation — expands bright regions.
     *
     * @param  array $img     Binary or grayscale pixel array.
     * @param  int   $radius  Structuring element radius (disk).
     * @return array
     */
    public static function morphDilate(array $img, int $radius = 1): array
    {
        return self::morphOp($img, $radius, 'max');
    }

    /**
     * Morphological erosion — shrinks bright regions.
     *
     * @param  array $img     Binary or grayscale pixel array.
     * @param  int   $radius  Structuring element radius.
     * @return array
     */
    public static function morphErode(array $img, int $radius = 1): array
    {
        return self::morphOp($img, $radius, 'min');
    }

    /**
     * Morphological opening (erosion then dilation) — removes small bright spots.
     *
     * @param  array $img     Binary or grayscale pixel array.
     * @param  int   $radius  Structuring element radius.
     * @return array
     */
    public static function morphOpen(array $img, int $radius = 1): array
    {
        return self::morphDilate(self::morphErode($img, $radius), $radius);
    }

    /**
     * Morphological closing (dilation then erosion) — fills small dark holes.
     *
     * @param  array $img     Binary or grayscale pixel array.
     * @param  int   $radius  Structuring element radius.
     * @return array
     */
    public static function morphClose(array $img, int $radius = 1): array
    {
        return self::morphErode(self::morphDilate($img, $radius), $radius);
    }

    /**
     * Morphological gradient (dilation - erosion) — highlights edges in a binary map.
     *
     * @param  array $img     Binary or grayscale pixel array.
     * @param  int   $radius
     * @return array
     */
    public static function morphGradient(array $img, int $radius = 1): array
    {
        [$h, $w] = self::dims($img);
        $dil = self::morphDilate($img, $radius);
        $ero = self::morphErode($img, $radius);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $out[$y][$x] = self::clamp($dil[$y][$x] - $ero[$y][$x], 0, 255);
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Histogram & Enhancement
    // -------------------------------------------------------------------------

    /**
     * Compute the intensity histogram of a grayscale image.
     *
     * @param  array $img  Grayscale pixel array.
     * @return int[]       256-element frequency array (index = intensity).
     */
    public static function histogram(array $img): array
    {
        $hist = array_fill(0, 256, 0);
        [$h, $w] = self::dims($img);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $hist[$img[$y][$x]]++;
            }
        }

        return $hist;
    }

    /**
     * Histogram equalisation — redistributes intensities for global contrast enhancement.
     *
     * @param  array $img  Grayscale pixel array.
     * @return array       Equalised grayscale pixel array.
     */
    public static function histogramEqualize(array $img): array
    {
        [$h, $w] = self::dims($img);
        $total = $h * $w;
        $hist = self::histogram($img);

        // Cumulative distribution function
        $cdf = [];
        $cum = 0;
        foreach ($hist as $v) {
            $cum += $v;
            $cdf[] = $cum;
        }

        $cdfMin = min(array_filter($cdf, fn($v) => $v > 0));
        $lut = [];

        for ($i = 0; $i < 256; $i++) {
            $lut[$i] = self::clamp((int) round(($cdf[$i] - $cdfMin) / ($total - $cdfMin) * 255), 0, 255);
        }

        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $out[$y][$x] = $lut[$img[$y][$x]];
            }
        }

        return $out;
    }

    /**
     * Unsharp masking — sharpens by subtracting a blurred copy from the original.
     *
     * @param  array $img      Grayscale pixel array.
     * @param  float $sigma    Blur sigma for the mask.
     * @param  float $amount   Strength of the effect (typical: 0.5–2.0).
     * @return array           Sharpened grayscale pixel array.
     */
    public static function unsharpMask(array $img, float $sigma = 1.0, float $amount = 1.0): array
    {
        [$h, $w] = self::dims($img);
        $blurred = self::gaussianBlur($img, $sigma);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $v = $img[$y][$x] + $amount * ($img[$y][$x] - $blurred[$y][$x]);
                $out[$y][$x] = self::clamp((int) round($v), 0, 255);
            }
        }

        return $out;
    }

    /**
     * High-boost sharpening using a convolution kernel.
     *
     * @param  array $img    Grayscale pixel array.
     * @param  float $boost  Boost factor (>1 = sharper, 1 = standard Laplacian sharpen).
     * @return array
     */
    public static function sharpen(array $img, float $boost = 1.5): array
    {
        $c = $boost + 4;
        $kernel = [
            [0.0, -1.0, 0.0],
            [-1.0, $c, -1.0],
            [0.0, -1.0, 0.0],
        ];

        $raw = self::convolve($img, $kernel);
        [$h, $w] = self::dims($raw);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $out[$y][$x] = self::clamp((int) round($raw[$y][$x]), 0, 255);
            }
        }

        return $out;
    }

    /**
     * Gamma correction — adjusts mid-tone brightness.
     *
     * @param  array $img    Grayscale pixel array.
     * @param  float $gamma  < 1 brightens; > 1 darkens.
     * @return array
     */
    public static function gammaCorrection(array $img, float $gamma = 1.0): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);

        // Pre-compute LUT for performance
        $lut = [];
        for ($i = 0; $i < 256; $i++) {
            $lut[$i] = self::clamp((int) round(255.0 * (($i / 255.0) ** $gamma)), 0, 255);
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $out[$y][$x] = $lut[$img[$y][$x]];
            }
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Corner Detection
    // -------------------------------------------------------------------------

    /**
     * Harris corner detector.
     *
     * Returns a response map; local maxima above $threshold are corners.
     *
     * @param  array $img        Grayscale pixel array.
     * @param  float $sigma      Gaussian integration window sigma.
     * @param  float $k          Harris sensitivity parameter (typical: 0.04–0.06).
     * @param  float $threshold  Minimum response to count as corner (0–1, fraction of max).
     * @return array{map: array, corners: array} 'map' is the R response array;
     *                                            'corners' is [[y,x], ...].
     */
    public static function harrisCorners(array $img, float $sigma = 1.0, float $k = 0.05, float $threshold = 0.01): array
    {
        // Compute Ix, Iy
        $kSobX = [[-1, 0, 1], [-2, 0, 2], [-1, 0, 1]];
        $kSobY = [[1, 2, 1], [0, 0, 0], [-1, -2, -1]];
        $Ix = self::convolve($img, $kSobX);
        $Iy = self::convolve($img, $kSobY);

        // Compute products Ix², IxIy, Iy²
        [$h, $w] = self::dims($img);
        $Ixx = self::zeros($h, $w, 0.0);
        $Ixy = self::zeros($h, $w, 0.0);
        $Iyy = self::zeros($h, $w, 0.0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $ix = $Ix[$y][$x];
                $iy = $Iy[$y][$x];
                $Ixx[$y][$x] = $ix * $ix;
                $Ixy[$y][$x] = $ix * $iy;
                $Iyy[$y][$x] = $iy * $iy;
            }
        }

        // Gaussian window over products
        $Ixx = self::gaussianBlur($Ixx, $sigma);
        $Ixy = self::gaussianBlur($Ixy, $sigma);
        $Iyy = self::gaussianBlur($Iyy, $sigma);

        // Harris response R = det(M) - k*trace(M)²
        $R = self::zeros($h, $w, 0.0);
        $maxR = 0.0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $a = $Ixx[$y][$x];
                $b = $Ixy[$y][$x];
                $c = $Iyy[$y][$x];
                $det = $a * $c - $b * $b;
                $trace = $a + $c;
                $r = $det - $k * $trace * $trace;
                $R[$y][$x] = $r;
                if ($r > $maxR) {
                    $maxR = $r;
                }
            }
        }

        // Collect corners above threshold with simple NMS (3×3 local max)
        $limit = $threshold * $maxR;
        $corners = [];

        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $r = $R[$y][$x];
                if ($r < $limit) {
                    continue;
                }

                // Local maximum in 3×3 window
                $isMax = true;
                for ($dy = -1; $dy <= 1 && $isMax; $dy++) {
                    for ($dx = -1; $dx <= 1; $dx++) {
                        if ($dy === 0 && $dx === 0) {
                            continue;
                        }
                        if ($R[$y + $dy][$x + $dx] >= $r) {
                            $isMax = false;
                            break;
                        }
                    }
                }

                if ($isMax) {
                    $corners[] = [$y, $x, $r];
                }
            }
        }

        return ['map' => $R, 'corners' => $corners];
    }

    // -------------------------------------------------------------------------
    // Connected Components
    // -------------------------------------------------------------------------

    /**
     * Connected-components labelling (two-pass union-find, 8-connectivity).
     *
     * @param  array $binary  Binary pixel array (non-zero = foreground).
     * @return array{labels: array, count: int}
     *              'labels' is a 2-D int array where each foreground pixel carries
     *              its component ID (1..N); 'count' is the number of components.
     */
    public static function connectedComponents(array $binary): array
    {
        [$h, $w] = self::dims($binary);
        $labels = self::zeros($h, $w);
        $parent = [0]; // union-find; index 0 unused
        $nextLabel = 1;
        $dirs = [[-1, -1], [-1, 0], [-1, 1], [0, -1]]; // upper & left neighbours

        // First pass
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($binary[$y][$x] === 0) {
                    continue;
                }

                $neighbours = [];
                foreach ($dirs as [$dy, $dx]) {
                    $ny = $y + $dy;
                    $nx = $x + $dx;
                    if ($ny < 0 || $nx < 0 || $nx >= $w) {
                        continue;
                    }
                    if ($labels[$ny][$nx] > 0) {
                        $neighbours[] = self::ufFind($parent, $labels[$ny][$nx]);
                    }
                }

                if (empty($neighbours)) {
                    $labels[$y][$x] = $nextLabel;
                    $parent[] = $nextLabel;
                    $nextLabel++;
                } else {
                    $minLabel = min($neighbours);
                    $labels[$y][$x] = $minLabel;
                    foreach ($neighbours as $nb) {
                        self::ufUnion($parent, $minLabel, $nb);
                    }
                }
            }
        }

        // Second pass — flatten labels
        $remap = [];
        $finalCount = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $l = $labels[$y][$x];
                if ($l === 0) {
                    continue;
                }

                $root = self::ufFind($parent, $l);

                if (!isset($remap[$root])) {
                    $remap[$root] = ++$finalCount;
                }

                $labels[$y][$x] = $remap[$root];
            }
        }

        return ['labels' => $labels, 'count' => $finalCount];
    }

    /**
     * Compute axis-aligned bounding boxes for each connected component.
     *
     * @param  array $labels  Label array from connectedComponents()['labels'].
     * @param  int   $count   Number of components.
     * @return array          Array of [label => [minY, minX, maxY, maxX, area]].
     */
    public static function boundingBoxes(array $labels, int $count): array
    {
        $boxes = [];
        [$h, $w] = self::dims($labels);

        for ($i = 1; $i <= $count; $i++) {
            $boxes[$i] = [$h, $w, -1, -1, 0]; // [minY, minX, maxY, maxX, area]
        }

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $l = $labels[$y][$x];
                if ($l === 0) {
                    continue;
                }

                $b = &$boxes[$l];
                if ($y < $b[0]) {
                    $b[0] = $y;
                }
                if ($x < $b[1]) {
                    $b[1] = $x;
                }
                if ($y > $b[2]) {
                    $b[2] = $y;
                }
                if ($x > $b[3]) {
                    $b[3] = $x;
                }
                $b[4]++;
            }
        }

        return $boxes;
    }

    // -------------------------------------------------------------------------
    // Geometry
    // -------------------------------------------------------------------------

    /**
     * Resize an image (grayscale) using nearest-neighbour or bilinear interpolation.
     *
     * @param  array  $img     Grayscale pixel array.
     * @param  int    $newW    Target width.
     * @param  int    $newH    Target height.
     * @param  string $method  self::INTERP_NEAREST | self::INTERP_BILINEAR
     * @return array
     */
    public static function resize(array $img, int $newW, int $newH, string $method = self::INTERP_BILINEAR): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($newH, $newW);
        $scaleX = $w / $newW;
        $scaleY = $h / $newH;

        for ($ny = 0; $ny < $newH; $ny++) {
            for ($nx = 0; $nx < $newW; $nx++) {
                $fy = $ny * $scaleY;
                $fx = $nx * $scaleX;

                if ($method === self::INTERP_BILINEAR) {
                    $y0 = self::clamp((int) floor($fy), 0, $h - 1);
                    $x0 = self::clamp((int) floor($fx), 0, $w - 1);
                    $y1 = self::clamp($y0 + 1, 0, $h - 1);
                    $x1 = self::clamp($x0 + 1, 0, $w - 1);
                    $dy = $fy - $y0;
                    $dx = $fx - $x0;
                    $v = (1 - $dy) * ((1 - $dx) * $img[$y0][$x0] + $dx * $img[$y0][$x1])
                        + $dy * ((1 - $dx) * $img[$y1][$x0] + $dx * $img[$y1][$x1]);
                    $out[$ny][$nx] = self::clamp((int) round($v), 0, 255);
                } else {
                    $iy = self::clamp((int) round($fy), 0, $h - 1);
                    $ix = self::clamp((int) round($fx), 0, $w - 1);
                    $out[$ny][$nx] = $img[$iy][$ix];
                }
            }
        }

        return $out;
    }

    /**
     * Flip an image horizontally, vertically, or both.
     *
     * @param  array $img        Grayscale or RGB pixel array.
     * @param  bool  $horizontal Flip left-right.
     * @param  bool  $vertical   Flip top-bottom.
     * @return array
     */
    public static function flip(array $img, bool $horizontal = true, bool $vertical = false): array
    {
        [$h, $w] = self::dims($img);
        $out = [];

        for ($y = 0; $y < $h; $y++) {
            $row = [];
            for ($x = 0; $x < $w; $x++) {
                $sy = $vertical ? ($h - 1 - $y) : $y;
                $sx = $horizontal ? ($w - 1 - $x) : $x;
                $row[] = $img[$sy][$sx];
            }
            $out[] = $row;
        }

        return $out;
    }

    /**
     * Crop a rectangular region from an image.
     *
     * @param  array $img  Grayscale or RGB pixel array.
     * @param  int   $y0   Top-left Y.
     * @param  int   $x0   Top-left X.
     * @param  int   $y1   Bottom-right Y (exclusive).
     * @param  int   $x1   Bottom-right X (exclusive).
     * @return array
     */
    public static function crop(array $img, int $y0, int $x0, int $y1, int $x1): array
    {
        [$h, $w] = self::dims($img);
        $y0 = self::clamp($y0, 0, $h);
        $y1 = self::clamp($y1, $y0, $h);
        $x0 = self::clamp($x0, 0, $w);
        $x1 = self::clamp($x1, $x0, $w);
        $out = [];

        for ($y = $y0; $y < $y1; $y++) {
            $out[] = array_slice($img[$y], $x0, $x1 - $x0);
        }

        return $out;
    }

    // -------------------------------------------------------------------------
    // Feature Statistics
    // -------------------------------------------------------------------------

    /**
     * Compute basic statistics of a grayscale pixel array.
     *
     * @param  array $img  Grayscale pixel array.
     * @return array       ['min', 'max', 'mean', 'std', 'median']
     */
    public static function statistics(array $img): array
    {
        [$h, $w] = self::dims($img);
        $vals = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $vals[] = $img[$y][$x];
            }
        }

        $n = count($vals);
        $min = min($vals);
        $max = max($vals);
        $mean = array_sum($vals) / $n;
        $std = sqrt(array_sum(array_map(fn($v) => ($v - $mean) ** 2, $vals)) / $n);
        sort($vals, SORT_NUMERIC);
        $median = $n % 2 === 0
            ? ($vals[$n / 2 - 1] + $vals[$n / 2]) / 2.0
            : $vals[(int) floor($n / 2)];

        return compact('min', 'max', 'mean', 'std', 'median');
    }

    /**
     * Compute edge density (fraction of edge pixels) of a binary edge map.
     *
     * Useful as a simple feature for image classification tasks.
     *
     * @param  array $edgeMap  Binary edge map (0 or 255).
     * @return float           Value in [0, 1].
     */
    public static function edgeDensity(array $edgeMap): float
    {
        [$h, $w] = self::dims($edgeMap);
        $total = $h * $w;
        $count = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($edgeMap[$y][$x] > 0) {
                    $count++;
                }
            }
        }

        return $total > 0 ? $count / $total : 0.0;
    }

    // -------------------------------------------------------------------------
    // Private Helpers
    // -------------------------------------------------------------------------

    /**
     * Return [height, width] of a 2-D pixel array.
     */
    private static function dims(array $img): array
    {
        $h = count($img);
        $w = ($h > 0) ? count($img[0]) : 0;
        return [$h, $w];
    }

    /**
     * Create a 2-D array filled with $fill.
     */
    private static function zeros(int $h, int $w, mixed $fill = 0): array
    {
        return array_fill(0, $h, array_fill(0, $w, $fill));
    }

    /**
     * Clamp an integer value to [min, max].
     */
    private static function clamp(int $v, int $min, int $max): int
    {
        return max($min, min($max, $v));
    }

    /**
     * Normalise a float 2-D array to 0-255 int.
     */
    private static function normalise(array $img): array
    {
        [$h, $w] = self::dims($img);
        $min = PHP_FLOAT_MAX;
        $max = -PHP_FLOAT_MAX;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $v = (float) $img[$y][$x];
                if ($v < $min) {
                    $min = $v;
                }
                if ($v > $max) {
                    $max = $v;
                }
            }
        }

        $range = $max - $min;
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $out[$y][$x] = $range > 0.0
                    ? self::clamp((int) round(255.0 * ($img[$y][$x] - $min) / $range), 0, 255)
                    : 0;
            }
        }

        return $out;
    }

    /**
     * 2-D convolution with zero / replicate padding.
     *
     * For Sobel / Prewitt kernels zero-padding is standard; replicate is used
     * implicitly by clamping coordinates.
     *
     * @param  array $img     2-D numeric array.
     * @param  array $kernel  2-D numeric kernel.
     * @param  bool  $replicate  True = replicate border, false = zero-pad.
     * @return array          2-D float array.
     */
    private static function convolve(array $img, array $kernel, bool $replicate = false): array
    {
        [$h, $w] = self::dims($img);
        $kh = count($kernel);
        $kw = $kh > 0 ? count($kernel[0]) : 0;
        $hy = (int) floor($kh / 2);
        $hx = (int) floor($kw / 2);
        $out = self::zeros($h, $w, 0.0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $sum = 0.0;
                for ($ky = 0; $ky < $kh; $ky++) {
                    for ($kx = 0; $kx < $kw; $kx++) {
                        $iy = $y + ($ky - $hy);
                        $ix = $x + ($kx - $hx);

                        if ($iy < 0 || $iy >= $h || $ix < 0 || $ix >= $w) {
                            if ($replicate) {
                                $iy = self::clamp($iy, 0, $h - 1);
                                $ix = self::clamp($ix, 0, $w - 1);
                            } else {
                                continue; // zero-pad
                            }
                        }

                        $sum += (float) $img[$iy][$ix] * $kernel[$ky][$kx];
                    }
                }
                $out[$y][$x] = $sum;
            }
        }

        return $out;
    }

    /**
     * Build a normalised 2-D Gaussian kernel.
     *
     * Kernel size = ceil(sigma * 6) rounded up to the next odd number.
     */
    private static function makeGaussianKernel(float $sigma): array
    {
        $sigma = max(0.1, $sigma);
        $size = (int) ceil($sigma * 6);
        if ($size % 2 === 0) {
            $size++;
        }
        $half = (int) floor($size / 2);
        $kernel = self::zeros($size, $size, 0.0);
        $sum = 0.0;
        $inv2s2 = 1.0 / (2.0 * $sigma * $sigma);

        for ($y = -$half; $y <= $half; $y++) {
            for ($x = -$half; $x <= $half; $x++) {
                $v = exp(-($x * $x + $y * $y) * $inv2s2);
                $kernel[$y + $half][$x + $half] = $v;
                $sum += $v;
            }
        }

        // Normalise
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $kernel[$y][$x] /= $sum;
            }
        }

        return $kernel;
    }

    /**
     * Sobel gradients: returns [Gx, Gy].
     */
    private static function sobelGradients(array $img): array
    {
        $kx = [[-1, 0, 1], [-2, 0, 2], [-1, 0, 1]];
        $ky = [[1, 2, 1], [0, 0, 0], [-1, -2, -1]];
        return [self::convolve($img, $kx), self::convolve($img, $ky)];
    }

    /**
     * Compute gradient magnitude and direction from Gx / Gy arrays.
     *
     * @return array [magnitude (float[][]), direction in radians (float[][])]
     */
    private static function gradientMagDir(array $gx, array $gy): array
    {
        [$h, $w] = self::dims($gx);
        $mag = self::zeros($h, $w, 0.0);
        $dir = self::zeros($h, $w, 0.0);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $gxi = $gx[$y][$x];
                $gyi = $gy[$y][$x];
                $mag[$y][$x] = hypot($gxi, $gyi);
                $dir[$y][$x] = atan2($gyi, $gxi);
            }
        }

        return [$mag, $dir];
    }

    /**
     * Non-maximum suppression along gradient direction.
     */
    private static function nonMaxSuppression(array $mag, array $dir): array
    {
        [$h, $w] = self::dims($mag);
        $out = self::zeros($h, $w, 0.0);

        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $angle = $dir[$y][$x] * (180.0 / M_PI);
                if ($angle < 0) {
                    $angle += 180.0;
                }

                $m = $mag[$y][$x];

                if (($angle >= 0 && $angle < 22.5) || ($angle >= 157.5)) {
                    $q = $mag[$y][$x + 1];
                    $r = $mag[$y][$x - 1];
                } elseif ($angle >= 22.5 && $angle < 67.5) {
                    $q = $mag[$y - 1][$x + 1];
                    $r = $mag[$y + 1][$x - 1];
                } elseif ($angle >= 67.5 && $angle < 112.5) {
                    $q = $mag[$y - 1][$x];
                    $r = $mag[$y + 1][$x];
                } else {
                    $q = $mag[$y - 1][$x - 1];
                    $r = $mag[$y + 1][$x + 1];
                }

                $out[$y][$x] = ($m >= $q && $m >= $r) ? $m : 0.0;
            }
        }

        return $out;
    }

    /**
     * Double threshold: 0 = no edge, 1 = weak edge, 2 = strong edge.
     */
    private static function doubleThreshold(array $nms, int $low, int $high): array
    {
        [$h, $w] = self::dims($nms);
        $out = self::zeros($h, $w);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $v = $nms[$y][$x];
                $out[$y][$x] = match (true) {
                    $v >= $high => 2,
                    $v >= $low => 1,
                    default => 0,
                };
            }
        }

        return $out;
    }

    /**
     * Hysteresis edge tracking: propagates strong edges to connected weak edges.
     */
    private static function hysteresis(array $dt): array
    {
        [$h, $w] = self::dims($dt);
        $out = self::zeros($h, $w);
        $stack = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ($dt[$y][$x] === 2) {
                    $out[$y][$x] = 255;
                    $stack[] = [$y, $x];
                }
            }
        }

        $dirs = [[-1, -1], [-1, 0], [-1, 1], [0, -1], [0, 1], [1, -1], [1, 0], [1, 1]];

        while (!empty($stack)) {
            [$py, $px] = array_pop($stack);

            foreach ($dirs as [$dy, $dx]) {
                $ny = $py + $dy;
                $nx = $px + $dx;

                if ($ny < 0 || $ny >= $h || $nx < 0 || $nx >= $w) {
                    continue;
                }

                if ($out[$ny][$nx] === 0 && $dt[$ny][$nx] === 1) {
                    $out[$ny][$nx] = 255;
                    $stack[] = [$ny, $nx];
                }
            }
        }

        return $out;
    }

    /**
     * Generic morphological operation (max = dilate, min = erode).
     *
     * Uses a disk-shaped structuring element.
     */
    private static function morphOp(array $img, int $radius, string $op): array
    {
        [$h, $w] = self::dims($img);
        $out = self::zeros($h, $w);
        $r2 = $radius * $radius;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $best = ($op === 'max') ? 0 : 255;

                for ($ky = -$radius; $ky <= $radius; $ky++) {
                    for ($kx = -$radius; $kx <= $radius; $kx++) {
                        if ($ky * $ky + $kx * $kx > $r2) {
                            continue; // disk mask
                        }

                        $iy = self::clamp($y + $ky, 0, $h - 1);
                        $ix = self::clamp($x + $kx, 0, $w - 1);
                        $v = $img[$iy][$ix];

                        $best = ($op === 'max') ? max($best, $v) : min($best, $v);
                    }
                }

                $out[$y][$x] = $best;
            }
        }

        return $out;
    }

    // ---- Union-Find helpers for connectedComponents -------------------------

    /**
     * Find the root of a set in the union-find structure with path compression.
     */
    private static function ufFind(array &$parent, int $x): int
    {
        while ($parent[$x] !== $x) {
            $parent[$x] = $parent[$parent[$x]]; // path compression
            $x = $parent[$x];
        }
        return $x;
    }

    /**
     * Union two sets in the union-find structure.
     */
    private static function ufUnion(array &$parent, int $a, int $b): void
    {
        $ra = self::ufFind($parent, $a);
        $rb = self::ufFind($parent, $b);
        if ($ra !== $rb) {
            $parent[$rb] = $ra;
        }
    }
}
