<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use AllowDynamicProperties;
use ArrayAccess;
use ArrayIterator;
use Clover\Classes\ArraySummarizer;
use Clover\Classes\Data\BaseObject;
use Countable;
use Exception;
use Iterator;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use ReturnTypeWillChange;
use stdClass;
use Traversable;
use function array_key_exists;
use function array_slice;
use function count;
use function getType;
use function in_array;
use function is_array;
use function is_object;
use function is_string;

/**
 * Array Object
 */
#[AllowDynamicProperties]
class ArrayObject extends BaseObject implements ArrayAccess, Iterator, Countable
{
    /**
     * Raw data
     * 
     * @var array
     */
    protected $rawData;

    /**
     * Constructor
     * 
     * @param array|object $data
     * 
     * @throws Exception
     */
    public function __construct(object|array $data = [])
    {
        $type = getType($data);
        if ($type === 'object') {
            $data = (array) $data;
        }

        if ($type !== 'array') {
            throw new Exception('Data arguments must be type of array');
        }

        $this->rawData = $data;
    }

    /**
     * Get raw data
     * 
     * @return array|object
     */
    public function toPHPObject(): array|object
    {
        return $this->rawData;
    }

    /**
     * Remove item by key
     * 
     * @param string|int $key
     * 
     * @return void
     */
    public function remove(string|int $key): void
    {
        if (!isset($this->rawData[$key])) {
            throw new Exception('Array key is not contains in this object');
        }

        unset($this->rawData[$key]);
    }

    /**
     * Fill array from iterator
     * 
     * @param Traversable $iterator
     * @param bool $preserve_keys = true
     * 
     * @return ArrayObject
     */
    public function fromIterator(Traversable $iterator, bool $preserve_keys): static
    {
        $this->rawData = iterator_to_array($iterator, $preserve_keys);

        return $this;
    }

    /**
     * Filter array values that start with the given needle
     * 
     * @param string $needle
     * 
     * @return ArrayObject
     */
    public function endsWith(string $needle): ArrayObject
    {
        $data = &$this->rawData;
        $data = array_filter($data, function ($value) use ($needle): bool {
            return str_ends_with($value, $needle);
        });

        return new self($data);
    }

    /**
     * Check if array values start with given string
     * 
     * @param string $needle
     * 
     * @return ArrayObject
     */
    public function startsWith(string $needle): ArrayObject
    {
        $data = &$this->rawData;
        $data = array_filter($data, function ($value) use ($needle): bool {
            return str_starts_with($value, $needle);
        });

        return new self($data);
    }

    /**
     * Extract a slice of the array from the right
     * 
     * @param int $start
     * 
     * @return ArrayObject
     */
    public function sliceRight(int $start = 0): ArrayObject
    {
        return $this->slice($start, null);
    }

    /**
     * Extract a slice of the array from the left
     * 
     * @param int $size
     * 
     * @return ArrayObject
     */
    public function sliceLeft(int $size = 1): ArrayObject
    {
        return $this->slice(0, $size);
    }

    /**
     * Extract a slice of the array
     * 
     * @param int $start
     * @param int|null $end
     * 
     * @return ArrayObject
     */
    public function slice(int $start = 0, int|null $end = 1): ArrayObject
    {
        $data = $this->rawData;
        $data = array_slice($data, $start, $end);

        return new self($data);
    }

    /**
     * Replace all occurrences of the search string with the replacement string
     * 
     * @param string|array $search
     * @param string|array $replace
     * 
     * @return ArrayObject
     */
    public function replace(string|array $search, string|array $replace): ArrayObject
    {
        $data = $this->rawData;
        array_walk($data, function (&$value) use ($search, $replace): void {
            $value = str_replace($search, $replace, $value);
        });

        return new self($data);
    }

    /**
     * Extract keys from array recursively
     * 
     * @param mixed $data
     * 
     * @return array|stdClass|null
     */
    private function extractKeys(mixed $data): array|stdClass|null
    {
        if (is_array($data)) {
            $isAssoc = array_keys($data) !== range(0, count($data) - 1);

            if ($isAssoc) {
                $hasNested = false;
                foreach ($data as $v) {
                    if (is_array($v) || is_object($v)) {
                        $hasNested = true;
                        break;
                    }
                }
                if ($hasNested) {
                    $result = [];
                    foreach ($data as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            $result[$key] = $this->extractKeys($value);
                        } else {
                            $result[$key] = $key;
                        }
                    }

                    return $result;
                } else {
                    return array_keys($data);
                }
            } else {
                $result = [];
                foreach ($data as $item) {
                    $result[] = $this->extractKeys($item);
                }

                return $result;
            }
        } elseif (is_object($data)) {
            $result = new stdClass();
            $hasNested = false;
            foreach ($data as $v) {
                if (is_array($v) || is_object($v)) {
                    $hasNested = true;
                    break;
                }
            }

            if ($hasNested) {
                foreach ($data as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $result->$key = $this->extractKeys($value);
                    } else {
                        $result->$key = $key;
                    }
                }
                return $result;
            } else {
                $temp = new stdClass();
                foreach ($data as $key => $value) {
                    $temp->$key = $key;
                }
                return $temp;
            }
        } else {
            return null;
        }
    }

    /**
     * Extract keys from array
     * 
     * @return ArrayObject
     */
    public function getChainKeys(): ArrayObject
    {
        $arr = $this->cloneRawData();
        $key = $this->extractKeys($arr);

        return new self($key);
    }

    /**
     * Fill array with numbers within a specified range
     * 
     * @param int $start
     * @param int $end
     * 
     * @return ArrayObject
     */
    public function fillRange(int $start, int $end): static
    {
        $numbers = new ArrayObject(range($start, $end));

        $this->mergeUnique($numbers);

        return $this;
    }

    /**
     * Fill array with prime numbers within a specified range
     * 
     * @param int $start
     * @param int $end
     * 
     * @return ArrayObject
     */
    public function fillPrimes(int $start, int $end): ArrayObject
    {
        $primes = [];

        for ($i = $start; $i <= $end; $i++) {
            if ($i == 2) {
                $primes[] = 2;
                continue;
            }

            $isPrime = true;
            $sqrt = sqrt($i);
            for ($j = 2; $j <= $sqrt; $j++) {
                if ($i % $j == 0) {
                    $isPrime = false;
                    break;
                }
            }

            if ($isPrime) {
                $primes[] = $i;
            }
        }

        return new self($primes);
    }

    /**
     * Set item by offset
     * 
     * @param mixed $offset
     * @param mixed $value
     * 
     * @return void
     */
    #[ReturnTypeWillChange]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset instanceof StringObject) {
            $offset = $offset->__toString();
        }

        if ($offset === null) {
            $this->rawData[] = $value;
        } else {
            $this->rawData[$offset] = $value;
        }
    }

    /**
     * Return the current element
     * 
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function current(): mixed
    {
        return current($this->rawData);
    }

    /**
     * Count elements of an object
     * 
     * @return int
     */
    public function count(): int
    {
        return count($this->rawData);
    }

    /**
     * Check if current position is valid
     * 
     * @return bool
     */
    public function valid(): bool
    {
        return key($this->rawData) !== null;
    }

    /**
     * Move forward to next element
     * 
     * @return void
     */
    #[ReturnTypeWillChange]
    public function next(): void
    {
        next($this->rawData);
    }

    /**
     * Rewind the Iterator to the first element
     * 
     * @return void
     */
    public function rewind(): void
    {
        reset($this->rawData);
    }

    /**
     * Get current key
     * 
     * @return mixed
     */
    public function key(): mixed
    {
        return key($this->rawData);
    }

    /**
     * Unset item by offset
     * 
     * @param mixed $offset
     * 
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->rawData[$offset]);
    }

    /**
     * Get item by offset
     * 
     * @param mixed $offset
     * 
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function offsetGet(mixed $offset): mixed
    {
        if ($offset instanceof StringObject) {
            $offset = $offset->__toString();
        }

        $value = $this->rawData[$offset] ?? null;

        if (is_array($value)) {
            return new self($value);
        }

        if (is_string($value)) {
            return new StringObject($value);
        }

        return $value;
    }

    /**
     * Check if offset exists in array
     * 
     * @param mixed $offset
     * 
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->rawData[$offset]);
    }

    /**
     * Add item with key to array
     * 
     * @param string|int $key
     * @param mixed $item
     * 
     * @return void
     */
    public function addWithKey(string|int $key, mixed $item): void
    {
        if ($key instanceof StringObject) {
            $key = $key->__toString();
        }

        $this->rawData[$key] = $item;
    }

    /**
     * Get item by index
     * 
     * @param int $index
     * 
     * @return mixed
     */
    public function getByIndex(int $index): mixed
    {
        if (isset($this->rawData[$index])) {
            return $this->rawData[$index];
        }

        $keys = array_keys($this->rawData);
        return $this->rawData[$keys[$index]];
    }

    /**
     * Get summary of array
     * 
     * @return StringObject
     */
    public function summary(): StringObject
    {
        $arraySummarizer = new ArraySummarizer();
        $summaryData = $arraySummarizer->summarizeArray(array_unique($this->cloneRawData()));

        return new StringObject($summaryData);
    }

    /**
     * Add item to array
     * 
     * @param mixed $item
     * 
     * @return ArrayObject
     */
    public function add(mixed $item): static
    {
        $this->rawData[] = $item;

        return $this;
    }

    /**
     * Merge two arrays
     * 
     * @param ArrayObject $array
     * 
     * @return ArrayObject
     */
    public function merge(ArrayObject $array): self
    {
        $data = array_merge($this->cloneRawData(), $array->getRawData());

        return new self($data);
    }

    /**
     * Merge two arrays and removes duplicate values
     * 
     * @param ArrayObject $array
     * 
     * @return ArrayObject
     */
    public function mergeUnique(ArrayObject $array): self
    {
        $data = array_unique($this->merge($array)->getRawData());

        return new self($data);
    }

    /**
     * Merge two arrays recursively
     * 
     * @param array $array
     * 
     * @return ArrayObject
     */
    public function mergeRecursive(array $array): self
    {
        if (!function_exists('array_merge_recursive')) {
            throw new Exception('array_merge_recursive function is not exists');
        }

        $data = array_merge_recursive($this->cloneRawData(), $array);

        return new self($data);
    }

    /**
     * Merge two arrays, preserving values from the first array when keys conflict
     * 
     * @param array $array
     * 
     * @return ArrayObject
     */
    public function mergeNoClobber(array $array): self
    {
        if (!function_exists('array_merge_noclobber')) {
            throw new Exception('array_merge_noclobber function is not exists');
        }

        $data = array_merge_noclobber($this->cloneRawData(), $array);

        return new self($data);
    }

    /**
     * Merge two arrays, clobbering values from the first array with values from the second array when keys conflict
     * 
     * @param array $array
     * 
     * @return ArrayObject
     */
    public function mergeClobber(array $array): self
    {
        if (!function_exists('array_merge_clobber')) {
            throw new Exception('array_merge_clobber function is not exists');
        }

        $data = array_merge_clobber($this->cloneRawData(), $array);

        return new self($data);
    }

    /**
     * Iteratively reduce the array to a single value using a callback function
     * 
     * @param callable $callback
     * @param mixed $initial
     * 
     * @return ArrayObject
     */
    public function reduce(callable $callback, mixed $initial = null): self
    {
        $data = array_reduce($this->cloneRawData(), $callback, $initial);

        return new self($data);
    }

    /**
     * Iterates over each value in the array passing them to the callback function
     * 
     * @param callable|null $callback
     * 
     * @return ArrayObject
     */
    public function filter(callable|null $callback): self
    {
        $data = array_filter($this->cloneRawData(), $callback);

        return new self($data);
    }

    /**
     * Applies the callback to the elements of the given arrays
     * 
     * @param callable|null $callback
     * 
     * @return ArrayObject
     */
    public function map($callback): self
    {
        $data = array_map($callback, $this->cloneRawData());

        return new self($data);
    }

    /**
     * Convert array to string
     * 
     * @return StringObject
     */
    public function toString(): StringObject
    {
        return new StringObject(implode($this->rawData));
    }

    /**
     * Check if array is contains string or others
     * 
     * @param mixed $needle
     * 
     * @return bool
     */
    public function isContains(mixed $needle): bool
    {
        return in_array($needle, $this->getRawData());
    }

    /**
     * Reverse pop n elements off the end of array
     * 
     * @return ArrayObject
     */
    public function reversePop($n = 1): ArrayObject
    {
        $data = $this->cloneRawData();
        $data = array_splice($data, 0, -$n);

        return new self($data);
    }

    /**
     * Pop the element off the end of array
     * 
     * @return ArrayObject
     */
    public function pop(): ArrayObject
    {
        $data = $this->cloneRawData();
        $data = array_pop($data);

        return new self($data);
    }

    /**
     * Check if array contain key
     * 
     * @param string|int $key
     * 
     * @return bool
     */
    public function isContainKey($key): bool
    {
        return array_key_exists($key, $this->rawData);
    }

    /**
     * Get first value of array
     * 
     * @return bool|ArrayObject|StringObject
     */
    public function first(): ArrayObject|bool|StringObject
    {
        return $this->get(0);
    }

    /**
     * Get last value of array
     * 
     * @return bool|ArrayObject|StringObject
     */
    public function last(): ArrayObject|bool|StringObject
    {
        return $this->get($this->getLastKey());
    }

    /**
     * Get array object value by key
     * 
     * @param mixed $key
     * @return ArrayObject
     */
    public function getArray($key)
    {
        return $this->getByType($key, ArrayObject::class);
    }

    /**
     * Get string object value by key
     * 
     * @param mixed $key
     * @return StringObject
     */
    public function getString($key)
    {
        return $this->getByType($key, StringObject::class);
    }

    /**
     * Get value by key and type
     * 
     * @param bool|float|int|resource|string|null $key
     * 
     * @return bool|ArrayObject|StringObject
     */
    public function getByType($key, string $type)
    {
        $object = $this->get($key);

        return $object instanceof $type ? $object : null;
    }

    /**
     * Get value by key
     * 
     * @param bool|float|int|resource|string|null $key
     * 
     * @return bool|ArrayObject|StringObject|string|int|float|null
     */
    public function get($key): bool|ArrayObject|StringObject|string|int|float|null
    {
        if ($key instanceof StringObject) {
            $key = $key->__toString();
        }

        $data = ($this->rawData instanceof stdClass) ? $this->rawData->$key : $this->rawData[$key];

        if (is_string($data)) {
            return new StringObject($data);
        }

        if (is_numeric($data) || is_bool($data)) {
            return $data;
        }

        if ($data instanceof ArrayObject) {
            return $data;
        }

        if ($data == null) {
            return null;
        }

        return new self($data);
    }

    /**
     * Return all the keys or a subset of the keys of an array
     * 
     * @return ArrayObject
     */
    public function getKeys(): self
    {
        $clone = array_keys($this->rawData);

        return new self($clone);
    }

    /**
     * Return all the values of an array
     * 
     * @return ArrayObject
     */
    public function getValues(): self
    {
        $this->rawData = array_values($this->rawData);

        return $this;
    }

    /**
     * Shuffle an array
     * 
     * @return ArrayObject
     */
    public function shuffle(): self
    {
        $result = $this->cloneRawData();

        shuffle($result);

        return new self($result);
    }

    /**
     * Sort an array using a case insensitive "natural order" algorithm
     * 
     * @return ArrayObject
     */
    public function sortByCaseInsensitiveNaturalOrderAlgorithm(): self
    {
        $result = $this->cloneRawData();
        natcasesort($result);

        return new self($result);
    }

    /**
     * Computes the intersection of arrays with additional index check
     * 
     * @return ArrayObject
     */
    public function computeIntersectionWithIndex(?array $array = []): self
    {
        $result = $this->cloneRawData();
        $result = array_intersect_assoc($result, $array);

        return new self($result);
    }

    /**
     * Computes the intersection of arrays
     * 
     * @return ArrayObject
     */
    public function computeIntersection(?array $array = []): self
    {
        $result = $this->cloneRawData();
        $result = array_intersect($result, $array);

        return new self($result);
    }

    /**
     * Sort an array using a "natural order" algorithm
     * 
     * @return ArrayObject
     */
    public function sortByNaturalOrderAlgorithm(): self
    {
        $result = $this->cloneRawData();
        natsort($result);

        return new self($result);
    }

    /**
     * Sort an array by key in descending order
     * 
     * @return ArrayObject
     */
    public function sortByKeyInReverseOrder(): self
    {
        $result = $this->cloneRawData();
        krsort($result);

        return new self($result);
    }

    /**
     * Reverse an array
     * 
     * @return ArrayObject
     */
    public function reverse(): ArrayObject
    {
        $result = $this->cloneRawData();
        $result = array_reverse($result);

        return new self($result);
    }

    /**
     * Sort an array by key
     * 
     * @return ArrayObject
     */
    public function sortByKey(): self
    {
        $result = $this->cloneRawData();
        ksort($result);

        return new self($result);
    }

    /**
     * Get the last key of the given array without affecting the internal array pointer
     * 
     * @return int|string|null
     */
    public function getLastKey(): int|string|null
    {
        return array_key_last($this->rawData);
    }

    /**
     * Get the first key of the given array without affecting the internal array pointer.
     * 
     * @return int|string|null
     */
    public function getFirstKey(): int|string|null
    {
        return array_key_first($this->rawData);
    }

    /**
     * Fetch a key from an array
     * 
     * @return int|string|null
     */
    public function fetchKey(): int|string|null
    {
        return key($this->rawData);
    }

    /**
     * Get the maximum depth of array
     * 
     * @return int
     */
    public function getMaxDepth(): int
    {
        $currentDepth = 0;
        $depth = 0;
        $arrayReclusive = new RecursiveArrayIterator($this->getRawData());
        $iteratorReclusive = new RecursiveIteratorIterator($arrayReclusive);

        /** @var RecursiveArrayIterator $iterator */
        foreach ($iteratorReclusive as $iterator) {
            $currentDepth = $iteratorReclusive->getDepth();
            $depth = $currentDepth > $depth ? $currentDepth : $depth;
        }

        return $depth;
    }

    /**
     * Check that array is traversable
     *
     * @return bool
     */
    public function isTraversable(): bool
    {
        return $this->rawData instanceof Traversable;
    }

    /**
     * Check that array is countable
     *
     * @return bool
     */
    public function isCountable(): bool
    {
        return is_countable($this->getRawData());
    }

    /**
     * Checks if the given key or index exists in the array
     *
     * @param bool|float|int|resource|string|null $key
     *
     * @return bool
     */
    public function isKeyExists(mixed $key): bool
    {
        return array_key_exists($key, $this->rawData);
    }

    /**
     * Searches the array for a given value and returns the first corresponding key after a given index if successful
     *
     * @param mixed $needle
     * @param int $startIndex 
     * 
     * @return bool|int|string
     */
    public function getKeyByValueAfter(mixed $needle, int $startIndex = 0): bool|int|string
    {
        return array_search($needle, array_slice($this->cloneRawData(), $startIndex, null, true));
    }

    /**
     * Searches the array for a given value and returns the first corresponding key if successful
     *
     * @param string $key
     * 
     * @return bool|int|string
     */
    public function getKeyByValue(string $key): bool|int|string
    {
        return array_search($key, $this->rawData);
    }

    /**
     * Convert array to string
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->isCountable() && $this->count() > 0) {
            return implode(" ", $this->rawData);
        }

        return (string) $this->rawData;
    }

    /**
     * Computes the difference of arrays
     *
     * @param ArrayObject $array
     *
     * @return ArrayObject
     */
    public function computesDifference(ArrayObject $array): self
    {
        $cloneOriginal = &$this;
        $cloneTarget = &$array;

        $result = array_diff($cloneOriginal->rawData, $cloneTarget->rawData);

        return new self($result);
    }

    /**
     * Filters duplicate values from two arrays
     *
     * @param array $arrayB
     *
     * @return ArrayObject
     */
    public function filterDuplicates(array $arrayB): ArrayObject
    {
        $arrayA = self::getRawData();

        $allValuesA = [];
        $allValuesB = [];

        array_walk_recursive($arrayA, function ($value) use (&$allValuesA) {
            $allValuesA[] = $value;
        });

        array_walk_recursive($arrayB, function ($value) use (&$allValuesB) {
            $allValuesB[] = $value;
        });

        $duplicates = array_intersect($allValuesA, $allValuesB);

        $removeFromArray = function ($array, $duplicates) use (&$removeFromArray) {
            $result = [];

            foreach ($array as $key => $value) {
                if (is_array($value)) {
                    $subResult = $removeFromArray($value, $duplicates);

                    if (!empty($subResult)) {
                        $result[$key] = $subResult;
                    }
                } else {
                    if (!in_array($value, $duplicates, true)) {
                        $result[$key] = $value;
                    }
                }
            }

            return $result;
        };

        $resultA = $removeFromArray($arrayA, $duplicates);
        $resultB = $removeFromArray($arrayB, $duplicates);

        $mergeArrays = function ($array1, $array2) use (&$mergeArrays) {
            $result = $array1;

            foreach ($array2 as $key => $value) {
                if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = $mergeArrays($result[$key], $value);
                } elseif (!isset($result[$key])) {
                    $result[$key] = $value;
                }
            }

            return $result;
        };

        return new self($mergeArrays($resultA, $resultB));
    }

    /**
     * Computes the unique values of two arrays
     *
     * @param ArrayObject $array
     *
     * @return ArrayObject
     */
    public function computesUnique(ArrayObject $array): ArrayObject
    {
        $cloneOriginal = &$this;
        $cloneTarget = &$array;

        $uniqueA = array_diff($cloneOriginal->rawData, $cloneTarget->rawData);
        $uniqueB = array_diff($cloneTarget->rawData, $cloneOriginal->rawData);

        $result = array_merge($uniqueA, $uniqueB);

        return new self($result);

    }

    /**
     * Flatten a multi-dimensional array
     *
     * @return ArrayObject
     */
    public function flatten(): self
    {
        $array = $this->cloneRawData();
        $result = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                foreach ($item as $subItem) {
                    $result[] = $subItem;
                }
            } else {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * Check if array has key
     *
     * @param string|int $key
     *
     * @return bool
     */
    public function has(string|int $key): bool
    {
        return isset($this->rawData[$key]);
    }

    /**
     * Group array by key
     *
     * @param string $key
     *
     * @return ArrayObject
     */
    public function group(string $key): self
    {
        $array = $this->cloneRawData();
        $grouped = [];

        foreach ($array as $item) {
            if (isset($item[$key])) {
                $grouped[$item[$key]][] = $item;
            }
        }

        return new self($grouped);
    }

    /**
     * Pluck values by key
     *
     * @param string $key
     *
     * @return ArrayObject
     */
    public function pluck(string $key): self
    {
        $array = $this->cloneRawData();
        $result = [];

        foreach ($array as $item) {
            if (is_array($item) && isset($item[$key])) {
                $result[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $result[] = $item->$key;
            }
        }

        return new self($result);
    }

    /**
     * Order array by key
     *
     * @param string $key
     *
     * @return ArrayObject
     */
    public function order(string $key): self
    {
        $array = $this->cloneRawData();
        usort($this->cloneRawData(), function ($a, $b) use ($key) {
            return $a[$key] <=> $b[$key];
        });

        return new self($array);
    }

    /**
     * Clean array by removing null and empty string values
     *
     * @return ArrayObject
     */
    public function clean(): self
    {
        $array = $this->getRawData();

        $array = array_filter($array, static function ($value) {
            return ($value !== null && $value !== '');
        });

        $this->setRawData($array);

        return $this;
    }

    /**
     * Split an array into chunks
     *
     * @param int $size
     *
     * @return ArrayObject
     */
    public function chunk(int $size = 1): self
    {
        $array = $this->cloneRawData();
        $chunks = [];
        $chunk = [];

        foreach ($array as $key => $value) {
            $chunk[$key] = $value;
            if (count($chunk) === $size) {
                $chunks[] = $chunk;
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $chunks[] = $chunk;
        }

        return new self($chunks);
    }


    /**
     * Check if two arrays are equal
     *
     * @param ArrayObject $b
     *
     * @return bool
     */
    public function isEquals(ArrayObject $b): bool
    {
        return
            count($this->rawData) == count($b->rawData) &&
            array_diff($this->rawData, $b->rawData) == array_diff($b->rawData, $this->rawData);
    }

    /**
     * Sort an array
     * 
     * @param int $flags
     *
     * @return ArrayObject
     */
    public function sort(int $flags = SORT_REGULAR): self
    {
        $clone = $this->cloneRawData();

        sort($clone, $flags);

        return new self($clone);
    }

    /**
     * Check if array is list
     *
     * @return bool
     */
    public function isList(): bool
    {
        return array_is_list($this->getRawData());
    }

    /**
     * Get random values from array
     *
     * @param int $num
     *
     * @return object
     */
    public function getRandom(int $num = 1): object
    {
        $data = $this->cloneRawData();
        $data = array_rand($data, $num);

        $this->setRawData($data);

        return $this->toObject();
    }

    /**
     * Join array elements with a string
     *
     * @param array|string $separator
     *
     * @return StringObject
     */
    public function join(array|string $separator): StringObject
    {
        $clone = implode($separator, $this->cloneRawData());

        return new StringObject($clone);
    }

    /**
     * Counts all elements in an array
     * 
     * @return int
     */
    public function size(): int
    {
        $rawData = $this->getRawData();

        if ($rawData instanceof Countable) {
            return count($rawData);
        }

        if (getType($rawData) == 'array') {
            return count($rawData);
        }

        if ($rawData instanceof ArrayObject) {
            return count($rawData);
        }

        if (is_array($rawData)) {
            return count($rawData);
        }

        if (!empty($rawData)) {
            return 1;
        }

        return 0;
    }

    /**
     * Iterate over array with index
     *
     * @param callable $callback
     *
     * @return void
     */
    public function forIn(callable $callback): void
    {
        $size = $this->size();

        for ($i = 0; $i < $size; $i++) {
            $data = $this->getByIndex($i);

            $return = $callback($data);

            if ($return) {
                break;
            }
        }
    }

    /**
     * Iterate with index
     *
     * @param callable $callback
     *
     * @return void
     */
    public function forInWithIndex(callable $callback): void
    {
        if (!is_callable($callback)) {
            throw new Exception('Callback is not callable');
        }

        $size = $this->size();

        for ($i = 0; $i < $size; $i++) {
            $data = $this->getByIndex($i);

            $return = $callback($i, $data);

            if ($return === false) {
                continue;
            }

            if ($return) {
                break;
            }
        }
    }

    /**
     * Check if size is greater than given size
     *
     * @param int $size
     *
     * @return bool
     */
    public function sizeGreaterThan(int $size): bool
    {
        return $this->size() > $size;
    }

    /**
     * Check if size is equal to given size
     *
     * @param int $size
     *
     * @return bool
     */
    public function sizeEquals(int $size): bool
    {
        return $this->size() == $size;
    }

    /**
     * Check if size is smaller than given size
     *
     * @param int $size
     *
     * @return bool
     */
    public function sizeSmallerThan(int $size): bool
    {
        return $this->size() < $size;
    }

    /**
     * Convert array to object
     *
     * @return object
     */
    public function toObject(): object
    {
        $array = $this->rawData;
        $object = new stdClass();

        foreach ($array as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * Retrieve an external iterator
     *
     * @return Traversable An instance of an object implementing <b>Traversable</b>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->getRawData());
    }

    /**
     * Clear all items in the array
     * 
     * @return ArrayObject
     */
    public function clear(): static
    {
        $this->rawData = [];

        return $this;
    }
}
