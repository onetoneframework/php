<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Sort;

/**
 * Abstract Sort
 *
 * Base class for sorting algorithms.
 */
abstract class AbstractSort
{
	/**
	 * Sorts an array using the implemented sorting algorithm.
	 *
	 * @param array $array The array to be sorted.
	 * @return array The sorted array.
	 */
	abstract public static function sort(array $array): array;

	/**
	 * Swaps two values in an array.
	 *
	 * @param mixed $a The first value.
	 * @param mixed $b The second value.
	 */
	protected static function swap(mixed &$a, mixed &$b): void
	{
		$tmp = $a;
		$a = $b;
		$b = $tmp;
	}

	/**
	 * Compares two values.
	 *
	 * @param mixed $a The first value.
	 * @param mixed $b The second value.
	 * @return int Returns -1 if $a is less than $b, 0 if they are equal, and 1 if $a is greater than $b.
	 */
	protected static function compare(mixed $a, mixed $b): int
	{
		return $a <=> $b;
	}
}
