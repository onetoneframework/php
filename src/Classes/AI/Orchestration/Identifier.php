<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Exception\AI\InvalidIdentifierException;

/**
 * Validates names used at AI orchestration boundaries.
 */
final class Identifier
{
	private const PATTERN = '/\A[A-Za-z][A-Za-z0-9_.:-]*\z/D';

	private const MAXIMUM_LENGTH = 128;

	private const REGULAR_EXPRESSION_MATCHED = 1;

	private string $value;

	/**
	 * Create a validated identifier.
	 */
	public function __construct(string $value)
	{
		if (
			$value !== trim($value)
			|| strlen($value) > self::MAXIMUM_LENGTH
			|| preg_match(self::PATTERN, $value) !== self::REGULAR_EXPRESSION_MATCHED
		) {
			throw new InvalidIdentifierException(
				'AI orchestration identifiers must start with a letter and contain only letters, numbers, underscores, dots, colons, or hyphens.'
			);
		}

		$this->value = $value;
	}

	/**
	 * Return the validated identifier value.
	 */
	public function value(): string
	{
		return $this->value;
	}
}
