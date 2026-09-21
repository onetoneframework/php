<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Scheduler;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use function array_keys;
use function count;
use function explode;
use function is_string;
use function preg_match;
use function strtolower;
use function trim;
use function sprintf;

/**
 * Cron Expression
 *
 * Parses and evaluates standard 5-field cron expressions.
 *
 * Supported field forms:
 *  - Wildcard:        *
 *  - Exact value:     5
 *  - Range:           1-5
 *  - Step:            star/5, 0-30/10
 *  - List:            1,3,5
 *
 * Supported macros: @yearly, @annually, @monthly, @weekly, @daily, @midnight, @hourly.
 *
 * This class intentionally avoids external cron libraries so the scheduler
 * contract stays framework-internal and vendor-agnostic.
 */
final class CronExpression
{
	/**
	 * @var int Minute field index.
	 */
	private const FIELD_MINUTE = 0;

	/**
	 * @var int Hour field index.
	 */
	private const FIELD_HOUR = 1;

	/**
	 * @var int Day-of-month field index.
	 */
	private const FIELD_DAY_OF_MONTH = 2;

	/**
	 * @var int Month field index.
	 */
	private const FIELD_MONTH = 3;

	/**
	 * @var int Day-of-week field index (0-6, where 0/7 = Sunday).
	 */
	private const FIELD_DAY_OF_WEEK = 4;

	/**
	 * Field value ranges keyed by field index.
	 *
	 * @var array<int, array{0: int, 1: int}>
	 */
	private const FIELD_BOUNDS = [
		self::FIELD_MINUTE => [0, 59],
		self::FIELD_HOUR => [0, 23],
		self::FIELD_DAY_OF_MONTH => [1, 31],
		self::FIELD_MONTH => [1, 12],
		self::FIELD_DAY_OF_WEEK => [0, 6],
	];

	/**
	 * Macro expansion table.
	 *
	 * @var array<string, string>
	 */
	private const MACROS = [
		'@yearly' => '0 0 1 1 *',
		'@annually' => '0 0 1 1 *',
		'@monthly' => '0 0 1 * *',
		'@weekly' => '0 0 * * 0',
		'@daily' => '0 0 * * *',
		'@midnight' => '0 0 * * *',
		'@hourly' => '0 * * * *',
	];

	/**
	 * @var string Original expression supplied by the caller, after macro expansion.
	 */
	private string $expression;

	/**
	 * Parsed sets of allowed values, keyed by field index.
	 *
	 * Each entry is a map of value => true for O(1) lookup.
	 *
	 * @var array<int, array<int, bool>>
	 */
	private array $allowed;

	/**
	 * Create a cron expression from a string form.
	 *
	 * @param string $expression 5-field cron expression or supported macro.
	 *
	 * @throws InvalidArgumentException When the expression is malformed.
	 */
	public function __construct(string $expression)
	{
		$this->expression = self::normalize($expression);
		$this->allowed = self::parseFields($this->expression);
	}

	/**
	 * Named constructor mirroring {@see __construct} for fluent call sites.
	 *
	 * @param string $expression 5-field cron expression or supported macro.
	 *
	 * @return self
	 */
	public static function parse(string $expression): self
	{
		return new self($expression);
	}

	/**
	 * Check whether the given instant matches this cron expression.
	 *
	 * @param DateTimeImmutable $now Instant to evaluate.
	 * @param DateTimeZone|null $timezone Optional timezone override.
	 *
	 * @return bool True when the instant satisfies every field.
	 */
	public function isDue(DateTimeImmutable $now, ?DateTimeZone $timezone = null): bool
	{
		$instant = $timezone === null ? $now : $now->setTimezone($timezone);

		$minute = (int) $instant->format('i');
		$hour = (int) $instant->format('G');
		$dayOfMonth = (int) $instant->format('j');
		$month = (int) $instant->format('n');
		// PHP `w` returns 0 (Sunday) .. 6 (Saturday), matching cron day-of-week.
		$dayOfWeek = (int) $instant->format('w');

		if (!isset($this->allowed[self::FIELD_MINUTE][$minute])) {
			return false;
		}

		if (!isset($this->allowed[self::FIELD_HOUR][$hour])) {
			return false;
		}

		if (!isset($this->allowed[self::FIELD_MONTH][$month])) {
			return false;
		}

		return $this->dayMatches($dayOfMonth, $dayOfWeek);
	}

	/**
	 * Returns the normalized expression string.
	 *
	 * @return string
	 */
	public function getExpression(): string
	{
		return $this->expression;
	}

	/**
	 * Day-of-month and day-of-week use "OR" semantics when both fields are
	 * restricted, mirroring POSIX cron behavior.
	 *
	 * @param int $dayOfMonth Current day of month (1-31).
	 * @param int $dayOfWeek Current day of week (0-6).
	 *
	 * @return bool
	 */
	private function dayMatches(int $dayOfMonth, int $dayOfWeek): bool
	{
		$domRestricted = $this->isFieldRestricted(self::FIELD_DAY_OF_MONTH);
		$dowRestricted = $this->isFieldRestricted(self::FIELD_DAY_OF_WEEK);

		$domHit = isset($this->allowed[self::FIELD_DAY_OF_MONTH][$dayOfMonth]);
		$dowHit = isset($this->allowed[self::FIELD_DAY_OF_WEEK][$dayOfWeek]);

		if ($domRestricted && $dowRestricted) {
			return $domHit || $dowHit;
		}

		if ($domRestricted) {
			return $domHit;
		}

		if ($dowRestricted) {
			return $dowHit;
		}

		return true;
	}

	/**
	 * A field is "restricted" when it does not match every value in its range.
	 *
	 * @param int $field Field index.
	 *
	 * @return bool
	 */
	private function isFieldRestricted(int $field): bool
	{
		[$min, $max] = self::FIELD_BOUNDS[$field];
		$allowedCount = count($this->allowed[$field]);

		return $allowedCount !== ($max - $min + 1);
	}

	/**
	 * Normalize an expression by trimming whitespace and expanding macros.
	 *
	 * @param string $expression Original input.
	 *
	 * @return string Canonical 5-field cron expression.
	 *
	 * @throws InvalidArgumentException
	 */
	private static function normalize(string $expression): string
	{
		$trimmed = trim($expression);

		if ($trimmed === '') {
			throw new InvalidArgumentException('Cron expression must not be empty.');
		}

		$lower = strtolower($trimmed);
		if (isset(self::MACROS[$lower])) {
			return self::MACROS[$lower];
		}

		return $trimmed;
	}

	/**
	 * Parse every field of the expression into allowed-value sets.
	 *
	 * @param string $expression Canonical 5-field cron expression.
	 *
	 * @return array<int, array<int, bool>> Allowed-value maps keyed by field index.
	 *
	 * @throws InvalidArgumentException
	 */
	private static function parseFields(string $expression): array
	{
		$fields = preg_split('/\s+/', $expression) ?: [];

		if (count($fields) !== 5) {
			throw new InvalidArgumentException(sprintf('Cron expression must have 5 fields, got "%s".', $expression));
		}

		$allowed = [];
		foreach (array_keys(self::FIELD_BOUNDS) as $field) {
			[$min, $max] = self::FIELD_BOUNDS[$field];
			$allowed[$field] = self::parseField($fields[$field], $min, $max, $field);
		}

		return $allowed;
	}

	/**
	 * Parse a single field value (minute/hour/etc.) into a lookup map.
	 *
	 * @param string $field Raw field value.
	 * @param int $min Inclusive lower bound for this field.
	 * @param int $max Inclusive upper bound for this field.
	 * @param int $fieldIndex Field index for error reporting.
	 *
	 * @return array<int, bool> Map of allowed values keyed by the value itself.
	 *
	 * @throws InvalidArgumentException
	 */
	private static function parseField(string $field, int $min, int $max, int $fieldIndex): array
	{
		$allowed = [];

		foreach (explode(',', $field) as $token) {
			$token = trim($token);
			if ($token === '') {
				throw new InvalidArgumentException(sprintf('Empty token in cron field #%d.', $fieldIndex));
			}

			$step = 1;
			$range = $token;
			if (str_contains($token, '/')) {
				$parts = explode('/', $token, 2);
				$range = $parts[0];
				$stepRaw = $parts[1];

				if (!is_string($stepRaw) || !preg_match('/^\d+$/', $stepRaw) || (int) $stepRaw === 0) {
					throw new InvalidArgumentException(sprintf('Invalid step "%s" in cron field #%d.', $stepRaw, $fieldIndex));
				}

				$step = (int) $stepRaw;
			}

			[$rangeStart, $rangeEnd] = self::resolveRange($range, $min, $max, $fieldIndex);

			// Day-of-week allows "7" as Sunday; normalize 7 to 0 before storage.
			for ($value = $rangeStart; $value <= $rangeEnd; $value += $step) {
				$normalizedValue = $value;
				if ($fieldIndex === self::FIELD_DAY_OF_WEEK && $value === 7) {
					$normalizedValue = 0;
				}

				$allowed[$normalizedValue] = true;
			}
		}

		return $allowed;
	}

	/**
	 * Resolve a range segment ("*", "5", "1-5") to inclusive [start, end].
	 *
	 * @param string $range Range token without step.
	 * @param int $min Inclusive lower bound for this field.
	 * @param int $max Inclusive upper bound for this field.
	 * @param int $fieldIndex Field index for error reporting.
	 *
	 * @return array{0: int, 1: int}
	 *
	 * @throws InvalidArgumentException
	 */
	private static function resolveRange(string $range, int $min, int $max, int $fieldIndex): array
	{
		if ($range === '*') {
			return [$min, $max];
		}

		if (str_contains($range, '-')) {
			$parts = explode('-', $range, 2);
			$start = self::parseInt($parts[0], $fieldIndex);
			$end = self::parseInt($parts[1], $fieldIndex);

			if ($fieldIndex === self::FIELD_DAY_OF_WEEK) {
				if ($start === 7) {
					$start = 0;
				}
				if ($end === 7) {
					$end = 0;
				}
			}

			if ($start > $end) {
				throw new InvalidArgumentException(sprintf('Invalid range "%s" in cron field #%d.', $range, $fieldIndex));
			}

			self::assertBounds($start, $min, $max, $fieldIndex);
			self::assertBounds($end, $min, $max, $fieldIndex);

			return [$start, $end];
		}

		$value = self::parseInt($range, $fieldIndex);
		if ($fieldIndex === self::FIELD_DAY_OF_WEEK && $value === 7) {
			$value = 0;
		}

		self::assertBounds($value, $min, $max, $fieldIndex);

		return [$value, $value];
	}

	/**
	 * Parse a token as a non-negative integer.
	 *
	 * @param string $value Raw token.
	 * @param int $fieldIndex Field index for error reporting.
	 *
	 * @return int
	 *
	 * @throws InvalidArgumentException
	 */
	private static function parseInt(string $value, int $fieldIndex): int
	{
		$trimmed = trim($value);
		if (!preg_match('/^\d+$/', $trimmed)) {
			throw new InvalidArgumentException(sprintf('Invalid value "%s" in cron field #%d.', $value, $fieldIndex));
		}

		return (int) $trimmed;
	}

	/**
	 * Assert that a resolved value sits within the field's allowed range.
	 *
	 * @param int $value Resolved numeric value.
	 * @param int $min Inclusive lower bound.
	 * @param int $max Inclusive upper bound.
	 * @param int $fieldIndex Field index for error reporting.
	 *
	 * @return void
	 *
	 * @throws InvalidArgumentException
	 */
	private static function assertBounds(int $value, int $min, int $max, int $fieldIndex): void
	{
		if ($value < $min || $value > $max) {
			throw new InvalidArgumentException(sprintf('Value %d out of range [%d-%d] in cron field #%d.', $value, $min, $max, $fieldIndex));
		}
	}

}
