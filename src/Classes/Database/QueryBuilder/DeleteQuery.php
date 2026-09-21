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
use Clover\Classes\Database\QueryBuilder\Traits\LimitTrait;
use Clover\Classes\Database\SqlIdentifier;
use function is_array;

/**
 * Class DeleteQuery
 *
 * This class allows building SQL DELETE queries
 *
 * The target table, the tables named in USING and both sides of a join condition are validated and
 * quoted by {@see SqlIdentifier}. WHERE values are still inlined by {@see WhereTrait}.
 */
class DeleteQuery
{
    use WhereTrait, JoinTrait, OrderByTrait, LimitTrait;

    private $connection;
    private $table;
    private $using = [];
    private $queryType;

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
     * Set DELETE query
     * 
     * @param string|null $table
     * 
     * @return self
     */
    public function delete(?string $table = null): self
    {
        if ($table) {
            $this->table = SqlIdentifier::quote($table);
        }
        return $this;
    }

    /**
     * Set FROM clause
     * 
     * @param string $table
     * 
     * @return self
     */
    public function from(string $table): self
    {
        $this->table = SqlIdentifier::quote($table);
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
     * Add USING clause
     *
     * Each entry must be a plain table name; `table alias` spellings are not accepted, because an
     * alias cannot be told apart from an injected fragment once the two are in one string.
     *
     * @param string|array $table
     *
     * @return self
     *
     * @throws \InvalidArgumentException When any table is not a valid identifier.
     */
    public function using(string|array $table): self
    {
        if (is_array($table)) {
            foreach ($table as $name) {
                $this->using[] = SqlIdentifier::quote((string) $name);
            }

            return $this;
        }

        $this->using[] = SqlIdentifier::quote($table);
        return $this;
    }

    /**
     * Set TRUNCATE query
     * 
     * @param string|null $table
     * 
     * @return self
     */
    public function truncate(string|null $table = null): self
    {
        if ($table) {
            $this->table = SqlIdentifier::quote($table);
        }
        $this->queryType = 'TRUNCATE';
        return $this;
    }

    /**
     * Build and get the SQL query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        if (isset($this->queryType) && $this->queryType === 'TRUNCATE') {
            return 'TRUNCATE TABLE ' . $this->table;
        }

        $sql = [];

        if (!empty($this->using)) {
            $sql[] = 'DELETE ' . $this->table . ' FROM ' . $this->table;
            $sql[] = 'USING ' . implode(', ', $this->using);
        } else {
            $sql[] = 'DELETE FROM ' . $this->table;
        }

        if (!empty($this->joins)) {
            $sql[] = implode(' ', $this->joins);
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
     * Execute the delete query
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
    public function getAffectedRows(): mixed
    {
        $this->execute();
        return $this->connection->rowCount();
    }
}
