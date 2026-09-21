<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder\Traits;

use Clover\Classes\Database\SqlIdentifier;
use InvalidArgumentException;
use function count;
use function in_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Trait JoinTrait
 *
 * A trait for adding JOIN functionality to query builders.
 *
 * Table names and the two sides of an ON condition are validated and quoted by
 * {@see SqlIdentifier}; the join type and the AND/OR connector are matched against closed
 * whitelists. Only {@see joinSub()} emits an unvalidated fragment, and only the subquery text
 * itself — its alias is quoted like any other identifier.
 */
trait JoinTrait
{
    /**
     * Join types this builder is willing to emit.
     *
     * @var list<string>
     */
    private static $joinTypes = [
        'INNER',
        'LEFT',
        'LEFT OUTER',
        'RIGHT',
        'RIGHT OUTER',
        'FULL',
        'FULL OUTER',
        'CROSS',
        'STRAIGHT_JOIN',
        'NATURAL',
        'NATURAL JOIN',
    ];

    protected $joins = [];

    /**
     * Add JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     * @param string $type
     *
     * @return self
     *
     * @throws InvalidArgumentException When an identifier, the operator or the join type is not
     *                                  accepted.
     */
    public function join($table, $first, $operator = null, $second = null, $type = 'INNER'): self
    {
        $table = empty($table) ? '' : SqlIdentifier::quote((string) $table);

        return $this->addJoinExpression($table, $first, $operator, $second, $type);
    }

    /**
     * Append a JOIN whose table part has already been compiled.
     *
     * Shared by {@see join()}, which quotes a plain table name, and {@see joinSub()}, which builds
     * a `(subquery) AS alias` expression.
     *
     * @param string $table Compiled table expression, already quoted or otherwise trusted.
     * @param string|mixed $first
     * @param string|mixed $operator
     * @param string|mixed $second
     * @param string|mixed $type
     *
     * @return self
     */
    protected function addJoinExpression($table, $first, $operator = null, $second = null, $type = 'INNER'): self
    {
        $query = "";

        if (!empty($table) && !empty($type)) {
            $type = $this->normalizeJoinType((string) $type);
            $query .= "{$type} JOIN {$table}";
        }

        if (!empty($first)) {
            $first = SqlIdentifier::quote((string) $first);
            $query .= " ON {$first}";
        }

        if (!empty($operator) && !empty($second)) {
            $operator = SqlIdentifier::operator((string) $operator);
            $second = SqlIdentifier::quote((string) $second);
            $query .= " {$operator} {$second}";
        }

        if (!empty($query)) {
            $this->joins[] = $query;
        }

        return $this;
    }

    /**
     * Validate a join type against the whitelist.
     *
     * @param string $type
     *
     * @return string The canonical upper-case type.
     *
     * @throws InvalidArgumentException When the type is not one this builder emits.
     */
    private function normalizeJoinType(string $type): string
    {
        $candidate = strtoupper(trim($type));
        $candidate = (string) preg_replace('/\s+/', ' ', $candidate);

        if (!in_array($candidate, self::$joinTypes, true)) {
            throw new InvalidArgumentException(sprintf(
                'The join type `%s` is not supported.',
                $type
            ));
        }

        return $candidate;
    }

    /**
     * Validate the AND/OR connector joining two ON conditions.
     *
     * @param string $boolean
     *
     * @return string
     *
     * @throws InvalidArgumentException When the connector is neither AND nor OR.
     */
    private function normalizeJoinBoolean(string $boolean): string
    {
        $candidate = strtoupper(trim($boolean));

        if (!in_array($candidate, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException(sprintf(
                'The clause connector `%s` is not supported; use AND or OR.',
                $boolean
            ));
        }

        return $candidate;
    }

    /**
     * Add INNER JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function innerJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'INNER');
    }

    /**
     * Add LEFT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function leftJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function rightJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add STRAIGHT JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function straightJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'STRAIGHT_JOIN');
    }

    /**
     * Add NATURAL JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function naturalJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'NATURAL JOIN');
    }

    /**
     * Add FULL OUTER JOIN clause
     *
     * @param string $table
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function fullOuterJoin($table, $first, $operator = null, $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'FULL OUTER');
    }

    /**
     * Add CROSS JOIN clause
     *
     * @param string $table
     *
     * @return self
     *
     * @throws InvalidArgumentException When the table is not a valid identifier.
     */
    public function crossJoin($table): self
    {
        $this->joins[] = 'CROSS JOIN ' . SqlIdentifier::quote((string) $table);
        return $this;
    }

    /**
     * Add JOIN with subquery
     *
     * The subquery is emitted untouched; only its alias is validated.
     *
     * @param string $query
     * @param string $alias
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     * @param string $type
     *
     * @return self
     */
    public function joinSub($query, $alias, $first, $operator = null, $second = null, $type = 'INNER'): self
    {
        $table = "({$query}) AS " . SqlIdentifier::quote((string) $alias);

        return $this->addJoinExpression($table, $first, $operator, $second, $type);
    }

    /**
     * Add INNER JOIN with subquery
     *
     * @param string $query
     * @param string $alias
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function leftJoinSub($query, $alias, $first, $operator = null, $second = null): self
    {
        return $this->joinSub($query, $alias, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN with subquery
     *
     * @param string $query
     * @param string $alias
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function rightJoinSub($query, $alias, $first, $operator = null, $second = null): self
    {
        return $this->joinSub($query, $alias, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Add ON condition to the last JOIN clause
     *
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When an identifier, the operator or the connector is not
     *                                  accepted.
     */
    public function on($first, $operator, $second, $boolean = 'AND'): self
    {
        $boolean = $this->normalizeJoinBoolean((string) $boolean);
        $first = SqlIdentifier::quote((string) $first);
        $operator = SqlIdentifier::operator((string) $operator);
        $second = SqlIdentifier::quote((string) $second);

        if (!empty($this->joins)) {
            $lastJoin = &$this->joins[count($this->joins) - 1];
            $lastJoin .= " {$boolean} {$first} {$operator} {$second}";
        }

        return $this;
    }

    /**
     * Add OR ON condition to the last JOIN clause
     *
     * @param string $first
     * @param string|mixed $operator
     * @param string|mixed $second
     *
     * @return self
     */
    public function orOn($first, $operator, $second): self
    {
        return $this->on($first, $operator, $second, 'OR');
    }

    /**
     * Add WHERE condition to the last JOIN clause
     *
     * The value is still inlined rather than bound; callers must not pass untrusted values.
     *
     * @param string $column
     * @param string|mixed $operator
     * @param string|mixed $value
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column or operator is not accepted.
     */
    public function whereOn($column, $operator, $value): self
    {
        $column = SqlIdentifier::quote((string) $column);
        $operator = SqlIdentifier::operator((string) $operator);

        if (!empty($this->joins)) {
            $lastJoin = &$this->joins[count($this->joins) - 1];
            $value = is_string($value) ? "'{$value}'" : $value;
            $lastJoin .= " AND {$column} {$operator} {$value}";
        }

        return $this;
    }

    /**
     * Add OR WHERE condition to the last JOIN clause
     *
     * The value is still inlined rather than bound; callers must not pass untrusted values.
     *
     * @param string $column
     * @param string|mixed $operator
     * @param string|mixed $value
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column or operator is not accepted.
     */
    public function orWhereOn($column, $operator, $value): self
    {
        $column = SqlIdentifier::quote((string) $column);
        $operator = SqlIdentifier::operator((string) $operator);

        if (!empty($this->joins)) {
            $lastJoin = &$this->joins[count($this->joins) - 1];
            $value = is_string($value) ? "'{$value}'" : $value;
            $lastJoin .= " OR {$column} {$operator} {$value}";
        }

        return $this;
    }
}
