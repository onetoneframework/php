<?php

declare(strict_types=1);

namespace Clover\Classes\GraphQL;

use function ctype_alpha;
use function ctype_alnum;
use function is_numeric;
use function strlen;
use function strtolower;
use function trim;

/**
 * Class GraphQLParser
 * @package Clover\Classes\GraphQL
 */
final class GraphQLParser
{
	/** @var string $query The GraphQL query being parsed */
	private string $query = '';
	/** @var int $position The current position in the query string during parsing */
	private int $position = 0;
	/** @var int $length The total length of the query string */
	private int $length = 0;

	/**
	 * Parses a GraphQL query string into a GraphQLDocument object.
	 * 
	 * @param string $query The GraphQL query string to parse
	 * @return GraphQLDocument The parsed GraphQL document
	 * @throws GraphQLParseException If the query is invalid or cannot be parsed
	 */
	public function parse(string $query): GraphQLDocument
	{
		$this->query = $query;
		$this->position = 0;
		$this->length = strlen($query);

		$this->skipWhitespace();
		$operationType = 'query';
		$name = $this->readName();
		if ($name !== null && ($name === 'query' || $name === 'mutation')) {
			$operationType = $name;
		} elseif ($name !== null) {
			$this->position = 0;
		}

		$this->skipWhitespace();
		$selections = $this->parseSelectionSet();
		$this->skipWhitespace();
		if ($this->position < $this->length) {
			throw new GraphQLParseException('Unexpected token after selection set.');
		}

		return new GraphQLDocument($operationType, $selections);
	}

	/**
	 * Parses a selection set, which is a group of field selections enclosed in curly braces.
	 * 
	 * @return array<int, GraphQLFieldSelection>
	 */
	private function parseSelectionSet(): array
	{
		$this->expectChar('{');
		$selections = [];
		while (true) {
			$this->skipWhitespace();
			if ($this->peekChar() === '}') {
				$this->position++;
				break;
			}

			$selections[] = $this->parseFieldSelection();
		}

		return $selections;
	}

	/**
	 * Parses a single field selection, which may include arguments and child selections.
	 * 
	 * @return GraphQLFieldSelection The parsed field selection
	 * @throws GraphQLParseException If the field selection is invalid or cannot be parsed
	 */
	private function parseFieldSelection(): GraphQLFieldSelection
	{
		$name = $this->readName();
		if ($name === null) {
			throw new GraphQLParseException('Expected field name.');
		}

		$this->skipWhitespace();
		$arguments = [];
		if ($this->peekChar() === '(') {
			$arguments = $this->parseArguments();
		}

		$this->skipWhitespace();
		$children = [];
		if ($this->peekChar() === '{') {
			$children = $this->parseSelectionSet();
		}

		return new GraphQLFieldSelection($name, $arguments, $children);
	}

	/**
	 * Parses a list of arguments for a field selection, which are key-value pairs enclosed in parentheses.
	 * 
	 * @return array<string, mixed> An associative array of argument names to their parsed values
	 * @throws GraphQLParseException If the arguments are invalid or cannot be parsed
	 */
	private function parseArguments(): array
	{
		$this->expectChar('(');
		$arguments = [];
		while (true) {
			$this->skipWhitespace();
			if ($this->peekChar() === ')') {
				$this->position++;
				break;
			}

			$key = $this->readName();
			if ($key === null) {
				throw new GraphQLParseException('Expected argument name.');
			}
			$this->skipWhitespace();
			$this->expectChar(':');
			$this->skipWhitespace();
			$arguments[$key] = $this->parseValue();
			$this->skipWhitespace();
			if ($this->peekChar() === ',') {
				$this->position++;
			}
		}

		return $arguments;
	}

	/**
	 * Parses a value, which can be a string, number, boolean, null, or enum.
	 * 
	 * @return mixed The parsed value
	 * @throws GraphQLParseException If the value is invalid or cannot be parsed
	 */
	private function parseValue(): mixed
	{
		$char = $this->peekChar();
		if ($char === '"') {
			return $this->parseString();
		}
		if ($char === '-' || ($char !== null && ctype_digit($char))) {
			return $this->parseNumber();
		}

		$name = $this->readName();
		if ($name === null) {
			throw new GraphQLParseException('Expected value.');
		}

		$lower = strtolower($name);
		if ($lower === 'true') {
			return true;
		}
		if ($lower === 'false') {
			return false;
		}
		if ($lower === 'null') {
			return null;
		}

		return $name;
	}

	/**
	 * Parses a string value, handling escape sequences and ensuring proper termination.
	 * 
	 * @return string The parsed string value
	 * @throws GraphQLParseException If the string is unterminated or contains invalid escape sequences
	 */
	private function parseString(): string
	{
		$this->expectChar('"');
		$value = '';
		while (true) {
			$char = $this->peekChar();
			if ($char === null) {
				throw new GraphQLParseException('Unterminated string.');
			}
			if ($char === '"') {
				$this->position++;
				break;
			}
			if ($char === '\\') {
				$this->position++;
				$escaped = $this->peekChar();
				if ($escaped === null) {
					throw new GraphQLParseException('Invalid escaped sequence.');
				}
				$value .= $escaped;
				$this->position++;
				continue;
			}
			$value .= $char;
			$this->position++;
		}

		return $value;
	}

	/**
	 * Parses a number value, which can be an integer or a floating-point number, and handles optional negative sign.
	 * 
	 * @return int|float The parsed number value
	 * @throws GraphQLParseException If the number format is invalid
	 */
	private function parseNumber(): int|float
	{
		$start = $this->position;
		if ($this->peekChar() === '-') {
			$this->position++;
		}
		while (($char = $this->peekChar()) !== null && (ctype_digit($char) || $char === '.')) {
			$this->position++;
		}

		$token = trim(substr($this->query, $start, $this->position - $start));
		if (!is_numeric($token)) {
			throw new GraphQLParseException('Invalid number format.');
		}

		return str_contains($token, '.') ? (float) $token : (int) $token;
	}

	/**
	 * Reads a name token, which must start with a letter or underscore and can contain letters, digits, and underscores.
	 * 
	 * @return string|null The read name, or null if the next token is not a valid name
	 */
	private function readName(): ?string
	{
		$this->skipWhitespace();
		$char = $this->peekChar();
		if ($char === null || !($char === '_' || ctype_alpha($char))) {
			return null;
		}

		$start = $this->position;
		$this->position++;
		while (($next = $this->peekChar()) !== null && ($next === '_' || ctype_alnum($next))) {
			$this->position++;
		}

		return substr($this->query, $start, $this->position - $start);
	}

	/**
	 * Skips over whitespace characters and comments in the query string, advancing the position until a non-whitespace character is found.
	 */
	private function skipWhitespace(): void
	{
		while ($this->position < $this->length) {
			$char = $this->query[$this->position];
			if ($char === '#') {
				while ($this->position < $this->length && $this->query[$this->position] !== "\n") {
					$this->position++;
				}
				continue;
			}
			if (!ctype_space($char)) {
				break;
			}
			$this->position++;
		}
	}

	/**
	 * Expects the next character in the query string to match the given expected character, and advances the position if it does.
	 * 
	 * @param string $expected The character that is expected at the current position
	 * @throws GraphQLParseException If the actual character does not match the expected character
	 */
	private function expectChar(string $expected): void
	{
		$actual = $this->peekChar();
		if ($actual !== $expected) {
			throw new GraphQLParseException('Expected `' . $expected . '`, got `' . ($actual ?? 'EOF') . '`.');
		}
		$this->position++;
	}

	/**
	 * Peeks at the next character in the query string without advancing the position, returning null if the end of the string is reached.
	 * 
	 * @return string|null The next character, or null if at the end of the string
	 */
	private function peekChar(): ?string
	{
		if ($this->position >= $this->length) {
			return null;
		}

		return $this->query[$this->position];
	}
}
