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

class HeapSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);

		for ($i = intdiv($count, 2) - 1; $i >= 0; $i--) {
			static::heapify($array, $count, $i);
		}

		for ($i = $count - 1; $i > 0; $i--) {
			static::swap($array[0], $array[$i]);
			static::heapify($array, $i, 0);
		}

		return $array;
	}

	private static function heapify(array &$array, int $size, int $root): void
	{
		$largest = $root;
		$left = 2 * $root + 1;
		$right = 2 * $root + 2;

		if ($left < $size && static::compare($array[$left], $array[$largest]) > 0) {
			$largest = $left;
		}

		if ($right < $size && static::compare($array[$right], $array[$largest]) > 0) {
			$largest = $right;
		}

		if ($largest !== $root) {
			static::swap($array[$root], $array[$largest]);
			static::heapify($array, $size, $largest);
		}
	}
}