<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database;

use Closure;
use function in_array;
use function is_array;
use function mb_strlen;

/**
 * The rule engine behind ActiveRecord::validate().
 *
 * Lifted out unchanged, every branch and every default message included. Only
 * the `unique` rule needed a seam: it reaches for a database connection and the
 * entity's own table and column metadata, neither of which belongs here, so the
 * caller supplies that lookup as a closure and the rule asks it whether the
 * value is taken.
 *
 * @package Clover\Classes\Database
 */
class RecordValidator
{
	/**
	 * Validation errors populated by the last validate() call.
	 *
	 * @var array<string, string[]>
	 */
	private array $errors = [];

	/**
	 * @param array<string, string|array<int, string>> $rules         Rules per field, pipe-separated or as a list.
	 * @param array<string, string>                    $messages      Overrides keyed "field.rule" or "field".
	 * @param Closure|null                             $uniqueChecker fn(string $field, mixed $value, ?string $ruleParam): bool
	 *                                                                returning true when the value is already taken.
	 *                                                                Null disables the `unique` rule, which is what a model
	 *                                                                without a connection has always done.
	 */
	public function __construct(
		private readonly array $rules,
		private readonly array $messages,
		private readonly ?Closure $uniqueChecker = null
	) {
	}

	/**
	 * Run every rule against the supplied data.
	 *
	 * @param array<string, mixed> $data The entity's attributes.
	 *
	 * @return bool True if valid
	 */
	public function validate(array $data): bool
	{
		$this->errors = [];

		foreach ($this->rules as $field => $ruleSet) {
			$rules = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
			$value = $data[$field] ?? null;

			foreach ($rules as $rule) {
				$error = $this->applyValidationRule($field, $value, $rule, $data);

				if ($error !== null) {
					$this->errors[$field][] = $error;
				}
			}
		}

		return empty($this->errors);
	}

	/**
	 * All validation errors indexed by field name.
	 *
	 * @return array<string, string[]>
	 */
	public function errors(): array
	{
		return $this->errors;
	}

	/**
	 * Apply a single validation rule to a value.
	 *
	 * @param string               $field Property name
	 * @param mixed                $value Current value
	 * @param string               $rule  Rule string (e.g. "required", "min:3")
	 * @param array<string, mixed> $data  Full entity data (for cross-field rules)
	 *
	 * @return string|null Error message or null when valid
	 */
	private function applyValidationRule(string $field, mixed $value, string $rule, array $data): ?string
	{
		// Custom message helper
		$msg = function (string $ruleKey, string $default) use ($field): string {
			return $this->messages["{$field}.{$ruleKey}"] ?? $this->messages[$field] ?? $default;
		};

		[$ruleName, $ruleParam] = array_pad(explode(':', $rule, 2), 2, null);

		switch ($ruleName) {
			case 'required':
				if ($value === null || $value === '') {
					return $msg('required', "The {$field} field is required.");
				}
				break;

			case 'min':
				if ($value !== null && is_numeric($value) && (float) $value < (float) $ruleParam) {
					return $msg('min', "The {$field} must be at least {$ruleParam}.");
				}
				break;

			case 'max':
				if ($value !== null && is_numeric($value) && (float) $value > (float) $ruleParam) {
					return $msg('max', "The {$field} must not exceed {$ruleParam}.");
				}
				break;

			case 'minLength':
				if ($value !== null && mb_strlen((string) $value) < (int) $ruleParam) {
					return $msg('minLength', "The {$field} must be at least {$ruleParam} characters.");
				}
				break;

			case 'maxLength':
				if ($value !== null && mb_strlen((string) $value) > (int) $ruleParam) {
					return $msg('maxLength', "The {$field} must not exceed {$ruleParam} characters.");
				}
				break;

			case 'numeric':
				if ($value !== null && $value !== '' && !is_numeric($value)) {
					return $msg('numeric', "The {$field} must be a number.");
				}
				break;

			case 'integer':
				if ($value !== null && $value !== '' && filter_var($value, FILTER_VALIDATE_INT) === false) {
					return $msg('integer', "The {$field} must be an integer.");
				}
				break;

			case 'email':
				if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
					return $msg('email', "The {$field} must be a valid email address.");
				}
				break;

			case 'url':
				if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
					return $msg('url', "The {$field} must be a valid URL.");
				}
				break;

			case 'regex':
				if ($value !== null && $value !== '' && !preg_match($ruleParam, (string) $value)) {
					return $msg('regex', "The {$field} format is invalid.");
				}
				break;

			case 'in':
				$allowed = explode(',', $ruleParam ?? '');
				if ($value !== null && $value !== '' && !in_array((string) $value, $allowed, true)) {
					return $msg('in', "The {$field} must be one of: {$ruleParam}.");
				}
				break;

			case 'notIn':
				$forbidden = explode(',', $ruleParam ?? '');
				if ($value !== null && in_array((string) $value, $forbidden, true)) {
					return $msg('notIn', "The {$field} must not be one of: {$ruleParam}.");
				}
				break;

			case 'confirmed':
				// Checks that {field}_confirmation matches {field}
				$confirmKey = "{$field}_confirmation";
				if (($data[$confirmKey] ?? null) !== $value) {
					return $msg('confirmed', "The {$field} confirmation does not match.");
				}
				break;

			case 'unique':
				// unique:table.column  or just unique (uses current table/column)
				if ($value !== null && $value !== '' && $this->uniqueChecker !== null) {
					if (($this->uniqueChecker)($field, $value, $ruleParam)) {
						return $msg('unique', "The {$field} has already been taken.");
					}
				}
				break;
		}

		return null;
	}
}
