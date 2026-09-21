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
 * Class Builder
 *
 * Provides a fluent interface for building regular expressions in PHP. It allows you to construct complex regex patterns using method chaining, making it easier to read and maintain.
 */
class Builder
{
	/** @var string $pattern */
	private string $pattern = '';
	
	/** @var string $modifiers */
	private string $modifiers = '';
	
	/** @var string $delimiter */
	private string $delimiter = '/';

	/**
	 * Constructor
	 *
	 * @param string $pattern The initial regex pattern
	 */
	public function __construct(string $pattern = '')
	{
		$this->pattern = $pattern;
	}

	/**
	 * Create a new Builder instance with an optional initial pattern
	 *
	 * @param string $pattern The initial regex pattern
	 * @return static A new instance of Builder initialized with the provided pattern
	 */
	public static function create(string $pattern = ''): static
	{
		return new static($pattern);
	}

	/**
	 * Append a raw regex pattern to the current pattern
	 *
	 * @param string $pattern The raw regex pattern to append
	 * @return static The current Builder instance for method chaining
	 */
	public function raw(string $pattern): static
	{
		$this->pattern .= $pattern;
		return $this;
	}

	/**
	 * Append a literal string to the regex pattern, escaping special characters
	 *
	 * @param string $text The literal string to append
	 * @return static The current Builder instance for method chaining
	 */
	public function literal(string $text): static
	{
		$this->pattern .= preg_quote($text, $this->delimiter);
		return $this;
	}

	/**
	 * Append a regex pattern that matches the start of a line
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function startOfLine(): static
	{
		$this->pattern .= '^';
		return $this;
	}

	/**
	 * Append a regex pattern that matches the end of a line
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function endOfLine(): static
	{
		$this->pattern .= '$';
		return $this;
	}

	/**
	 * Append a regex pattern that matches any character (except newline)
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function anyChar(): static
	{
		$this->pattern .= '.';
		return $this;
	}

	/**
	 * Append a regex pattern that matches any character zero or more times
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function digit(): static
	{
		$this->pattern .= '\d';
		return $this;
	}

	/**
	 * Append a regex pattern that matches one or more digits
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function digits(): static
	{
		$this->pattern .= '\d+';
		return $this;
	}

	/**
	 * Append a regex pattern that matches any non-digit character
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function nonDigit(): static
	{
		$this->pattern .= '\D';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a word character (alphanumeric or underscore)
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function word(): static
	{
		$this->pattern .= '\w';
		return $this;
	}

	/**
	 * Append a regex pattern that matches one or more word characters
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function words(): static
	{
		$this->pattern .= '\w+';
		return $this;
	}

	/**
	 * Append a regex pattern that matches any non-word character
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function nonWord(): static
	{
		$this->pattern .= '\W';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a whitespace character
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function whitespace(): static
	{
		$this->pattern .= '\s';
		return $this;
	}

	/**
	 * Append a regex pattern that matches one or more whitespace characters
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function whitespaces(): static
	{
		$this->pattern .= '\s+';
		return $this;
	}

	/**
	 * Append a regex pattern that matches any non-whitespace character
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function nonWhitespace(): static
	{
		$this->pattern .= '\S';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a word boundary
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function wordBoundary(): static
	{
		$this->pattern .= '\b';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a non-word boundary
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function tab(): static
	{
		$this->pattern .= '\t';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a newline character
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function newline(): static
	{
		$this->pattern .= '\n';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a carriage return
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function carriageReturn(): static
	{
		$this->pattern .= '\r';
		return $this;
	}

	/**
	 * Append a regex pattern that defines a character set, matching any one of the specified characters
	 *
	 * @param string $chars The characters to include in the character set
	 * @return static The current Builder instance for method chaining
	 */
	public function charSet(string $chars): static
	{
		$this->pattern .= '[' . $chars . ']';
		return $this;
	}

	/**
	 * Append a regex pattern that defines a negated character set, matching any character that is not in the specified set
	 *
	 * @param string $chars The characters to exclude from the match
	 * @return static The current Builder instance for method chaining
	 */
	public function negatedCharSet(string $chars): static
	{
		$this->pattern .= '[^' . $chars . ']';
		return $this;
	}

	/**
	 * Append a regex pattern that defines a character range within a character set
	 *
	 * @param string $from The starting character of the range
	 * @param string $to The ending character of the range
	 * @return static The current Builder instance for method chaining
	 */
	public function range(string $from, string $to): static
	{
		$this->pattern .= $from . '-' . $to;
		return $this;
	}

	/**
	 * Append a regex pattern that defines a capturing group with the specified content
	 *
	 * @param string $content The regex pattern for the content to capture within the group
	 * @return static The current Builder instance for method chaining
	 */
	public function group(string $content): static
	{
		$this->pattern .= '(' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that opens a capturing group
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function groupStart(): static
	{
		$this->pattern .= '(';
		return $this;
	}

	/**
	 * Append a regex pattern that closes a capturing group
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function groupEnd(): static
	{
		$this->pattern .= ')';
		return $this;
	}

	/**
	 * Append a regex pattern that defines a non-capturing group, allowing you to group parts of the pattern without capturing them for backreferences
	 *
	 * @param string $content The regex pattern for the content to group
	 * @return static The current Builder instance for method chaining
	 */
	public function nonCapturingGroup(string $content): static
	{
		$this->pattern .= '(?:' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that defines a named capturing group, allowing you to capture a specific portion of the match and reference it by name
	 *
	 * @param string $name The name of the capturing group
	 * @param string $content The regex pattern for the content to capture within the group
	 * @return static The current Builder instance for method chaining
	 */
	public function namedGroup(string $name, string $content): static
	{
		$this->pattern .= '(?P<' . $name . '>' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that performs a positive lookahead assertion, ensuring that the specified content follows the current position in the string
	 *
	 * @param string $content The content that should follow the current position
	 * @return static The current Builder instance for method chaining
	 */
	public function positiveLookahead(string $content): static
	{
		$this->pattern .= '(?=' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that performs a negative lookahead assertion, ensuring that the specified content does not follow the current position in the string
	 *
	 * @param string $content The content that should not follow the current position
	 * @return static The current Builder instance for method chaining
	 */
	public function negativeLookahead(string $content): static
	{
		$this->pattern .= '(?!' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that performs a positive lookbehind assertion, ensuring that the specified content precedes the current position in the string
	 *
	 * @param string $content The content that should precede the current position
	 * @return static The current Builder instance for method chaining
	 */
	public function positiveLookbehind(string $content): static
	{
		$this->pattern .= '(?<=' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that performs a negative lookbehind assertion, ensuring that the specified content does not precede the current position in the string
	 *
	 * @param string $content The content that should not precede the current position
	 * @return static The current Builder instance for method chaining
	 */
	public function negativeLookbehind(string $content): static
	{
		$this->pattern .= '(?<!' . $content . ')';
		return $this;
	}

	/**
	 * Append a regex pattern that makes the preceding element optional (matches zero or one time)
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function optional(): static
	{
		$this->pattern .= '?';
		return $this;
	}

	/**
	 * Append a regex pattern that matches zero or more occurrences of the preceding element
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function zeroOrMore(): static
	{
		$this->pattern .= '*';
		return $this;
	}

	/**
	 * Append a regex pattern that matches one or more occurrences of the preceding element
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function oneOrMore(): static
	{
		$this->pattern .= '+';
		return $this;
	}

	/**
	 * Append a regex pattern that makes the preceding element optional (matches zero or one time)
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function lazy(): static
	{
		$this->pattern .= '?';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a specific number of occurrences
	 *
	 * @param int $count The exact number of occurrences to match
	 * @return static The current Builder instance for method chaining
	 */
	public function times(int $count): static
	{
		$this->pattern .= '{' . $count . '}';
		return $this;
	}

	/**
	 * Append a regex pattern that matches a specific number of occurrences between a minimum and maximum
	 *
	 * @param int $min The minimum number of occurrences to match
	 * @param int $max The maximum number of occurrences to match
	 * @return static The current Builder instance for method chaining
	 */
	public function between(int $min, int $max): static
	{
		$this->pattern .= '{' . $min . ',' . $max . '}';
		return $this;
	}

	/**
	 * Append a regex pattern that matches at least a minimum number of occurrences
	 *
	 * @param int $min The minimum number of occurrences to match
	 * @return static The current Builder instance for method chaining
	 */
	public function atLeast(int $min): static
	{
		$this->pattern .= '{' . $min . ',}';
		return $this;
	}

	/**
	 * Append an alternation to the regex pattern, allowing for multiple possible matches
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function or(): static
	{
		$this->pattern .= '|';
		return $this;
	}

	/**
	 * Append a backreference to the regex pattern, allowing you to reference a previously defined capturing group by its index
	 *
	 * @param int $group The index of the capturing group to reference (starting from 1)
	 * @return static The current Builder instance for method chaining
	 */
	public function backreference(int $group): static
	{
		$this->pattern .= '\\' . $group;
		return $this;
	}

	/**
	 * Append a named backreference to the regex pattern, allowing you to reference a previously defined named group
	 *
	 * @param string $name The name of the group to reference
	 * @return static The current Builder instance for method chaining
	 */
	public function namedBackreference(string $name): static
	{
		$this->pattern .= '(?P=' . $name . ')';
		return $this;
	}

	/**
	 * Append the case-insensitive modifier to the regex pattern, making it match letters in a case-insensitive manner
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function caseInsensitive(): static
	{
		if (strpos($this->modifiers, 'i') === false) {
			$this->modifiers .= 'i';
		}
		return $this;
	}

	/**
	 * Append the multiline modifier to the regex pattern, allowing ^ and $ to match the start and end of lines
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function multiline(): static
	{
		if (strpos($this->modifiers, 'm') === false) {
			$this->modifiers .= 'm';
		}
		return $this;
	}

	/**
	 * Append the dot-all modifier to the regex pattern, allowing the dot (.) to match newline characters
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function dotAll(): static
	{
		if (strpos($this->modifiers, 's') === false) {
			$this->modifiers .= 's';
		}
		return $this;
	}

	/**
	 * Append the Unicode modifier to the regex pattern, enabling support for Unicode characters
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function unicode(): static
	{
		if (strpos($this->modifiers, 'u') === false) {
			$this->modifiers .= 'u';
		}
		return $this;
	}

	/**
	 * Append the extended modifier to the regex pattern, allowing for whitespace and comments
	 *
	 * @return static The current Builder instance for method chaining
	 */
	public function extended(): static
	{
		if (strpos($this->modifiers, 'x') === false) {
			$this->modifiers .= 'x';
		}
		return $this;
	}

	/**
	 * Set the regex delimiter
	 *
	 * @param string $delimiter The delimiter to use for the regex pattern
	 * @return static The current Builder instance for method chaining
	 */
	public function setDelimiter(string $delimiter): static
	{
		$this->delimiter = $delimiter;
		return $this;
	}

	/**
	 * Get the current regex pattern without delimiters and modifiers
	 *
	 * @return string The current regex pattern
	 */
	public function getPattern(): string
	{
		return $this->pattern;
	}

	/**
	 * Build the complete regex pattern with delimiters and modifiers
	 *
	 * @return string The complete regex pattern ready for use in regex functions
	 */
	public function build(): string
	{
		return $this->delimiter . $this->pattern . $this->delimiter . $this->modifiers;
	}

	/**
	 * Test if the regex pattern matches the given subject string
	 *
	 * @param string $subject The string to test against the regex pattern
	 * @return bool True if the pattern matches the subject, false otherwise
	 */
	public function test(string $subject): bool
	{
		return (bool)preg_match($this->build(), $subject);
	}

	/**
	 * Get the first match of the regex pattern in the subject string
	 *
	 * @param string $subject The string to search for a match
	 * @return ArrayResult An ArrayResult instance containing the match and related information
	 */
	public function match(string $subject): ArrayResult
	{
		$result = Executor::match($this->build(), $subject);
		return (new ArrayResult())->getSingleton($result);
	}

	/**
	 * Get all matches of the regex pattern in the subject string
	 *
	 * @param string $subject The string to search for matches
	 * @return ArrayResult An ArrayResult instance containing the matches and related information
	 */
	public function matchAll(string $subject): ArrayResult
	{
		$result = Executor::matchAll($this->build(), $subject);
		return (new ArrayResult())->getSingleton($result);
	}

	/**
	 * Replace occurrences of the regex pattern in the subject string with a replacement string
	 *
	 * @param string $replacement The string to replace matches with
	 * @param string $subject The string to perform the replacement on
	 * @return string|null The resulting string after replacements, or null on failure
	 */
	public function replace(string $replacement, string $subject): string|null
	{
		return preg_replace($this->build(), $replacement, $subject);
	}

	/**
	 * Split the subject string using the built regex pattern
	 *
	 * @param string $subject The string to split
	 * @param int $limit The maximum number of splits (default is -1 for no limit)
	 * @param int $flags Optional flags for preg_split (default is PREG_SPLIT_NO_EMPTY)
	 * @return array|false An array of split strings or false on failure
	 */
	public function split(string $subject, int $limit = -1, int $flags = PREG_SPLIT_NO_EMPTY): array|false
	{
		return preg_split($this->build(), $subject, $limit, $flags);
	}

	/**
	 * Convert the Builder instance to a string representation of the regex pattern
	 * @return string The complete regex pattern with delimiters and modifiers
	 */
	public function __toString(): string
	{
		return $this->build();
	}
}
