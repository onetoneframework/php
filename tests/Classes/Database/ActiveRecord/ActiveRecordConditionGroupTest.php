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
use PHPUnit\Framework\TestCase;

/**
 * Entity for the condition-builder tests. Named so it cannot collide with the fixtures declared by
 * the sibling ActiveRecord test classes, which share one process.
 */
class Membership extends ActiveRecord
{
    /**
     * @Id
     * @Column(name="id", type="integer", nullable=false)
     */
    protected int $id;

    /**
     * @Column(name="age", type="integer", nullable=true)
     */
    protected ?int $age;

    /**
     * @Column(name="role", type="string", nullable=true)
     */
    protected ?string $role;

    /**
     * @Column(name="name", type="string", nullable=true)
     */
    protected ?string $name;

    /**
     * @Column(name="email", type="string", nullable=true)
     */
    protected ?string $email;
}

/**
 * Conditions are held as an ordered list of records rather than a map keyed by column, and groups
 * render as parenthesised sub-expressions.
 *
 * The builder methods are protected and are reached through __call/__callStatic, which is how
 * application code calls them, so the tests call them the same way. None of these tests need a
 * database connection: toSql() renders the statement without touching PDO.
 */
final class ActiveRecordConditionGroupTest extends TestCase
{
    /**
     * Return only the WHERE clause, so an assertion cannot pass on the SELECT list alone.
     */
    private static function whereClause(ActiveRecord $query): string
    {
        $sql = $query->toSql();
        $position = strpos($sql, 'WHERE');

        return $position === false ? '' : substr($sql, $position);
    }

    #region same-column conditions

    /**
     * A map keyed by column can only hold one entry per column, so the first bound of a range was
     * silently discarded and the query returned every row above or below the surviving bound.
     */
    public function testTwoConditionsOnTheSameColumnBothSurvive(): void
    {
        $sql = self::whereClause(Membership::where('age', '>', 18)->where('age', '<', 65));

        $this->assertSame('WHERE `age` > ? AND `age` < ?', $sql);
    }

    public function testThreeConditionsOnTheSameColumnAllSurvive(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 0)->where('age', '!=', 13)->where('age', '<', 120)
        );

        $this->assertSame('WHERE `age` > ? AND `age` != ? AND `age` < ?', $sql);
    }

    public function testBindingsFollowPlaceholderOrder(): void
    {
        $sql = Membership::where('age', '>=', 18)
            ->where('role', 'admin')
            ->whereIn('name', ['a', 'b'])
            ->toSqlWithBindings();

        $this->assertStringContainsString("`age` >= '18'", $sql);
        $this->assertStringContainsString("`role` = 'admin'", $sql);
        $this->assertStringContainsString("`name` IN ('a', 'b')", $sql);
    }

    #endregion

    #region null handling

    /**
     * The two-argument form with a null value means IS NULL. Rendering `col = ?` bound to null
     * produced a comparison that is never true.
     */
    public function testTwoArgumentNullBecomesIsNull(): void
    {
        $this->assertSame('WHERE `role` IS NULL', self::whereClause(Membership::where('role', null)));
    }

    public function testInequalityAgainstNullBecomesIsNotNull(): void
    {
        $this->assertSame('WHERE `role` IS NOT NULL', self::whereClause(Membership::where('role', '!=', null)));
    }

    /**
     * A three-argument call whose value is null must not collapse into the two-argument form: the
     * operator would be read as the value and the query would silently become `age = '>'`.
     */
    public function testOrderingOperatorAgainstNullIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be used with a null value');

        Membership::where('age', '>', null)->toSql();
    }

    #endregion

    #region groups

    public function testClosureOpensAnAndGroup(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->where(
                static function ($query): void {
                    $query->where('role', 'admin')->orWhere('role', 'owner');
                }
            )
        );

        $this->assertSame('WHERE `age` > ? AND (`role` = ? OR `role` = ?)', $sql);
    }

    public function testClosureOpensAnOrGroup(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->orWhere(
                static function ($query): void {
                    $query->where('role', 'admin')->where('name', 'root');
                }
            )
        );

        $this->assertSame('WHERE `age` > ? OR (`role` = ? AND `name` = ?)', $sql);
    }

    public function testGroupsNestTwoLevelsDeep(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 0)->where(
                static function ($query): void {
                    $query->where('role', 'admin')->orWhere(
                        static function ($inner): void {
                            $inner->where('age', '>=', 18)->where('age', '<', 65);
                        }
                    );
                }
            )
        );

        $this->assertSame('WHERE `age` > ? AND (`role` = ? OR (`age` >= ? AND `age` < ?))', $sql);
    }

    /**
     * An empty group would otherwise emit `()`, which is a syntax error.
     */
    public function testEmptyGroupContributesNothing(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->where(static function (): void {
            })
        );

        $this->assertSame('WHERE `age` > ?', $sql);
    }

    #endregion

    #region negation

    public function testWhereNotNegatesASingleComparison(): void
    {
        $this->assertSame('WHERE NOT (`role` = ?)', self::whereClause(Membership::whereNot('role', 'admin')));
    }

    public function testWhereNotNegatesAGroup(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->whereNot(
                static function ($query): void {
                    $query->where('role', 'admin')->orWhere('role', 'owner');
                }
            )
        );

        $this->assertSame('WHERE `age` > ? AND NOT (`role` = ? OR `role` = ?)', $sql);
    }

    public function testOrWhereNotJoinsWithOr(): void
    {
        $sql = self::whereClause(Membership::where('age', '>', 18)->orWhereNot('role', 'admin'));

        $this->assertSame('WHERE `age` > ? OR NOT (`role` = ?)', $sql);
    }

    #endregion

    #region column sets

    public function testWhereAnyGroupsColumnsWithOr(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->whereAny(['name', 'email'], 'LIKE', '%a%')
        );

        $this->assertSame('WHERE `age` > ? AND (`name` LIKE ? OR `email` LIKE ?)', $sql);
    }

    public function testWhereAllGroupsColumnsWithAnd(): void
    {
        $sql = self::whereClause(Membership::whereAll(['name', 'email'], '!=', ''));

        $this->assertSame('WHERE (`name` != ? AND `email` != ?)', $sql);
    }

    public function testWhereNoneNegatesTheOrGroup(): void
    {
        $sql = self::whereClause(Membership::whereNone(['name', 'email'], 'LIKE', '%spam%'));

        $this->assertSame('WHERE NOT (`name` LIKE ? OR `email` LIKE ?)', $sql);
    }

    public function testColumnSetRejectsAnEmptyColumnList(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one column is required.');

        Membership::whereAny([], '=', 1)->toSql();
    }

    /**
     * A hostile column name must be rejected inside a group exactly as it is at the top level.
     */
    public function testColumnSetValidatesIdentifiers(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Membership::whereAny(['name', 'email` FROM users -- '], '=', 1)->toSql();
    }

    #endregion

    #region primary key

    public function testWhereKeyMatchesASingleKey(): void
    {
        $this->assertSame('WHERE `id` = ?', self::whereClause(Membership::whereKey(7)));
    }

    public function testWhereKeyMatchesAKeyList(): void
    {
        $this->assertSame('WHERE `id` IN (?, ?, ?)', self::whereClause(Membership::whereKey([1, 2, 3])));
    }

    public function testWhereKeyNotExcludesAKeyList(): void
    {
        $this->assertSame('WHERE `id` NOT IN (?, ?)', self::whereClause(Membership::whereKeyNot([1, 2])));
    }

    #endregion

    #region LIKE search

    public function testWhereLikeAnySearchesEveryColumn(): void
    {
        $sql = self::whereClause(Membership::whereLikeAny(['name', 'email'], 'john'));

        $this->assertSame('WHERE (`name` LIKE ? OR `email` LIKE ?)', $sql);
    }

    /**
     * Without escaping, a term containing `%` widens the search to every row, and `_` matches any
     * single character — both are attacker-controlled in a search box.
     */
    public function testWhereLikeAnyEscapesWildcardsInsideTheTerm(): void
    {
        $sql = Membership::whereLikeAny(['name'], 'jo%hn_x')->toSqlWithBindings();

        $this->assertStringContainsString('%jo\\%hn\\_x%', $sql);
    }

    #endregion

    #region empty IN lists

    /**
     * `IN ()` is a syntax error. The clause must degrade to a constant with the same truth value.
     */
    public function testEmptyWhereInMatchesNothing(): void
    {
        $this->assertSame('WHERE 1 = 0', self::whereClause(Membership::whereIn('role', [])));
    }

    public function testEmptyWhereNotInMatchesEverything(): void
    {
        $this->assertSame('WHERE 1 = 1', self::whereClause(Membership::whereNotIn('role', [])));
    }

    #endregion

    #region ordering, having and JSON

    public function testOrWhereAnyJoinsTheGroupWithOr(): void
    {
        $sql = self::whereClause(
            Membership::where('age', '>', 18)->orWhereAny(['name', 'email'], 'LIKE', '%a%')
        );

        $this->assertSame('WHERE `age` > ? OR (`name` LIKE ? OR `email` LIKE ?)', $sql);
    }

    public function testOrWhereNotLikeJoinsWithOr(): void
    {
        $sql = self::whereClause(Membership::where('age', '>', 18)->orWhereNotLike('name', 'x%'));

        $this->assertSame('WHERE `age` > ? OR `name` NOT LIKE ?', $sql);
    }

    public function testWhereJsonLengthComparesElementCount(): void
    {
        $sql = self::whereClause(Membership::whereJsonLength('name', '>=', 3));

        $this->assertSame('WHERE (JSON_LENGTH(`name`) >= ?)', $sql);
    }

    public function testWhereJsonLengthRejectsAHostileOperator(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Membership::whereJsonLength('name', '> 0 UNION SELECT', 3)->toSql();
    }

    public function testHavingBetweenBindsBothBounds(): void
    {
        $sql = Membership::groupBy('age')->havingBetween('age', 1, 9)->toSqlWithBindings();

        $this->assertStringContainsString("HAVING `age` BETWEEN '1' AND '9'", $sql);
    }

    /**
     * Ordering is additive, so without reorder() an inherited sort cannot be replaced.
     */
    public function testReorderReplacesTheExistingOrdering(): void
    {
        $sql = Membership::orderBy('name')->reorder('age', 'DESC')->toSql();

        $this->assertStringContainsString('ORDER BY `age` DESC', $sql);
        $this->assertStringNotContainsString('`name`', $sql);
    }

    public function testReorderWithoutArgumentsLeavesTheQueryUnordered(): void
    {
        $sql = Membership::orderBy('name')->reorder()->toSql();

        $this->assertStringNotContainsString('ORDER BY', $sql);
    }

    public function testReorderClearsRawOrdering(): void
    {
        $sql = Membership::orderByRaw('RAND()')->reorder()->toSql();

        $this->assertStringNotContainsString('RAND()', $sql);
    }

    #endregion

    #region legacy option arrays

    /**
     * Callers still pass column-keyed condition maps through options arrays, in both the plain and
     * the operator/value shapes. Both must keep rendering as they did.
     */
    public function testLegacyColumnKeyedConditionsStillRender(): void
    {
        $sql = self::whereClause(Membership::where('role', 'admin'));

        $this->assertSame('WHERE `role` = ?', $sql);
    }

    #endregion
}
