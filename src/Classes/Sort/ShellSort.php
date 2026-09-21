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

class ShellSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);
		$gap = intdiv($count, 2);

		while ($gap > 0) {
			for ($i = $gap; $i < $count; $i++) {
				$key = $array[$i];
				$j = $i;

				while ($j >= $gap && static::compare($array[$j - $gap], $key) > 0) {
					$array[$j] = $array[$j - $gap];
					$j -= $gap;
				}

				$array[$j] = $key;
			}

			$gap = intdiv($gap, 2);
		}

		return $array;
	}
}