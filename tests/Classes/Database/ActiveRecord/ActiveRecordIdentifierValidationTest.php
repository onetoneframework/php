<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database\ActiveRecord;

use Clover\Classes\Database\ActiveRecord;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Entity used to exercise the query builder. Deliberately named so it cannot collide with the
 * fixtures declared by Clover\Tests\Classes\Database\ActiveRecordTest.
 */
class Ledger extends ActiveRecord
{
    /**
     * @Id
     * @Column(name="id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @Column(name="title", type="string", nullable=false)
     */
    protected string $title;

    /**
     * @Column(name="amount", type="integer", nullable=true)
     */
    protected ?int $amount;

    /**
     * @Column(name="created_at", type="datetime", nullable=true)
     */
    protected ?string $created_at;
}

/**
 * Every builder method that writes a caller-supplied identifier, operator or sort direction into a
 * statement must reject anything that is not a plain name / whitelisted keyword, because none of
 * those positions can be bound as a parameter.
 *
 * The methods under test are declared protected and are reached through __call/__callStatic. That
 * is exactly how application code calls them — and therefore the path an attacker's input travels —
 * so the tests call them the same way rather than prying them open with reflection.
 *
 * None of these tests need a database connection: rejection happens while the query is being built,
 * and toSql() renders the statement without touching PDO.
 */
final class ActiveRecordIdentifierValidationTest extends TestCase
{
    #region providers

    /**
     * Column names an attacker would try: closing the quote, terminating the statement, appending a
     * UNION, hiding a comment, smuggling a second column or a subquery past the interpolation.
     *
     * @return array<string, array{string}>
     */
    public static function hostileIdentifierProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ['   '],
            'statement terminator' => ['id; DROP TABLE ledgers'],
            'union injection' => ['id UNION SELECT password FROM users'],
            'backtick escape' => ['ti`tle'],
            'backtick closing then expression' => ['title` = 1 OR `1'],
            'inline comment' => ['title -- '],
            'block comment' => ['title/**/'],
            'boolean tautology' => ['1=1'],
            'direction smuggled into column' => ['title DESC'],
            'comma separated list' => ['title, amount'],
            'parenthesised subquery' => ['(SELECT password FROM users)'],
            'function call' => ['COUNT(*)'],
            'over qualified' => ['db.ledgers.title'],
            'quoted string' => ["'title'"],
            'wildcard' => ['*'],
            'sleep payload' => ['title, SLEEP(5)'],
        ];
    }

    /**
     * Operators outside the whitelist, including SQL that is valid but not on it.
     *
     * @return array<string, array{string}>
     */
    public static function hostileOperatorProvider(): array
    {
        return [
            'empty' => [''],
            'tautology appended' => ['= 1 OR 1'],
            'statement terminator' => ['=; DROP TABLE ledgers'],
            'comment' => ['= --'],
            'unlisted null safe equal' => ['<=>'],
            'unlisted regexp' => ['RLIKE'],
            'unlisted between' => ['BETWEEN'],
            'union' => ['= 1 UNION SELECT 1'],
            'subquery' => ['IN (SELECT 1)'],
        ];
    }

    /**
     * Sort directions outside ASC / DESC.
     *
     * @return array<string, array{string}>
     */
    public static function hostileDirectionProvider(): array
    {
        return [
            'empty' => [''],
            'statement terminator' => ['ASC; DROP TABLE ledgers'],
            'comment' => ['ASC --'],
            'extra column' => ['ASC, amount DESC'],
            'limit appended' => ['DESC LIMIT 1'],
            'subquery' => ['(SELECT 1)'],
            'nonsense keyword' => ['SIDEWAYS'],
        ];
    }

    /**
     * Names that are legitimate and must keep working, with the quoting each should receive.
     *
     * @return array<string, array{string, string}>
     */
    public static function legitimateIdentifierProvider(): array
    {
        return [
            'plain column' => ['title', '`title`'],
            'underscored column' => ['created_at', '`created_at`'],
            'qualified column' => ['posts.title', '`posts`.`title`'],
            'qualified by alias' => ['p.amount', '`p`.`amount`'],
        ];
    }

    #endregion

    #region ORDER BY

    #[DataProvider('hostileIdentifierProvider')]
    public function testOrderByRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::orderBy($column);
    }

    #[DataProvider('hostileDirectionProvider')]
    public function testOrderByRejectsHostileDirection(string $direction): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::orderBy('title', $direction);
    }

    #[DataProvider('legitimateIdentifierProvider')]
    public function testOrderByAcceptsLegitimateColumn(string $column, string $quoted): void
    {
        $sql = Ledger::orderBy($column)->toSql();

        $this->assertStringContainsString("ORDER BY {$quoted} ASC", $sql);
    }

    public function testOrderByCanonicalisesDirection(): void
    {
        $this->assertStringContainsString('ORDER BY `title` DESC', Ledger::orderBy('title', 'desc')->toSql());
        $this->assertStringContainsString('ORDER BY `title` ASC', Ledger::orderBy('title', ' asc ')->toSql());
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testOrderByDescRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::orderByDesc($column);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testLatestAndOldestRejectHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::latest($column);
    }

    public function testLatestAndOldestStillDelegateToOrderBy(): void
    {
        $this->assertStringContainsString('ORDER BY `created_at` DESC', Ledger::latest()->toSql());
        $this->assertStringContainsString('ORDER BY `created_at` ASC', Ledger::oldest()->toSql());
        $this->assertStringContainsString('ORDER BY `amount` DESC', Ledger::orderByDesc('amount')->toSql());
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testOrderByMultipleRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::orderByMultiple([$column => 'ASC']);
    }

    public function testOrderByMultipleQuotesEveryColumn(): void
    {
        $sql = Ledger::orderByMultiple(['title' => 'DESC', 'amount' => 'ASC'])->toSql();

        $this->assertStringContainsString('ORDER BY `title` DESC, `amount` ASC', $sql);
    }

    #endregion

    #region HAVING

    #[DataProvider('hostileIdentifierProvider')]
    public function testHavingRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::having($column, '>=', 1);
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testHavingRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::having('amount', $operator, 1);
    }

    #[DataProvider('legitimateIdentifierProvider')]
    public function testHavingAcceptsLegitimateColumn(string $column, string $quoted): void
    {
        $sql = Ledger::having($column, '>=', 1)->toSql();

        $this->assertStringContainsString("HAVING {$quoted} >= ?", $sql);
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testHavingCountRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::havingCount($operator, 1);
    }

    public function testHavingCountKeepsWhitelistedOperator(): void
    {
        $this->assertStringContainsString('HAVING COUNT(*) >= ?', Ledger::havingCount('>=', 1)->toSql());
    }

    #endregion

    #region whereColumn

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereColumnRejectsHostileFirstOperand(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereColumn($column, '=', 'amount');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereColumnRejectsHostileSecondOperand(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereColumn('amount', '=', $column);
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testWhereColumnRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereColumn('amount', $operator, 'title');
    }

    public function testWhereColumnQuotesBothOperands(): void
    {
        $sql = Ledger::whereColumn('posts.title', '!=', 'title')->toSql();

        $this->assertStringContainsString('`posts`.`title` != `title`', $sql);
    }

    #endregion

    #region whereFullText

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereFullTextRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereFullText([$column], 'search');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereFullTextRejectsHostileColumnAmongValidOnes(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereFullText(['title', $column], 'search');
    }

    public function testWhereFullTextRejectsEmptyColumnList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereFullText([], 'search');
    }

    public function testWhereFullTextQuotesEveryColumn(): void
    {
        $sql = Ledger::whereFullText(['title', 'posts.title'], 'search', 'boolean')->toSql();

        $this->assertStringContainsString('MATCH(`title`, `posts`.`title`)', $sql);
        $this->assertStringContainsString('IN BOOLEAN MODE', $sql);
    }

    #endregion

    #region JOIN

    #[DataProvider('hostileIdentifierProvider')]
    public function testJoinRejectsHostileTable(string $table): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::join($table, 'ledger.id', '=', 'posts.ledger_id');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testJoinRejectsHostileOnOperands(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::join('posts', $column, '=', 'posts.ledger_id');
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testJoinRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::join('posts', 'ledger.id', $operator, 'posts.ledger_id');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testLeftJoinRejectsHostileTable(string $table): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::leftJoin($table, 'ledger.id', '=', 'posts.ledger_id');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testRightJoinRejectsHostileSecondOperand(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::rightJoin('posts', 'ledger.id', '=', $column);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testCrossJoinRejectsHostileTable(string $table): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::crossJoin($table);
    }

    public function testJoinQuotesTableAndBothOperands(): void
    {
        $sql = Ledger::leftJoin('posts', 'ledger.id', '=', 'posts.ledger_id')->toSql();

        $this->assertStringContainsString(
            'LEFT JOIN `posts` ON `ledger`.`id` = `posts`.`ledger_id`',
            $sql
        );
    }

    public function testCrossJoinKeepsItsLiteralOnCondition(): void
    {
        $sql = Ledger::crossJoin('posts')->toSql();

        $this->assertStringContainsString('CROSS JOIN `posts` ON 1 = 1', $sql);
    }

    #endregion

    #region remaining identifier positions

    #[DataProvider('hostileIdentifierProvider')]
    public function testGroupByRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::groupBy($column);
    }

    public function testGroupByQuotesEveryColumn(): void
    {
        $sql = Ledger::groupBy('title', 'posts.title')->toSql();

        $this->assertStringContainsString('GROUP BY `title`, `posts`.`title`', $sql);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereBetweenRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereBetween($column, 1, 10);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereNotBetweenRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereNotBetween($column, 1, 10);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testOrWhereBetweenRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::orWhereBetween($column, 1, 10);
    }

    public function testWhereBetweenQuotesColumn(): void
    {
        $this->assertStringContainsString('`amount` BETWEEN ? AND ?', Ledger::whereBetween('amount', 1, 10)->toSql());
        $this->assertStringContainsString('`amount` NOT BETWEEN ? AND ?', Ledger::whereNotBetween('amount', 1, 10)->toSql());
    }

    /**
     * Each date/time builder with a comparison value of the type its signature demands.
     *
     * @return array<string, array{string, string|int}>
     */
    public static function dateBuilderProvider(): array
    {
        return [
            'whereDate' => ['whereDate', '2026-01-01'],
            'whereYear' => ['whereYear', 2026],
            'whereMonth' => ['whereMonth', 1],
            'whereDay' => ['whereDay', 1],
            'whereTime' => ['whereTime', '12:00:00'],
        ];
    }

    #[DataProvider('dateBuilderProvider')]
    public function testDatePartBuildersRejectHostileColumn(string $method, string|int $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::$method('created_at`; DROP TABLE ledgers --', '=', $value);
    }

    #[DataProvider('dateBuilderProvider')]
    public function testDatePartBuildersRejectHostileOperator(string $method, string|int $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::$method('created_at', '= 1 OR 1', $value);
    }

    public function testDatePartBuildersQuoteColumn(): void
    {
        $this->assertStringContainsString('DATE(`created_at`) = ?', Ledger::whereDate('created_at', '=', '2026-01-01')->toSql());
        $this->assertStringContainsString('YEAR(`created_at`) >= ?', Ledger::whereYear('created_at', '>=', 2026)->toSql());
        $this->assertStringContainsString('MONTH(`created_at`) = ?', Ledger::whereMonth('created_at', '=', 1)->toSql());
        $this->assertStringContainsString('DAY(`created_at`) = ?', Ledger::whereDay('created_at', '=', 1)->toSql());
        $this->assertStringContainsString('TIME(`created_at`) < ?', Ledger::whereTime('created_at', '<', '12:00:00')->toSql());
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereJsonContainsRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereJsonContains($column, 'value');
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereJsonPathRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereJsonPath($column, '$.name', '=', 'value');
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testWhereJsonPathRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereJsonPath('title', '$.name', $operator, 'value');
    }

    public function testJsonBuildersQuoteColumn(): void
    {
        $this->assertStringContainsString('JSON_CONTAINS(`title`, ?)', Ledger::whereJsonContains('title', 'v')->toSql());
        $this->assertStringContainsString('JSON_EXTRACT(`title`, ?) = ?', Ledger::whereJsonPath('title', '$.n', '=', 'v')->toSql());
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereInSubqueryRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereInSubquery($column, 'SELECT id FROM posts');
    }

    public function testWhereInSubqueryQuotesColumnAndKeepsSubqueryRaw(): void
    {
        $sql = Ledger::whereInSubquery('amount', 'SELECT amount FROM posts')->toSql();

        $this->assertStringContainsString('`amount` IN (SELECT amount FROM posts)', $sql);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereSubqueryRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereSubquery($column, 'IN', 'SELECT id FROM posts');
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testWhereSubqueryRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::whereSubquery('amount', $operator, 'SELECT id FROM posts');
    }

    public function testWhereSubqueryQuotesColumnAndKeepsSubqueryRaw(): void
    {
        $sql = Ledger::whereSubquery('amount', 'IN', 'SELECT amount FROM posts')->toSql();

        $this->assertStringContainsString('`amount` IN (SELECT amount FROM posts)', $sql);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testAddSubSelectRejectsHostileAlias(string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::addSubSelect($alias, 'SELECT COUNT(*) FROM posts');
    }

    public function testAddSubSelectQuotesAliasAndKeepsSubqueryRaw(): void
    {
        $sql = Ledger::addSubSelect('post_count', 'SELECT COUNT(*) FROM posts')->toSql();

        $this->assertStringContainsString('(SELECT COUNT(*) FROM posts) AS `post_count`', $sql);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testFromSubqueryRejectsHostileAlias(string $alias): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::fromSubquery('SELECT * FROM posts', [], $alias);
    }

    public function testFromSubqueryQuotesAliasAndKeepsBodyRaw(): void
    {
        $sql = Ledger::fromSubquery('SELECT * FROM posts', [], 'p')->toSqlWithBindings();

        $this->assertStringContainsString('FROM (SELECT * FROM posts) AS `p`', $sql);
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testWithCteRejectsHostileName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::withCte($name, 'SELECT 1')->toSql();
    }

    public function testWithCteQuotesNameAndKeepsBodyRaw(): void
    {
        // toSql() does not render the WITH prefix; toSqlWithBindings() does.
        $sql = Ledger::withCte('recent', 'SELECT 1')->toSqlWithBindings();

        $this->assertStringContainsString('`recent` AS (SELECT 1)', $sql);
    }

    #endregion

    #region WHERE conditions

    #[DataProvider('hostileIdentifierProvider')]
    public function testWhereRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::where($column, 1)->toSql();
    }

    #[DataProvider('hostileOperatorProvider')]
    public function testWhereRejectsHostileOperator(string $operator): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::where('amount', $operator, 1)->toSql();
    }

    #[DataProvider('hostileIdentifierProvider')]
    public function testOrWhereRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        Ledger::where('amount', 1)->orWhere($column, 2)->toSql();
    }

    #[DataProvider('legitimateIdentifierProvider')]
    public function testWhereAcceptsLegitimateColumn(string $column, string $quoted): void
    {
        $sql = Ledger::where($column, 1)->toSql();

        $this->assertStringContainsString("WHERE {$quoted} = ?", $sql);
    }

    #endregion

    #region the Raw family stays raw by design

    /**
     * The *Raw methods are documented as trusted-input-only and must NOT be validated: they exist
     * precisely to pass an expression through untouched. If one of these ever starts throwing, the
     * change broke the documented contract rather than fixing a hole.
     */
    public function testRawFamilyIsNotValidated(): void
    {
        $this->assertStringContainsString(
            'amount / 2 > 3',
            Ledger::whereRaw('amount / 2 > 3')->toSql()
        );

        $this->assertStringContainsString(
            'SUBSTR(title, 1, 1)',
            Ledger::groupByRaw('SUBSTR(title, 1, 1)')->toSql()
        );

        $this->assertStringContainsString(
            'ORDER BY LENGTH(title) DESC',
            Ledger::orderByRaw('LENGTH(title) DESC')->toSql()
        );

        $this->assertStringContainsString(
            'SUM(amount) > 10',
            Ledger::havingRaw('SUM(amount) > 10')->toSql()
        );

        $this->assertStringContainsString(
            'COUNT(*) AS total',
            Ledger::selectRaw('COUNT(*) AS total')->toSql()
        );

        $this->assertStringContainsString(
            'EXISTS (SELECT 1 FROM posts)',
            Ledger::whereExists('SELECT 1 FROM posts')->toSql()
        );
    }

    #endregion
}
