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
use function is_string;

/**
 * Trait GroupByTrait
 *
 * A trait for adding GROUP BY functionality to query builders.
 *
 * Columns are validated and quoted as they are collected by {@see groupBy()}, so the array the
 * builder renders from holds quoted identifiers and nothing else — unless {@see groupByRaw()} was
 * used. That method is dangerous: it puts its expression into the statement untouched, with no
 * quoting, checking or escaping, so it must never be given a string that came from user input. It
 * is there for expression grouping such as `DATE(created_at)`, which is not an identifier and
 * cannot be validated; for a plain column name use {@see groupBy()}, which does validate it.
 */
trait GroupByTrait
{
    protected $groups = [];

    /**
     * Add GROUP BY clause
     *
     * @param string|array $columns
     *
     * @return self
     *
     * @throws \InvalidArgumentException When any column is not a valid identifier.
     */
    public function groupBy(string|array $columns): self
    {
        if (is_string($columns)) {
            $this->groups[] = SqlIdentifier::quote($columns);

            return $this;
        }

        foreach ($columns as $column) {
            $this->groups[] = SqlIdentifier::quote((string) $column);
        }

        return $this;
    }

    /**
     * Add a raw GROUP BY expression
     *
     * The expression is stored verbatim and written into the statement verbatim: it is not quoted,
     * validated or escaped in any way. It must therefore never contain user input, directly or by
     * interpolation — a raw expression built from a request parameter is a SQL injection. Use it
     * for the expression grouping {@see groupBy()} cannot express, such as `DATE(created_at)`; for
     * a plain column name use {@see groupBy()} instead, because it validates what it is given.
     *
     * @param string $expression The trusted SQL fragment, emitted untouched.
     *
     * @return self
     */
    public function groupByRaw(string $expression): self
    {
        $this->groups[] = $expression;

        return $this;
    }

    /**
     * Remove all GROUP BY clauses
     *
     * @return self
     */
    public function withoutGroupBy(): self
    {
        $this->groups = [];
        return $this;
    }
}
