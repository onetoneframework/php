<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Sort;

use function count;

/**
 * Bubble Sort
 *
 * Simple in-place sorting algorithm with O(n^2) time complexity.
 */
class BubbleSort extends AbstractSort
{
	/**
	 * Sort an array using the bubble sort algorithm.
	 *
	 * @param array $array Input array to sort.
	 * @return array Sorted array.
	 */
	public static function sort(array $array): array
	{
		$count = count($array);

		for ($i = 1; $i < $count; $i++) {
			$swapped = false;
			$boundary = $count - $i;

			for ($j = 0; $j < $boundary; $j++) {
				if (static::compare($array[$j], $array[$j + 1]) > 0) {
					static::swap($array[$j], $array[$j + 1]);
					$swapped = true;
				}
			}

			if (!$swapped) {
				break;
			}
		}

		return $array;
	}
}
