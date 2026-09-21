<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use ArrayAccess;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use Traversable;
use function array_key_exists;
use function is_array;

/**
 * Class ArrayObject
 * 
 * @package Clover\Classes
 */
class ArrayObject extends BaseClass
{
	/**
	 * Return all the keys or a subset of the keys of an array
	 * 
	 * @param array $array
	 * @param mixed $filter_value
	 * @param bool $strict
	 * 
	 * @return array
	 */
	public static function getKeys(array $array, mixed $filter_value, bool $strict = false): array
	{
		return array_keys($array, $filter_value, $strict);
	}

	/**
	 * Determine whether the value can be accessed like an array.
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public static function isAccessible(mixed $value): mixed
	{
		return is_array($value) || $value instanceof ArrayAccess;
	}

	/**
	 * Searches the array for a given value and returns the first corresponding key if successful
	 * 
	 * @param string $needle
	 * @param array $haystack
	 * @param bool $strict
	 * 
	 * @return bool|int|string
	 */
	public static function getIndexByValue(string $needle, array $haystack, bool $strict = false): mixed
	{
		foreach ($haystack as $key => $value) {
			if ($strict) {
				if ((string) $value === $needle) {
					return $key;
				}
			} else {
				if ((string) $value == $needle) {
					return $key;
				}
			}
		}

		return false;
	}

	/**
	 * Get the last key of the given array without affecting the internal array pointer
	 * 
	 * @param array $array
	 * 
	 * @return int|string|null
	 */
	public static function getLastKey(array $array): int|string|null
	{
		return array_key_last($array);
	}

	/**
	 * Get the first key of the given array without affecting the internal array pointer.
	 * 
	 * @param array $array
	 * 
	 * @return int|string|null
	 */
	public static function getFirstKey(array $array): mixed
	{
		return array_key_first($array);
	}

	/**
	 * Return all the values of an array
	 * 
	 * @param array $array
	 * 
	 * @return array
	 */
	public static function getAllValues(array $array): array
	{
		return array_values($array);
	}

	/**
	 * Shuffle an array
	 * 
	 * @param array $array
	 * 
	 * @return bool
	 */
	public static function shuffle(array $array): bool
	{
		return shuffle($array);
	}

	/**
	 * Sort an array using a case insensitive "natural order" algorithm
	 * 
	 * @param array $array
	 * 
	 * @return bool
	 */
	public static function sortByCaseInsensitiveNaturalOrderAlgorithm(array $array): bool
	{
		return natcasesort($array);
	}

	/**
	 * Sort an array using a "natural order" algorithm
	 * 
	 * @param array $array
	 * 
	 * @return bool
	 */
	public static function sortByNaturalOrderAlgorithm(array $array): bool
	{
		return natsort($array);
	}

	/**
	 * Sort an array by key in descending order
	 * 
	 * @param array $array
	 * 
	 * @return bool
	 */
	public static function sortByKeyInDescendingOrder(array $array): bool
	{
		return krsort($array);
	}

	/**
	 * Sort an array by key in ascending order
	 * 
	 * @param array $array
	 * 
	 * @return bool
	 */
	public static function sortByKeyInAscendingOrder(array $array): bool
	{
		return ksort($array);
	}

	/**
	 * Set a value in a multi-dimensional array using a deep copy
	 * 
	 * @param array $original
	 * @param array $array
	 * @param mixed $value
	 * 
	 * @return void
	 */
	public static function setDeepCopy(array &$original, array $array, mixed $value): void
	{
		$current = &$original;
		foreach ($array as $key) {
			$current = &$current[$key];
		}

		$current = $value;
	}

	/**
	 * Fetch a key from an array
	 * 
	 * @param array|object $array
	 * 
	 * @return int|string|null
	 */
	public static function fetchKey(array|object $array): int|string|null
	{
		return key($array);
	}

	/**
	 * Checks if the given value is an array
	 * 
	 * @param mixed $value
	 * 
	 * @return bool
	 */
	public static function isArray(mixed $value): bool
	{
		return is_array($value);
	}

	/**
	 * Checks if the given key or index exists in the array
	 * 
	 * @param array $array
	 * @param string|int|float|bool|resource|null $key
	 * 
	 * @return bool
	 */
	public static function isKeyExists(array $array, mixed $key): bool
	{
		return array_key_exists($key, $array);
	}

	/**
	 * Searches the array for a given value and returns the first corresponding key if successful
	 * 
	 * @param array $array
	 * 
	 * @return bool|int|string
	 */
	public static function getKeyByValue(array $array, string $key): bool|int|string
	{
		return array_search($key, $array);
	}

	/**
	 * Checks if the given array is traversable
	 * 
	 * @param mixed $array
	 * 
	 * @return bool
	 */
	public static function isTraversable(mixed $array): bool
	{
		if ($array instanceof Traversable) {
			return true;
		}

		return false;
	}

	/**
	 * Get the depth of an array
	 * 
	 * @param array $array
	 * 
	 * @return int
	 */
	public static function getDepth(array $array): int
	{
		$depth = 0;
		$arrayReclusive = new RecursiveArrayIterator($array);
		$iteratorReclusive = new RecursiveIteratorIterator($arrayReclusive);

		/** @var RecursiveIteratorIterator $iterator */
		foreach ($iteratorReclusive as $iterator) {
			$currentDepth = $iterator->getDepth();

			$depth = $currentDepth > $depth ? $currentDepth : $depth;
		}

		return $depth;
	}
}
