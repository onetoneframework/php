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

/**
 * Trait OrderByTrait
 *
 * A trait for adding ORDER BY functionality to query builders.
 *
 * Every column reaching {@see orderBy()} is validated and quoted by {@see SqlIdentifier}, and every
 * direction is matched against the ASC/DESC whitelist, so an expression that is not a plain
 * identifier — `RAND()`, `FIELD(id, 3, 1, 2)` — is rejected there.
 *
 * {@see orderByRaw()} is the door for those expressions, and it is dangerous: its argument is put
 * into the statement untouched, with no quoting, validation or escaping, so it must never be given
 * a string that came from user input. For a plain column name use {@see orderBy()}, which
 * validates both the column and the direction. {@see orderByRandom()} also emits an expression,
 * but a fixed literal rather than caller input.
 */
trait OrderByTrait
{
    protected $orders = [];

    /**
     * Add ORDER BY clause with condition
     *
     * @param string $column
     * @param string $operator
     * @param string|null $value
     * @param string $direction
     *
     * @return self
     *
     * @throws \InvalidArgumentException When the column, operator or direction is not accepted.
     */
    public function orderByCondition(string $column, string $operator = '=', ?string $value = null, string $direction = 'ASC'): self
    {
        $direction = SqlIdentifier::direction($direction);
        $column = SqlIdentifier::quote($column);
        $operator = SqlIdentifier::operator($operator);

        $this->orders[] = "({$column} {$operator} {$value}) {$direction}";
        return $this;
    }

    /**
     * Add ORDER BY clause
     *
     * @param string $column
     * @param string $direction
     *
     * @return self
     *
     * @throws \InvalidArgumentException When the column is not a valid identifier or the direction
     *                                   is neither ASC nor DESC.
     */
    public function orderBy($column, $direction = 'ASC'): self
    {
        $direction = SqlIdentifier::direction((string) $direction);
        $column = SqlIdentifier::quote((string) $column);

        $this->orders[] = "{$column} {$direction}";
        return $this;
    }

    /**
     * Add ORDER BY DESC clause
     *
     * @param string $column
     *
     * @return self
     */
    public function orderByDesc($column): self
    {
        return $this->orderBy($column, 'DESC');
    }

    /**
     * Add ORDER BY ASC clause
     *
     * @param string $column
     *
     * @return self
     */
    public function orderByAsc($column): self
    {
        return $this->orderBy($column, 'ASC');
    }

    /**
     * Add a raw ORDER BY expression
     *
     * The expression is stored verbatim and written into the statement verbatim: neither the
     * expression nor any direction inside it is quoted, validated or escaped. It must therefore
     * never contain user input, directly or by interpolation — a raw expression built from a
     * request parameter is a SQL injection, and sorting is a common place for one to arrive. Use
     * it for the custom ordering {@see orderBy()} cannot express, such as `FIELD(id, 3, 1, 2)`;
     * for a plain column name use {@see orderBy()} instead, because it validates both the column
     * and the direction.
     *
     * @param string $expression The trusted SQL fragment, emitted untouched.
     *
     * @return self
     */
    public function orderByRaw(string $expression): self
    {
        $this->orders[] = $expression;

        return $this;
    }

    /**
     * Add ORDER BY RANDOM clause
     *
     * @return self
     */
    public function orderByRandom(): self
    {
        $this->orders[] = 'RAND()';
        return $this;
    }

    /**
     * Alias for orderByRandom()
     *
     * @return self
     */
    public function inRandomOrder(): self
    {
        return $this->orderByRandom();
    }

    /**
     * Clear existing ORDER BY clauses and set a new one
     *
     * @param string|null $column
     * @param string $direction
     *
     * @return self
     */
    public function reorder($column = null, $direction = 'ASC'): self
    {
        $this->orders = [];

        if ($column !== null) {
            return $this->orderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Remove all ORDER BY clauses
     *
     * @return self
     */
    public function withoutOrderBy(): self
    {
        $this->orders = [];
        return $this;
    }
}
