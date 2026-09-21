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
 * Class Executor
 *
 * Provides static methods to execute regex operations such as matching a pattern against a subject string and returning the results in an array format.
 */
class Executor
{
	/**
	 * Execute a regex match operation
	 *
	 * @param string $pattern The regex pattern to match
	 * @param string $subject The subject string to search within
	 * @param int $flags
	 * @param int $offset
	 * @return array An associative array containing the boolean result, pattern, subject, and matches
	 */
	public static function match(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
	{
		$bool = @preg_match($pattern, $subject, $matches, $flags, $offset);

		return ['Boolean' => $bool, 'Pattern' => $pattern, 'Subject' => $subject, 'Matches' => $matches];
	}

	/**
	 * Execute a regex match operation to find all matches
	 *
	 * @param string $pattern The regex pattern to match
	 * @param string $subject The subject string to search within
	 * @param int $flags
	 * @param int $offset
	 * @return array An associative array containing the boolean result, pattern, subject, and all matches
	 */
	public static function matchAll(string $pattern, string $subject, int $flags = 0, int $offset = 0): array
	{
		$bool = @preg_match_all($pattern, $subject, $matches, $flags, $offset);

		return ['Boolean' => $bool, 'Pattern' => $pattern, 'Subject' => $subject, 'Matches' => $matches];
	}
}
