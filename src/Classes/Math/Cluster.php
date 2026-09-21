<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use function array_slice;

/**
 * Class Cluster
 *
 * A utility class for clustering algorithms.
 */
class Cluster
{
    /**
     * Perform k-means clustering on a set of data points.
     *
     * @param array $dataPoints An array of data points, where each data point is an array of features.
     * @param int $numClusters The number of clusters to form.
     * @param int $maxIterations The maximum number of iterations to perform (default is 20).
     * @return array An array of cluster assignments for each data point.
     */
    public static function kmeans(array $dataPoints, int $numClusters, int $maxIterations = 20): array
    {
        if (empty($dataPoints)) {
            return [];
        }

        $centroids = array_slice($dataPoints, 0, $numClusters);
        $assignments = [];

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $assignmentsChanged = false;

            foreach ($dataPoints as $pointIndex => $point) {
                $minDist = PHP_FLOAT_MAX;
                $bestCluster = -1;

                foreach ($centroids as $clusterIndex => $centroid) {
                    $dist = array_sum(array_map(fn($a, $b) => ($a - $b) ** 2, $point, $centroid));

                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $bestCluster = $clusterIndex;
                    }
                }

                if (!isset($assignments[$pointIndex]) || $assignments[$pointIndex] !== $bestCluster) {
                    $assignments[$pointIndex] = $bestCluster;
                    $assignmentsChanged = true;
                }
            }

            $newCentroids = array_fill(0, $numClusters, array_fill(0, count($dataPoints[0]), 0.0));
            $counts = array_fill(0, $numClusters, 0);

            foreach ($dataPoints as $pointIndex => $point) {
                $clusterId = $assignments[$pointIndex];
                foreach ($point as $featureIndex => $value) {
                    $newCentroids[$clusterId][$featureIndex] += $value;
                }
                $counts[$clusterId]++;
            }

            foreach ($newCentroids as $clusterId => $sumVector) {
                if ($counts[$clusterId] > 0) {
                    $centroids[$clusterId] = array_map(fn($sum) => $sum / $counts[$clusterId], $sumVector);
                }
            }

            if (!$assignmentsChanged) {
                break;
            }
        }

        return $assignments;
    }

}
