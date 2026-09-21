<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\Traits\WhereTrait;
use Clover\Classes\Database\QueryBuilder\Traits\JoinTrait;
use Clover\Classes\Database\QueryBuilder\Traits\OrderByTrait;
use Clover\Classes\Database\QueryBuilder\Traits\GroupByTrait;
use Clover\Classes\Database\QueryBuilder\Traits\HavingTrait;
use Clover\Classes\Database\QueryBuilder\Traits\LimitTrait;
use Clover\Classes\Database\SqlIdentifier;
use function array_map;
use function explode;
use function is_string;
use function trim;

/**
 * SELECT Query Builder
 *
 * Purpose a generate select query via object orientation
 *
 * Tables, aliases, CTE names and every column in a WHERE, GROUP BY, HAVING or ORDER BY clause are
 * validated and quoted by {@see SqlIdentifier}. The SELECT list is the one deliberately permissive
 * site: it has to carry `*`, `COUNT(*) as count` and similar expressions, so a member that is a
 * plain identifier is quoted and anything else is passed through untouched. Subqueries handed to
 * {@see fromSub()}, {@see with()} and {@see union()} are likewise passed through and are the
 * caller's responsibility.
 *
 * {@see selectRaw()} makes that pass-through explicit rather than incidental, and it is dangerous
 * in the same way as the `*Raw` methods on the traits this class uses ({@see WhereTrait::whereRaw()},
 * {@see HavingTrait::havingRaw()}, {@see GroupByTrait::groupByRaw()},
 * {@see OrderByTrait::orderByRaw()}): whatever it is given is written into the statement untouched,
 * so it must never receive user input. For a plain column name use {@see select()}, which validates
 * and quotes any member of its list that is one.
 */
class SelectQuery
{
    use WhereTrait, JoinTrait, OrderByTrait, GroupByTrait, HavingTrait, LimitTrait;

    private $connection;
    private $columns = [];
    private $from = [];
    private $distinct = false;
    private $withQueries = [];
    private $unions = [];

    /**
     * Constructor
     * 
     * @param mixed $connection
     */
    public function __construct($connection = null)
    {
        $this->connection = $connection;
    }

    /**
     * Set SELECT columns
     * 
     * @param array|string $columns
     * 
     * @return self
     */
    public function select(array|string $columns): self
    {
        if (is_string($columns)) {
            $columns = [$columns];
        }

        $this->columns = array_map(
            fn ($column) => $this->compileSelectColumn((string) $column),
            $columns
        );

        return $this;
    }

    /**
     * Append a raw expression to the SELECT list
     *
     * The expression is stored verbatim and written into the statement verbatim: it is not quoted,
     * validated or escaped. It must therefore never contain user input, directly or by
     * interpolation — a raw expression built from a request parameter is a SQL injection. Use it
     * for the aggregates and computed columns a plain identifier cannot express, such as
     * `COUNT(*) AS total`; for a plain column name use {@see select()} instead, because it
     * validates and quotes what it recognises.
     *
     * Unlike {@see select()}, which replaces the list, this appends to it, so a validated
     * `select()` call and a raw one can be combined.
     *
     * @param string $expression The trusted SQL fragment, emitted untouched.
     *
     * @return self
     */
    public function selectRaw(string $expression): self
    {
        $this->columns[] = $expression;

        return $this;
    }

    /**
     * Compile one member of the SELECT list.
     *
     * A plain identifier (`id`, `u.name`) is quoted. Anything else — `*`, `u.*`,
     * `COUNT(*) as count`, `DISTINCT country` — is an expression this builder cannot parse and is
     * emitted unchanged, so the SELECT list is trusted input by contract. It is not an exploit
     * path in the way a WHERE column is: the value being compared is not attacker-reachable from
     * here. Callers that take a column name from a request must check it against their own
     * allow-list, or pass it through {@see SqlIdentifier::isValid()} first.
     *
     * @param string $column
     *
     * @return string
     */
    private function compileSelectColumn(string $column): string
    {
        $column = trim($column);

        return SqlIdentifier::isValid($column) ? SqlIdentifier::quote($column) : $column;
    }

    /**
     * Add DISTINCT clause
     * 
     * @return self
     */
    public function distinct(): self
    {
        $this->distinct = true;
        return $this;
    }

    /**
     * Set FROM clause
     * 
     * @param string $table
     * @param string|null $alias
     * 
     * @return self
     */
    public function from(string $table, string|null $alias = null): self
    {
        $table = SqlIdentifier::quote($table);

        if ($alias) {
            $this->from[] = $table . ' AS ' . SqlIdentifier::quote($alias);
        } else {
            $this->from[] = $table;
        }

        return $this;
    }

    /**
     * Alias for from()
     * 
     * @param string $table
     * @param string|null $alias
     * 
     * @return self
     */
    public function table(string $table, string|null $alias = null): self
    {
        return $this->from($table, $alias);
    }

    /**
     * Set FROM clause with a subquery
     * 
     * @param string $query
     * @param string $alias
     * 
     * @return self
     */
    public function fromSub(string $query, string $alias): self
    {
        $this->from[] = "({$query}) AS " . SqlIdentifier::quote($alias);
        return $this;
    }

    /**
     * Add a CTE
     * 
     * @param string $name
     * @param string $query
     * @param string|array|null $columns
     * 
     * @return self
     */
    public function with(string $name, string $query, string|array|null $columns = null): self
    {
        $cte = SqlIdentifier::quote($name);

        if (!empty($columns)) {
            if (is_string($columns)) {
                $columns = array_map('trim', explode(',', $columns));
            }

            $cte .= " (" . SqlIdentifier::quoteList($columns) . ")";
        }

        $this->withQueries[] = "{$cte} AS ({$query})";

        return $this;
    }

    /**
     * Add UNION clause
     * 
     * @param string $query
     * @param bool $all
     * 
     * @return self
     */
    public function union(string $query, bool $all = false): self
    {
        $this->unions[] = [
            'query' => $query,
            'all' => $all
        ];

        return $this;
    }

    /**
     * Add UNION ALL clause
     * 
     * @param string $query
     * 
     * @return self
     */
    public function unionAll(string $query): self
    {
        return $this->union($query, true);
    }

    /**
     * Add WHERE IN clause
     * 
     * @param string $column
     * @param string $query
     * 
     * @return self
     */
    public function whereIn(string $column, string $query): self
    {
        return $this->where($column, 'IN', $query);
    }

    /**
     * Add WHERE NOT IN clause
     * 
     * @param string $column
     * @param string $query
     * 
     * @return self
     */
    public function whereNotIn(string $column, string $query): self
    {
        return $this->where($column, 'NOT IN', $query);
    }

    /**
     * Add WHERE BETWEEN clause
     * 
     * @param string $column
     * @param int $query1
     * @param int $query2
     * 
     * @return self
     */
    public function whereBetween(string $column, int $query1, int $query2): self
    {
        return $this->where($column, 'BETWEEN', $query1, 'AND', $query2);
    }

    /**
     * Build and get the SQL query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        $sql = [];

        if (!empty($this->withQueries)) {
            $sql[] = 'WITH ' . implode(', ', $this->withQueries);
        }

        $select = 'SELECT';
        if ($this->distinct) {
            $select .= ' DISTINCT';
        }
        $select .= ' ' . implode(', ', $this->columns);
        $sql[] = $select;

        if (!empty($this->from)) {
            $sql[] = 'FROM ' . implode(', ', $this->from);
        }

        if (!empty($this->joins)) {
            $sql[] = implode(' ', $this->joins);
        }

        if (!empty($this->wheres)) {
            $sql[] = 'WHERE ' . $this->buildWhereClause();
        }

        if (!empty($this->groups)) {
            $sql[] = 'GROUP BY ' . implode(', ', $this->groups);
        }

        if (!empty($this->havings)) {
            $sql[] = 'HAVING ' . $this->buildHavingClause();
        }

        if (!empty($this->orders)) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql[] = 'LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql[] = 'OFFSET ' . $this->offset;
            }
        }

        if (!empty($this->unions)) {
            foreach ($this->unions as $union) {
                $unionType = $union['all'] ? 'UNION ALL' : 'UNION';
                $sql[] = $unionType . ' ' . $union['query'];
            }
        }

        return implode(' ', $sql);
    }

    /**
     * Execute the built query
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function execute(): mixed
    {
        if ($this->connection) {
            $sql = $this->toSql();
            return $this->connection->query($sql);
        }

        throw new \Exception('No database connection available');
    }

    /**
     * Fetch all results from the executed query
     * 
     * @return array
     * 
     * @throws \Exception
     */
    public function get(): array
    {
        $result = $this->execute();
        return $result ? $result->fetchAll() : [];
    }

    /**
     * Fetch the first result from the executed query
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function first(): mixed
    {
        $result = $this->execute();
        return $result ? $result->fetch() : null;
    }

    /**
     * Count the number of records
     * 
     * @return int
     */
    public function count(): int
    {
        $countQuery = clone $this;
        $countQuery->select('COUNT(*) as count');
        $result = $countQuery->first();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Pluck a single column from the result set
     * 
     * @param string $column
     * 
     * @return array
     */
    public function pluck(string $column): array
    {
        $result = $this->get();
        return array_column($result, $column);
    }

    /**
     * Key the result by a specific column
     * 
     * @param string $column
     * 
     * @return array
     */
    public function keyBy(string $column): array
    {
        $result = $this->get();
        $keyed = [];
        foreach ($result as $row) {
            $keyed[$row[$column]] = $row;
        }
        
        return $keyed;
    }
}
