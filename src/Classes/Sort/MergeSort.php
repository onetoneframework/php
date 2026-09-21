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
use function array_slice;

class MergeSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);

		if ($count <= 1) {
			return $array;
		}

		$mid = intdiv($count, 2);
		$left = static::sort(array_slice($array, 0, $mid));
		$right = static::sort(array_slice($array, $mid));

		return static::merge($left, $right);
	}

	private static function merge(array $left, array $right): array
	{
		$result = [];
		$i = 0;
		$j = 0;
		$leftCount = count($left);
		$rightCount = count($right);

		while ($i < $leftCount && $j < $rightCount) {
			if (static::compare($left[$i], $right[$j]) <= 0) {
				$result[] = $left[$i++];
			} else {
				$result[] = $right[$j++];
			}
		}

		while ($i < $leftCount) {
			$result[] = $left[$i++];
		}

		while ($j < $rightCount) {
			$result[] = $right[$j++];
		}

		return $result;
	}
}