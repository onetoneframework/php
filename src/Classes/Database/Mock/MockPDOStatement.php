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
use PDOStatement;
use function count;
use function array_slice;

/**
 * MockPDOStatement is a mock implementation of the PDOStatement class for testing purposes.
 * It simulates the behavior of PDO statements, allowing you to execute SQL queries and retrieve results from an in-memory mock database.
 */
class MockPDOStatement extends PDOStatement
{
    private array $data = [];
    private int $position = 0;
    private array $boundParams = [];
    private int $affectedRows = 0;
    private string $sql;
    private MockPDO $pdo;

    public function __construct(string $sql, MockPDO $pdo)
    {
        $this->sql = $sql;
        $this->pdo = $pdo;
    }

    #[\ReturnTypeWillChange]
    public function execute(?array $params = null): bool
    {
        $params = $params ?? $this->boundParams;

        if (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?/i', $this->sql, $matches)) {
            $table = $matches[1];
            $this->executeInsert($table, $params);
            return true;
        }

        if (preg_match('/^UPDATE `?(\w+)`?/i', $this->sql, $matches)) {
            $table = $matches[1];
            $this->executeUpdate($table, $params);
            return true;
        }

        if (preg_match('/^DELETE FROM `?(\w+)`?/i', $this->sql, $matches)) {
            $table = $matches[1];
            $this->executeDelete($table, $params);
            return true;
        }

        if (preg_match('/^SELECT/i', $this->sql)) {
            $this->executeSelect($params);
            return true;
        }

        if (preg_match('/^SHOW\s+COLUMNS/i', $this->sql)) {
            $this->executeSelect($params);
            return true;
        }

        if (preg_match('/^TRUNCATE TABLE `?(\w+)`?/i', $this->sql, $matches)) {
            $table = $matches[1];
            $this->pdo->truncateTable($table);
            return true;
        }

        return true;
    }

    private function executeInsert(string $table, array $params): void
    {
        preg_match_all('/`?(\w+)`?/i', $this->sql, $columnMatches);
        preg_match('/VALUES\s*\(([\?,\s]+)\)/i', $this->sql, $valueMatches);

        $columns = [];
        if (preg_match('/\((.*?)\)\s*VALUES/i', $this->sql, $colMatch)) {
            $colString = $colMatch[1];
            preg_match_all('/`?(\w+)`?/', $colString, $cols);
            $columns = $cols[1];
        }

        $row = [];
        foreach ($columns as $index => $column) {
            $row[$column] = $params[$index] ?? null;
        }

        $id = $this->pdo->insertRow($table, $row);
        $this->affectedRows = 1;
    }

    private function executeUpdate(string $table, array $params): void
    {
        preg_match('/WHERE (.+)$/i', $this->sql, $whereMatches);
        $whereClause = $whereMatches[1] ?? '';

        preg_match('/SET\s+(.+?)(?:\s+WHERE|$)/i', $this->sql, $setPartMatch);
        $setPart = $setPartMatch[1] ?? '';

        $setColumns = [];
        $setExpressions = []; // column => ['direct'|'arithmetic', op, param_index]
        $paramIndex = 0;

        foreach (preg_split('/,\s*(?=`?\w+`?\s*=)/', $setPart) as $assignment) {
            $assignment = trim($assignment);
            if (preg_match('/`?(\w+)`?\s*=\s*`?\1`?\s*([+\-*\/])\s*\?/i', $assignment, $m)) {
                $setColumns[] = $m[1];
                $setExpressions[$m[1]] = ['mode' => 'arithmetic', 'op' => $m[2], 'idx' => $paramIndex++];
            } elseif (preg_match('/`?(\w+)`?\s*=\s*\?/', $assignment, $m)) {
                $setColumns[] = $m[1];
                $setExpressions[$m[1]] = ['mode' => 'direct', 'idx' => $paramIndex++];
            }
        }

        $whereValues = array_slice($params, $paramIndex);

        $matchedRows = $this->pdo->selectRows($table, $whereClause, $whereValues, '', null, 0);
        $updates = [];
        foreach ($setColumns as $col) {
            $expr = $setExpressions[$col];
            if ($expr['mode'] === 'direct') {
                $updates[$col] = $params[$expr['idx']];
            } elseif ($expr['mode'] === 'arithmetic') {
                $updates[$col] = null; // resolved per-row below
            }
        }

        if (!array_filter($setExpressions, fn($e) => $e['mode'] === 'arithmetic')) {
            $this->affectedRows = $this->pdo->updateRows($table, $updates, $whereClause, $whereValues);
            return;
        }

        $count = 0;
        foreach ($matchedRows as $row) {
            $rowUpdates = $updates;
            foreach ($setColumns as $col) {
                $expr = $setExpressions[$col];
                if ($expr['mode'] === 'arithmetic') {
                    $current = (float) ($row[$col] ?? 0);
                    $operand = (float) $params[$expr['idx']];
                    $rowUpdates[$col] = match ($expr['op']) {
                        '+' => $current + $operand,
                        '-' => $current - $operand,
                        '*' => $current * $operand,
                        '/' => $operand != 0 ? $current / $operand : $current,
                    };
                }
            }
            $pkWhere = '`id` = ?';
            $this->pdo->updateRows($table, $rowUpdates, $pkWhere, [$row['id']]);
            $count++;
        }
        $this->affectedRows = $count;
    }

    private function executeDelete(string $table, array $params): void
    {
        preg_match('/WHERE (.+)$/i', $this->sql, $whereMatches);
        $whereClause = $whereMatches[1] ?? '';

        $this->affectedRows = $this->pdo->deleteRows($table, $whereClause, $params);
    }

    private function executeSelect(array $params): void
    {
        // Handle SHOW COLUMNS FROM queries (used by getColumnListing())
        if (preg_match('/^SHOW\s+COLUMNS\s+FROM\s+`?(\w+)`?/i', $this->sql, $m)) {
            $columns = $this->pdo->getColumnNames($m[1]);
            $this->data = array_map(fn($col) => ['Field' => $col], $columns);
            $this->position = 0;
            return;
        }

        // Handle COUNT(*) aggregate queries (used by count(), paginate(), exists(), etc.)
        if (preg_match('/SELECT\s+COUNT\(\*\)\s+as\s+(\w+)/i', $this->sql, $cntMatch)) {
            $alias = $cntMatch[1];

            preg_match('/FROM\s+`?(\w+)`?/i', $this->sql, $tableMatches);
            $table = $tableMatches[1] ?? '';

            // Extract WHERE clause, stopping before GROUP BY / HAVING / ORDER BY / LIMIT
            preg_match(
                '/WHERE\s+(.+?)(?:\s+GROUP\s+BY|\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is',
                $this->sql,
                $whereMatches
            );
            $whereClause = trim($whereMatches[1] ?? '');

            $matchedRows = $this->pdo->selectRows($table, $whereClause, $params, '', null, 0);
            $this->data = [[$alias => count($matchedRows)]];
            $this->position = 0;
            return;
        }

        // Handle simple aggregate functions: SUM, AVG, MIN, MAX
        if (preg_match('/SELECT\s+(SUM|AVG|MIN|MAX)\(`?(\w+)`?\)\s+as\s+result/i', $this->sql, $aggMatch)) {
            $func = strtoupper($aggMatch[1]);
            $column = $aggMatch[2];

            preg_match('/FROM\s+`?(\w+)`?/i', $this->sql, $tableMatches);
            $table = $tableMatches[1] ?? '';

            preg_match(
                '/WHERE\s+(.+?)(?:\s+GROUP\s+BY|\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is',
                $this->sql,
                $whereMatches
            );
            $whereClause = trim($whereMatches[1] ?? '');

            $rows = $this->pdo->selectRows($table, $whereClause, $params, '', null, 0);
            $values = array_filter(
                array_map(fn($r) => $r[$column] ?? null, $rows),
                fn($v) => $v !== null
            );

            $result = match ($func) {
                'SUM' => array_sum($values),
                'AVG' => count($values) > 0 ? array_sum($values) / count($values) : null,
                'MIN' => count($values) > 0 ? min($values) : null,
                'MAX' => count($values) > 0 ? max($values) : null,
                default => null,
            };

            $this->data = [['result' => $result]];
            $this->position = 0;
            return;
        }

        // Handle GROUP BY count queries: SELECT `col`, COUNT(*) as _cnt … GROUP BY `col`
        if (preg_match('/SELECT\s+`?(\w+)`?,\s+COUNT\(\*\)\s+as\s+(\w+)/i', $this->sql, $grpMatch)) {
            $groupCol = $grpMatch[1];
            $alias = $grpMatch[2];

            preg_match('/FROM\s+`?(\w+)`?/i', $this->sql, $tableMatches);
            $table = $tableMatches[1] ?? '';

            preg_match(
                '/WHERE\s+(.+?)(?:\s+GROUP\s+BY|\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is',
                $this->sql,
                $whereMatches
            );
            $whereClause = trim($whereMatches[1] ?? '');

            $rows = $this->pdo->selectRows($table, $whereClause, $params, '', null, 0);
            $groups = [];
            foreach ($rows as $row) {
                $key = $row[$groupCol] ?? null;
                $groups[$key] = ($groups[$key] ?? 0) + 1;
            }

            $this->data = [];
            foreach ($groups as $key => $cnt) {
                $this->data[] = [$groupCol => $key, $alias => $cnt];
            }
            $this->position = 0;
            return;
        }

        // --- Standard SELECT ---
        preg_match('/FROM `?(\w+)`?/i', $this->sql, $tableMatches);
        $table = $tableMatches[1] ?? '';

        preg_match('/WHERE (.+?)(?:ORDER BY|LIMIT|$)/i', $this->sql, $whereMatches);
        $whereClause = $whereMatches[1] ?? '';

        preg_match('/ORDER BY (.+?)(?:LIMIT|$)/i', $this->sql, $orderMatches);
        $orderClause = $orderMatches[1] ?? '';

        preg_match('/LIMIT (\d+)(?:\s+OFFSET\s+(\d+))?/i', $this->sql, $limitMatches);
        $limit = isset($limitMatches[1]) ? (int) $limitMatches[1] : null;
        $offset = isset($limitMatches[2]) ? (int) $limitMatches[2] : 0;

        $this->data = $this->pdo->selectRows($table, $whereClause, $params, $orderClause, $limit, $offset);
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function fetch(int $fetchStyle = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        if ($this->position >= count($this->data)) {
            return false;
        }

        $row = $this->data[$this->position++];

        if ($fetchStyle === PDO::FETCH_OBJ) {
            return (object) $row;
        }

        return $row;
    }

    #[\ReturnTypeWillChange]
    public function fetchAll(int $fetchStyle = PDO::FETCH_DEFAULT, mixed ...$args): array
    {
        if ($fetchStyle === PDO::FETCH_OBJ) {
            return array_map(fn($row) => (object) $row, $this->data);
        }

        return $this->data;
    }

    #[\ReturnTypeWillChange]
    public function fetchColumn(int $column = 0): mixed
    {
        if (empty($this->data)) {
            return false;
        }

        $row = array_values($this->data[0]);
        return $row[$column] ?? false;
    }

    #[\ReturnTypeWillChange]
    public function rowCount(): int
    {
        return $this->affectedRows > 0 ? $this->affectedRows : count($this->data);
    }

    #[\ReturnTypeWillChange]
    public function bindParam(string|int $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool
    {
        $this->boundParams[$param] = &$var;
        return true;
    }

    #[\ReturnTypeWillChange]
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool
    {
        $this->boundParams[$param] = $value;
        return true;
    }
}
