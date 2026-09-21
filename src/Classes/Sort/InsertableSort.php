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

class InsertableSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);

		for ($i = 1; $i < $count; $i++) {
			$key = $array[$i];
			$j = $i - 1;

			while ($j >= 0 && static::compare($array[$j], $key) > 0) {
				$array[$j + 1] = $array[$j];
				$j--;
			}

			$array[$j + 1] = $key;
		}

		return $array;
	}
}