<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use InvalidArgumentException;
use function count;

/**
 * Class Distance
 *
 * A utility class for distance-related mathematical operations.
 */
class Distance
{

    /**
     * Calculate Euclidean distance between two points.
     * Formula: $\sqrt{\sum_{i=1}^{n}(x_i - y_i)^2}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float|int
     */
    public static function euclidean(array $x, array $y): float|int
    {
        $sum = array_sum(array_map(function ($n, $m) {
            return pow($n - $m, 2);
        }, $x, $y));

        return sqrt($sum);
    }

    /**
     * Calculate Chebyshev (Chessboard) distance between two points.
     * Formula: $\max_i |x_i - y_i|$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float|int
     */
    public static function chebyshev(array $x, array $y): float|int
    {
        return max(array_map(function ($n, $m) {
            return abs($n - $m);
        }, $x, $y));
    }

	/**
	 * Compatibility alias for the Chebyshev distance calculation.
	 *
	 * @deprecated Use chebyshev() instead.
	 *
	 * @param array $x
	 * @param array $y
	 *
	 * @return float|int
	 */
	public static function hebyshev(array $x, array $y): float|int
	{
		return self::chebyshev($x, $y);
	}

    /**
     * Calculate Manhattan (Taxicab) distance between two points.
     * Formula: $\sum_{i=1}^{n}|x_i - y_i|$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float|int
     */
    public static function manhattan(array $x, array $y): float|int
    {
        return array_sum(array_map(function ($n, $m) {
            return abs($n - $m);
        }, $x, $y));
    }

    /**
     * Calculate Minkowski distance between two points.
     * Formula: $(\sum_{i=1}^{n}|x_i - y_i|^p)^{1/p}$
     *
     * @param array      $x
     * @param array      $y
     * @param int|float  $p
     * 
     * @return float|int
     */
    public static function minkowski(array $x, array $y, int|float $p = 2): float|int
    {
        $sum = array_sum(array_map(function ($n, $m) use ($p) {
            return pow(abs($n - $m), $p);
        }, $x, $y));

        return pow($sum, 1 / $p);
    }

    /**
     * Calculate Cosine distance between two vectors.
     * Formula: $1 - \frac{\vec{x} \cdot \vec{y}}{||\vec{x}|| \cdot ||\vec{y}||}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float
     */
    public static function cosine(array $x, array $y): float
    {
        $dotProduct = array_sum(array_map(function ($n, $m) {
            return $n * $m;
        }, $x, $y));

        $magnitudeX = sqrt(array_sum(array_map(function ($n) {
            return $n * $n;
        }, $x)));

        $magnitudeY = sqrt(array_sum(array_map(function ($n) {
            return $n * $n;
        }, $y)));

        if ($magnitudeX === 0 || $magnitudeY === 0) {
            return 0;
        }

        return 1 - ($dotProduct / ($magnitudeX * $magnitudeY));
    }

    /**
     * Calculate Hamming distance between two arrays.
     * Count of positions where elements differ.
     *
     * @param array $x
     * @param array $y
     * 
     * @return int
     */
    public static function hamming(array $x, array $y): int
    {
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Arrays must have the same length for Hamming distance');
        }

        return array_sum(array_map(function ($n, $m) {
            return $n !== $m ? 1 : 0;
        }, $x, $y));
    }

    /**
     * Calculate the square distance between two points.
     * 
     * @param Point $p1
     * @param Point $p2
     * 
     * @return float
     */
    public static function squareDistance(Point $p1, Point $p2): float
    {
        $dx = $p2->x - $p1->x;
        $dy = $p2->y - $p1->y;
        return $dx * $dx + $dy * $dy;
    }

    /**
     * Calculate Jaccard distance between two sets.
     * Formula: $1 - \frac{|A \cap B|}{|A \cup B|}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float
     */
    public static function jaccard(array $x, array $y): float
    {
        $intersection = count(array_intersect($x, $y));
        $union = count(array_unique(array_merge($x, $y)));

        if ($union === 0) {
            return 0;
        }

        return 1 - ($intersection / $union);
    }

    /**
     * Calculate Haversine distance between two geographic points.
     * For latitude/longitude coordinates in radians.
     * Formula: $d = 2R \arcsin(\sqrt{\sin^2(\Delta\phi/2) + \cos\phi_1\cos\phi_2\sin^2(\Delta\lambda/2)})$
     *
     * @param array $coord1  [latitude, longitude] in radians
     * @param array $coord2  [latitude, longitude] in radians
     * @param int   $radius  Earth radius in kilometers (default: 6371)
     * 
     * @return float Distance in kilometers
     */
    public static function haversine(array $coord1, array $coord2, int $radius = 6371): float
    {
        $lat1 = $coord1[0];
        $lon1 = $coord1[1];
        $lat2 = $coord2[0];
        $lon2 = $coord2[1];

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = pow(sin($dlat / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($dlon / 2), 2);
        $c = 2 * asin(sqrt($a));

        return $radius * $c;
    }

    /**
     * Calculate Canberra distance between two points.
     * Formula: $\sum_{i=1}^{n}\frac{|x_i - y_i|}{|x_i| + |y_i|}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float
     */
    public static function canberra(array $x, array $y): float
    {
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Arrays must have the same length');
        }

        $distance = 0.0;
        for ($i = 0; $i < count($x); $i++) {
            $numerator = abs($x[$i] - $y[$i]);
            $denominator = abs($x[$i]) + abs($y[$i]);

            if ($denominator !== 0) {
                $distance += $numerator / $denominator;
            }
        }

        return $distance;
    }

    /**
     * Calculate Bray-Curtis distance between two vectors.
     * Formula: $\frac{\sum_i |x_i - y_i|}{\sum_i |x_i + y_i|}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float
     */
    public static function brayCurtis(array $x, array $y): float
    {
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Arrays must have the same length');
        }

        $numerator = array_sum(array_map(function ($n, $m) {
            return abs($n - $m);
        }, $x, $y));

        $denominator = array_sum(array_map(function ($n, $m) {
            return abs($n + $m);
        }, $x, $y));

        if ($denominator === 0) {
            return 0;
        }

        return $numerator / $denominator;
    }

    /**
     * Calculate Bhattacharyya distance between two probability distributions.
     * Formula: $-\ln\left(\sum_{i=1}^{n}\sqrt{p_i q_i}\right)$
     * Both arrays should represent probability distributions (sum to 1).
     *
     * @param array $p  First probability distribution
     * @param array $q  Second probability distribution
     * 
     * @return float
     */
    public static function bhattacharyya(array $p, array $q): float
    {
        if (count($p) !== count($q)) {
            throw new InvalidArgumentException('Distributions must have the same length');
        }

        $bc = array_sum(array_map(function ($pi, $qi) {
            return sqrt($pi * $qi);
        }, $p, $q));

        if ($bc <= 0) {
            return PHP_FLOAT_MAX;
        }

        return -log($bc);
    }

    /**
     * Calculate Pearson correlation distance between two vectors.
     * Formula: $1 - \text{corr}(x, y)$ where $\text{corr}(x, y) = \frac{\text{cov}(x,y)}{\sigma_x \sigma_y}$
     *
     * @param array $x
     * @param array $y
     * 
     * @return float
     */
    public static function pearson(array $x, array $y): float
    {
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Arrays must have the same length');
        }

        $n = count($x);
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $numerator = array_sum(array_map(function ($xi, $yi) use ($meanX, $meanY) {
            return ($xi - $meanX) * ($yi - $meanY);
        }, $x, $y));

        $varX = array_sum(array_map(function ($xi) use ($meanX) {
            return pow($xi - $meanX, 2);
        }, $x));

        $varY = array_sum(array_map(function ($yi) use ($meanY) {
            return pow($yi - $meanY, 2);
        }, $y));

        if ($varX === 0 || $varY === 0) {
            return 1;
        }

        $correlation = $numerator / sqrt($varX * $varY);

        return 1 - $correlation;
    }

    /**
     * Calculate Levenshtein distance between two strings.
     * Minimum edit distance (insertions, deletions, substitutions).
     *
     * @param string $s1
     * @param string $s2
     * 
     * @return int
     */
    public static function levenshtein(string $s1, string $s2): int
    {
        return levenshtein($s1, $s2);
    }

    /**
     * Calculate Squared Euclidean distance between two points.
     * Formula: $\sum_{i=1}^{n}(x_i - y_i)^2$
     * Faster than Euclidean for ranking purposes (avoids sqrt).
     *
     * @param array $x
     * @param array $y
     * 
     * @return float|int
     */
    public static function squaredEuclidean(array $x, array $y): float|int
    {
        return array_sum(array_map(function ($n, $m) {
            return pow($n - $m, 2);
        }, $x, $y));
    }

    /**
     * Calculate Mahalanobis distance between two points.
     * Accounts for correlation in the data.
     * Formula: $\sqrt{(x - y)^T S^{-1} (x - y)}$
     *
     * @param array $x
     * @param array $y
     * @param array $invCovarianceMatrix  Inverse covariance matrix
     * 
     * @return float
     */
    public static function mahalanobis(array $x, array $y, array $invCovarianceMatrix): float
    {
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Vectors must have the same length');
        }

        $diff = array_map(function ($xi, $yi) {
            return $xi - $yi;
        }, $x, $y);

        $temp = [];
        for ($i = 0; $i < count($diff); $i++) {
            $temp[$i] = 0;
            for ($j = 0; $j < count($diff); $j++) {
                $temp[$i] += $invCovarianceMatrix[$i][$j] * $diff[$j];
            }
        }

        $distance = 0;
        for ($i = 0; $i < count($diff); $i++) {
            $distance += $diff[$i] * $temp[$i];
        }

        return sqrt(max(0, $distance));
    }
}
