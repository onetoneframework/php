<?php

declare(strict_types=1);

namespace Clover\Classes\Database;

use InvalidArgumentException;
use function explode;
use function implode;
use function in_array;
use function preg_match;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function substr_count;
use function trim;

/**
 * Validation and quoting for the parts of a SQL statement that cannot be bound as parameters.
 *
 * Column names, table names, operators and sort directions are structure, not data, so a prepared
 * statement has no placeholder for them. Everywhere the query builders interpolate one of those
 * into a statement, it goes through this class first: identifiers are checked against a strict
 * character set and wrapped in backticks, operators and directions are matched against a closed
 * whitelist. Anything else raises {@see InvalidArgumentException} rather than reaching the driver.
 *
 * The character set is deliberately narrower than what MySQL accepts inside backticks. A quoted
 * identifier may legally contain almost any character, including a backtick doubled to escape
 * itself, but accepting that would mean the safety of every call site depended on escaping being
 * applied exactly once. Restricting the input to `[A-Za-z0-9_]` (plus a single dot separating a
 * qualifier from its column) makes the escaping question moot: no accepted identifier contains a
 * character that is special inside backticks.
 *
 * This class covers the builder methods that take an identifier as data. The `*Raw` family
 * (`whereRaw`, `orderByRaw`, `havingRaw`, `selectRaw`, `groupByRaw`) is exempt by definition — its
 * whole purpose is to pass an expression through untouched, and it is documented as
 * trusted-input-only on the classes that expose it.
 *
 * @package Clover\Classes\Database
 */
final class SqlIdentifier
{
    /**
     * Comparison operators a caller may supply.
     *
     * Anything that is not on this list — including operators MySQL would accept, such as `<=>`
     * or `RLIKE` — is rejected. A builder that needs one of those has `whereRaw()`.
     *
     * @var list<string>
     */
    private const OPERATORS = [
        '=',
        '!=',
        '<>',
        '<',
        '<=',
        '>',
        '>=',
        'LIKE',
        'NOT LIKE',
        'IN',
        'NOT IN',
        'IS',
        'IS NOT',
    ];

    /** @var list<string> The only sort directions that may follow an ORDER BY expression. */
    private const DIRECTIONS = ['ASC', 'DESC'];

    /** The character set a single identifier segment may be built from. */
    private const SEGMENT_PATTERN = '/^[A-Za-z0-9_]+$/D';

    /**
     * The only characters stripped from around an identifier before it is validated.
     *
     * Deliberately narrower than PHP's default trim set, which also strips NUL, vertical tab and
     * newlines — stripping those would silently normalise `"id\0"` into a valid `id` instead of
     * rejecting it. Padding a column name with a space or a tab is an ordinary typo; embedding a
     * control character is not, and must fail validation rather than be cleaned up.
     */
    private const TRIMMED_CHARACTERS = " \t";

    /**
     * Validate an identifier and return it wrapped in backticks.
     *
     * Accepts a bare name (`title`) or one qualifier and a name (`posts.title`); the qualifier may
     * be a table name or an alias. Each segment is quoted separately, so `posts.title` becomes
     * `` `posts`.`title` `` and not `` `posts.title` ``, which MySQL would read as a single column
     * whose name contains a dot.
     *
     * @param string $identifier The unquoted column, table or alias name.
     *
     * @return string The backtick-quoted identifier.
     *
     * @throws InvalidArgumentException When the identifier is empty, over-qualified, or contains a
     *                                  character outside `[A-Za-z0-9_]`.
     */
    public static function quote(string $identifier): string
    {
        $candidate = trim($identifier, self::TRIMMED_CHARACTERS);

        if ($candidate === '') {
            throw new InvalidArgumentException('A SQL identifier must not be empty.');
        }

        if (substr_count($candidate, '.') > 1) {
            throw new InvalidArgumentException(sprintf(
                'The SQL identifier `%s` is over-qualified; at most one qualifier is allowed.',
                $identifier
            ));
        }

        $quoted = [];

        foreach (explode('.', $candidate) as $segment) {
            $segment = trim($segment, self::TRIMMED_CHARACTERS);

            if (preg_match(self::SEGMENT_PATTERN, $segment) !== 1) {
                throw new InvalidArgumentException(sprintf(
                    'The SQL identifier `%s` is not a valid name; only letters, digits and underscores are allowed.',
                    $identifier
                ));
            }

            $quoted[] = '`' . $segment . '`';
        }

        return implode('.', $quoted);
    }

    /**
     * Validate and quote a list of identifiers, returning them as a comma-separated clause.
     *
     * @param iterable<string> $identifiers The unquoted names.
     *
     * @return string The quoted names joined by `, `.
     *
     * @throws InvalidArgumentException When the list is empty or any member is not a valid name.
     */
    public static function quoteList(iterable $identifiers): string
    {
        $quoted = [];

        foreach ($identifiers as $identifier) {
            $quoted[] = self::quote($identifier);
        }

        if ($quoted === []) {
            throw new InvalidArgumentException('A SQL identifier list must not be empty.');
        }

        return implode(', ', $quoted);
    }

    /**
     * Validate a comparison operator and return it in canonical upper-case form.
     *
     * Internal runs of whitespace are collapsed to one space, so `not   like` and `NOT LIKE` are
     * the same operator.
     *
     * @param string $operator The caller-supplied operator.
     *
     * @return string The canonical operator.
     *
     * @throws InvalidArgumentException When the operator is not on the whitelist.
     */
    public static function operator(string $operator): string
    {
        $candidate = strtoupper(trim($operator, self::TRIMMED_CHARACTERS));
        $candidate = (string) preg_replace('/\s+/', ' ', $candidate);

        if (!in_array($candidate, self::OPERATORS, true)) {
            throw new InvalidArgumentException(sprintf(
                'The comparison operator `%s` is not supported.',
                $operator
            ));
        }

        return $candidate;
    }

    /**
     * Validate a sort direction and return it in canonical upper-case form.
     *
     * @param string $direction The caller-supplied direction.
     *
     * @return string Either `ASC` or `DESC`.
     *
     * @throws InvalidArgumentException When the direction is neither ascending nor descending.
     */
    public static function direction(string $direction): string
    {
        $candidate = strtoupper(trim($direction, self::TRIMMED_CHARACTERS));

        if (!in_array($candidate, self::DIRECTIONS, true)) {
            throw new InvalidArgumentException(sprintf(
                'The sort direction `%s` is not supported; use ASC or DESC.',
                $direction
            ));
        }

        return $candidate;
    }

    /**
     * Report whether an identifier would be accepted, without raising.
     *
     * Useful where a builder wants to fall back to a raw expression path rather than fail, and in
     * tests that assert the boundary of the accepted set.
     *
     * @param string $identifier The unquoted name.
     *
     * @return bool True when {@see quote()} would succeed.
     */
    public static function isValid(string $identifier): bool
    {
        try {
            self::quote($identifier);
        } catch (InvalidArgumentException) {
            return false;
        }

        return true;
    }
}
