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

class RangeSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);

		for ($i = 0; $i < $count - 1; $i++) {
			$minIndex = $i;

			for ($j = $i + 1; $j < $count; $j++) {
				if (static::compare($array[$j], $array[$minIndex]) < 0) {
					$minIndex = $j;
				}
			}

			if ($minIndex !== $i) {
				static::swap($array[$i], $array[$minIndex]);
			}
		}

		return $array;
	}
}