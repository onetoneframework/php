<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder;

use Clover\Classes\Database\SqlIdentifier;
use SimpleXMLElement;
use function array_map;
use function is_array;
use function is_string;
use function in_array;
use function is_object;
use function sprintf;
use function gettype;

/**
 * Class InsertQuery
 *
 * Build and execute INSERT SQL queries
 *
 * The table and every column name are validated and quoted by {@see SqlIdentifier}. Values are
 * escaped by doubling single quotes as they are rendered, which is the only value escaping in this
 * builder family; it is still not a substitute for binding.
 */
class InsertQuery
{
    private $connection;
    private $table;
    private $columns = [];
    private $values = [];
    private $onDuplicateKeyUpdate = [];
    private $ignore = false;

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
     * Set INSERT query
     * 
     * @param string $table
     * 
     * @return self
     */
    public function insert(string $table): self
    {
        $this->table = SqlIdentifier::quote($table);
        return $this;
    }

    /**
     * Add IGNORE clause
     * 
     * @return self
     */
    public function ignore(): self
    {
        $this->ignore = true;
        return $this;
    }

    /**
     * Add single row for insert
     * 
     * @param array $data
     * 
     * @return self
     */
    public function values(array $data): self
    {
        if (is_array($data)) {
            if (empty($this->columns)) {
                $this->columns = array_keys($data);
            }
            $this->values[] = array_values($data);
        }

        return $this;
    }

    /**
     * Add multiple rows for insert
     * 
     * @param array $data
     * 
     * @return self
     */
    public function insertMultiple(array $data): self
    {
        if (is_array($data) && !empty($data)) {
            if (empty($this->columns)) {
                $this->columns = array_keys($data[0]);
            }

            foreach ($data as $row) {
                $this->values[] = array_values($row);
            }
        }
        return $this;
    }

    /**
     * Set columns for insert
     * 
     * @param array|string $columns
     * 
     * @return self
     */
    public function columns(array|string $columns): self
    {
        if (is_array($columns)) {
            $this->columns = $columns;
        } elseif (is_string($columns)) {
            $this->columns = [$columns];
        }
        return $this;
    }

    /**
     * Add single value for a column
     * 
     * @param string $column
     * @param mixed $value
     * 
     * @return self
     */
    public function value(string $column, mixed $value): self
    {
        if (!in_array($column, $this->columns)) {
            $this->columns[] = $column;
        }

        if (empty($this->values)) {
            $this->values = [[]];
        }

        $columnIndex = array_search($column, $this->columns);
        $this->values[0][$columnIndex] = $value;

        return $this;
    }

    /**
     * Add ON DUPLICATE KEY UPDATE clause
     * 
     * @param array $data
     * 
     * @return self
     */
    public function onDuplicateKeyUpdate(array $data): self
    {
        if (is_array($data)) {
            foreach ($data as $column => $value) {
                $this->onDuplicateKeyUpdate[] = SqlIdentifier::quote((string) $column)
                    . ' = ' . (is_string($value) ? "'{$value}'" : $value);
            }
        }

        return $this;
    }

    /**
     * Add ON DUPLICATE KEY UPDATE column = VALUES(column)
     * 
     * @param string $column
     * 
     * @return self
     */
    public function onDuplicateKeyUpdateValue(string $column): self
    {
        $column = SqlIdentifier::quote($column);
        $this->onDuplicateKeyUpdate[] = "{$column} = VALUES({$column})";
        return $this;
    }

    /**
     * Insert data from a SELECT query
     * 
     * @param string $query
     * 
     * @return self
     */
    public function insertFromSelect(string $query): self
    {
        $this->values = [$query];
        return $this;
    }

    /**
     * Build and get the SQL query string
     * 
     * @return string
     */
    public function toSql(): string
    {
        $sql = [];

        // INSERT [IGNORE] INTO
        $insert = 'INSERT';
        if ($this->ignore) {
            $insert .= ' IGNORE';
        }
        $insert .= ' INTO ' . $this->table;
        $sql[] = $insert;

        if (!empty($this->columns)) {
            // Rendered into a local, not written back onto $this->columns: the column list has to
            // survive a second toSql() call unchanged, and value() looks the names up by identity.
            $columns = array_map(
                static fn ($column) => SqlIdentifier::quote((string) $column),
                $this->columns
            );

            $sql[] = '(' . implode(', ', $columns) . ')';
        }

        if (!empty($this->values)) {
            if (is_string($this->values[0])) {
                $sql[] = $this->values[0];
            } else {
                $valueStrings = [];
                foreach ($this->values as $row) {
                    $rowValues = array_map(function ($value) {
                        if (gettype($value) == 'object' && $value instanceof SimpleXMLElement) {
                            $value = $value->__tostring();
                        }

                        return is_object($value) || is_string($value) ? sprintf("'%s'", str_replace("'", "''", $value)) : $value;
                    }, $row);
                    $valueStrings[] = '(' . implode(', ', $rowValues) . ')';
                }

                $sql[] = 'VALUES ' . implode(', ', $valueStrings);
            }
        }

        // ON DUPLICATE KEY UPDATE
        if (!empty($this->onDuplicateKeyUpdate)) {
            $sql[] = 'ON DUPLICATE KEY UPDATE ' . implode(', ', $this->onDuplicateKeyUpdate);
        }

        return implode(' ', $sql);
    }

    /**
     * Execute the insert query
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
     * Get the last inserted ID
     * 
     * @return mixed
     * 
     * @throws \Exception
     */
    public function getLastInsertId(): mixed
    {
        $this->execute();
        return $this->connection->lastInsertId();
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
