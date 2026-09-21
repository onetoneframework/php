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
 * Trait WhereTrait
 *
 * A trait for adding WHERE functionality to query builders.
 *
 * Columns, operators and the AND/OR connector are validated by {@see SqlIdentifier} at the moment
 * the clause is recorded, so the entries in `$wheres` already hold quoted identifiers and canonical
 * operators and {@see buildWhereClause()} can interpolate them directly. Values are a separate
 * problem: this builder still inlines them, so callers must not pass untrusted values.
 *
 * {@see whereRaw()} and {@see orWhereRaw()} are the one exception, and they are dangerous. The
 * expression they are given is written into the statement exactly as it arrives — nothing is
 * validated, quoted or escaped — so they must never be handed a string that came from a request,
 * a configuration file, or anywhere else outside the source tree. They exist for the predicates
 * this builder cannot express, such as `LENGTH(name) > 10`; for a plain column name use
 * {@see where()}, which validates it. The AND/OR connector is not part of the raw expression and
 * is still checked against the whitelist.
 */
trait WhereTrait
{
    protected $wheres = [];
    protected $whereBindings = [];

    /**
     * Add WHERE clause
     *
     * @param string $column
     * @param int|string|null $operator
     * @param mixed $value
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column, operator or connector is not accepted.
     */
    public function where(string $column, int|string|null $operator = null, mixed $value = null, string $boolean = 'AND'): self
    {
        $arguments = func_num_args();

        if ($arguments === 2) {
            $value = $operator;
            $operator = '=';
        }

        $column = SqlIdentifier::quote($column);
        $boolean = $this->normalizeWhereBoolean($boolean);

        // The five-argument form is the BETWEEN spelling: `where($column, 'BETWEEN', $low, 'AND',
        // $high)`. Its operator is not rendered, so it is dropped rather than validated against the
        // comparison whitelist, which has no BETWEEN in it.
        if ($arguments === 5) {
            $this->wheres[] = [
                'type' => 'between',
                'column' => $column,
                'value1' => $value,
                'boolean' => $boolean,
                'value2' => func_get_arg(4)
            ];

            return $this;
        }

        $this->wheres[] = [
            'type' => 'basic',
            'column' => $column,
            'operator' => SqlIdentifier::operator((string) ($operator ?? '=')),
            'value' => $value,
            'boolean' => $boolean
        ];

        return $this;
    }

    /**
     * Add OR WHERE clause
     *
     * @param string $column
     * @param string|mixed $operator
     * @param string|mixed $value
     *
     * @return self
     */
    public function orWhere($column, $operator = null, $value = null): self
    {
        return $this->where($column, $operator, $value, 'OR');
    }

    /**
     * Add a raw WHERE expression
     *
     * The expression is stored verbatim and written into the statement verbatim: no column is
     * quoted, no operator is checked against the whitelist and no value is escaped. It must
     * therefore never contain user input, directly or by interpolation — a raw expression built
     * from a request parameter is a SQL injection. Use it only for the predicates {@see where()}
     * cannot express, such as `LENGTH(name) > 10` or `id <=> NULL`; for a plain column name use
     * {@see where()} instead, because it validates what it is given.
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
    public function whereRaw(string $expression, string $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'raw',
            'expression' => $expression,
            'boolean' => $this->normalizeWhereBoolean($boolean)
        ];

        return $this;
    }

    /**
     * Add a raw OR WHERE expression
     *
     * Carries every warning on {@see whereRaw()}: the expression is emitted into the statement
     * untouched and must never come from user input. {@see orWhere()} is the validated sibling to
     * use for a plain column name.
     *
     * @param string $expression The trusted SQL fragment, emitted untouched.
     *
     * @return self
     */
    public function orWhereRaw(string $expression): self
    {
        return $this->whereRaw($expression, 'OR');
    }

    /**
     * Add WHERE IN clause
     *
     * @param string $column
     * @param array|string $values
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column is not a valid identifier.
     */
    public function whereIn($column, $values, $boolean = 'AND'): self
    {
        if (is_array($values)) {
            $values = '(' . implode(', ', array_map(function ($v) {
                return is_string($v) ? "'{$v}'" : $v;
            }, $values)) . ')';
        }

        $this->wheres[] = [
            'type' => 'in',
            'column' => SqlIdentifier::quote((string) $column),
            'values' => $values,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE NOT IN clause
     *
     * @param string $column
     * @param array|string $values
     * @param string $boolean
     *
     * @return self
     *
     * @throws InvalidArgumentException When the column is not a valid identifier.
     */
    public function whereNotIn($column, $values, $boolean = 'AND'): self
    {
        if (is_array($values)) {
            $values = '(' . implode(', ', array_map(function ($v) {
                return is_string($v) ? "'{$v}'" : $v;
            }, $values)) . ')';
        }

        $this->wheres[] = [
            'type' => 'not_in',
            'column' => SqlIdentifier::quote((string) $column),
            'values' => $values,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE BETWEEN clause
     *
     * @param string $column
     * @param mixed $value1
     * @param mixed $value2
     * @param string $boolean
     *
     * @return self
     */
    public function whereBetween($column, $value1, $value2, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'between',
            'column' => SqlIdentifier::quote((string) $column),
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE NOT BETWEEN clause
     *
     * @param string $column
     * @param mixed $value1
     * @param mixed $value2
     * @param string $boolean
     *
     * @return self
     */
    public function whereNotBetween($column, $value1, $value2, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'not_between',
            'column' => SqlIdentifier::quote((string) $column),
            'value1' => $value1,
            'value2' => $value2,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE IS NULL clause
     *
     * @param string $column
     * @param string $boolean
     *
     * @return self
     */
    public function whereNull($column, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'null',
            'column' => SqlIdentifier::quote((string) $column),
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE IS NOT NULL clause
     *
     * @param string $column
     * @param string $boolean
     *
     * @return self
     */
    public function whereNotNull($column, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'not_null',
            'column' => SqlIdentifier::quote((string) $column),
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE LIKE clause
     *
     * @param string $column
     * @param string $value
     * @param string $boolean
     *
     * @return self
     */
    public function whereLike($column, $value, $boolean = 'AND'): self
    {
        return $this->where($column, 'LIKE', $value, $boolean);
    }

    /**
     * Add WHERE EXISTS clause
     *
     * The subquery is emitted untouched; it is the caller's job to build it from trusted parts.
     *
     * @param string $query
     * @param string $boolean
     *
     * @return self
     */
    public function whereExists($query, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'exists',
            'query' => $query,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add WHERE NOT EXISTS clause
     *
     * The subquery is emitted untouched; it is the caller's job to build it from trusted parts.
     *
     * @param string $query
     * @param string $boolean
     *
     * @return self
     */
    public function whereNotExists($query, $boolean = 'AND'): self
    {
        $this->wheres[] = [
            'type' => 'not_exists',
            'query' => $query,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
        ];

        return $this;
    }

    /**
     * Add grouped WHERE clauses
     *
     * @param callable $callback
     * @param string $boolean
     *
     * @return self
     */
    public function whereGroup($callback, $boolean = 'AND'): self
    {
        $query = new class {
            private $wheres = [];

            public function where($column, $operator = null, $value = null, $boolean = 'AND')
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

                $this->wheres[] = [
                    'type' => 'basic',
                    'column' => SqlIdentifier::quote((string) $column),
                    'operator' => SqlIdentifier::operator((string) ($operator ?? '=')),
                    'value' => $value,
                    'boolean' => $connector
                ];
                return $this;
            }

            public function orWhere($column, $value = null, $operator = '=')
            {
                return $this->where($column, $operator, $value, 'OR');
            }

            public function buildWhereClause()
            {
                $clauses = [];
                foreach ($this->wheres as $index => $where) {
                    $clause = '';
                    if ($index > 0) {
                        $clause .= $where['boolean'] . ' ';
                    }
                    $value = is_string($where['value']) ? "'{$where['value']}'" : $where['value'];
                    $clause .= "{$where['column']} {$where['operator']} {$value}";
                    $clauses[] = $clause;
                }
                return implode(' ', $clauses);
            }
        };

        $callback($query);

        $this->wheres[] = [
            'type' => 'group',
            'query' => $query,
            'boolean' => $this->normalizeWhereBoolean((string) $boolean)
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
    private function normalizeWhereBoolean(string $boolean): string
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
     * Build WHERE clause string
     *
     * Columns and operators in `$wheres` were validated and quoted when the clause was recorded.
     * The `raw` entries were not: they are emitted exactly as {@see whereRaw()} received them.
     *
     * @return string
     */
    protected function buildWhereClause(): string
    {
        $clauses = [];

        foreach ($this->wheres as $index => $where) {
            $clause = '';

            if ($index > 0) {
                $clause .= $where['boolean'] . ' ';
            }

            switch ($where['type']) {
                case 'basic':
                    $value = is_string($where['value']) && !is_numeric($where['value']) ? "'{$where['value']}'" : $where['value'];

                    if (isset($value) && is_array($value)) {
                        $value = '(' . implode(', ', array_map(function ($value) {
                            if (is_string($value) && !is_numeric($value)) {
                                return "'{$value}'";
                            }

                            return $value;
                        }, $value)) . ')';
                    }

                    $clause .= "{$where['column']} {$where['operator']} {$value}";
                    break;

                case 'raw':
                    // Emitted untouched by design; see whereRaw()'s docblock for the contract.
                    $clause .= $where['expression'];
                    break;

                case 'in':
                    $clause .= "{$where['column']} IN {$where['values']}";
                    break;

                case 'not_in':
                    $clause .= "{$where['column']} NOT IN {$where['values']}";
                    break;

                case 'between':
                    $value1 = is_string($where['value1']) ? "'{$where['value1']}'" : $where['value1'];
                    $value2 = is_string($where['value2']) ? "'{$where['value2']}'" : $where['value2'];
                    $clause .= "{$where['column']} BETWEEN {$value1} AND {$value2}";
                    break;

                case 'not_between':
                    $value1 = is_string($where['value1']) ? "'{$where['value1']}'" : $where['value1'];
                    $value2 = is_string($where['value2']) ? "'{$where['value2']}'" : $where['value2'];
                    $clause .= "{$where['column']} NOT BETWEEN {$value1} AND {$value2}";
                    break;

                case 'null':
                    $clause .= "{$where['column']} IS NULL";
                    break;

                case 'not_null':
                    $clause .= "{$where['column']} IS NOT NULL";
                    break;

                case 'exists':
                    $clause .= "EXISTS ({$where['query']})";
                    break;

                case 'not_exists':
                    $clause .= "NOT EXISTS ({$where['query']})";
                    break;

                case 'group':
                    $clause .= "({$where['query']->buildWhereClause()})";
                    break;
            }

            $clauses[] = $clause;
        }

        return implode(' ', $clauses);
    }
}
