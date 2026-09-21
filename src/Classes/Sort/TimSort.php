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

class TimSort extends AbstractSort
{
	private const MIN_RUN = 32;

	public static function sort(array $array): array
	{
		$count = count($array);

		if ($count <= 1) {
			return $array;
		}

		$minRun = static::computeMinRun($count);

		for ($start = 0; $start < $count; $start += $minRun) {
			$end = min($start + $minRun - 1, $count - 1);
			static::insertionSort($array, $start, $end);
		}

		for ($size = $minRun; $size < $count; $size *= 2) {
			for ($left = 0; $left < $count; $left += $size * 2) {
				$mid = min($left + $size - 1, $count - 1);
				$right = min($left + $size * 2 - 1, $count - 1);

				if ($mid < $right) {
					static::merge($array, $left, $mid, $right);
				}
			}
		}

		return $array;
	}

	private static function computeMinRun(int $n): int
	{
		$remainder = 0;

		while ($n >= self::MIN_RUN) {
			$remainder |= ($n & 1);
			$n >>= 1;
		}

		return $n + $remainder;
	}

	private static function insertionSort(array &$array, int $left, int $right): void
	{
		for ($i = $left + 1; $i <= $right; $i++) {
			$key = $array[$i];
			$j = $i - 1;

			while ($j >= $left && static::compare($array[$j], $key) > 0) {
				$array[$j + 1] = $array[$j];
				$j--;
			}

			$array[$j + 1] = $key;
		}
	}

	private static function merge(array &$array, int $left, int $mid, int $right): void
	{
		$leftPart = array_slice($array, $left, $mid - $left + 1);
		$rightPart = array_slice($array, $mid + 1, $right - $mid);

		$i = 0;
		$j = 0;
		$k = $left;
		$leftLen = count($leftPart);
		$rightLen = count($rightPart);

		while ($i < $leftLen && $j < $rightLen) {
			if (static::compare($leftPart[$i], $rightPart[$j]) <= 0) {
				$array[$k++] = $leftPart[$i++];
			} else {
				$array[$k++] = $rightPart[$j++];
			}
		}

		while ($i < $leftLen) {
			$array[$k++] = $leftPart[$i++];
		}

		while ($j < $rightLen) {
			$array[$k++] = $rightPart[$j++];
		}
	}
}