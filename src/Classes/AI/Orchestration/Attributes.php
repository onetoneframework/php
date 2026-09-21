<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use ArrayIterator;
use Clover\Exception\AI\AttributeNotFoundException;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * Immutable scalar attributes shared with AI workflow steps.
 *
 * @implements IteratorAggregate<string, string|int|float|bool>
 */
final class Attributes implements Countable, IteratorAggregate
{
	public const MAXIMUM_COUNT = 256;

	public const MAXIMUM_KEY_BYTES = 256;

	public const MAXIMUM_STRING_VALUE_BYTES = 1_048_576;

	private const BOOLEAN_BYTES = 1;

	private const FLOAT_BYTES = 8;

	/**
	 * @var array<string, string|int|float|bool>
	 */
	private array $values = [];

	/**
	 * Create a validated attribute collection.
	 *
	 * @param iterable<string, string|int|float|bool> $values
	 */
	public function __construct(iterable $values = [])
	{
		foreach ($values as $key => $value) {
			if (!is_string($key) || trim($key) === '' || strlen($key) > self::MAXIMUM_KEY_BYTES) {
				throw new InvalidArgumentException('AI orchestration attribute keys must be non-blank strings within the size limit.');
			}

			if (!is_string($value) && !is_int($value) && !is_float($value) && !is_bool($value)) {
				throw new InvalidArgumentException('AI orchestration attributes must contain scalar values.');
			}

			if (is_string($value) && strlen($value) > self::MAXIMUM_STRING_VALUE_BYTES) {
				throw new InvalidArgumentException('AI orchestration string attributes exceed the size limit.');
			}

			if (!array_key_exists($key, $this->values) && count($this->values) >= self::MAXIMUM_COUNT) {
				throw new InvalidArgumentException('AI orchestration attributes exceed the collection size limit.');
			}

			$this->values[$key] = $value;
		}
	}

	/**
	 * Return whether the collection contains the requested key.
	 */
	public function has(string $key): bool
	{
		return array_key_exists($key, $this->values);
	}

	/**
	 * Return a required attribute value.
	 */
	public function get(string $key): string|int|float|bool
	{
		if (!$this->has($key)) {
			throw new AttributeNotFoundException($key);
		}

		return $this->values[$key];
	}

	/**
	 * Return a new collection containing the provided value.
	 */
	public function with(string $key, string|int|float|bool $value): self
	{
		$values = $this->values;
		$values[$key] = $value;

		return new self($values);
	}

	/**
	 * Return the number of stored attributes.
	 */
	public function count(): int
	{
		return count($this->values);
	}

	/**
	 * Return the normalized payload size represented by this collection.
	 */
	public function sizeInBytes(): int
	{
		$sizeInBytes = 0;
		foreach ($this->values as $key => $value) {
			$sizeInBytes += strlen($key);

			if (is_string($value)) {
				$sizeInBytes += strlen($value);
			} elseif (is_int($value)) {
				$sizeInBytes += PHP_INT_SIZE;
			} elseif (is_float($value)) {
				$sizeInBytes += self::FLOAT_BYTES;
			} else {
				$sizeInBytes += self::BOOLEAN_BYTES;
			}
		}

		return $sizeInBytes;
	}

	/**
	 * Iterate over the stored attributes without exposing mutable state.
	 *
	 * @return Traversable<string, string|int|float|bool>
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->values);
	}
}
