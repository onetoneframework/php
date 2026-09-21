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

class QuickSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		static::quickSort($array, 0, count($array) - 1);

		return $array;
	}

	private static function quickSort(array &$array, int $low, int $high): void
	{
		if ($low >= $high) {
			return;
		}

		$pivotIndex = static::partition($array, $low, $high);
		static::quickSort($array, $low, $pivotIndex - 1);
		static::quickSort($array, $pivotIndex + 1, $high);
	}

	private static function partition(array &$array, int $low, int $high): int
	{
		$mid = $low + intdiv($high - $low, 2);
		static::medianOfThree($array, $low, $mid, $high);

		$pivot = $array[$high];
		$i = $low - 1;

		for ($j = $low; $j < $high; $j++) {
			if (static::compare($array[$j], $pivot) <= 0) {
				$i++;
				static::swap($array[$i], $array[$j]);
			}
		}

		static::swap($array[$i + 1], $array[$high]);

		return $i + 1;
	}

	private static function medianOfThree(array &$array, int $a, int $b, int $c): void
	{
		if (static::compare($array[$a], $array[$b]) > 0) {
			static::swap($array[$a], $array[$b]);
		}
		if (static::compare($array[$a], $array[$c]) > 0) {
			static::swap($array[$a], $array[$c]);
		}
		if (static::compare($array[$b], $array[$c]) > 0) {
			static::swap($array[$b], $array[$c]);
		}

		static::swap($array[$b], $array[$c]);
	}
}