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

class RadixSort extends AbstractSort
{
	public static function sort(array $array): array
	{
		$count = count($array);

		if ($count <= 1) {
			return $array;
		}

		$negatives = [];
		$positives = [];

		foreach ($array as $value) {
			if ($value < 0) {
				$negatives[] = -$value;
			} else {
				$positives[] = $value;
			}
		}

		$positives = static::radixSortUnsigned($positives);

		if (!empty($negatives)) {
			$negatives = static::radixSortUnsigned($negatives);
			$negatives = array_reverse($negatives);
			array_walk($negatives, function (&$v) {
				$v = -$v;
			});

			return array_merge($negatives, $positives);
		}

		return $positives;
	}

	private static function radixSortUnsigned(array $array): array
	{
		$count = count($array);

		if ($count <= 1) {
			return $array;
		}

		$max = max($array);

		for ($exp = 1; $max / $exp >= 1; $exp *= 10) {
			$array = static::countingSortByDigit($array, $count, $exp);
		}

		return $array;
	}

	private static function countingSortByDigit(array $array, int $count, int $exp): array
	{
		$output = array_fill(0, $count, 0);
		$buckets = array_fill(0, 10, 0);

		for ($i = 0; $i < $count; $i++) {
			$digit = intdiv($array[$i], $exp) % 10;
			$buckets[$digit]++;
		}

		for ($i = 1; $i < 10; $i++) {
			$buckets[$i] += $buckets[$i - 1];
		}

		for ($i = $count - 1; $i >= 0; $i--) {
			$digit = intdiv($array[$i], $exp) % 10;
			$output[--$buckets[$digit]] = $array[$i];
		}

		return $output;
	}
}