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
use function func_num_args;
use function in_array;
use function is_array;
use function is_string;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Trait HavingTrait
 *
 * A trait for adding HAVING functionality to query builders.
 *
 * The mirror of {@see WhereTrait}: columns, operators and connectors are validated and quoted as
 * the clause is recorded. HAVING is frequently written against an aggregate (`COUNT(*)`), which is
 * not an identifier and so is not accepted by {@see having()}: either give the aggregate an alias
 * in the SELECT list and use the alias here, or write the whole condition with {@see havingRaw()}.
 *
 * {@see havingRaw()} is dangerous and is the only unvalidated door in this trait. Its expression
 * reaches the statement exactly as it was supplied — nothing is quoted, checked or escaped — so it
 * must never be given a string that originated in user input. {@see having()} is the validated
 * sibling to use for a plain column name. The AND/OR connector is not part of the raw expression
 * and is still checked against the whitelist.
 */
trait HavingTrait
{
    protected $havings = [];

    /**
     * Add HAVING clause
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed $value
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column, operator or connector is not accepted.
     */
    public function having(string $column, string|null $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }

        $this->havings[] = [
            'type' => 'basic',
            'column' => SqlIdentifier::quote($column),
            'operator' => SqlIdentifier::operator((string) ($operator ?? '=')),
            'value' => $value,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add OR HAVING clause
     *
     * @param string $column
     * @param string|null $operator
     * @param mixed $value
     *
     * @return self
     */
    public function orHaving(string $column, string|null $operator = null, mixed $value = null): self
    {
        return $this->having($column, $operator, $value, 'OR');
    }

    /**
     * Add a raw HAVING expression
     *
     * The expression is stored verbatim and written into the statement verbatim: no column is
     * quoted, no operator is checked against the whitelist and no value is escaped. It must
     * therefore never contain user input, directly or by interpolation — a raw expression built
     * from a request parameter is a SQL injection. This is the method for the aggregate condition
     * HAVING exists for, `havingRaw('COUNT(*) > 5')`; for a plain column name use {@see having()}
     * instead, because it validates what it is given.
     *
     * The connector is not part of the expression and is still validated.
     *
     * @param string $expression The trusted SQL fragment, emitted untouched.
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the connector is neither AND nor OR.
     */
    public function havingRaw(string $expression, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'type' => 'raw',
            'expression' => $expression,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING IN clause
     *
     * @param string $column
     * @param array|string $values
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column is not a valid identifier.
     */
    public function havingIn(string $column, array|string $values, string $boolean = 'AND'): self
    {
        if (is_array($values)) {
            $values = '(' . implode(', ', array_map(function ($v) {
                return is_string($v) ? "'{$v}'" : $v;
            }, $values)) . ')';
        }

        $this->havings[] = [
            'type' => 'in',
            'column' => SqlIdentifier::quote($column),
            'values' => $values,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING NOT IN clause
     *
     * @param string $column
     * @param array|string $values
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column is not a valid identifier.
     */
    public function havingNotIn(string $column, array|string $values, string $boolean = 'AND'): self
    {
        if (is_array($values)) {
            $values = '(' . implode(', ', array_map(function ($v) {
                return is_string($v) ? "'{$v}'" : $v;
            }, $values)) . ')';
        }

        $this->havings[] = [
            'type' => 'not_in',
            'column' => SqlIdentifier::quote($column),
            'values' => $values,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING BETWEEN clause
     *
     * @param string $column
     * @param mixed $value1
     * @param mixed $value2
     * @param string $boolean
     *
     * @return self
     */
    public function havingBetween(string $column, mixed $value1, mixed $value2, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'type' => 'between',
            'column' => SqlIdentifier::quote($column),
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING NOT BETWEEN clause
     *
     * @param string $column
     * @param mixed $value1
     * @param mixed $value2
     * @param string $boolean
     *
     * @return self
     */
    public function havingNotBetween(string $column, mixed $value1, mixed $value2, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'type' => 'not_between',
            'column' => SqlIdentifier::quote($column),
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING IS NULL clause
     *
     * @param string $column
     * @param string $boolean
     *
     * @return self
     */
    public function havingNull(string $column, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'type' => 'null',
            'column' => SqlIdentifier::quote($column),
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add HAVING IS NOT NULL clause
     *
     * @param string $column
     * @param string $boolean
     *
     * @return self
     */
    public function havingNotNull(string $column, string $boolean = 'AND'): self
    {
        $this->havings[] = [
            'type' => 'not_null',
            'column' => SqlIdentifier::quote($column),
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add grouped HAVING clauses
     *
     * @param callable $callback
     * @param string $boolean
     *
     * @return self
     */
    public function havingGroup(callable $callback, string $boolean = 'AND'): self
    {
        $query = new class {
            private $havings = [];

            public function having($column, $operator = null, $value = null, $boolean = 'AND')
            {
                if (func_num_args() === 2) {
                    $value = $operator;
                    $operator = '=';
                }

                $connector = strtoupper(trim((string) $boolean));

                if (!in_array($connector, ['AND', 'OR'], true)) {
                    throw new InvalidArgumentException(sprintf(
                        'The clause connector `%s` is not supported; use AND or OR.',
                        $boolean
                    ));
                }

                $this->havings[] = [
                    'type' => 'basic',
                    'column' => SqlIdentifier::quote((string) $column),
                    'operator' => SqlIdentifier::operator((string) ($operator ?? '=')),
                    'value' => $value,
                    'boolean' => $connector
                ];
                return $this;
            }

            public function orHaving($column, $operator = null, $value = null)
            {
                return $this->having($column, $operator, $value, 'OR');
            }

            public function buildHavingClause()
            {
                $clauses = [];
                foreach ($this->havings as $index => $having) {
                    $clause = '';
                    if ($index > 0) {
                        $clause .= $having['boolean'] . ' ';
                    }
                    $value = is_string($having['value']) ? "'{$having['value']}'" : $having['value'];
                    $clause .= "{$having['column']} {$having['operator']} {$value}";
                    $clauses[] = $clause;
                }
                return implode(' ', $clauses);
            }
        };

        $callback($query);

        $this->havings[] = [
            'type' => 'group',
            'query' => $query,
            'boolean' => $this->normalizeHavingBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Validate the AND/OR connector between two clauses.
     *
     * @param string $boolean
     *
     * @return string
     *
     * @throws InvalidArgumentException When the connector is neither AND nor OR.
     */
    private function normalizeHavingBoolean(string $boolean): string
    {
        $candidate = strtoupper(trim($boolean));
        $candidate = (string) preg_replace('/\s+/', ' ', $candidate);

        if (!in_array($candidate, ['AND', 'OR'], true)) {
            throw new InvalidArgumentException(sprintf(
                'The clause connector `%s` is not supported; use AND or OR.',
                $boolean
            ));
        }

        return $candidate;
    }

    /**
     * Build the HAVING clause
     *
     * Columns and operators in `$havings` were validated and quoted when the clause was recorded.
     * The `raw` entries were not: they are emitted exactly as {@see havingRaw()} received them.
     *
     * @return string
     */
    protected function buildHavingClause(): string
    {
        $clauses = [];

        foreach ($this->havings as $index => $having) {
            $clause = '';

            if ($index > 0) {
                $clause .= $having['boolean'] . ' ';
            }

            switch ($having['type']) {
                case 'basic':
                    $value = is_string($having['value']) ? "'{$having['value']}'" : $having['value'];
                    $clause .= "{$having['column']} {$having['operator']} {$value}";
                    break;

                case 'raw':
                    // Emitted untouched by design; see havingRaw()'s docblock for the contract.
                    $clause .= $having['expression'];
                    break;

                case 'in':
                    $clause .= "{$having['column']} IN {$having['values']}";
                    break;

                case 'not_in':
                    $clause .= "{$having['column']} NOT IN {$having['values']}";
                    break;

                case 'between':
                    $value1 = is_string($having['value1']) ? "'{$having['value1']}'" : $having['value1'];
                    $value2 = is_string($having['value2']) ? "'{$having['value2']}'" : $having['value2'];
                    $clause .= "{$having['column']} BETWEEN {$value1} AND {$value2}";
                    break;

                case 'not_between':
                    $value1 = is_string($having['value1']) ? "'{$having['value1']}'" : $having['value1'];
                    $value2 = is_string($having['value2']) ? "'{$having['value2']}'" : $having['value2'];
                    $clause .= "{$having['column']} NOT BETWEEN {$value1} AND {$value2}";
                    break;

                case 'null':
                    $clause .= "{$having['column']} IS NULL";
                    break;

                case 'not_null':
                    $clause .= "{$having['column']} IS NOT NULL";
                    break;

                case 'group':
                    $clause .= "({$having['query']->buildHavingClause()})";
                    break;
            }

            $clauses[] = $clause;
        }

        return implode(' ', $clauses);
    }
}
