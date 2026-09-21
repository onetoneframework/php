<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use Clover\Classes\Data\StringObject;
use Clover\Classes\Regex\{ArrayResult, StringResult, Executor};
use Clover\Traits\Regex\RegexError;
use Exception;

/*
 * Class Regex
 *
 * Provides static methods for performing regex operations, such as validating regex patterns, filtering strings, quoting special characters, and executing regex matches.
 */
class Regex
{
	use RegexError;

	/**
	 * Check if a regex pattern is valid
	 *
	 * @param string $regex The regex pattern to validate
	 * @return bool True if the regex pattern is valid, false otherwise
	 */
	public static function isValid(string $regex): bool
	{
		try {
			$oldLevel = error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
			error_reporting(E_ERROR | E_PARSE);
			preg_match($regex, "");
			error_reporting($oldLevel);
		} catch (Exception $ignore) {
		}

		return self::hasError();
	}

	/**
	 * Perform a regex filter operation
	 *
	 * @param string $pattern The regex pattern to search for
	 * @param string $replacement The replacement string
	 * @param string|array $subject The input string or array of strings to search and replace
	 * @param int $limit The maximum number of replacements to perform (default is -1, which means no limit)
	 * @return string|array|null The resulting string or array after replacements, or null if an error occurred
	 */
	public static function filter(string $pattern, string $replacement, string|array $subject, int $limit = -1): string|array|null
	{
		return @preg_filter($pattern, $replacement, $subject, $limit);
	}

	/**
	 * Quote regular expression characters in a string
	 *
	 * @param string $string The input string to quote
	 * @param string|null $delimiter An optional delimiter character to escape
	 * @return string The quoted string with special regex characters escaped
	 */
	public static function quote(string $string, ?string $delimiter = null): string
	{
		return preg_quote($string, $delimiter);
	}

	/**
	 * Perform a regex match and return the result as an ArrayResult object
	 *
	 * @param string|StringObject $pattern The regex pattern to match
	 * @param string|StringObject $subject The input string to search for matches
	 * @return ArrayResult An ArrayResult object containing the match results
	 */
	public static function match(string|StringObject $pattern, string|StringObject $subject): ArrayResult
	{
		$patternStr = $pattern instanceof StringObject ? (string) $pattern : $pattern;
		$subjectStr = $subject instanceof StringObject ? (string) $subject : $subject;

		$result = Executor::match($patternStr, $subjectStr);

		return (new ArrayResult())->getSingleton($result);
	}

	/**
	 * Perform a regex match and return all matches as an ArrayResult object
	 *
	 * @param string|StringObject $pattern The regex pattern to match
	 * @param string|StringObject $subject The input string to search for matches
	 * @return ArrayResult An ArrayResult object containing all match results
	 */
	public static function matchAll(string $pattern, string $subject): ArrayResult
	{
		$result = Executor::matchAll($pattern, $subject);

		return (new ArrayResult())->getSingleton($result);
	}

	/**
	 * Split a string using a regex pattern
	 *
	 * @param string $pattern The regex pattern to use for splitting
	 * @param string $subject The input string to split
	 * @param int $limit The maximum number of splits to perform (default is -1, which means no limit)
	 * @param int $flags Optional flags to modify the behavior of the split (default is 0)
	 * @return array|false An array of split strings, or false if an error occurred
	 */
	public static function split(string $pattern, string $subject, int $limit = -1, int $flags = 0): array|false
	{
		return @preg_split($pattern, $subject, $limit, $flags);
	}

	/**
	 * Perform a regex replacement
	 *
	 * @param string|array $pattern The regex pattern(s) to search for
	 * @param string|array $replacement The replacement string or array of strings
	 * @param string|array $subject The input string or array of strings to search and replace
	 * @param int $limit The maximum number of replacements to perform (default is -1, which means no limit)
	 * @param int|null $count If provided, this variable will be set to the number of replacements performed
	 * @return string|array|null The resulting string or array after replacements, or null if an error occurred
	 */
	public static function replace(string|array $pattern, string|array $replacement, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null
	{
		return @preg_replace($pattern, $replacement, $subject, $limit, $count);
	}

	/**
	 * Perform a regex replacement using a callback function
	 *
	 * @param string|array $pattern The regex pattern(s) to search for
	 * @param callable $callback The callback function to generate the replacement string
	 * @param string|array $subject The input string or array of strings to search and replace
	 * @param int $limit The maximum number of replacements to perform (default is -1, which means no limit)
	 * @param int|null $count If provided, this variable will be set to the number of replacements performed
	 * @return string|array|null The resulting string or array after replacements, or null if an error occurred
	 */
	public static function replaceCallback(string|array $pattern, callable $callback, string|array $subject, int $limit = -1, ?int &$count = null): string|array|null
	{
		return @preg_replace_callback($pattern, $callback, $subject, $limit, $count);
	}

	public static function grep(string $pattern, array $array, int $flags = 0): array|false
	{
		return @preg_grep($pattern, $array, $flags);
	}
}
