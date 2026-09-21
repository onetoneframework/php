<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Mock;

use PDO;
use function array_slice;
use function in_array;

/**
 * MockPDO is a simple in-memory implementation of PDO for testing purposes.
 * It supports basic SQL operations like CREATE TABLE, INSERT, UPDATE, DELETE, and SELECT.
 * It also handles transactions and auto-incrementing IDs.
 */
class MockPDO extends PDO
{
    private array $tables = [];
    private array $autoIncrements = [];
    private ?string $lastInsertId = null;
    private bool $inTransaction = false;

    public function __construct()
    {
    }

    /** 
     * Override prepare to return a MockPDOStatement that can execute queries against the in-memory tables.
     * 
     * @param string $query
     * @param array $options
     * @return MockPDOStatement
     */
    #[\ReturnTypeWillChange]
    public function prepare(string $query, array $options = []): MockPDOStatement
    {
        return new MockPDOStatement($query, $this);
    }

    /** 
     * Override exec to handle CREATE TABLE and DELETE FROM statements for managing in-memory tables.
     * For other statements, it returns 0 as a default.
     * 
     * @param string $statement
     * @return int|false
     */
    public function exec(string $statement): int|false
    {
        if (preg_match('/^CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
            $table = $matches[1];
            $this->tables[$table] = [];
            $this->autoIncrements[$table] = 1;
            return 0;
        }

        if (preg_match('/^DELETE FROM `?(\w+)`?/i', $statement, $matches)) {
            $table = $matches[1];
            $count = count($this->tables[$table] ?? []);
            $this->tables[$table] = [];
            return $count;
        }

        return 0;
    }

    /** 
     * Override query to execute the given SQL query and return a MockPDOStatement with the results.
     * For simplicity, it only supports SELECT statements and returns an empty result set for others.
     * 
     * @param string $query
     * @param int|null $fetchMode
     * @param mixed ...$fetchModeArgs
     * @return MockPDOStatement|false
     */
    #[\ReturnTypeWillChange]
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): MockPDOStatement|false
    {
        $stmt = $this->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    /** 
     * Override lastInsertId to return the last auto-increment ID generated for the most recent insert operation.
     * If no insert has been performed, it returns false.
     * 
     * @param string|null $name
     * @return string|false
     */
    public function lastInsertId(?string $name = null): string|false
    {
        return $this->lastInsertId ?? false;
    }

    /** 
     * Override setAttribute to allow setting attributes on the MockPDO instance.
     * For simplicity, it does not actually store or use any attributes and always returns true.
     * 
     * @param int $attribute
     * @param mixed $value
     * @return bool
     */
    public function setAttribute(int $attribute, mixed $value): bool
    {
        return true;
    }

    /**
     * Override beginTransaction to set the inTransaction flag to true. In a real implementation, this would also start a transaction context.
     * For simplicity, it does not actually start a transaction and always returns true.
     * 
     * @return bool
     */
    public function beginTransaction(): bool
    {
        $this->inTransaction = true;
        return true;
    }

    /* 
     * Override commit to set the inTransaction flag to false. In a real implementation, this would also apply any changes made during the transaction.
     * For simplicity, it does not actually apply any changes and always returns true.
     * 
     * @return bool
     */
    public function commit(): bool
    {
        $this->inTransaction = false;
        return true;
    }

    /* 
     * Override rollBack to set the inTransaction flag to false. In a real implementation, this would also revert any changes made during the transaction.
     * For simplicity, it does not actually revert any changes and always returns true.
     * 
     * @return bool
     */
    public function rollBack(): bool
    {
        $this->inTransaction = false;
        return true;
    }

    /** 
     * Override inTransaction to return whether a transaction is currently active.
     * This is used by MockPDOStatement to determine if it should apply changes immediately or defer them until commit.
     * 
     * @return bool
     */
    public function inTransaction(): bool
    {
        return $this->inTransaction;
    }

    /** 
     * Insert a row into a specific table in the mock database. It automatically assigns an auto-incrementing ID if not provided.
     * It returns the ID of the inserted row.
     * 
     * @param string $table
     * @param array $row
     * @return int
     */
    public function insertRow(string $table, array $row): int
    {
        if (!isset($this->tables[$table])) {
            $this->tables[$table] = [];
            $this->autoIncrements[$table] = 1;
        }

        if (!isset($row['id']) || $row['id'] === null) {
            $row['id'] = $this->autoIncrements[$table]++;
        }

        $this->tables[$table][] = $row;
        $this->lastInsertId = (string) $row['id'];

        return (int) $row['id'];
    }

    /** 
     * Update rows in a specific table in the mock database that match the given WHERE clause and parameters.
     * It returns the number of rows updated.
     * 
     * @param string $table
     * @param array $updates
     * @param string $whereClause
     * @param array $whereParams
     * @return int
     */
    public function updateRows(string $table, array $updates, string $whereClause, array $whereParams): int
    {
        if (!isset($this->tables[$table])) {
            return 0;
        }

        $count = 0;
        foreach ($this->tables[$table] as &$row) {
            if ($this->matchesWhere($row, $whereClause, $whereParams)) {
                foreach ($updates as $column => $value) {
                    $row[$column] = $value;
                }
                $count++;
            }
        }

        return $count;
    }

    /** 
     * Delete rows from a specific table in the mock database that match the given WHERE clause and parameters.
     * It returns the number of rows deleted.
     * 
     * @param string $table
     * @param string $whereClause
     * @param array $whereParams
     * @return int
     */
    public function deleteRows(string $table, string $whereClause, array $whereParams): int
    {
        if (!isset($this->tables[$table])) {
            return 0;
        }

        $count = 0;
        $this->tables[$table] = array_values(array_filter($this->tables[$table], function ($row) use ($whereClause, $whereParams, &$count) {
            $matches = $this->matchesWhere($row, $whereClause, $whereParams);
            if ($matches) {
                $count++;
            }
            return !$matches;
        }));

        return $count;
    }

    /**
     * Select rows from a specific table in the mock database that match the given WHERE clause and parameters.
     * It also supports ordering and pagination through the orderClause, limit, and offset parameters.
     * 
     * @param string $table
     * @param string $whereClause
     * @param array $whereParams
     * @param string $orderClause
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function selectRows(string $table, string $whereClause, array $whereParams, string $orderClause, ?int $limit, int $offset): array
    {
        if (!isset($this->tables[$table])) {
            return [];
        }

        $rows = $this->tables[$table];

        if ($whereClause) {
            $rows = array_filter($rows, fn($row) => $this->matchesWhere($row, $whereClause, $whereParams));
        }

        if ($orderClause) {
            $this->applyOrder($rows, $orderClause);
        }

        $rows = array_values($rows);

        if ($limit !== null) {
            $rows = array_slice($rows, $offset, $limit);
        }

        return $rows;
    }

    /** 
     * Truncate a specific table in the mock database, removing all rows and resetting auto-increment counters.
     * This is useful for resetting state between tests.
     * 
     * @param string $table
     */
    public function truncateTable(string $table): void
    {
        $this->tables[$table] = [];
        $this->autoIncrements[$table] = 1;
    }

    /** 
     * Check if a given row matches the WHERE clause conditions. This is a very basic implementation that only supports simple equality and IN conditions.
     * It does not support complex expressions, OR conditions, or other SQL features.
     * 
     * @param array $row
     * @param string $whereClause
     * @param array $params
     * @return bool
     */
    private function matchesWhere(array $row, string $whereClause, array $params): bool
    {
        if (empty($whereClause)) {
            return true;
        }

        $conditions = preg_split('/\s+AND\s+/i', $whereClause);
        $paramIndex = 0;

        foreach ($conditions as $condition) {
            $condition = trim($condition);

            if (preg_match('/`?(\w+)`?\s*=\s*\?/', $condition, $matches)) {
                $column = $matches[1];
                if (!isset($params[$paramIndex])) {
                    continue;
                }

                if (!isset($row[$column]) || $row[$column] != $params[$paramIndex]) {
                    return false;
                }
                $paramIndex++;
            } elseif (preg_match('/`?(\w+)`?\s*IN\s*\(([\?,\s]+)\)/i', $condition, $matches)) {
                $column = $matches[1];
                $placeholderCount = substr_count($matches[2], '?');
                $inValues = array_slice($params, $paramIndex, $placeholderCount);
                if (!isset($row[$column]) || !in_array($row[$column], $inValues)) {
                    return false;
                }
                $paramIndex += $placeholderCount;
            } elseif (preg_match('/`?(\w+)`?\s*IS\s+NOT\s+NULL/i', $condition, $matches)) {
                $column = $matches[1];
                if (!isset($row[$column]) || $row[$column] === null) {
                    return false;
                }
            } elseif (preg_match('/`?(\w+)`?\s*IS\s+NULL/i', $condition, $matches)) {
                $column = $matches[1];
                if (isset($row[$column]) && $row[$column] !== null) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Apply ORDER BY clause to the given rows. This is a very basic implementation that only supports single-column ordering.
     * It does not support complex expressions or multiple columns.
     * 
     * @param array $rows
     * @param string $orderClause
     */
    private function applyOrder(array &$rows, string $orderClause): void
    {
        if (preg_match('/`?(\w+)`?\s+(ASC|DESC)/i', $orderClause, $matches)) {
            $column = $matches[1];
            $direction = strtoupper($matches[2]);

            usort($rows, function ($a, $b) use ($column, $direction) {
                $valA = $a[$column] ?? null;
                $valB = $b[$column] ?? null;

                $cmp = $valA <=> $valB;
                return $direction === 'DESC' ? -$cmp : $cmp;
            });
        }
    }

    /** 
     * Return the raw data for a specific table in the mock database. This is used by MockPDOStatement to fetch results.
     * 
     * @param string $table
     * @return array
     */
    public function getTableData(string $table): array
    {
        return $this->tables[$table] ?? [];
    }

    /**
     * Return the column names seen so far for a table (derived from inserted rows).
     * Used by MockPDOStatement to satisfy SHOW COLUMNS FROM queries.
     * 
     * @param string $table
     * @return array
     */
    public function getColumnNames(string $table): array
    {
        $rows = $this->tables[$table] ?? [];
        if (empty($rows)) {
            return [];
        }
        return array_keys($rows[0]);
    }

    /** 
     * Clear all rows from a specific table in the mock database. This is useful for resetting state between tests.
     * 
     * @param string $table
     */
    public function clearTable(string $table): void
    {
        if (isset($this->tables[$table])) {
            $this->tables[$table] = [];
        }
    }

    /** 
     * Clear all tables in the mock database. This is useful for resetting state between tests.
     */
    public function clearAllTables(): void
    {
        foreach (array_keys($this->tables) as $table) {
            $this->tables[$table] = [];
        }
    }
}
