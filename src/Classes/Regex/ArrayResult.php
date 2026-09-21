<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Regex;

/*
 * Class ArrayResult
 *
 * Encapsulates the result of a regex operation, providing methods to access the boolean result, pattern, subject, and matches.
 */
final class ArrayResult
{
	/** @var bool|int $boolean */
	private bool|int $boolean;

	/** @var string $subject */
	private string $subject;

	/** @var string $pattern */
	private string $pattern;

	/** @var array $matches */
	private array $matches = [];

	/**
	 * Constructor
	 *
	 * @param array{Boolean: bool|int, Pattern: string, Subject: string, Matches: array} $result The result array containing 'Boolean', 'Pattern', 'Subject', and 'Matches' keys
	 */
	public function __construct(array $result = [])
	{
		$this->boolean = $result['Boolean'] ?? false;
		$this->pattern = $result['Pattern'] ?? "";
		$this->subject = $result['Subject'] ?? "";
		$this->matches = $result['Matches'] ?? [];
	}

	/**
	 * Get a singleton instance of ArrayResult
	 *
	 * @param array $result The result array to initialize the instance with
	 * @return static A new instance of ArrayResult initialized with the provided result
	 */
	public function getSingleton(array $result): static
	{
		return new static($result);
	}

	/**
	 * Check if the regex operation was successful
	 *
	 * @return bool|int True if the operation was successful, false otherwise
	 */
	public function hasResult(): bool|int
	{
		return $this->boolean === 1 || $this->boolean;
	}

	/**
	 * Get the regex pattern used in the operation
	 *
	 * @return string The regex pattern
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	/**
	 * Get the subject string that was tested against the regex pattern
	 *
	 * @return string The subject string
	 */
	public function getSubject(): string
	{
		return $this->subject;
	}

	/**
	 * Get the matches found by the regex operation
	 *
	 * @return array The array of matches
	 */
	public function getMatches(): array
	{
		return $this->matches;
	}

	/**
	 * Get a specific match by index
	 *
	 * @param int $index The index of the match to retrieve
	 * @return mixed The match at the specified index, or an empty string if it does not exist
	 */
	public function getByIndex(int $index = 0): mixed
	{
		$matches = $this->getMatches();

		return $matches[$index] ?? "";
	}

	/**
	 * Get all matches found by the regex operation
	 *
	 * @return array The array of matches
	 */
	public function getResults(): array
	{
		return $this->getMatches();
	}
}
