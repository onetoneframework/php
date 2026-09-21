<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Regex;

/**
 * Class StringResult
 *
 * @package Clover\Classes\Regex
 */
final class StringResult
{
	/**
	 * @var bool Indicates whether the regex operation resulted in a match
	 */
	private bool $boolean;

	/**
	 * @var string The regex pattern used in the operation
	 */
	private string $pattern;

	/**
	 * @var string The subject string used in the operation
	 */
	private string $subject;

	/**
	 * @var array An array of matches found in the regex operation
	 */
	private array $matches;

	/**
	 * StringResult constructor.
	 *
	 * @param array{Boolean: bool|int, Pattern: string, Subject: string, Matches: array} $result
	 */
	public function __construct(array $result = [])
	{
		$this->boolean = $result['Boolean'] ?? false;
		$this->pattern = $result['Pattern'] ?? "";
		$this->subject = $result['Subject'] ?? "";
		$this->matches = $result['Matches'] ?? [];
	}

	/**
	 * Get a singleton instance of StringResult with the provided result data
	 *
	 * @param array $result The result data to initialize the StringResult instance
	 * @return static A new instance of StringResult initialized with the provided result data
	 */
	public function getSingleton(array $result): static
	{
		return new static($result);
	}

	/**
	 * Check if the regex operation resulted in a match
	 *
	 * @return bool True if a match was found, false otherwise
	 */
	public function hasResult(): bool
	{
		return $this->boolean;
	}

	/**
	 * Get the regex pattern that was used in the regex operation
	 *
	 * @return string The regex pattern
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	/**
	 * Get the subject string that was used in the regex operation
	 *
	 * @return string The subject string
	 */
	public function getSubject(): string
	{
		return $this->subject;
	}

	/**
	 * Get the matches from the regex operation
	 *
	 * @return array An array of matches found in the regex operation
	 */
	public function getMatches(): array
	{
		return $this->matches;
	}

	/**
	 * Get the first match result
	 *
	 * @return string The first matched string, or an empty string if no matches were found
	 */
	public function get(): string
	{
		return $this->matches[0] ?? "";
	}

	/**
	 * Get a specific match group by index
	 *
	 * @param int $index The index of the match group to retrieve
	 * @return string The matched string for the specified group, or an empty string if the group does not exist
	 */
	public function getGroup(int $index): string
	{
		return $this->matches[$index] ?? "";
	}

	/**
	 * Get all matches as an array
	 *
	 * @return array An array of all matches
	 */
	public function getAll(): array
	{
		return $this->matches;
	}
}
