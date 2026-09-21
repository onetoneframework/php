<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\DeleteQuery;
use Clover\Classes\Database\QueryBuilder\SelectQuery;
use Clover\Classes\Database\QueryBuilder\UpdateQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * The `*Raw` family is the deliberate hole in the identifier validation, and these tests pin both
 * halves of the bargain.
 *
 * Validation by itself would have made ordinary SQL unwritable: an aggregate in HAVING, a grouping
 * by `DATE(created_at)`, a custom sort by `FIELD(...)` and a predicate over `LENGTH(name)` are all
 * expressions rather than identifiers, so {@see \Clover\Classes\Database\SqlIdentifier} refuses
 * them. Every one of them is now reachable through a raw method, and every raw method emits what
 * it was given untouched — which is exactly why it must never be handed user input.
 *
 * The tests therefore come in pairs: the raw method emits the expression verbatim, and the
 * validated sibling still refuses the very same string. What is passed to a raw method is the
 * caller's responsibility; what is passed to its sibling is checked.
 */
final class QueryBuilderRawExpressionTest extends TestCase
{
    public function testWhereRawEmitsTheExpressionVerbatim(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->whereRaw('LENGTH(name) > 10')
            ->toSql();

        $this->assertSame('SELECT * FROM `users` WHERE LENGTH(name) > 10', $sql);
    }

    public function testOrWhereRawJoinsWithOr(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->where('active', '=', 1)
            ->orWhereRaw('LENGTH(name) > 10')
            ->toSql();

        $this->assertSame('SELECT * FROM `users` WHERE `active` = 1 OR LENGTH(name) > 10', $sql);
    }

    public function testHavingRawEmitsTheExpressionVerbatim(): void
    {
        $sql = (new SelectQuery())
            ->select(['user_id'])
            ->from('posts')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 5')
            ->toSql();

        $this->assertSame(
            'SELECT `user_id` FROM `posts` GROUP BY `user_id` HAVING COUNT(*) > 5',
            $sql
        );
    }

    public function testGroupByRawEmitsTheExpressionVerbatim(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('orders')
            ->groupByRaw('DATE(created_at)')
            ->toSql();

        $this->assertSame('SELECT * FROM `orders` GROUP BY DATE(created_at)', $sql);
    }

    public function testOrderByRawEmitsTheExpressionVerbatim(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->orderByRaw('FIELD(id,3,1,2)')
            ->toSql();

        $this->assertSame('SELECT * FROM `users` ORDER BY FIELD(id,3,1,2)', $sql);
    }

    public function testSelectRawEmitsTheExpressionVerbatim(): void
    {
        $sql = (new SelectQuery())
            ->selectRaw('COUNT(*) AS total')
            ->from('users')
            ->toSql();

        $this->assertSame('SELECT COUNT(*) AS total FROM `users`', $sql);
    }

    /**
     * selectRaw() appends rather than replacing, so a validated list and a raw member coexist.
     */
    public function testSelectRawAppendsToAValidatedSelectList(): void
    {
        $sql = (new SelectQuery())
            ->select(['u.id', 'u.name'])
            ->selectRaw('COUNT(*) AS total')
            ->from('users', 'u')
            ->toSql();

        $this->assertSame('SELECT `u`.`id`, `u`.`name`, COUNT(*) AS total FROM `users` AS `u`', $sql);
    }

    /**
     * The four calls that section 1.1 broke, each written through its raw counterpart. This is the
     * regression the family exists to prevent: identifier validation must not make ordinary SQL
     * unwritable.
     */
    public function testTheFourMotivatingExpressionsAreWritableThroughTheirRawCounterpart(): void
    {
        $sql = (new SelectQuery())
            ->select(['user_id'])
            ->from('posts')
            ->whereRaw('LENGTH(name) > 10')
            ->groupByRaw('DATE(created_at)')
            ->havingRaw('COUNT(*) > 5')
            ->orderByRaw('FIELD(id,3,1,2)')
            ->toSql();

        $this->assertSame(
            'SELECT `user_id` FROM `posts` WHERE LENGTH(name) > 10'
            . ' GROUP BY DATE(created_at) HAVING COUNT(*) > 5 ORDER BY FIELD(id,3,1,2)',
            $sql
        );
    }

    /**
     * A raw expression and a validated clause mix in one statement, and only the validated one is
     * quoted.
     */
    public function testRawAndValidatedClausesCombineInOneStatement(): void
    {
        $sql = (new SelectQuery())
            ->select(['u.id'])
            ->selectRaw('COUNT(p.id) AS post_count')
            ->from('users', 'u')
            ->where('u.active', '=', 1)
            ->whereRaw('LENGTH(u.name) > 10')
            ->groupBy('u.id')
            ->groupByRaw('DATE(u.created_at)')
            ->having('u.id', '>', 0)
            ->havingRaw('COUNT(p.id) > 5')
            ->orderBy('u.name', 'DESC')
            ->orderByRaw('FIELD(u.id,3,1,2)')
            ->toSql();

        $this->assertSame(
            'SELECT `u`.`id`, COUNT(p.id) AS post_count FROM `users` AS `u`'
            . ' WHERE `u`.`active` = 1 AND LENGTH(u.name) > 10'
            . ' GROUP BY `u`.`id`, DATE(u.created_at)'
            . ' HAVING `u`.`id` > 0 AND COUNT(p.id) > 5'
            . ' ORDER BY `u`.`name` DESC, FIELD(u.id,3,1,2)',
            $sql
        );
    }

    /**
     * WhereTrait is shared, so the raw door opens on every builder that uses it.
     */
    public function testWhereRawIsAvailableOnTheWriteBuilders(): void
    {
        $delete = (new DeleteQuery())
            ->delete('sessions')
            ->whereRaw('LENGTH(token) > 10')
            ->toSql();

        $update = (new UpdateQuery())
            ->update('users')
            ->set(['name' => 'Ada'])
            ->whereRaw('LENGTH(name) > 10')
            ->toSql();

        $this->assertSame('DELETE FROM `sessions` WHERE LENGTH(token) > 10', $delete);
        $this->assertSame("UPDATE `users` SET `name` = 'Ada' WHERE LENGTH(name) > 10", $update);
    }

    /**
     * The point of the family, stated as a test: nothing about the expression is inspected. A
     * string that would be refused anywhere else in the builder is emitted unchanged, which is why
     * the docblocks say a raw method must never be given user input.
     */
    public function testRawExpressionsAreNotInspectedAtAll(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->whereRaw("id = 1 OR '1'='1' -- ")
            ->toSql();

        $this->assertSame("SELECT * FROM `users` WHERE id = 1 OR '1'='1' -- ", $sql);
    }

    /**
     * The connector is structure the builder writes itself, not part of the caller's expression, so
     * it is still matched against the AND/OR whitelist.
     */
    public function testTheConnectorIsStillWhitelistedOnTheRawMethods(): void
    {
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->whereRaw('1=1', 'AND 1=1 OR')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->whereRaw('1=1', 'UNION')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->havingRaw('COUNT(*) > 5', 'OR 1=1')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->havingRaw('COUNT(*) > 5', '; DROP TABLE users --')
        );
    }

    public function testTheConnectorIsStillNormalisedOnTheRawMethods(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->where('active', '=', 1)
            ->whereRaw('LENGTH(name) > 10', ' or ')
            ->toSql();

        $this->assertSame('SELECT * FROM `users` WHERE `active` = 1 OR LENGTH(name) > 10', $sql);
    }

    /**
     * The other half of the bargain: adding the raw door must not have loosened the front one. Each
     * expression that a raw method accepts is still refused by its validated sibling.
     */
    public function testTheValidatedSiblingStillRejectsTheSameExpression(): void
    {
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->where('LENGTH(name)', '>', 10)
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->having('COUNT(*)', '>', 5)
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->groupBy('DATE(created_at)')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->orderBy('FIELD(id,3,1,2)')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->orWhere('LENGTH(name)', '>', 10)
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->orHaving('COUNT(*)', '>', 5)
        );
    }

    /**
     * Assert that a builder call refuses its input instead of producing SQL from it.
     *
     * @param callable $build
     *
     * @return void
     */
    private function assertRejected(callable $build): void
    {
        try {
            $build();
        } catch (InvalidArgumentException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Expected the builder to reject the identifier, operator or connector.');
    }
}
