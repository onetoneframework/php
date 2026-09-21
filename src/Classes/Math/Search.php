<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Math;

use SplPriorityQueue;
use SplQueue;

/**
 * Class Search
 *
 * A utility class for search algorithms like BFS and A*.
 */
class Search
{
    /**
     * Perform Breadth-First Search (BFS) on a graph.
     *
     * @param array $graph The graph represented as an adjacency list.
     * @param int $startNode The starting node for the BFS.
     * @return array An array of distances from the start node to each node in the graph.
     */
    public static function bfs(array $graph, int $startNode): array
    {
        $queue = new SplQueue();
        $distances = array_fill_keys(array_keys($graph), -1);

        $distances[$startNode] = 0;
        $queue->enqueue($startNode);

        while (!$queue->isEmpty()) {
            $node = $queue->dequeue();

            foreach ($graph[$node] as $neighbor) {
                if ($distances[$neighbor] === -1) {
                    $distances[$neighbor] = $distances[$node] + 1;
                    $queue->enqueue($neighbor);
                }
            }
        }

        return $distances;
    }

    /**
     * Perform A* Search on a graph.
     *
     * @param array $graph The graph represented as an adjacency list with edge costs.
     * @param array $coordinates The coordinates of each node for heuristic calculation.
     * @param int $startNode The starting node for the A* search.
     * @param int $goalNode The goal node for the A* search.
     * @return array|null An array representing the path from start to goal, or null if no path exists.
     */
    public static function aStar(array $graph, array $coordinates, int $startNode, int $goalNode): ?array
    {
        $openList = new SplPriorityQueue();
        $openList->setExtractFlags(SplPriorityQueue::EXTR_DATA);

        $cameFrom = []; 
        $gScore = array_fill_keys(array_keys($graph), INF);
        $gScore[$startNode] = 0;

        $heuristic = fn($a, $b) => abs($coordinates[$a][0] - $coordinates[$b][0]) + abs($coordinates[$a][1] - $coordinates[$b][1]);

        $fScore = $heuristic($startNode, $goalNode);
        $openList->insert($startNode, -$fScore);

        while (!$openList->isEmpty()) {
            $current = $openList->extract();

            if ($current === $goalNode) {
                $path = [$current];
                while (isset($cameFrom[$current])) {
                    $current = $cameFrom[$current];
                    array_unshift($path, $current);
                }
                return $path;
            }

            foreach ($graph[$current] as $neighbor => $cost) {
                $tentativeGScore = $gScore[$current] + $cost;
                if ($tentativeGScore < ($gScore[$neighbor] ?? INF)) {
                    $cameFrom[$neighbor] = $current;
                    $gScore[$neighbor] = $tentativeGScore;
                    $fScore = $tentativeGScore + $heuristic($neighbor, $goalNode);
                    $openList->insert($neighbor, -$fScore);
                }
            }
        }

        return null;
    }

}
