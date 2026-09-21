<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\Traits\{WhereTrait, JoinTrait, OrderByTrait, LimitTrait};
use Clover\Classes\Database\SqlIdentifier;
use function is_string;
use function is_array;
use function func_num_args;

/**
 * Class UpdateQuery
 *
 * This class allows building SQL UPDATE queries
 *
 * The table, every assigned column and both sides of a join condition are validated and quoted by
 * {@see SqlIdentifier}. Assigned values are still inlined rather than bound, so callers must not
 * pass untrusted values to {@see set()}.
 */
class UpdateQuery
{
    use WhereTrait, JoinTrait, OrderByTrait, LimitTrait;

    private $connection;
    private $table;
    private $sets = [];

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
     * Set UPDATE query
     * 
     * @param string $table
     * 
     * @return self
     */
    public function update(string $table): self
    {
        $this->table = SqlIdentifier::quote($table);
        return $this;
    }

    /**
     * Set column values
     * 
     * @param string|array $column
     * @param string|null $value
     * 
     * @return self
     */
    public function set(string|array $column, string|null $value = null): self
    {
        if (is_array($column)) {
            foreach ($column as $col => $val) {
                $this->sets[] = SqlIdentifier::quote((string) $col) . ' = ' . (is_string($val) ? "'{$val}'" : $val);
            }
        } else {
            $this->sets[] = SqlIdentifier::quote($column) . ' = ' . (is_string($value) ? "'{$value}'" : $value);
        }
        return $this;
    }

    /**
     * Increment a column value
     * 
     * @param string $column
     * @param int|float $amount
     * 
     * @return self
     */
    public function increment(string $column, int|float $amount = 1): self
    {
        $column = SqlIdentifier::quote($column);
        $this->sets[] = "{$column} = {$column} + {$amount}";
        return $this;
    }

    /**
     * Decrement a column value
     * 
     * @param string $column
     * @param int|float $amount
     * 
     * @return self
     */
    public function decrement(string $column, int|float $amount = 1): self
    {
        $column = SqlIdentifier::quote($column);
        $this->sets[] = "{$column} = {$column} - {$amount}";
        return $this;
    }

    /**
     * Set a column to NULL
     * 
     * @param string $column
     * 
     * @return self
     */
    public function setNull(string $column): self
    {
        $this->sets[] = SqlIdentifier::quote($column) . ' = NULL';
        return $this;
    }

    /**
     * Set a column to CURRENT_TIMESTAMP
     * 
     * @param string $column
     * 
     * @return self
     */
    public function setCurrentTimestamp(string $column): self
    {
        $this->sets[] = SqlIdentifier::quote($column) . ' = CURRENT_TIMESTAMP';
        return $this;
    }

    /**
     * Set a column to NOW()
     * 
     * @param string $column
     * 
     * @return self
     */
    public function setNow(string $column): self
    {
        $this->sets[] = SqlIdentifier::quote($column) . ' = NOW()';
        return $this;
    }

    /**
     * Set a column to a subquery
     *
     * The subquery is emitted untouched; only the column is validated.
     *
     * @param string $column
     * @param string $query
     *
     * @return self
     */
    public function setSub(string $column, string $query): self
    {
        $this->sets[] = SqlIdentifier::quote($column) . " = ({$query})";
        return $this;
    }

    /**
     * Add JOIN clause
     * 
     * @param string $table
     * @param string $first
     * @param string|null $operator
     * @param string|null $second
     * @param string $type
     * 
     * @return self
     */
    public function join(string $table, string $first, string|null $operator = null, string|null $second = null, string $type = 'INNER'): self
    {
        return $this->addJoinExpression(SqlIdentifier::quote($table), $first, $operator, $second, $type);
    }

    /**
     * Add INNER JOIN clause
     * 
     * @param string $table
     * @param string $first
     * @param string|null $operator
     * @param string|null $second
     * 
     * @return self
     */
    public function leftJoin(string $table, string $first, string|null $operator = null, string|null $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     * 
     * @param string $table
     * @param string $first
     * @param string|null $operator
     * @param string|null $second
     * 
     * @return self
     */
    public function rightJoin(string $table, string $first, string|null $operator = null, string|null $second = null): self
    {
        return $this->join($table, $first, $operator, $second, 'RIGHT');
    }

    /**
     * Build and get the SQL query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        $sql = [];

        $sql[] = 'UPDATE ' . $this->table;

        if (!empty($this->joins)) {
            $sql[] = implode(' ', $this->joins);
        }

        if (!empty($this->sets)) {
            $sql[] = 'SET ' . implode(', ', $this->sets);
        }

        if (!empty($this->wheres)) {
            $sql[] = 'WHERE ' . $this->buildWhereClause();
        }

        if (!empty($this->orders)) {
            $sql[] = 'ORDER BY ' . implode(', ', $this->orders);
        }

        if ($this->limit !== null) {
            $sql[] = 'LIMIT ' . $this->limit;
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
     * Get the number of affected rows
     * 
     * @return int
     * 
     * @throws \Exception
     */
    public function getAffectedRows(): int
    {
        $this->execute();
        return $this->connection->rowCount();
    }
}
