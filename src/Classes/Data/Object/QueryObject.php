<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use Clover\Classes\Data\StringObject as StringObject;

use function count;

/**
 * Class QueryObject
 *
 * Parses a raw SQL query string into a structured representation.
 *
 * Supported statement types:
 *   SELECT  – including DISTINCT, CTEs (WITH), UNION / UNION ALL,
 *             JOINs, WHERE, GROUP BY, HAVING, ORDER BY, LIMIT / OFFSET,
 *             FOR UPDATE, LOCK IN SHARE MODE, and subqueries.
 *   INSERT  – including IGNORE / LOW_PRIORITY / HIGH_PRIORITY / DELAYED,
 *             column lists, VALUES (multi-row), INSERT … SELECT,
 *             and ON DUPLICATE KEY UPDATE.
 *   REPLACE – same structure as INSERT.
 *   UPDATE  – including LOW_PRIORITY / IGNORE, multi-table updates,
 *             JOINs, SET, WHERE, ORDER BY, LIMIT.
 *   DELETE  – including LOW_PRIORITY / QUICK / IGNORE, multi-table
 *             deletes, JOINs, WHERE, ORDER BY, LIMIT.
 *   CREATE  – TABLE (columns, constraints, table options),
 *             VIEW (as SELECT), INDEX, DATABASE / SCHEMA.
 *   DROP    – TABLE, VIEW, INDEX, DATABASE / SCHEMA, PROCEDURE,
 *             FUNCTION, TRIGGER, EVENT (with IF EXISTS).
 *   ALTER   – TABLE actions: ADD / DROP / MODIFY / CHANGE COLUMN,
 *             ADD / DROP INDEX / FOREIGN KEY / PRIMARY KEY, RENAME,
 *             table-level ENGINE / CHARSET options.
 *   TRUNCATE – TABLE.
 *
 * All parsing methods are aware of nested parentheses and quoted strings,
 * so function calls and subexpressions are handled correctly.
 */
#[\AllowDynamicProperties]
class QueryObject extends StringObject
{
    /**
     * The raw SQL query string supplied at construction time.
     *
     * @var mixed
     */
    protected $rawData;

    /**
     * In-memory cache for the parsed result.
     * Avoids repeating expensive work when parseStructure() is called
     * more than once on the same instance.
     *
     * @var array<string, mixed>|null
     */
    private ?array $parsedCache = null;

    // -------------------------------------------------------------------------
    // Construction
    // -------------------------------------------------------------------------

    /**
     * @param mixed $data The SQL query string to wrap.
     */
    public function __construct($data)
    {
        $this->rawData = $data;
        parent::__construct($data);
    }

    // -------------------------------------------------------------------------
    // Public API – statement-type helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the primary SQL statement keyword in upper-case.
     *
     * Leading CTE (WITH …) clauses are stripped before detection so that
     * "WITH cte AS (…) SELECT …" still returns "SELECT".
     *
     * @return string  e.g. "SELECT", "INSERT", "UPDATE", "DELETE",
     *                 "CREATE", "DROP", "ALTER", "TRUNCATE", or "UNKNOWN".
     */
    public function getStatementType(): string
    {
        $query = trim((string) $this->rawData);

        // Remove a leading CTE block so the keyword beneath it is found correctly
        $stripped = preg_replace(
            '/^WITH\s+(?:RECURSIVE\s+)?.+?\s+(?=SELECT|INSERT|UPDATE|DELETE)/si',
            '',
            $query
        );
        $stripped = trim($stripped ?? $query);

        if (
            preg_match(
                '/^(SELECT|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|ALTER|TRUNCATE
              |SHOW|DESCRIBE|EXPLAIN|CALL|EXEC(?:UTE)?|MERGE
              |GRANT|REVOKE|BEGIN|COMMIT|ROLLBACK|SAVEPOINT|SET|USE)\b/ix',
                $stripped,
                $m
            )
        ) {
            return strtoupper($m[1]);
        }

        return 'UNKNOWN';
    }

    /** Returns true when the query is a SELECT statement. */
    public function isSelect(): bool
    {
        return $this->getStatementType() === 'SELECT';
    }

    /** Returns true when the query is an INSERT statement. */
    public function isInsert(): bool
    {
        return $this->getStatementType() === 'INSERT';
    }

    /** Returns true when the query is an UPDATE statement. */
    public function isUpdate(): bool
    {
        return $this->getStatementType() === 'UPDATE';
    }

    /** Returns true when the query is a DELETE statement. */
    public function isDelete(): bool
    {
        return $this->getStatementType() === 'DELETE';
    }

    /** Returns true when the query is a REPLACE statement. */
    public function isReplace(): bool
    {
        return $this->getStatementType() === 'REPLACE';
    }

    /**
     * Returns true when the query contains a UNION or UNION ALL operator
     * at the top level (not inside a subquery).
     */
    public function isUnion(): bool
    {
        return (bool) preg_match('/\bUNION\b/i', (string) $this->rawData);
    }

    /**
     * Returns true when the query contains at least one subquery
     * (a SELECT inside parentheses).
     */
    public function hasSubquery(): bool
    {
        return (bool) preg_match('/\(\s*SELECT\b/i', (string) $this->rawData);
    }

    /**
     * Returns true when the query begins with a CTE (WITH … AS (…)) clause.
     */
    public function hasCte(): bool
    {
        return (bool) preg_match('/^\s*WITH\s+/i', (string) $this->rawData);
    }

    // -------------------------------------------------------------------------
    // Public API – main entry point
    // -------------------------------------------------------------------------

    /**
     * Parses the SQL query and returns its structure as an associative array.
     *
     * The top-level keys always present:
     *   - statement_type  (string)   PRIMARY keyword in upper-case
     *   - is_union        (bool)     true when UNION / UNION ALL is detected
     *   - has_subquery    (bool)     true when a nested SELECT is detected
     *   - has_cte         (bool)     true when a WITH … clause is present
     *
     * Additional keys depend on the statement type. See each private
     * parse*() method for the structure it returns.
     *
     * Results are cached: repeated calls return the same array without
     * re-parsing the query.
     *
     * @return array|array{has_cte: bool, has_subquery: bool, is_union: bool, statement_type: string, raw: string, union_parts: array{ 'union_type': string, 'query': array }}
     */
    public function parseStructure(): array
    {
        if ($this->parsedCache !== null) {
            return $this->parsedCache;
        }

        $query = $this->normalizeQuery((string) $this->rawData);

        $result = [
            'statement_type' => $this->getStatementType(),
            'is_union' => $this->isUnion(),
            'has_subquery' => $this->hasSubquery(),
            'has_cte' => $this->hasCte(),
        ];

        // UNION queries are split into individual SELECT parts first
        if ($this->isUnion()) {
            $result['union_parts'] = $this->parseUnion($query);
            return $this->parsedCache = $result;
        }

        // Dispatch to the dedicated parser for each statement type
        switch ($result['statement_type']) {
            case 'SELECT':
                $result += $this->parseSelect($query);
                break;
            case 'INSERT':
                $result += $this->parseInsert($query, false);
                break;
            case 'REPLACE':
                $result += $this->parseInsert($query, true);
                break;
            case 'UPDATE':
                $result += $this->parseUpdate($query);
                break;
            case 'DELETE':
                $result += $this->parseDelete($query);
                break;
            case 'CREATE':
                $result += $this->parseCreate($query);
                break;
            case 'DROP':
                $result += $this->parseDrop($query);
                break;
            case 'ALTER':
                $result += $this->parseAlter($query);
                break;
            case 'TRUNCATE':
                $result += $this->parseTruncate($query);
                break;
            default:
                // Store the raw query for unsupported statement types
                $result['raw'] = $query;
                break;
        }

        return $this->parsedCache = $result;
    }

    // =========================================================================
    // UNION
    // =========================================================================

    /**
     * Splits a UNION query into its individual SELECT statements and parses
     * each one independently.
     *
     * The first part carries union_type = 'FIRST'; every subsequent part
     * carries 'UNION' or 'UNION ALL' to indicate how it is connected to
     * the preceding part.
     *
     * @param  string $query The full UNION query.
     * @return array<int, array{union_type: string, query: array}>
     */
    private function parseUnion(string $query): array
    {
        // Split on top-level UNION / UNION ALL while preserving the delimiter
        $parts = preg_split(
            '/\b(UNION\s+ALL|UNION)\b/i',
            $query,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );

        $result = [];
        $unionType = null;

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Delimiter token
            if (preg_match('/^UNION(\s+ALL)?$/i', $part)) {
                $unionType = strtoupper(preg_replace('/\s+/', ' ', $part));
                continue;
            }

            $subParser = new static($part);
            $result[] = [
                'union_type' => $unionType ?? 'FIRST',
                'query' => $subParser->parseSelect($part),
            ];
            $unionType = null;
        }

        return $result;
    }

    // =========================================================================
    // SELECT
    // =========================================================================

    /**
     * Parses a SELECT statement.
     *
     * Returned keys (all optional; absent when the clause is not present):
     *   cte        – array of CTE entries from a leading WITH clause
     *   modifier   – "DISTINCT" or "ALL"
     *   columns    – structured column list (see parseColumnList())
     *   tables     – FROM table list (see parseTableList())
     *   joins      – JOIN entries (see parseJoins())
     *   conditions – WHERE conditions (see parseWhereConditions())
     *   group_by   – GROUP BY expression list
     *   having     – HAVING conditions
     *   order_by   – ORDER BY entries (see parseOrderBy())
     *   limit      – LIMIT / OFFSET (see parseLimit())
     *   locking    – "FOR UPDATE" or "LOCK IN SHARE MODE"
     *
     * @param  string $query The SELECT query (may include a leading WITH).
     * @return array<string, mixed>
     */
    private function parseSelect(string $query): array
    {
        $result = [];

        // ── CTE (WITH … AS (…)) ─────────────────────────────────────────────
        if (preg_match('/^WITH\s+(?P<body>(?:RECURSIVE\s+)?.+?)\s+(?=SELECT\b)/si', $query, $m)) {
            $result['cte'] = $this->parseCte($m['body']);
            // Strip the CTE so the remainder parses as a plain SELECT
            $query = ltrim(
                (string) preg_replace('/^WITH\s+(?:RECURSIVE\s+)?.+?\s+(?=SELECT\b)/si', '', $query)
            );
        }

        // ── DISTINCT / ALL modifier ──────────────────────────────────────────
        if (preg_match('/^SELECT\s+(DISTINCT|ALL)\s+/i', $query, $m)) {
            $result['modifier'] = strtoupper($m[1]);
        }

        // ── Selected columns (between SELECT … and FROM) ─────────────────────
        $columnsRaw = $this->extractClause(
            $query,
            'SELECT(?:\s+(?:DISTINCT|ALL))?',
            'FROM'
        );
        if ($columnsRaw !== null) {
            $result['columns'] = $this->parseColumnList($columnsRaw);
        }

        // ── FROM tables ───────────────────────────────────────────────────────
        // Stop before any JOIN keyword or subsequent clause keyword
        $fromRaw = $this->extractClause(
            $query,
            'FROM',
            'WHERE|GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|UNION'
            . '|FOR\s+UPDATE|LOCK\s+IN'
            . '|(?:LEFT|RIGHT|INNER|CROSS|FULL|STRAIGHT_|NATURAL)(?:\s+OUTER)?\s+JOIN\b|(?<!\w)JOIN\b'
        );
        if ($fromRaw !== null) {
            $result['tables'] = $this->parseTableList($fromRaw);
        }

        // ── JOINs ─────────────────────────────────────────────────────────────
        $joins = $this->parseJoins($query);
        if (!empty($joins)) {
            $result['joins'] = $joins;
        }

        // ── WHERE ─────────────────────────────────────────────────────────────
        $whereRaw = $this->extractClause(
            $query,
            'WHERE',
            'GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT|UNION|FOR\s+UPDATE|LOCK\s+IN'
        );
        if ($whereRaw !== null) {
            $result['conditions'] = $this->parseWhereConditions($whereRaw);
        }

        // ── GROUP BY ──────────────────────────────────────────────────────────
        $groupByRaw = $this->extractClause(
            $query,
            'GROUP\s+BY',
            'HAVING|ORDER\s+BY|LIMIT|UNION|FOR\s+UPDATE|LOCK\s+IN'
        );
        if ($groupByRaw !== null) {
            $result['group_by'] = $this->splitByCommaOutsideParentheses($groupByRaw);
        }

        // ── HAVING ────────────────────────────────────────────────────────────
        $havingRaw = $this->extractClause(
            $query,
            'HAVING',
            'ORDER\s+BY|LIMIT|UNION|FOR\s+UPDATE|LOCK\s+IN'
        );
        if ($havingRaw !== null) {
            $result['having'] = $this->parseWhereConditions($havingRaw);
        }

        // ── ORDER BY ──────────────────────────────────────────────────────────
        $orderByRaw = $this->extractClause(
            $query,
            'ORDER\s+BY',
            'LIMIT|UNION|FOR\s+UPDATE|LOCK\s+IN'
        );
        if ($orderByRaw !== null) {
            $result['order_by'] = $this->parseOrderBy($orderByRaw);
        }

        // ── LIMIT (and optional OFFSET) ───────────────────────────────────────
        $limitRaw = $this->extractClause(
            $query,
            'LIMIT',
            'UNION|FOR\s+UPDATE|LOCK\s+IN'
        );
        if ($limitRaw !== null) {
            $result['limit'] = $this->parseLimit($limitRaw);
        }

        // ── Locking reads ─────────────────────────────────────────────────────
        if (preg_match('/\bFOR\s+UPDATE\b/i', $query)) {
            $result['locking'] = 'FOR UPDATE';
        } elseif (preg_match('/\bLOCK\s+IN\s+SHARE\s+MODE\b/i', $query)) {
            $result['locking'] = 'LOCK IN SHARE MODE';
        }

        return $result;
    }

    // =========================================================================
    // INSERT / REPLACE
    // =========================================================================

    /**
     * Parses an INSERT or REPLACE statement.
     *
     * Returned keys:
     *   modifier              – "IGNORE", "DELAYED", "LOW_PRIORITY", "HIGH_PRIORITY"
     *   table                 – target table name (unquoted)
     *   columns               – list of column names from the column list
     *   values                – array of value-rows (INSERT … VALUES form)
     *   select                – parsed SELECT structure (INSERT … SELECT form)
     *   on_duplicate_key_update – SET assignments for ON DUPLICATE KEY UPDATE
     *
     * @param  string $query     The INSERT / REPLACE query.
     * @param  bool   $isReplace true when the statement is REPLACE INTO.
     * @return array<string, mixed>
     */
    private function parseInsert(string $query, bool $isReplace = false): array
    {
        $result = [];
        $keyword = $isReplace ? 'REPLACE' : 'INSERT';

        // ── Modifier ──────────────────────────────────────────────────────────
        if (preg_match("/{$keyword}\s+(IGNORE|DELAYED|LOW_PRIORITY|HIGH_PRIORITY)\s+/i", $query, $m)) {
            $result['modifier'] = strtoupper($m[1]);
        }

        // ── Target table ──────────────────────────────────────────────────────
        if (
            preg_match(
                "/{$keyword}(?:\s+(?:IGNORE|DELAYED|LOW_PRIORITY|HIGH_PRIORITY))?\s+(?:INTO\s+)?([`'\"\\[\\]]?[\\w.]+[`'\"\\[\\]]?)/i",
                $query,
                $m
            )
        ) {
            $result['table'] = $this->stripQuotes($m[1]);
        }

        // ── Column list (optional) ─────────────────────────────────────────────
        // Match the first parenthesised list that precedes VALUES or SELECT
        if (preg_match('/\(\s*([^)]+)\s*\)\s*(?:VALUES|SELECT)\b/i', $query, $m)) {
            $result['columns'] = array_map([$this, 'stripQuotes'], $this->splitByCommaOutsideParentheses($m[1]));
        }

        // ── INSERT … SELECT form ──────────────────────────────────────────────
        // Detect SELECT that is NOT inside the column list / VALUES
        $selectPos = stripos($query, ' SELECT ');
        if ($selectPos !== false) {
            $selectQuery = ltrim(substr($query, $selectPos));
            $subParser = new static($selectQuery);
            $result['select'] = $subParser->parseSelect($selectQuery);
        } else {
            // ── VALUES (multiple rows) ─────────────────────────────────────────
            if (preg_match('/\bVALUES\s*(\(.+\)(?:\s*,\s*\(.+\))*)\s*(?:ON\s+DUPLICATE|$)/is', $query, $m)) {
                $result['values'] = $this->parseInsertValues($m[1]);
            }
        }

        // ── ON DUPLICATE KEY UPDATE ───────────────────────────────────────────
        if (preg_match('/\bON\s+DUPLICATE\s+KEY\s+UPDATE\s+(.+)$/is', $query, $m)) {
            $result['on_duplicate_key_update'] = $this->parseSetAssignments(trim($m[1]));
        }

        return $result;
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /**
     * Parses an UPDATE statement.
     *
     * Returned keys:
     *   modifier   – "LOW_PRIORITY" or "IGNORE"
     *   tables     – target table(s) (see parseTableList())
     *   joins      – JOIN entries
     *   set        – SET assignments (see parseSetAssignments())
     *   conditions – WHERE conditions
     *   order_by   – ORDER BY entries
     *   limit      – LIMIT entry
     *
     * @param  string $query The UPDATE query.
     * @return array<string, mixed>
     */
    private function parseUpdate(string $query): array
    {
        $result = [];

        // ── Modifier ──────────────────────────────────────────────────────────
        if (preg_match('/^UPDATE\s+(LOW_PRIORITY|IGNORE)\s+/i', $query, $m)) {
            $result['modifier'] = strtoupper($m[1]);
        }

        // ── Target table(s) ───────────────────────────────────────────────────
        // Extract everything between UPDATE [modifier] and the first SET keyword
        if (preg_match('/^UPDATE(?:\s+(?:LOW_PRIORITY|IGNORE))?\s+(.+?)\s+SET\s+/is', $query, $m)) {
            $tablesRaw = trim($m[1]);
            // If there is a JOIN in the table section, take only the first token
            $tableOnly = preg_split(
                '/\s+(?:LEFT|RIGHT|INNER|CROSS|FULL|NATURAL|STRAIGHT_)?(?:\s+OUTER)?\s*JOIN\b/i',
                $tablesRaw
            )[0] ?? $tablesRaw;
            $result['tables'] = $this->parseTableList(trim($tableOnly));
        }

        // ── JOINs ─────────────────────────────────────────────────────────────
        $joins = $this->parseJoins($query);
        if (!empty($joins)) {
            $result['joins'] = $joins;
        }

        // ── SET ───────────────────────────────────────────────────────────────
        $setRaw = $this->extractClause($query, 'SET', 'WHERE|ORDER\s+BY|LIMIT');
        if ($setRaw !== null) {
            $result['set'] = $this->parseSetAssignments($setRaw);
        }

        // ── WHERE ─────────────────────────────────────────────────────────────
        $whereRaw = $this->extractClause($query, 'WHERE', 'ORDER\s+BY|LIMIT');
        if ($whereRaw !== null) {
            $result['conditions'] = $this->parseWhereConditions($whereRaw);
        }

        // ── ORDER BY ──────────────────────────────────────────────────────────
        $orderByRaw = $this->extractClause($query, 'ORDER\s+BY', 'LIMIT');
        if ($orderByRaw !== null) {
            $result['order_by'] = $this->parseOrderBy($orderByRaw);
        }

        // ── LIMIT ─────────────────────────────────────────────────────────────
        $limitRaw = $this->extractClause($query, 'LIMIT', null);
        if ($limitRaw !== null) {
            $result['limit'] = $this->parseLimit($limitRaw);
        }

        return $result;
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /**
     * Parses a DELETE statement.
     *
     * Returned keys:
     *   modifier       – "LOW_PRIORITY", "QUICK", or "IGNORE"
     *   target_tables  – tables listed before FROM in a multi-table DELETE
     *   tables         – FROM table list
     *   joins          – JOIN entries
     *   conditions     – WHERE conditions
     *   order_by       – ORDER BY entries
     *   limit          – LIMIT entry
     *
     * @param  string $query The DELETE query.
     * @return array<string, mixed>
     */
    private function parseDelete(string $query): array
    {
        $result = [];

        // ── Modifier ──────────────────────────────────────────────────────────
        if (preg_match('/^DELETE\s+(LOW_PRIORITY|QUICK|IGNORE)\s+/i', $query, $m)) {
            $result['modifier'] = strtoupper($m[1]);
        }

        // ── Multi-table DELETE: DELETE t1, t2 FROM … ──────────────────────────
        // The regex checks that what follows DELETE is not FROM (i.e. there are
        // explicit target aliases listed before FROM).
        if (preg_match('/^DELETE(?:\s+(?:LOW_PRIORITY|QUICK|IGNORE))?\s+((?!FROM\b).+?)\s+FROM\s+/is', $query, $m)) {
            $result['target_tables'] = array_map('trim', $this->splitByCommaOutsideParentheses($m[1]));
        }

        // ── FROM table ────────────────────────────────────────────────────────
        $fromRaw = $this->extractClause(
            $query,
            'FROM',
            'WHERE|ORDER\s+BY|LIMIT'
            . '|(?:LEFT|RIGHT|INNER|CROSS|FULL|STRAIGHT_|NATURAL)(?:\s+OUTER)?\s+JOIN\b|(?<!\w)JOIN\b'
        );
        if ($fromRaw !== null) {
            $result['tables'] = $this->parseTableList($fromRaw);
        }

        // ── JOINs ─────────────────────────────────────────────────────────────
        $joins = $this->parseJoins($query);
        if (!empty($joins)) {
            $result['joins'] = $joins;
        }

        // ── WHERE ─────────────────────────────────────────────────────────────
        $whereRaw = $this->extractClause($query, 'WHERE', 'ORDER\s+BY|LIMIT');
        if ($whereRaw !== null) {
            $result['conditions'] = $this->parseWhereConditions($whereRaw);
        }

        // ── ORDER BY ──────────────────────────────────────────────────────────
        $orderByRaw = $this->extractClause($query, 'ORDER\s+BY', 'LIMIT');
        if ($orderByRaw !== null) {
            $result['order_by'] = $this->parseOrderBy($orderByRaw);
        }

        // ── LIMIT ─────────────────────────────────────────────────────────────
        $limitRaw = $this->extractClause($query, 'LIMIT', null);
        if ($limitRaw !== null) {
            $result['limit'] = $this->parseLimit($limitRaw);
        }

        return $result;
    }

    // =========================================================================
    // CREATE
    // =========================================================================

    /**
     * Parses a CREATE statement.
     *
     * Always present:
     *   object_type – "TABLE", "VIEW", "INDEX", or "DATABASE"
     *
     * For TABLE:
     *   table         – table name
     *   if_not_exists – bool
     *   temporary     – bool
     *   columns       – column / constraint definitions (see parseCreateTableColumns())
     *   table_options – ENGINE, CHARSET, COLLATE, etc.
     *
     * For VIEW:
     *   view – view name
     *   as   – parsed SELECT structure for the defining query
     *
     * For INDEX:
     *   index   – index name
     *   table   – table name
     *   columns – indexed column list
     *   unique  – bool
     *
     * For DATABASE / SCHEMA:
     *   database      – database name
     *   if_not_exists – bool
     *
     * @param  string $query The CREATE query.
     * @return array<string, mixed>
     */
    private function parseCreate(string $query): array
    {
        $result = [];

        // ── CREATE [TEMPORARY] TABLE ──────────────────────────────────────────
        if (
            preg_match(
                '/^CREATE\s+(?:OR\s+REPLACE\s+)?(?:TEMPORARY\s+)?TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?([`\'"\[]?[\w.]+[`\'"\]]?)/i',
                $query,
                $m
            )
        ) {
            $result['object_type'] = 'TABLE';
            $result['table'] = $this->stripQuotes($m[1]);
            $result['if_not_exists'] = (bool) preg_match('/\bIF\s+NOT\s+EXISTS\b/i', $query);
            $result['temporary'] = (bool) preg_match('/\bTEMPORARY\b/i', $query);

            // Column definitions live inside the outermost parentheses
            if (preg_match('/\((.+)\)\s*(?:[A-Z]|$)/is', $query, $bodyM)) {
                $result['columns'] = $this->parseCreateTableColumns($bodyM[1]);
            }
            $result['table_options'] = $this->parseTableOptions($query);

            // ── CREATE [OR REPLACE] VIEW ─────────────────────────────────────────
        } elseif (
            preg_match(
                '/^CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+([`\'"\[]?[\w.]+[`\'"\]]?)\s+AS\s+(.+)$/is',
                $query,
                $m
            )
        ) {
            $result['object_type'] = 'VIEW';
            $result['view'] = $this->stripQuotes($m[1]);
            $subParser = new static(trim($m[2]));
            $result['as'] = $subParser->parseSelect(trim($m[2]));

            // ── CREATE [UNIQUE|FULLTEXT|SPATIAL] INDEX ───────────────────────────
        } elseif (
            preg_match(
                '/^CREATE\s+(?:UNIQUE\s+|FULLTEXT\s+|SPATIAL\s+)?INDEX\s+'
                . '([`\'"\[]?[\w]+[`\'"\]]?)\s+(?:USING\s+\w+\s+)?ON\s+'
                . '([`\'"\[]?[\w]+[`\'"\]]?)\s*\(([^)]+)\)/i',
                $query,
                $m
            )
        ) {
            $result['object_type'] = 'INDEX';
            $result['index'] = $this->stripQuotes($m[1]);
            $result['table'] = $this->stripQuotes($m[2]);
            $result['columns'] = array_map('trim', explode(',', $m[3]));
            $result['unique'] = (bool) preg_match('/\bUNIQUE\b/i', $query);

            // ── CREATE DATABASE / SCHEMA ─────────────────────────────────────────
        } elseif (
            preg_match(
                '/^CREATE\s+(?:DATABASE|SCHEMA)\s+(?:IF\s+NOT\s+EXISTS\s+)?([`\'"\[]?[\w]+[`\'"\]]?)/i',
                $query,
                $m
            )
        ) {
            $result['object_type'] = 'DATABASE';
            $result['database'] = $this->stripQuotes($m[1]);
            $result['if_not_exists'] = (bool) preg_match('/\bIF\s+NOT\s+EXISTS\b/i', $query);
        }

        return $result;
    }

    // =========================================================================
    // DROP
    // =========================================================================

    /**
     * Parses a DROP statement.
     *
     * Returned keys:
     *   object_type – "TABLE", "VIEW", "INDEX", "DATABASE", etc.
     *   if_exists   – bool
     *   objects     – list of names being dropped
     *   table       – (INDEX only) the table the index belongs to
     *
     * @param  string $query The DROP query.
     * @return array<string, mixed>
     */
    private function parseDrop(string $query): array
    {
        $result = [];

        if (
            preg_match(
                '/^DROP\s+(TABLE|DATABASE|SCHEMA|VIEW|INDEX|PROCEDURE|FUNCTION|TRIGGER|EVENT)'
                . '\s+(?:IF\s+EXISTS\s+)?(.+?)(?:\s+ON\s+(.+))?$/i',
                $query,
                $m
            )
        ) {
            $result['object_type'] = strtoupper($m[1]);
            $result['if_exists'] = (bool) preg_match('/\bIF\s+EXISTS\b/i', $query);
            // Multiple tables can be dropped in a single statement (e.g. DROP TABLE a, b)
            $result['objects'] = array_map(
                fn(string $n): string => $this->stripQuotes(trim($n)),
                $this->splitByCommaOutsideParentheses(trim($m[2]))
            );
            // DROP INDEX … ON table_name
            if (!empty($m[3])) {
                $result['table'] = $this->stripQuotes(trim($m[3]));
            }
        }

        return $result;
    }

    // =========================================================================
    // ALTER
    // =========================================================================

    /**
     * Parses an ALTER TABLE (or ALTER DATABASE) statement.
     *
     * Returned keys:
     *   object_type – "TABLE" or "DATABASE"
     *   table       – table name (TABLE only)
     *   database    – database name (DATABASE only)
     *   actions     – array of action entries (see parseAlterActions())
     *
     * @param  string $query The ALTER query.
     * @return array<string, mixed>
     */
    private function parseAlter(string $query): array
    {
        $result = [];

        if (preg_match('/^ALTER\s+TABLE\s+([`\'"\[]?[\w.]+[`\'"\]]?)\s+(.+)$/is', $query, $m)) {
            $result['object_type'] = 'TABLE';
            $result['table'] = $this->stripQuotes($m[1]);
            $result['actions'] = $this->parseAlterActions($m[2]);
        } elseif (preg_match('/^ALTER\s+(?:DATABASE|SCHEMA)\s+([`\'"\[]?[\w]+[`\'"\]]?)\s+(.+)$/is', $query, $m)) {
            $result['object_type'] = 'DATABASE';
            $result['database'] = $this->stripQuotes($m[1]);
            $result['actions'] = [trim($m[2])];
        }

        return $result;
    }

    private function parseAlterActions(string $actionsStr): array
    {
        $parts = $this->splitByCommaOutsideParentheses($actionsStr);
        $actions = [];

        foreach ($parts as $actionRaw) {
            $action = trim($actionRaw);
            if ($action === '') {
                continue;
            }

            $entry = ['raw' => $action];

            // ── ORDER MATTERS: specific ADD sub-types before generic ADD COLUMN ──

            if (preg_match('/^ADD\s+PRIMARY\s+KEY\s*\(([^)]+)\)/i', $action, $m)) {
                // ADD PRIMARY KEY (col1, col2)
                $entry['type'] = 'ADD_PRIMARY_KEY';
                $entry['columns'] = array_map('trim', explode(',', $m[1]));

            } elseif (preg_match('/^ADD\s+(?:CONSTRAINT\s+[`\'"\[]?[\w]+[`\'"\]]?\s+)?FOREIGN\s+KEY/i', $action)) {
                // ADD [CONSTRAINT name] FOREIGN KEY …
                $entry['type'] = 'ADD_FOREIGN_KEY';

            } elseif (preg_match('/^ADD\s+(?:UNIQUE\s+)?(?:INDEX|KEY)\b/i', $action)) {
                // ADD [UNIQUE] INDEX/KEY …
                $entry['type'] = 'ADD_INDEX';
                $entry['unique'] = (bool) preg_match('/\bUNIQUE\b/i', $action);

            } elseif (preg_match('/^ADD\s+(?:COLUMN\s+)?(.+)$/i', $action, $m)) {
                // Generic ADD [COLUMN] — must come last among ADD variants
                $entry['type'] = 'ADD_COLUMN';
                $entry['definition'] = trim($m[1]);

            } elseif (preg_match('/^DROP\s+PRIMARY\s+KEY/i', $action)) {
                $entry['type'] = 'DROP_PRIMARY_KEY';

            } elseif (preg_match('/^DROP\s+FOREIGN\s+KEY\s+([`\'"\[]?[\w]+[`\'"\]]?)/i', $action, $m)) {
                $entry['type'] = 'DROP_FOREIGN_KEY';
                $entry['constraint'] = $this->stripQuotes($m[1]);

            } elseif (preg_match('/^DROP\s+(?:INDEX|KEY)\s+([`\'"\[]?[\w]+[`\'"\]]?)/i', $action, $m)) {
                $entry['type'] = 'DROP_INDEX';
                $entry['index'] = $this->stripQuotes($m[1]);

            } elseif (preg_match('/^DROP\s+COLUMN\s+([`\'"\[]?[\w]+[`\'"\]]?)/i', $action, $m)) {
                $entry['type'] = 'DROP_COLUMN';
                $entry['column'] = $this->stripQuotes($m[1]);

            } elseif (preg_match('/^MODIFY\s+(?:COLUMN\s+)?(.+)$/i', $action, $m)) {
                $entry['type'] = 'MODIFY_COLUMN';
                $entry['definition'] = trim($m[1]);

            } elseif (preg_match('/^CHANGE\s+(?:COLUMN\s+)?([`\'"\[]?[\w]+[`\'"\]]?)\s+(.+)$/i', $action, $m)) {
                $entry['type'] = 'CHANGE_COLUMN';
                $entry['old_column'] = $this->stripQuotes($m[1]);
                $entry['definition'] = trim($m[2]);

            } elseif (preg_match('/^RENAME\s+(?:TO|AS)\s+([`\'"\[]?[\w.]+[`\'"\]]?)/i', $action, $m)) {
                $entry['type'] = 'RENAME';
                $entry['new_name'] = $this->stripQuotes($m[1]);

            } elseif (preg_match('/^(?:ENGINE|DEFAULT\s+CHARSET|CHARACTER\s+SET|COLLATE|AUTO_INCREMENT)\s*=\s*(.+)/i', $action, $m)) {
                $entry['type'] = 'TABLE_OPTION';
                $entry['value'] = trim($m[1]);

            } else {
                $entry['type'] = 'UNKNOWN';
            }

            $actions[] = $entry;
        }

        return $actions;
    }

    // =========================================================================
    // TRUNCATE
    // =========================================================================

    /**
     * Parses a TRUNCATE TABLE statement.
     *
     * Returned keys:
     *   object_type – always "TABLE"
     *   table       – the table name
     *
     * @param  string $query The TRUNCATE query.
     * @return array<string, mixed>
     */
    private function parseTruncate(string $query): array
    {
        $result = [];

        if (preg_match('/^TRUNCATE\s+(?:TABLE\s+)?([`\'"\[]?[\w.]+[`\'"\]]?)/i', $query, $m)) {
            $result['object_type'] = 'TABLE';
            $result['table'] = $this->stripQuotes($m[1]);
        }

        return $result;
    }

    // =========================================================================
    // CTE parser
    // =========================================================================

    /**
     * Parses the body that follows the WITH keyword.
     *
     * Handles multiple CTEs: WITH cte1 AS (…), cte2 AS (…)
     * and the RECURSIVE modifier on the entire WITH clause.
     *
     * Each returned entry:
     *   name      – CTE alias
     *   recursive – bool (true when WITH RECURSIVE)
     *   query     – parsed SELECT structure for the CTE body
     *
     * @param  string $cteBody Everything after "WITH" up to the main SELECT.
     * @return array<int, array{name: string, recursive: bool, query: array}>
     */
    private function parseCte(string $cteBody): array
    {
        $isRecursive = (bool) preg_match('/^RECURSIVE\s+/i', $cteBody);
        $cteBody = (string) preg_replace('/^RECURSIVE\s+/i', '', $cteBody);

        $ctes = [];
        $remaining = trim($cteBody);

        while ($remaining !== '') {
            // Each CTE starts with:  name AS (
            if (!preg_match('/^([`\'"\[]?[\w]+[`\'"\]]?)\s+AS\s*\(/i', $remaining, $m)) {
                break;
            }

            $name = $this->stripQuotes($m[1]);
            $openParen = strlen($m[0]) - 1; // position of '('

            // Extract the balanced body inside the parentheses
            $body = $this->extractBalancedParentheses($remaining, $openParen);
            if ($body === null) {
                break;
            }

            $subParser = new static($body);
            $ctes[] = [
                'name' => $name,
                'recursive' => $isRecursive,
                'query' => $subParser->parseSelect($body),
            ];

            // Advance past "name AS (body)" plus the closing ')'
            $consumed = $openParen + 1 + strlen($body) + 1;
            $remaining = ltrim(substr($remaining, $consumed), " \t\n\r\0\x0B,");
        }

        return $ctes;
    }

    // =========================================================================
    // JOIN parser
    // =========================================================================

    /**
     * Parses all JOIN clauses from a query string.
     *
     * Supported join types (case-insensitive):
     *   JOIN, INNER JOIN, LEFT [OUTER] JOIN, RIGHT [OUTER] JOIN,
     *   FULL [OUTER] JOIN, CROSS JOIN, STRAIGHT_JOIN, NATURAL JOIN.
     *
     * Each returned entry:
     *   type  – normalised join type, e.g. "LEFT JOIN"
     *   table – joined table name (unquoted)
     *   alias – table alias if present
     *   on    – structured condition array from the ON clause
     *   using – list of column names from USING (…)
     *
     * @param  string $query The full query string.
     * @return array<int, array<string, mixed>>
     */
    private function parseJoins(string $query): array
    {
        $joins = [];

        // Locate every JOIN keyword variant (order matters: longer forms first)
        $joinRx =
            '/\b((?:LEFT|RIGHT|FULL|INNER|CROSS|NATURAL)(?:\s+OUTER)?\s+JOIN'
            . '|STRAIGHT_JOIN|JOIN)\b/i';

        if (!preg_match_all($joinRx, $query, $kwMatches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [];
        }

        foreach ($kwMatches as $kw) {
            $keyword = $kw[0][0];
            $kwEnd = $kw[0][1] + strlen($keyword);
            $joinType = strtoupper(preg_replace('/\s+/', ' ', trim($keyword)));

            // Extract the segment that belongs to this JOIN (up to the next
            // JOIN keyword or major clause keyword, respecting nesting)
            $tail = ltrim(substr($query, $kwEnd));
            $segLen = $this->findJoinSegmentEnd($tail);
            $segment = trim(substr($tail, 0, $segLen));

            // ── Table name ────────────────────────────────────────────────────
            if (!preg_match('/^([`\'"\[]?[\w.]+[`\'"\]]?)(.*)/s', $segment, $m)) {
                continue;
            }
            $table = $this->stripQuotes($m[1]);
            $rest = ltrim($m[2]);

            $entry = ['type' => $joinType, 'table' => $table];

            // ── Alias (AS alias | bare-word alias) ────────────────────────────
            // Reserved words must not be consumed as an alias
            $reservedRx = '/^(?:ON|USING|WHERE|SET|HAVING|ORDER|GROUP|LIMIT|UNION|FOR|LOCK)$/i';

            if (preg_match('/^AS\s+([`\'"\[]?[\w]+[`\'"\]]?)\s*/i', $rest, $am)) {
                // Explicit AS keyword
                $entry['alias'] = $this->stripQuotes($am[1]);
                $rest = ltrim(substr($rest, strlen($am[0])));
            } elseif (
                preg_match('/^([`\'"\[]?[\w]+[`\'"\]]?)\s+/s', $rest, $am)
                && !preg_match($reservedRx, trim($am[1]))
            ) {
                // Bare-word alias followed by a space (e.g. "customers c ON …")
                $entry['alias'] = $this->stripQuotes(trim($am[1]));
                $rest = ltrim(substr($rest, strlen($am[0])));
            }

            // ── ON clause ─────────────────────────────────────────────────────
            if (preg_match('/^ON\s+(.*)/is', $rest, $onM)) {
                // Strip any trailing USING clause from the ON text
                $onText = trim((string) preg_replace('/\s+USING\s*\(.+$/is', '', $onM[1]));
                if ($onText !== '') {
                    $entry['on'] = $this->parseWhereConditions($onText);
                }
            }

            // ── USING clause ──────────────────────────────────────────────────
            if (preg_match('/\bUSING\s*\(([^)]+)\)/i', $rest, $usingM)) {
                $entry['using'] = array_map('trim', explode(',', $usingM[1]));
            }

            $joins[] = $entry;
        }

        return $joins;
    }

    /**
     * Returns the byte-length of the current JOIN's content segment within $str.
     *
     * Scans $str forward one character at a time, respecting quoted strings and
     * nested parentheses, and stops when a JOIN keyword or major clause keyword
     * is encountered at depth 0 (outside any quotes or parentheses).
     *
     * The scan always skips at least one character so that the table name that
     * immediately follows the JOIN keyword is never mistaken for a stop word.
     *
     * @param  string $str Text that begins right after the JOIN keyword,
     *                     e.g. "customers c ON o.id = c.o_id WHERE …"
     * @return int         Number of characters that belong to this segment.
     */
    private function findJoinSegmentEnd(string $str): int
    {
        // Keywords that signal the end of a JOIN segment
        $stopRx =
            '/^(?:(?:LEFT|RIGHT|FULL|INNER|CROSS|NATURAL)(?:\s+OUTER)?\s+JOIN'
            . '|STRAIGHT_JOIN|JOIN|WHERE|GROUP\s+BY|HAVING|ORDER\s+BY'
            . '|LIMIT|UNION|FOR\s+UPDATE|LOCK\s+IN|SET)\b/i';

        $len = strlen($str);
        $depth = 0;
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < $len; $i++) {
            $ch = $str[$i];

            // ── Quote tracking ────────────────────────────────────────────────
            if (!$inQuote && ($ch === "'" || $ch === '"' || $ch === '`')) {
                $inQuote = true;
                $quoteChar = $ch;
                continue;
            }
            if ($inQuote) {
                if ($ch === $quoteChar && ($i === 0 || $str[$i - 1] !== '\\')) {
                    $inQuote = false;
                }
                continue;
            }

            // ── Parenthesis depth ─────────────────────────────────────────────
            if ($ch === '(') {
                $depth++;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                continue;
            }

            // ── Stop-keyword detection at top level ───────────────────────────
            // Only check after at least one character has been consumed (so the
            // table name itself is never treated as a stop word) and only when
            // the previous character is a non-word character (word boundary).
            if ($depth === 0 && $i > 0) {
                $prev = $str[$i - 1];
                if (!ctype_alnum($prev) && $prev !== '_') {
                    if (preg_match($stopRx, substr($str, $i))) {
                        return $i;
                    }
                }
            }
        }

        return $len; // No stop keyword found → segment runs to end of string
    }

    // =========================================================================
    // WHERE / HAVING condition parser
    // =========================================================================

    /**
     * Parses a WHERE or HAVING clause string into a structured condition list.
     *
     * Supported condition forms:
     *   column OP value           – standard comparison (=, !=, <>, <, >, <=, >=)
     *   column LIKE / NOT LIKE    – pattern matching
     *   column REGEXP / NOT REGEXP
     *   column IN (…) / NOT IN (…) – value list or subquery
     *   column BETWEEN x AND y    – range check
     *   column IS NULL / IS NOT NULL
     *   EXISTS (subquery)
     *   (nested group)            – parenthesised sub-expression
     *
     * Each entry is one of three shapes:
     *
     *   Condition:
     *     type             => 'condition'
     *     column           => string
     *     operator         => string (upper-case)
     *     value            => string | array | null
     *     logical_operator => 'AND' | 'OR' | null
     *
     *   Group (parenthesised sub-expression):
     *     type             => 'group'
     *     conditions       => array (recursive)
     *     logical_operator => 'AND' | 'OR' | null
     *
     *   EXISTS / NOT EXISTS:
     *     type             => 'exists'
     *     negated          => bool
     *     subquery         => string (raw SQL)
     *     logical_operator => 'AND' | 'OR' | null
     *
     *   Raw (unrecognised token):
     *     type             => 'raw'
     *     raw              => string
     *     logical_operator => 'AND' | 'OR' | null
     *
     * @param  string $whereClause The raw WHERE / HAVING / ON clause text.
     * @return array<int, array<string, mixed>>
     */
    private function parseWhereConditions(string $whereClause): array
    {
        $whereClause = trim($whereClause);
        if ($whereClause === '') {
            return [];
        }

        // Tokenise on top-level AND / OR (i.e. not inside parentheses / quotes)
        $tokens = $this->tokenizeConditions($whereClause);

        $conditions = [];
        $i = 0;
        $total = count($tokens);

        while ($i < $total) {
            $token = $tokens[$i];

            // Skip standalone logical-operator tokens; they are consumed below
            if (preg_match('/^(AND|OR)$/i', $token)) {
                $i++;
                continue;
            }

            // Determine which logical operator (if any) follows this token
            $logicalOp = null;
            if (isset($tokens[$i + 1]) && preg_match('/^(AND|OR)$/i', $tokens[$i + 1])) {
                $logicalOp = strtoupper($tokens[$i + 1]);
            }

            // ── Parenthesised group ──────────────────────────────────────────
            if (str_starts_with($token, '(') && str_ends_with($token, ')')) {
                $inner = substr($token, 1, -1);
                $conditions[] = [
                    'type' => 'group',
                    'conditions' => $this->parseWhereConditions($inner),
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── EXISTS / NOT EXISTS ──────────────────────────────────────────
            if (preg_match('/^(NOT\s+)?EXISTS\s*\((.+)\)$/is', $token, $m)) {
                $conditions[] = [
                    'type' => 'exists',
                    'negated' => $m[1] !== '',
                    'subquery' => trim($m[2]),
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── BETWEEN … AND … ──────────────────────────────────────────────
            if (preg_match('/^(.+?)\s+(NOT\s+BETWEEN|BETWEEN)\s+(.+?)\s+AND\s+(.+)$/i', $token, $m)) {
                $conditions[] = [
                    'type' => 'condition',
                    'column' => trim($m[1]),
                    'operator' => strtoupper($m[2]),
                    'value' => [trim($m[3]), trim($m[4])],
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── IN / NOT IN ──────────────────────────────────────────────────
            if (preg_match('/^(.+?)\s+(NOT\s+IN|IN)\s*\((.+)\)$/is', $token, $m)) {
                $inner = trim($m[3]);
                // Check whether the value list is actually a subquery
                $value = preg_match('/^\s*SELECT\b/i', $inner)
                    ? ['subquery' => $inner]
                    : array_map('trim', $this->splitByCommaOutsideParentheses($inner));

                $conditions[] = [
                    'type' => 'condition',
                    'column' => trim($m[1]),
                    'operator' => strtoupper((string) preg_replace('/\s+/', ' ', $m[2])),
                    'value' => $value,
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── IS NULL / IS NOT NULL ─────────────────────────────────────────
            if (preg_match('/^(.+?)\s+(IS\s+(?:NOT\s+)?NULL)$/i', $token, $m)) {
                $conditions[] = [
                    'type' => 'condition',
                    'column' => trim($m[1]),
                    'operator' => strtoupper((string) preg_replace('/\s+/', ' ', $m[2])),
                    'value' => null,
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── Standard comparison ───────────────────────────────────────────
            if (
                preg_match(
                    '/^(.+?)\s*(=|!=|<>|<=|>=|<|>|NOT\s+LIKE|LIKE|NOT\s+REGEXP|REGEXP)\s*(.+)$/is',
                    $token,
                    $m
                )
            ) {
                $conditions[] = [
                    'type' => 'condition',
                    'column' => trim($m[1]),
                    'operator' => strtoupper((string) preg_replace('/\s+/', ' ', trim($m[2]))),
                    'value' => trim($m[3]),
                    'logical_operator' => $logicalOp,
                ];
                $i++;
                continue;
            }

            // ── Fallback: store as raw ────────────────────────────────────────
            $conditions[] = [
                'type' => 'raw',
                'raw' => $token,
                'logical_operator' => $logicalOp,
            ];
            $i++;
        }

        return $conditions;
    }

    /**
     * Splits a WHERE / HAVING / ON clause into condition tokens and
     * AND / OR delimiter tokens.
     *
     * Splitting is done only at the top level: content inside parentheses
     * or quoted strings is treated as opaque and never split.
     *
     * Special case: the AND keyword that belongs to a BETWEEN … AND … range
     * expression is NOT treated as a logical delimiter. A boolean $inBetween
     * flag is set when the BETWEEN keyword is accumulated into the current
     * token, and is cleared once its paired AND is consumed.
     *
     * @param  string $clause The clause text to tokenise.
     * @return string[]
     */
    private function tokenizeConditions(string $clause): array
    {
        $tokens = [];
        $depth = 0;
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $inBetween = false;          // true while waiting for the BETWEEN…AND separator
        $len = strlen($clause);

        for ($i = 0; $i < $len; $i++) {
            $ch = $clause[$i];

            // ── Quoted-string tracking ────────────────────────────────────────
            if (!$inQuote && ($ch === "'" || $ch === '"' || $ch === '`')) {
                $inQuote = true;
                $quoteChar = $ch;
                $current .= $ch;
                continue;
            }
            if ($inQuote) {
                $current .= $ch;
                if ($ch === $quoteChar && ($i === 0 || $clause[$i - 1] !== '\\')) {
                    $inQuote = false;
                }
                continue;
            }

            // ── Parenthesis depth tracking ────────────────────────────────────
            if ($ch === '(') {
                $depth++;
                $current .= $ch;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                $current .= $ch;
                continue;
            }

            // ── Top-level AND / OR split ──────────────────────────────────────
            if ($depth === 0) {
                $rest = substr($clause, $i);
                if (preg_match('/^\s+(AND|OR)\s+/i', $rest, $m)) {
                    $keyword = strtoupper($m[1]);

                    // This AND is the range separator inside BETWEEN … AND …
                    // Absorb it into the current token and clear the flag.
                    if ($inBetween && $keyword === 'AND') {
                        $inBetween = false;
                        $current .= $m[0];           // include " AND " literally
                        $i += strlen($m[0]) - 1;
                        continue;
                    }

                    // Real logical AND / OR: flush the current token and emit the delimiter.
                    if (($trimmed = trim($current)) !== '') {
                        $tokens[] = $trimmed;
                    }
                    $tokens[] = $keyword;
                    $i += strlen($m[0]) - 1;
                    $current = '';
                    $inBetween = false;               // reset on every logical split
                    continue;
                }
            }

            $current .= $ch;

            // Detect the BETWEEN keyword as it is being accumulated so that the
            // very next top-level AND is recognised as its range separator.
            if (!$inBetween && preg_match('/\bBETWEEN\s*$/i', $current)) {
                $inBetween = true;
            }
        }

        if (($trimmed = trim($current)) !== '') {
            $tokens[] = $trimmed;
        }

        return $tokens;
    }

    // =========================================================================
    // Column / Table / Value parsers
    // =========================================================================

    /**
     * Parses a SELECT column list into structured column entries.
     *
     * Each entry:
     *   expression    – the raw column expression (e.g. "COUNT(*)", "t.col")
     *   alias         – column alias after AS (or unquoted space-alias), or null
     *   is_wildcard   – true for '*' or 'table.*'
     *   is_aggregate  – true when an aggregate function is detected
     *
     * The list is split on commas that are outside nested parentheses so that
     * function arguments are not confused with column separators.
     *
     * @param  string $columnList The raw text between SELECT … and FROM.
     * @return array<int, array<string, mixed>>
     */
    private function parseColumnList(string $columnList): array
    {
        $parts = $this->splitByCommaOutsideParentheses($columnList);
        $columns = [];

        static $aggregateFunctions = [
        'COUNT',
        'SUM',
        'AVG',
        'MIN',
        'MAX',
        'GROUP_CONCAT',
        'STRING_AGG',
        'ARRAY_AGG',
        'STDDEV',
        'VARIANCE',
        'BIT_AND',
        'BIT_OR',
        'BIT_XOR',
        ];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $entry = [
                'expression' => $part,
                'alias' => null,
                'is_wildcard' => false,
                'is_aggregate' => false,
            ];

            // Wildcard columns: * or table.*
            if ($part === '*' || preg_match('/^[\w.`"\'\[\]]+\.\*$/', $part)) {
                $entry['is_wildcard'] = true;
                $columns[] = $entry;
                continue;
            }

            // Alias extraction: prefer "AS alias", then a quoted alias without AS
            if (preg_match('/^(.+?)\s+AS\s+([`\'"\[]?[\w]+[`\'"\]]?)\s*$/i', $part, $m)) {
                $entry['expression'] = trim($m[1]);
                $entry['alias'] = $this->stripQuotes($m[2]);
            } elseif (preg_match('/^(.+?)\s+([`\'"\["][`\'"\[\]\w\s]+[`\'"\]])\s*$/', $part, $m)) {
                // Quoted alias without AS keyword
                $entry['expression'] = trim($m[1]);
                $entry['alias'] = $this->stripQuotes($m[2]);
            }

            // Aggregate function detection
            foreach ($aggregateFunctions as $agg) {
                if (preg_match('/\b' . $agg . '\s*\(/i', $entry['expression'])) {
                    $entry['is_aggregate'] = true;
                    break;
                }
            }

            $columns[] = $entry;
        }

        return $columns;
    }

    /**
     * Parses a table reference list (FROM clause, UPDATE target, etc.)
     * into structured table entries.
     *
     * Each entry:
     *   table  – unquoted table name (without schema prefix)
     *   schema – schema/database prefix when the reference is "schema.table"
     *   alias  – table alias after AS (or unquoted space-alias), or null
     *
     * JOIN clauses are parsed separately by parseJoins() and should not be
     * passed to this method.
     *
     * @param  string $tableList The raw table reference text.
     * @return array<int, array<string, mixed>>
     */
    private function parseTableList(string $tableList): array
    {
        // Remove any accidentally included JOIN fragments
        $tableList = (string) preg_replace(
            '/\s+(?:LEFT|RIGHT|INNER|CROSS|FULL|NATURAL|STRAIGHT_)(?:\s+OUTER)?\s+JOIN\b.*$/is',
            '',
            $tableList
        );
        $tableList = (string) preg_replace('/\s+JOIN\b.*$/is', '', $tableList);
        $tableList = trim($tableList);

        $parts = $this->splitByCommaOutsideParentheses($tableList);
        $tables = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $entry = ['table' => $part, 'schema' => null, 'alias' => null];

            // AS alias
            if (preg_match('/^([`\'"\[]?[\w.]+[`\'"\]]?)\s+AS\s+([`\'"\[]?[\w]+[`\'"\]]?)\s*$/i', $part, $m)) {
                $entry['table'] = $this->stripQuotes($m[1]);
                $entry['alias'] = $this->stripQuotes($m[2]);
                // Space alias (unquoted single word only, to avoid false positives)
            } elseif (preg_match('/^([`\'"\[]?[\w.]+[`\'"\]]?)\s+([a-zA-Z_][\w]*)\s*$/', $part, $m)) {
                $entry['table'] = $this->stripQuotes($m[1]);
                $entry['alias'] = $this->stripQuotes($m[2]);
            } else {
                $entry['table'] = $this->stripQuotes($part);
            }

            // Separate schema prefix ("schema.table" → schema + table)
            if (str_contains($entry['table'], '.')) {
                [$schema, $table] = explode('.', $entry['table'], 2);
                $entry['schema'] = $schema;
                $entry['table'] = $table;
            }

            $tables[] = $entry;
        }

        return $tables;
    }

    /**
     * Parses a VALUES string containing one or more row tuples.
     *
     * Example input:  (1, 'Alice'), (2, 'Bob')
     * Returned value: [['1', "'Alice'"], ['2', "'Bob'"]]
     *
     * @param  string $valuesStr The raw content of the VALUES clause.
     * @return array<int, array<int, string>>
     */
    private function parseInsertValues(string $valuesStr): array
    {
        $rows = [];

        // Match each top-level parenthesised group
        preg_match_all('/\(([^()]*(?:\([^()]*\)[^()]*)*)\)/i', $valuesStr, $matches);

        foreach ($matches[1] as $rowStr) {
            $rows[] = array_map('trim', $this->splitByCommaOutsideParentheses($rowStr));
        }

        return $rows;
    }

    /**
     * Parses the SET clause of an UPDATE or ON DUPLICATE KEY UPDATE statement.
     *
     * Example input:  col1 = 'v1', col2 = col2 + 1
     * Returned value: [['column' => 'col1', 'value' => "'v1'"], ['column' => 'col2', 'value' => 'col2 + 1']]
     *
     * @param  string $setStr The raw SET clause text.
     * @return array<int, array{column: string, value: string}>
     */
    private function parseSetAssignments(string $setStr): array
    {
        $parts = $this->splitByCommaOutsideParentheses($setStr);
        $assignments = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^([`\'"\[]?[\w.]+[`\'"\]]?)\s*=\s*(.+)$/s', $part, $m)) {
                $assignments[] = [
                    'column' => $this->stripQuotes(trim($m[1])),
                    'value' => trim($m[2]),
                ];
            }
        }

        return $assignments;
    }

    /**
     * Parses an ORDER BY clause into structured order entries.
     *
     * Each entry:
     *   expression – the ORDER BY expression (column name, alias, or function)
     *   direction  – "ASC" or "DESC" (defaults to "ASC" when not specified)
     *
     * @param  string $orderByStr The raw ORDER BY content.
     * @return array<int, array{expression: string, direction: string}>
     */
    private function parseOrderBy(string $orderByStr): array
    {
        $parts = $this->splitByCommaOutsideParentheses($orderByStr);
        $orders = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $direction = 'ASC'; // Default when no direction is specified
            if (preg_match('/\s+(ASC|DESC)\s*$/i', $part, $m)) {
                $direction = strtoupper($m[1]);
                $part = trim(substr($part, 0, -strlen($m[0])));
            }

            // Handle NULLS FIRST / NULLS LAST (standard SQL)
            $nullsOrder = null;
            if (preg_match('/\s+NULLS\s+(FIRST|LAST)\s*$/i', $part, $m)) {
                $nullsOrder = strtoupper($m[1]);
                $part = trim(substr($part, 0, -strlen($m[0])));
            }

            $entry = ['expression' => $part, 'direction' => $direction];
            if ($nullsOrder !== null) {
                $entry['nulls'] = $nullsOrder;
            }

            $orders[] = $entry;
        }

        return $orders;
    }

    /**
     * Parses a LIMIT clause that may include an OFFSET.
     *
     * Three supported forms:
     *   LIMIT n               → count = n,  offset = null
     *   LIMIT n OFFSET m      → count = n,  offset = m
     *   LIMIT m, n            → count = n,  offset = m   (MySQL shorthand)
     *
     * Values may be integer literals, '?' (positional placeholder), or
     * ':name' (named placeholder) so that prepared statements are handled.
     *
     * @param  string $limitStr The raw LIMIT clause content.
     * @return array{count: string, offset: string|null}
     */
    private function parseLimit(string $limitStr): array
    {
        $limitStr = trim($limitStr);

        // LIMIT n OFFSET m
        if (preg_match('/^(\d+|\?|:\w+)\s+OFFSET\s+(\d+|\?|:\w+)$/i', $limitStr, $m)) {
            return ['count' => $m[1], 'offset' => $m[2]];
        }

        // MySQL shorthand: LIMIT offset, count
        if (preg_match('/^(\d+|\?|:\w+)\s*,\s*(\d+|\?|:\w+)$/', $limitStr, $m)) {
            return ['count' => $m[2], 'offset' => $m[1]];
        }

        // Plain LIMIT count (no offset)
        return ['count' => $limitStr, 'offset' => null];
    }

    /**
     * Parses the column definition body of a CREATE TABLE statement.
     *
     * Each entry is either a column definition or a table-level constraint.
     *
     * Column definition keys:
     *   type        => 'column'
     *   name        – column name
     *   data_type   – e.g. "INT", "VARCHAR(255)"
     *   not_null    – bool
     *   auto_inc    – bool
     *   primary_key – bool
     *   unique      – bool
     *   unsigned    – bool
     *   default     – default value string (if present)
     *   comment     – column comment string (if present)
     *
     * Constraint entry keys:
     *   type       => 'constraint'
     *   constraint – e.g. "PRIMARY KEY", "UNIQUE KEY", "INDEX", "FOREIGN KEY"
     *   raw        – the full constraint definition string
     *
     * @param  string $body Everything inside the outermost parentheses of CREATE TABLE.
     * @return array<int, array<string, mixed>>
     */
    private function parseCreateTableColumns(string $body): array
    {
        $parts = $this->splitByCommaOutsideParentheses($body);
        $columns = [];

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Table-level constraint (PRIMARY KEY, UNIQUE, INDEX, FOREIGN KEY, CHECK, etc.)
            if (
                preg_match(
                    '/^(PRIMARY\s+KEY|UNIQUE(?:\s+KEY)?|(?:FULLTEXT\s+)?INDEX|KEY|FOREIGN\s+KEY|CONSTRAINT|CHECK)\b/i',
                    $part,
                    $m
                )
            ) {
                $columns[] = [
                    'type' => 'constraint',
                    'constraint' => strtoupper((string) preg_replace('/\s+/', ' ', $m[1])),
                    'raw' => $part,
                ];
                continue;
            }

            // Regular column: `name` DATATYPE [modifiers …]
            if (preg_match('/^([`\'"\[]?[\w]+[`\'"\]]?)\s+(\w+(?:\([^)]*\))?)/i', $part, $m)) {
                $entry = [
                    'type' => 'column',
                    'name' => $this->stripQuotes($m[1]),
                    'data_type' => strtoupper($m[2]),
                    'not_null' => (bool) preg_match('/\bNOT\s+NULL\b/i', $part),
                    'auto_inc' => (bool) preg_match('/\bAUTO_INCREMENT\b/i', $part),
                    'primary_key' => (bool) preg_match('/\bPRIMARY\s+KEY\b/i', $part),
                    'unique' => (bool) preg_match('/\bUNIQUE\b/i', $part),
                    'unsigned' => (bool) preg_match('/\bUNSIGNED\b/i', $part),
                ];

                if (preg_match('/\bDEFAULT\s+([^\s,]+|\'[^\']*\'|"[^"]*")/i', $part, $dm)) {
                    $entry['default'] = $dm[1];
                }
                if (preg_match('/\bCOMMENT\s+(\'[^\']*\'|"[^"]*")/i', $part, $cm)) {
                    $entry['comment'] = trim($cm[1], "'\"");
                }
                if (preg_match('/\bON\s+UPDATE\s+(.+?)(?:\s+|$)/i', $part, $oum)) {
                    $entry['on_update'] = trim($oum[1]);
                }

                $columns[] = $entry;
            } else {
                $columns[] = ['type' => 'raw', 'raw' => $part];
            }
        }

        return $columns;
    }

    /**
     * Extracts recognised table-level options from a CREATE TABLE statement.
     *
     * Recognised options: ENGINE, CHARSET (DEFAULT CHARSET / CHARACTER SET),
     * COLLATE, AUTO_INCREMENT, COMMENT, ROW_FORMAT, CHECKSUM,
     * AVG_ROW_LENGTH, MAX_ROWS, MIN_ROWS, KEY_BLOCK_SIZE, PACK_KEYS.
     *
     * @param  string $query The full CREATE TABLE query.
     * @return array<string, string>   option_key => value (strings stripped of quotes)
     */
    private function parseTableOptions(string $query): array
    {
        $options = [];
        $patterns = [
            'engine' => '/\bENGINE\s*=\s*(\w+)/i',
            'charset' => '/\b(?:DEFAULT\s+)?(?:CHARSET|CHARACTER\s+SET)\s*=?\s*(\w+)/i',
            'collate' => '/\bCOLLATE\s*=?\s*([\w]+)/i',
            'auto_increment' => '/\bAUTO_INCREMENT\s*=\s*(\d+)/i',
            'comment' => '/\bCOMMENT\s*=\s*(\'[^\']*\'|"[^"]*")/i',
            'row_format' => '/\bROW_FORMAT\s*=\s*(\w+)/i',
            'checksum' => '/\bCHECKSUM\s*=\s*(\d+)/i',
            'avg_row_length' => '/\bAVG_ROW_LENGTH\s*=\s*(\d+)/i',
            'max_rows' => '/\bMAX_ROWS\s*=\s*(\d+)/i',
            'min_rows' => '/\bMIN_ROWS\s*=\s*(\d+)/i',
            'key_block_size' => '/\bKEY_BLOCK_SIZE\s*=\s*(\d+)/i',
            'pack_keys' => '/\bPACK_KEYS\s*=\s*(\d+|DEFAULT)/i',
        ];

        foreach ($patterns as $key => $pattern) {
            if (preg_match($pattern, $query, $m)) {
                $options[$key] = trim($m[1], "'\"");
            }
        }

        return $options;
    }

    // =========================================================================
    // Utility methods
    // =========================================================================

    /**
     * Extracts the text of a named clause from the query string.
     *
     * Finds the content that begins immediately after $startKeyword and ends
     * just before the first occurrence of any $endKeyword pattern at the top
     * level (i.e. outside quoted strings and nested parentheses).
     *
     * @param  string      $query        The query to search.
     * @param  string      $startKeyword Regex pattern for the clause start (e.g. 'GROUP\s+BY').
     * @param  string|null $endKeyword   Pipe-separated patterns for clause end keywords.
     *                                   Pass null to capture to end of string.
     * @return string|null  Trimmed clause content, or null when the clause is absent.
     */
    private function extractClause(string $query, string $startKeyword, ?string $endKeyword = null): ?string
    {
        // Locate the start keyword (use word-boundary to avoid partial matches)
        if (!preg_match('/\b(?:' . $startKeyword . ')\b/i', $query, $sm, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $clauseStart = $sm[0][1] + strlen($sm[0][0]);
        $clause = substr($query, $clauseStart);

        if ($endKeyword === null) {
            return trim($clause);
        }

        // Walk through the clause character by character, respecting nesting
        $len = strlen($clause);
        $depth = 0;
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < $len; $i++) {
            $ch = $clause[$i];

            if (!$inQuote && ($ch === "'" || $ch === '"' || $ch === '`')) {
                $inQuote = true;
                $quoteChar = $ch;
                continue;
            }
            if ($inQuote) {
                if ($ch === $quoteChar && ($i === 0 || $clause[$i - 1] !== '\\')) {
                    $inQuote = false;
                }
                continue;
            }

            if ($ch === '(') {
                $depth++;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                continue;
            }

            // At the top level, test for an end keyword
            if ($depth === 0) {
                $rest = substr($clause, $i);
                if (preg_match('/^\s*\b(?:' . $endKeyword . ')\b/i', $rest)) {
                    return trim(substr($clause, 0, $i));
                }
            }
        }

        return trim($clause);
    }

    /**
     * Splits a comma-separated string while respecting:
     *   – nested parentheses  (function arguments, subqueries)
     *   – quoted strings       (single quotes, double quotes, backticks)
     *
     * This is the correct way to split column lists, value lists, SET
     * clauses, and any other comma-separated SQL construct that may contain
     * function calls or string literals.
     *
     * @param  string $str The raw string to split.
     * @return string[]     Trimmed parts.
     */
    private function splitByCommaOutsideParentheses(string $str): array
    {
        $parts = [];
        $depth = 0;
        $current = '';
        $inQuote = false;
        $quoteChar = '';
        $len = strlen($str);

        for ($i = 0; $i < $len; $i++) {
            $ch = $str[$i];

            if (!$inQuote && ($ch === "'" || $ch === '"' || $ch === '`')) {
                $inQuote = true;
                $quoteChar = $ch;
                $current .= $ch;
                continue;
            }
            if ($inQuote) {
                $current .= $ch;
                if ($ch === $quoteChar && ($i === 0 || $str[$i - 1] !== '\\')) {
                    $inQuote = false;
                }
                continue;
            }

            if ($ch === '(') {
                $depth++;
                $current .= $ch;
                continue;
            }
            if ($ch === ')') {
                $depth--;
                $current .= $ch;
                continue;
            }

            if ($ch === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $ch;
        }

        if ($current !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Extracts the content inside a balanced pair of parentheses starting
     * at position $offset in $str.
     *
     * The character at $str[$offset] must be '('. The method returns the
     * text between the opening '(' and its matching ')' (not including
     * either parenthesis).
     *
     * Returns null when the parentheses are unbalanced.
     *
     * @param  string $str    The source string.
     * @param  int    $offset The index of the opening '('.
     * @return string|null
     */
    private function extractBalancedParentheses(string $str, int $offset): ?string
    {
        if (!isset($str[$offset]) || $str[$offset] !== '(') {
            return null;
        }

        $depth = 0;
        $start = $offset + 1;
        $len = strlen($str);

        for ($i = $offset; $i < $len; $i++) {
            if ($str[$i] === '(') {
                $depth++;
            } elseif ($str[$i] === ')') {
                $depth--;
                if ($depth === 0) {
                    // Return the content between the matching '(' and ')'
                    return substr($str, $start, $i - $start);
                }
            }
        }

        return null; // Unbalanced parentheses
    }

    /**
     * Removes surrounding backticks, single quotes, double quotes, or
     * square brackets from an SQL identifier or string literal.
     *
     * @param  string $str The raw identifier or quoted string.
     * @return string The unquoted value.
     */
    private function stripQuotes(string $str): string
    {
        $str = trim($str);
        // Handle square-bracket quoting used by SQL Server / Access
        if (str_starts_with($str, '[') && str_ends_with($str, ']')) {
            return substr($str, 1, -1);
        }
        return trim($str, '`\'"');
    }

    /**
     * Normalises whitespace in a query string:
     *   – collapses runs of spaces, tabs, and newlines into a single space
     *   – trims leading and trailing whitespace
     *
     * @param  string $query The raw query.
     * @return string The normalised query.
     */
    private function normalizeQuery(string $query): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $query));
    }
}
