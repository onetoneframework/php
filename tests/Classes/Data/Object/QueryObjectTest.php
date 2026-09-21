<?php

declare(strict_types=1);

/**
 * Tests for Clover\Classes\Data\QueryObject
 *
 * Coverage goals
 * ──────────────
 * Statement types : SELECT, INSERT, REPLACE, UPDATE, DELETE,
 *                   CREATE (TABLE / VIEW / INDEX / DATABASE),
 *                   DROP, ALTER, TRUNCATE, UNION
 *
 * Clause features : DISTINCT, CTE (WITH), subqueries, JOINs (all types),
 *                   WHERE / HAVING conditions (all operators), GROUP BY,
 *                   ORDER BY (ASC / DESC), LIMIT / OFFSET,
 *                   FOR UPDATE / LOCK IN SHARE MODE,
 *                   ON DUPLICATE KEY UPDATE, INSERT … SELECT,
 *                   multi-row VALUES, multi-table DELETE/UPDATE,
 *                   all ALTER TABLE action types, table-option parsing.
 *
 * Helper methods  : isSelect, isInsert, isUpdate, isDelete, isReplace,
 *                   isUnion, hasSubquery, hasCte, getStatementType
 */

namespace Clover\Tests\Data\Object;

use Clover\Classes\Data\QueryObject;
use PHPUnit\Framework\TestCase;

class QueryObjectTest extends TestCase
{
    // =========================================================================
    // Statement-type helpers
    // =========================================================================

    /** getStatementType returns the correct keyword for each DML/DDL type. */
    public function testGetStatementTypeForEachKeyword(): void
    {
        $cases = [
            'SELECT 1'                   => 'SELECT',
            'INSERT INTO t VALUES (1)'   => 'INSERT',
            'UPDATE t SET c = 1'         => 'UPDATE',
            'DELETE FROM t'              => 'DELETE',
            'REPLACE INTO t VALUES (1)'  => 'REPLACE',
            'CREATE TABLE t (id INT)'    => 'CREATE',
            'DROP TABLE t'               => 'DROP',
            'ALTER TABLE t ADD COLUMN c INT' => 'ALTER',
            'TRUNCATE TABLE t'           => 'TRUNCATE',
        ];

        foreach ($cases as $sql => $expected) {
            $this->assertSame(
                $expected,
                (new QueryObject($sql))->getStatementType(),
                "Failed for: $sql"
            );
        }
    }

    /** CTE prefix must not confuse getStatementType into returning "UNKNOWN". */
    public function testGetStatementTypeWithCtePrefix(): void
    {
        $sql = 'WITH cte AS (SELECT id FROM t) SELECT * FROM cte';
        $this->assertSame('SELECT', (new QueryObject($sql))->getStatementType());
    }

    /** Boolean shortcut helpers agree with getStatementType. */
    public function testBooleanStatementTypeHelpers(): void
    {
        $this->assertTrue((new QueryObject('SELECT 1'))->isSelect());
        $this->assertFalse((new QueryObject('INSERT INTO t VALUES (1)'))->isSelect());

        $this->assertTrue((new QueryObject('INSERT INTO t VALUES (1)'))->isInsert());
        $this->assertFalse((new QueryObject('SELECT 1'))->isInsert());

        $this->assertTrue((new QueryObject('UPDATE t SET c = 1'))->isUpdate());
        $this->assertTrue((new QueryObject('DELETE FROM t'))->isDelete());
        $this->assertTrue((new QueryObject('REPLACE INTO t VALUES (1)'))->isReplace());
    }

    /** isUnion, hasSubquery, hasCte each fire on the appropriate query. */
    public function testQueryClassificationHelpers(): void
    {
        $this->assertTrue((new QueryObject('SELECT 1 UNION SELECT 2'))->isUnion());
        $this->assertFalse((new QueryObject('SELECT 1'))->isUnion());

        $this->assertTrue((new QueryObject('SELECT * FROM t WHERE id IN (SELECT id FROM s)'))->hasSubquery());
        $this->assertFalse((new QueryObject('SELECT * FROM t'))->hasSubquery());

        $this->assertTrue((new QueryObject('WITH cte AS (SELECT 1) SELECT * FROM cte'))->hasCte());
        $this->assertFalse((new QueryObject('SELECT 1'))->hasCte());
    }

    /** Calling parseStructure() twice must return the same (cached) array. */
    public function testParseStructureResultIsCached(): void
    {
        $obj = new QueryObject('SELECT id FROM users');
        $first  = $obj->parseStructure();
        $second = $obj->parseStructure();
        $this->assertSame($first, $second);
    }

    // =========================================================================
    // SELECT – basic
    // =========================================================================

    /** Basic SELECT: wildcard column, single table, simple equality condition. */
    public function testBasicSelect(): void
    {
        $sql = 'SELECT * FROM users WHERE status = 1';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('SELECT', $s['statement_type']);
        $this->assertTrue($s['columns'][0]['is_wildcard']);
        $this->assertSame('users', $s['tables'][0]['table']);
        $this->assertSame('status', $s['conditions'][0]['column']);
        $this->assertSame('=', $s['conditions'][0]['operator']);
        $this->assertSame('1', $s['conditions'][0]['value']);
    }

    /** SELECT … AS column alias is extracted correctly. */
    public function testSelectColumnAlias(): void
    {
        $sql = 'SELECT id, name AS full_name FROM users';
        $s   = (new QueryObject($sql))->parseStructure();

        $col = $s['columns'][1];
        $this->assertSame('name', $col['expression']);
        $this->assertSame('full_name', $col['alias']);
    }

    /** Aggregate function columns are detected; alias is extracted. */
    public function testSelectAggregateColumn(): void
    {
        $sql = 'SELECT COUNT(*) AS cnt, SUM(price) FROM orders';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertTrue($s['columns'][0]['is_aggregate']);
        $this->assertSame('cnt', $s['columns'][0]['alias']);
        $this->assertTrue($s['columns'][1]['is_aggregate']);
    }

    /** SELECT DISTINCT sets the modifier field. */
    public function testSelectDistinct(): void
    {
        $sql = 'SELECT DISTINCT email FROM users';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DISTINCT', $s['modifier']);
    }

    /** Schema-qualified table name is split into schema and table. */
    public function testSelectWithSchemaQualifiedTable(): void
    {
        $sql = 'SELECT * FROM mydb.users';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('mydb', $s['tables'][0]['schema']);
        $this->assertSame('users', $s['tables'][0]['table']);
    }

    /** Table alias (AS keyword) is captured. */
    public function testSelectTableAlias(): void
    {
        $sql = 'SELECT u.id FROM users AS u';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('users', $s['tables'][0]['table']);
        $this->assertSame('u',     $s['tables'][0]['alias']);
    }

    // =========================================================================
    // SELECT – WHERE operators
    // =========================================================================

    /** WHERE … LIKE operator is parsed correctly. */
    public function testWhereWithLike(): void
    {
        $sql = "SELECT * FROM t WHERE name LIKE '%foo%'";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('LIKE', $s['conditions'][0]['operator']);
    }

    /** WHERE … NOT LIKE is parsed with the full two-word operator. */
    public function testWhereWithNotLike(): void
    {
        $sql = "SELECT * FROM t WHERE name NOT LIKE '%foo%'";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('NOT LIKE', $s['conditions'][0]['operator']);
    }

    /** WHERE … IN (list) produces an array value. */
    public function testWhereWithIn(): void
    {
        $sql = 'SELECT * FROM t WHERE id IN (1, 2, 3)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('IN', $s['conditions'][0]['operator']);
        $this->assertIsArray($s['conditions'][0]['value']);
        $this->assertCount(3, $s['conditions'][0]['value']);
    }

    /** WHERE … NOT IN is detected correctly. */
    public function testWhereWithNotIn(): void
    {
        $sql = 'SELECT * FROM t WHERE id NOT IN (1, 2)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('NOT IN', $s['conditions'][0]['operator']);
    }

    /** WHERE … IN (SELECT …) stores a subquery marker. */
    public function testWhereWithInSubquery(): void
    {
        $sql = 'SELECT * FROM t WHERE id IN (SELECT id FROM s)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertArrayHasKey('subquery', $s['conditions'][0]['value']);
    }

    /** WHERE … BETWEEN x AND y captures both bounds as an array. */
    public function testWhereWithBetween(): void
    {
        $sql = 'SELECT * FROM t WHERE age BETWEEN 18 AND 65';
        $s   = (new QueryObject($sql))->parseStructure();

        $cond = $s['conditions'][0];
        $this->assertSame('BETWEEN', $cond['operator']);
        $this->assertSame(['18', '65'], $cond['value']);
    }

    /** WHERE … IS NULL produces a null value entry. */
    public function testWhereIsNull(): void
    {
        $sql = 'SELECT * FROM t WHERE deleted_at IS NULL';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('IS NULL', $s['conditions'][0]['operator']);
        $this->assertNull($s['conditions'][0]['value']);
    }

    /** WHERE … IS NOT NULL is detected correctly. */
    public function testWhereIsNotNull(): void
    {
        $sql = 'SELECT * FROM t WHERE deleted_at IS NOT NULL';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('IS NOT NULL', $s['conditions'][0]['operator']);
    }

    /** Multiple WHERE conditions joined by AND are all returned. */
    public function testWhereWithMultipleConditions(): void
    {
        $sql = 'SELECT * FROM t WHERE a = 1 AND b = 2 AND c = 3';
        $s   = (new QueryObject($sql))->parseStructure();

        // Filter out logical-operator tokens
        $conds = array_values(
            array_filter($s['conditions'], fn($c) => $c['type'] === 'condition')
        );
        $this->assertCount(3, $conds);
    }

    /** Parenthesised condition groups produce type = 'group'. */
    public function testWhereGroupedConditions(): void
    {
        $sql = 'SELECT * FROM t WHERE (a = 1 OR b = 2) AND c = 3';
        $s   = (new QueryObject($sql))->parseStructure();

        $group = array_values(array_filter($s['conditions'], fn($c) => $c['type'] === 'group'));
        $this->assertNotEmpty($group);
    }

    // =========================================================================
    // SELECT – GROUP BY / HAVING / ORDER BY / LIMIT
    // =========================================================================

    /** GROUP BY columns are returned as an array. */
    public function testSelectGroupBy(): void
    {
        $sql = 'SELECT dept, COUNT(*) FROM employees GROUP BY dept';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertContains('dept', $s['group_by']);
    }

    /** HAVING clause is parsed the same way as WHERE. */
    public function testSelectHaving(): void
    {
        $sql = 'SELECT dept, COUNT(*) AS cnt FROM employees GROUP BY dept HAVING cnt > 5';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertNotEmpty($s['having']);
        $this->assertSame('cnt', $s['having'][0]['column']);
        $this->assertSame('>', $s['having'][0]['operator']);
    }

    /** ORDER BY with explicit ASC / DESC directions is captured. */
    public function testSelectOrderBy(): void
    {
        $sql = 'SELECT * FROM t ORDER BY created_at DESC, name ASC';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('created_at', $s['order_by'][0]['expression']);
        $this->assertSame('DESC', $s['order_by'][0]['direction']);
        $this->assertSame('ASC',  $s['order_by'][1]['direction']);
    }

    /** LIMIT with plain count is captured. */
    public function testSelectLimit(): void
    {
        $sql = 'SELECT * FROM t LIMIT 10';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('10', $s['limit']['count']);
        $this->assertNull($s['limit']['offset']);
    }

    /** LIMIT … OFFSET … form is parsed into count + offset. */
    public function testSelectLimitWithOffset(): void
    {
        $sql = 'SELECT * FROM t LIMIT 10 OFFSET 20';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('10', $s['limit']['count']);
        $this->assertSame('20', $s['limit']['offset']);
    }

    /** MySQL shorthand LIMIT offset, count is parsed correctly. */
    public function testSelectLimitMysqlShorthand(): void
    {
        $sql = 'SELECT * FROM t LIMIT 20, 10';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('10', $s['limit']['count']);
        $this->assertSame('20', $s['limit']['offset']);
    }

    // =========================================================================
    // SELECT – locking reads
    // =========================================================================

    /** FOR UPDATE is captured in the locking field. */
    public function testSelectForUpdate(): void
    {
        $sql = 'SELECT * FROM t WHERE id = 1 FOR UPDATE';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('FOR UPDATE', $s['locking']);
    }

    /** LOCK IN SHARE MODE is captured in the locking field. */
    public function testSelectLockInShareMode(): void
    {
        $sql = 'SELECT * FROM t LOCK IN SHARE MODE';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('LOCK IN SHARE MODE', $s['locking']);
    }

    // =========================================================================
    // SELECT – JOINs
    // =========================================================================

    /** INNER JOIN with an ON clause is parsed into the joins array. */
    public function testSelectInnerJoin(): void
    {
        $sql = 'SELECT * FROM orders o INNER JOIN customers c ON o.customer_id = c.id';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertNotEmpty($s['joins']);
        $this->assertSame('INNER JOIN', $s['joins'][0]['type']);
        $this->assertSame('customers', $s['joins'][0]['table']);
        $this->assertSame('c', $s['joins'][0]['alias']);
    }

    /** LEFT JOIN is recognised by its join type. */
    public function testSelectLeftJoin(): void
    {
        $sql = 'SELECT * FROM t LEFT JOIN s ON t.id = s.t_id';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('LEFT JOIN', $s['joins'][0]['type']);
    }

    /** RIGHT JOIN is recognised by its join type. */
    public function testSelectRightJoin(): void
    {
        $sql = 'SELECT * FROM t RIGHT JOIN s ON t.id = s.t_id';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('RIGHT JOIN', $s['joins'][0]['type']);
    }

    /** Multiple JOINs are all returned in order. */
    public function testSelectMultipleJoins(): void
    {
        $sql = 'SELECT * FROM a JOIN b ON a.id = b.a_id JOIN c ON b.id = c.b_id';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertCount(2, $s['joins']);
    }

    // =========================================================================
    // SELECT – UNION
    // =========================================================================

    /** UNION query sets is_union=true and populates union_parts. */
    public function testUnionQuery(): void
    {
        $sql = 'SELECT id FROM a UNION SELECT id FROM b';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertTrue($s['is_union']);
        $this->assertCount(2, $s['union_parts']);
        $this->assertSame('FIRST', $s['union_parts'][0]['union_type']);
        $this->assertSame('UNION', $s['union_parts'][1]['union_type']);
    }

    /** UNION ALL is correctly distinguished from plain UNION. */
    public function testUnionAllQuery(): void
    {
        $sql = 'SELECT id FROM a UNION ALL SELECT id FROM b';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('UNION ALL', $s['union_parts'][1]['union_type']);
    }

    // =========================================================================
    // SELECT – CTE
    // =========================================================================

    /** WITH … AS (…) CTE is parsed and the main SELECT still works. */
    public function testCteSelect(): void
    {
        $sql = 'WITH recent AS (SELECT * FROM orders WHERE created_at > NOW()) SELECT * FROM recent';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertTrue($s['has_cte']);
        $this->assertNotEmpty($s['cte']);
        $this->assertSame('recent', $s['cte'][0]['name']);
    }

    // =========================================================================
    // INSERT
    // =========================================================================

    /** Basic INSERT … VALUES row is captured. */
    public function testBasicInsert(): void
    {
        $sql = "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com')";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('INSERT', $s['statement_type']);
        $this->assertSame('users', $s['table']);
        $this->assertContains('name',  $s['columns']);
        $this->assertContains('email', $s['columns']);
        $this->assertCount(1, $s['values']);
    }

    /** Multi-row INSERT produces one values entry per row. */
    public function testInsertMultipleRows(): void
    {
        $sql = "INSERT INTO t (a) VALUES (1), (2), (3)";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertCount(3, $s['values']);
    }

    /** INSERT IGNORE sets the modifier field. */
    public function testInsertIgnoreModifier(): void
    {
        $sql = 'INSERT IGNORE INTO t (a) VALUES (1)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('IGNORE', $s['modifier']);
    }

    /** ON DUPLICATE KEY UPDATE assignments are parsed correctly. */
    public function testInsertOnDuplicateKeyUpdate(): void
    {
        $sql = "INSERT INTO t (id, val) VALUES (1, 'x') ON DUPLICATE KEY UPDATE val = 'y'";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertNotEmpty($s['on_duplicate_key_update']);
        $this->assertSame('val', $s['on_duplicate_key_update'][0]['column']);
    }

    /** INSERT … SELECT form populates the select key instead of values. */
    public function testInsertSelect(): void
    {
        $sql = 'INSERT INTO archive SELECT * FROM logs WHERE year = 2023';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertArrayHasKey('select', $s);
        $this->assertArrayNotHasKey('values', $s);
    }

    // =========================================================================
    // REPLACE
    // =========================================================================

    /** REPLACE INTO is treated like INSERT but has statement_type REPLACE. */
    public function testReplaceInto(): void
    {
        $sql = "REPLACE INTO users (id, name) VALUES (1, 'Bob')";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('REPLACE', $s['statement_type']);
        $this->assertSame('users', $s['table']);
        $this->assertCount(1, $s['values']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** Basic UPDATE: target table, SET assignments, WHERE condition. */
    public function testBasicUpdate(): void
    {
        $sql = "UPDATE users SET name = 'Alice' WHERE id = 1";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('UPDATE', $s['statement_type']);
        $this->assertSame('users', $s['tables'][0]['table']);
        $this->assertSame('name', $s['set'][0]['column']);
        $this->assertSame("'Alice'", $s['set'][0]['value']);
        $this->assertSame('id', $s['conditions'][0]['column']);
    }

    /** UPDATE LOW_PRIORITY sets the modifier field. */
    public function testUpdateLowPriorityModifier(): void
    {
        $sql = 'UPDATE LOW_PRIORITY t SET a = 1';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('LOW_PRIORITY', $s['modifier']);
    }

    /** UPDATE with a JOIN in the SET section is handled. */
    public function testUpdateWithJoin(): void
    {
        $sql = 'UPDATE orders o INNER JOIN customers c ON o.customer_id = c.id SET o.discount = 10 WHERE c.vip = 1';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertNotEmpty($s['joins']);
        $this->assertSame('INNER JOIN', $s['joins'][0]['type']);
    }

    /** UPDATE with ORDER BY and LIMIT is fully parsed. */
    public function testUpdateWithOrderByAndLimit(): void
    {
        $sql = 'UPDATE t SET a = 1 ORDER BY id ASC LIMIT 5';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('id', $s['order_by'][0]['expression']);
        $this->assertSame('5', $s['limit']['count']);
    }

    /** Multiple SET assignments are all returned. */
    public function testUpdateMultipleSetAssignments(): void
    {
        $sql = "UPDATE t SET a = 1, b = 'hello', c = c + 1";
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertCount(3, $s['set']);
        $this->assertSame('a', $s['set'][0]['column']);
        $this->assertSame('b', $s['set'][1]['column']);
        $this->assertSame('c', $s['set'][2]['column']);
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** Basic DELETE: table and WHERE condition are captured. */
    public function testBasicDelete(): void
    {
        $sql = 'DELETE FROM logs WHERE id = 99';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DELETE', $s['statement_type']);
        $this->assertSame('logs', $s['tables'][0]['table']);
        $this->assertSame('id', $s['conditions'][0]['column']);
    }

    /** DELETE LOW_PRIORITY modifier is captured. */
    public function testDeleteModifier(): void
    {
        $sql = 'DELETE LOW_PRIORITY FROM t WHERE id = 1';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('LOW_PRIORITY', $s['modifier']);
    }

    /** Multi-table DELETE lists target tables separately from the FROM tables. */
    public function testMultiTableDelete(): void
    {
        $sql = 'DELETE o, i FROM orders o JOIN order_items i ON o.id = i.order_id WHERE o.status = 0';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertArrayHasKey('target_tables', $s);
        $this->assertContains('o', $s['target_tables']);
        $this->assertContains('i', $s['target_tables']);
    }

    /** DELETE with ORDER BY and LIMIT is parsed correctly. */
    public function testDeleteWithOrderByAndLimit(): void
    {
        $sql = 'DELETE FROM t ORDER BY id DESC LIMIT 10';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DESC', $s['order_by'][0]['direction']);
        $this->assertSame('10', $s['limit']['count']);
    }

    // =========================================================================
    // CREATE TABLE
    // =========================================================================

    /** CREATE TABLE: name, column definitions and constraints are captured. */
    public function testCreateTable(): void
    {
        $sql = <<<SQL
CREATE TABLE users (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL DEFAULT '',
    email VARCHAR(255) UNIQUE,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
SQL;
        $s = (new QueryObject($sql))->parseStructure();

        $this->assertSame('CREATE', $s['statement_type']);
        $this->assertSame('TABLE', $s['object_type']);
        $this->assertSame('users', $s['table']);

        // Find the 'id' column definition
        $idCol = array_values(array_filter($s['columns'], fn($c) => $c['type'] === 'column' && $c['name'] === 'id'))[0];
        $this->assertTrue($idCol['not_null']);
        $this->assertTrue($idCol['auto_inc']);
        $this->assertTrue($idCol['unsigned']);

        // Confirm PRIMARY KEY constraint was detected
        $constraints = array_values(array_filter($s['columns'], fn($c) => $c['type'] === 'constraint'));
        $this->assertNotEmpty($constraints);

        // Table options
        $this->assertSame('InnoDB',   $s['table_options']['engine']);
        $this->assertSame('utf8mb4',  $s['table_options']['charset']);
    }

    /** CREATE TABLE IF NOT EXISTS sets the if_not_exists flag. */
    public function testCreateTableIfNotExists(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS t (id INT)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertTrue($s['if_not_exists']);
    }

    /** CREATE TEMPORARY TABLE sets the temporary flag. */
    public function testCreateTemporaryTable(): void
    {
        $sql = 'CREATE TEMPORARY TABLE tmp (id INT)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertTrue($s['temporary']);
    }

    // =========================================================================
    // CREATE VIEW
    // =========================================================================

    /** CREATE VIEW captures view name and parses the defining SELECT. */
    public function testCreateView(): void
    {
        $sql = 'CREATE VIEW active_users AS SELECT * FROM users WHERE active = 1';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('VIEW', $s['object_type']);
        $this->assertSame('active_users', $s['view']);
        $this->assertArrayHasKey('tables', $s['as']);
        $this->assertSame('users', $s['as']['tables'][0]['table']);
    }

    // =========================================================================
    // CREATE INDEX
    // =========================================================================

    /** CREATE UNIQUE INDEX captures all index details. */
    public function testCreateUniqueIndex(): void
    {
        $sql = 'CREATE UNIQUE INDEX idx_email ON users (email)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('INDEX', $s['object_type']);
        $this->assertSame('idx_email', $s['index']);
        $this->assertSame('users', $s['table']);
        $this->assertContains('email', $s['columns']);
        $this->assertTrue($s['unique']);
    }

    // =========================================================================
    // CREATE DATABASE
    // =========================================================================

    /** CREATE DATABASE is detected and the name is extracted. */
    public function testCreateDatabase(): void
    {
        $sql = 'CREATE DATABASE IF NOT EXISTS myapp';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DATABASE', $s['object_type']);
        $this->assertSame('myapp', $s['database']);
        $this->assertTrue($s['if_not_exists']);
    }

    // =========================================================================
    // DROP
    // =========================================================================

    /** DROP TABLE with IF EXISTS and multiple names is fully parsed. */
    public function testDropTable(): void
    {
        $sql = 'DROP TABLE IF EXISTS old_table, archive_table';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DROP', $s['statement_type']);
        $this->assertSame('TABLE', $s['object_type']);
        $this->assertTrue($s['if_exists']);
        $this->assertContains('old_table',     $s['objects']);
        $this->assertContains('archive_table', $s['objects']);
    }

    /** DROP VIEW captures the view name. */
    public function testDropView(): void
    {
        $sql = 'DROP VIEW IF EXISTS active_users';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('VIEW', $s['object_type']);
        $this->assertContains('active_users', $s['objects']);
    }

    /** DROP INDEX … ON table captures the table name. */
    public function testDropIndex(): void
    {
        $sql = 'DROP INDEX idx_email ON users';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('INDEX', $s['object_type']);
        $this->assertContains('idx_email', $s['objects']);
        $this->assertSame('users', $s['table']);
    }

    /** DROP DATABASE captures the database name. */
    public function testDropDatabase(): void
    {
        $sql = 'DROP DATABASE old_db';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DATABASE', $s['object_type']);
        $this->assertContains('old_db', $s['objects']);
    }

    // =========================================================================
    // ALTER TABLE – individual action types
    // =========================================================================

    /** ADD COLUMN action is classified correctly. */
    public function testAlterTableAddColumn(): void
    {
        $sql = 'ALTER TABLE users ADD COLUMN phone VARCHAR(20)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('TABLE', $s['object_type']);
        $this->assertSame('users', $s['table']);
        $this->assertSame('ADD_COLUMN', $s['actions'][0]['type']);
    }

    /** DROP COLUMN action extracts the column name. */
    public function testAlterTableDropColumn(): void
    {
        $sql = 'ALTER TABLE users DROP COLUMN phone';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DROP_COLUMN', $s['actions'][0]['type']);
        $this->assertSame('phone', $s['actions'][0]['column']);
    }

    /** MODIFY COLUMN action is classified correctly. */
    public function testAlterTableModifyColumn(): void
    {
        $sql = 'ALTER TABLE users MODIFY COLUMN name VARCHAR(100) NOT NULL';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('MODIFY_COLUMN', $s['actions'][0]['type']);
    }

    /** CHANGE COLUMN action captures old and new column definitions. */
    public function testAlterTableChangeColumn(): void
    {
        $sql = 'ALTER TABLE users CHANGE COLUMN old_name new_name VARCHAR(100)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('CHANGE_COLUMN', $s['actions'][0]['type']);
        $this->assertSame('old_name', $s['actions'][0]['old_column']);
    }

    /** ADD INDEX action is classified; UNIQUE flag is set when present. */
    public function testAlterTableAddIndex(): void
    {
        $sql = 'ALTER TABLE users ADD UNIQUE INDEX idx_email (email)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('ADD_INDEX', $s['actions'][0]['type']);
        $this->assertTrue($s['actions'][0]['unique']);
    }

    /** DROP INDEX action extracts the index name. */
    public function testAlterTableDropIndex(): void
    {
        $sql = 'ALTER TABLE users DROP INDEX idx_email';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DROP_INDEX', $s['actions'][0]['type']);
        $this->assertSame('idx_email', $s['actions'][0]['index']);
    }

    /** ADD FOREIGN KEY action is classified. */
    public function testAlterTableAddForeignKey(): void
    {
        $sql = 'ALTER TABLE orders ADD FOREIGN KEY (customer_id) REFERENCES customers (id)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('ADD_FOREIGN_KEY', $s['actions'][0]['type']);
    }

    /** DROP FOREIGN KEY action extracts the constraint name. */
    public function testAlterTableDropForeignKey(): void
    {
        $sql = 'ALTER TABLE orders DROP FOREIGN KEY fk_customer';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DROP_FOREIGN_KEY', $s['actions'][0]['type']);
        $this->assertSame('fk_customer', $s['actions'][0]['constraint']);
    }

    /** ADD PRIMARY KEY action lists the key columns. */
    public function testAlterTableAddPrimaryKey(): void
    {
        $sql = 'ALTER TABLE t ADD PRIMARY KEY (id)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('ADD_PRIMARY_KEY', $s['actions'][0]['type']);
        $this->assertContains('id', $s['actions'][0]['columns']);
    }

    /** DROP PRIMARY KEY action is classified correctly. */
    public function testAlterTableDropPrimaryKey(): void
    {
        $sql = 'ALTER TABLE t DROP PRIMARY KEY';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('DROP_PRIMARY_KEY', $s['actions'][0]['type']);
    }

    /** RENAME TO action captures the new table name. */
    public function testAlterTableRename(): void
    {
        $sql = 'ALTER TABLE old_name RENAME TO new_name';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('RENAME', $s['actions'][0]['type']);
        $this->assertSame('new_name', $s['actions'][0]['new_name']);
    }

    /** ENGINE = … is detected as a TABLE_OPTION action. */
    public function testAlterTableEngineOption(): void
    {
        $sql = 'ALTER TABLE t ENGINE = MyISAM';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('TABLE_OPTION', $s['actions'][0]['type']);
    }

    /** Multiple ALTER actions separated by commas are all returned. */
    public function testAlterTableMultipleActions(): void
    {
        $sql = 'ALTER TABLE t ADD COLUMN a INT, DROP COLUMN b, ADD INDEX idx_a (a)';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertCount(3, $s['actions']);
    }

    /** TRUNCATE TABLE extracts the table name. */
    public function testTruncateTable(): void
    {
        $sql = 'TRUNCATE TABLE audit_log';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('TRUNCATE', $s['statement_type']);
        $this->assertSame('TABLE', $s['object_type']);
        $this->assertSame('audit_log', $s['table']);
    }

    /** TRUNCATE without the optional TABLE keyword also works. */
    public function testTruncateWithoutTableKeyword(): void
    {
        $sql = 'TRUNCATE audit_log';
        $s   = (new QueryObject($sql))->parseStructure();

        $this->assertSame('audit_log', $s['table']);
    }
}
