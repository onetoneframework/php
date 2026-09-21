<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\CreateQuery;
use Clover\Classes\Database\QueryBuilder\DeleteQuery;
use Clover\Classes\Database\QueryBuilder\InsertQuery;
use Clover\Classes\Database\QueryBuilder\SelectQuery;
use Clover\Classes\Database\QueryBuilder\UpdateQuery;
use Clover\Classes\Database\QueryBuilder\WithQuery;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Every identifier, operator and sort direction a caller can reach must go through
 * Clover\Classes\Database\SqlIdentifier before it is interpolated into a statement.
 *
 * The tests come in pairs: a hostile name has to be refused outright, and the ordinary name the
 * builder was written for has to keep producing the statement it always did, now quoted. The
 * hostile strings below are the shapes that actually break out of an unquoted interpolation —
 * a statement terminator, a comment, a closing backtick, a tautology, a UNION.
 */
final class QueryBuilderIdentifierValidationTest extends TestCase
{
    /**
     * Column names that must never reach the driver.
     *
     * @return array<string, array{string}>
     */
    public static function hostileColumns(): array
    {
        return [
            'statement terminator' => ['id; DROP TABLE users --'],
            'closing backtick' => ['id` = 1 OR `1'],
            'tautology' => ['1=1'],
            'union' => ['id UNION SELECT password FROM users'],
            'comment' => ['id -- '],
            'quote' => ["id' OR '1'='1"],
            'space' => ['id, password'],
            'over qualified' => ['db.users.id'],
            'empty' => [''],
        ];
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testOrderByRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->orderBy($column);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testHavingRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->having($column, '>', 1);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testWhereRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->where($column, '=', 1);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testJoinOnRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())
            ->select('*')
            ->from('users')
            ->join('sessions', 'sessions.user_id', '=', 'users.id')
            ->on($column, '=', 'users.id');
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testWhereOnRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())
            ->select('*')
            ->from('users')
            ->join('sessions', 'sessions.user_id', '=', 'users.id')
            ->whereOn($column, '=', 'expired');
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testGroupByRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->groupBy($column);
    }

    /**
     * SelectQuery narrows whereIn() to a subquery string; the array form lives on WhereTrait and
     * is reached through the other builders.
     *
     * @dataProvider hostileColumns
     */
    public function testWhereInRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->whereIn($column, '(SELECT id FROM roles)');
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testWhereInWithValueListRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeleteQuery())->delete('users')->whereIn($column, ['a', 'b']);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testWhereNotInRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new DeleteQuery())->delete('users')->whereNotIn($column, ['a', 'b']);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testHavingInRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())->select('*')->from('users')->havingIn($column, ['a', 'b']);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testUpdateSetRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new UpdateQuery())->update('users')->set([$column => 'x']);
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testInsertColumnRejectsHostileColumn(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new InsertQuery())->insert('users')->values([$column => 'x'])->toSql();
    }

    /**
     * @dataProvider hostileColumns
     */
    public function testCreateTableColumnRejectsHostileName(string $column): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new CreateQuery())->createTable('users')->integer($column, 11);
    }

    public function testWhereGroupRejectsHostileColumnInsideTheGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())
            ->select('*')
            ->from('users')
            ->whereGroup(function ($query) {
                $query->where('status`) OR 1=1 -- ', 'active');
            });
    }

    public function testHavingGroupRejectsHostileColumnInsideTheGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SelectQuery())
            ->select('*')
            ->from('users')
            ->havingGroup(function ($query) {
                $query->having('total`) OR 1=1 -- ', '>', 1);
            });
    }

    public function testTableNamesAreValidatedOnEveryBuilder(): void
    {
        $hostile = 'users; DROP TABLE sessions --';

        $this->assertRejected(fn () => (new SelectQuery())->from($hostile));
        $this->assertRejected(fn () => (new UpdateQuery())->update($hostile));
        $this->assertRejected(fn () => (new DeleteQuery())->delete($hostile));
        $this->assertRejected(fn () => (new DeleteQuery())->from($hostile));
        $this->assertRejected(fn () => (new DeleteQuery())->truncate($hostile));
        $this->assertRejected(fn () => (new DeleteQuery())->delete('users')->using($hostile));
        $this->assertRejected(fn () => (new InsertQuery())->insert($hostile));
        $this->assertRejected(fn () => (new CreateQuery())->createTable($hostile));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->join($hostile, 'a.id', '=', 'b.id'));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->crossJoin($hostile));
    }

    public function testAliasesAndCteNamesAreValidated(): void
    {
        $hostile = 'u` , (SELECT password FROM users) AS `x';

        $this->assertRejected(fn () => (new SelectQuery())->from('users', $hostile));
        $this->assertRejected(fn () => (new SelectQuery())->fromSub('SELECT 1', $hostile));
        $this->assertRejected(fn () => (new SelectQuery())->with($hostile, 'SELECT 1'));
        $this->assertRejected(fn () => (new SelectQuery())->with('recent', 'SELECT 1', ['id', $hostile]));
        $this->assertRejected(fn () => (new WithQuery())->with($hostile, 'SELECT 1'));
        $this->assertRejected(fn () => (new WithQuery())->withRecursive('tree', 'SELECT 1', ['id', $hostile]));
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->joinSub('SELECT 1', $hostile, 'u.id', '=', 'x.id')
        );
    }

    public function testOperatorsAreLimitedToTheWhitelist(): void
    {
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->where('id', 'IS NOT DISTINCT FROM', 1));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->where('id', '= 1 OR 1', 1));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->having('total', 'RLIKE', 1));
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->join('sessions', 'sessions.user_id', 'UNION', 'users.id')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->orderByCondition('id', '= 1; DROP TABLE users --', '1')
        );
    }

    public function testDirectionsAreRejectedRatherThanSilentlyCoerced(): void
    {
        // The previous implementation fell back to ASC for anything it did not recognise, which
        // meant `ORDER BY name ' . $injected` quietly became a valid statement. A direction that
        // is not ASC or DESC is now an error.
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->orderBy('name', 'ASC; DROP TABLE users --'));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->orderBy('name', 'UP'));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->reorder('name', 'sideways'));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->orderByCondition('id', '=', '1', 'UP'));
    }

    public function testConnectorsAndJoinTypesAreLimitedToTheWhitelist(): void
    {
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->where('id', '=', 1, 'AND 1=1 OR'));
        $this->assertRejected(fn () => (new SelectQuery())->select('*')->from('users')->having('total', '>', 1, 'OR 1=1'));
        $this->assertRejected(
            fn () => (new SelectQuery())
                ->select('*')
                ->from('users')
                ->join('sessions', 'sessions.user_id', '=', 'users.id')
                ->on('sessions.id', '=', 'users.id', 'AND 1=1 OR')
        );
        $this->assertRejected(
            fn () => (new SelectQuery())->select('*')->from('users')->join('sessions', 'sessions.user_id', '=', 'users.id', 'INNER; DROP TABLE users --')
        );
    }

    public function testDdlKeywordsThatAreNotIdentifiersAreAlsoConstrained(): void
    {
        $this->assertRejected(fn () => (new CreateQuery())->createTable('users')->column('id', 'INT(11), x TEXT, y TEXT'));
        $this->assertRejected(fn () => (new CreateQuery())->createTable('users')->engine('InnoDB; DROP TABLE users --'));
        $this->assertRejected(fn () => (new CreateQuery())->createTable('users')->charset("utf8mb4'"));
        $this->assertRejected(fn () => (new CreateQuery())->createTable('users')->collation('utf8mb4_unicode_ci --'));
        $this->assertRejected(
            fn () => (new CreateQuery())->createTable('users')->foreignKey('fk', 'role_id', 'roles(id)', 'CASCADE; DROP TABLE roles --')
        );
        $this->assertRejected(
            fn () => (new CreateQuery())->createTable('users')->index('idx`) , (SELECT 1', 'email')
        );
    }

    public function testSelectListQuotesPlainColumnsAndPassesExpressionsThrough(): void
    {
        // The SELECT list is the one permissive site: it has to carry `*` and aggregates, which
        // are not identifiers and cannot be quoted.
        $sql = (new SelectQuery())
            ->select(['id', 'u.name', 'COUNT(*) as total', '*'])
            ->from('users', 'u')
            ->toSql();

        $this->assertSame('SELECT `id`, `u`.`name`, COUNT(*) as total, * FROM `users` AS `u`', $sql);
    }

    public function testCountKeepsWorkingThroughThePermissiveSelectList(): void
    {
        $connection = new class {
            public string $lastQuery = '';

            public function query(string $sql): object
            {
                $this->lastQuery = $sql;

                return new class {
                    public function fetch(): array
                    {
                        return ['count' => 3];
                    }
                };
            }
        };

        $query = (new SelectQuery($connection))->select('*')->from('users');

        $this->assertSame(3, $query->count());
        $this->assertSame('SELECT COUNT(*) as count FROM `users`', $connection->lastQuery);
    }

    public function testLegitimateSelectStillBuildsTheExpectedStatement(): void
    {
        $sql = (new SelectQuery())
            ->select(['u.id', 'u.name'])
            ->from('users', 'u')
            ->leftJoin('posts', 'posts.user_id', '=', 'u.id')
            ->on('posts.published', '=', 'u.verified')
            ->where('u.active', '=', 1)
            ->where('u.role', 'IN', ['admin', 'editor'])
            ->whereNotNull('u.email')
            ->groupBy(['u.id', 'u.name'])
            ->having('u.id', '>', 0)
            ->orderBy('u.name', 'desc')
            ->limit(10)
            ->toSql();

        $this->assertSame(
            'SELECT `u`.`id`, `u`.`name` FROM `users` AS `u`'
            . ' LEFT JOIN `posts` ON `posts`.`user_id` = `u`.`id` AND `posts`.`published` = `u`.`verified`'
            . " WHERE `u`.`active` = 1 AND `u`.`role` IN ('admin', 'editor') AND `u`.`email` IS NOT NULL"
            . ' GROUP BY `u`.`id`, `u`.`name` HAVING `u`.`id` > 0 ORDER BY `u`.`name` DESC LIMIT 10',
            $sql
        );
    }

    public function testLegitimateWriteStatementsStillBuildAsExpected(): void
    {
        $update = (new UpdateQuery())
            ->update('users')
            ->set(['name' => 'Ada'])
            ->increment('logins', 1)
            ->setNow('updated_at')
            ->where('id', '=', 7)
            ->toSql();

        $delete = (new DeleteQuery())
            ->delete('sessions')
            ->where('expired', '=', 1)
            ->toSql();

        $insert = (new InsertQuery())
            ->insert('users')
            ->values(['name' => 'Ada', 'age' => 36])
            ->onDuplicateKeyUpdateValue('name')
            ->toSql();

        $this->assertSame(
            "UPDATE `users` SET `name` = 'Ada', `logins` = `logins` + 1, `updated_at` = NOW() WHERE `id` = 7",
            $update
        );
        $this->assertSame('DELETE FROM `sessions` WHERE `expired` = 1', $delete);
        $this->assertSame(
            "INSERT INTO `users` (`name`, `age`) VALUES ('Ada', 36) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`)",
            $insert
        );
    }

    public function testLegitimateDdlAndCteStillBuildAsExpected(): void
    {
        $create = (new CreateQuery())
            ->createTable('users')
            ->integer('id', 11, ['auto_increment' => true, 'nullable' => false])
            ->string('email', 255, ['unique' => true])
            ->primaryKey('id')
            ->index('idx_email', 'email')
            ->foreignKey('fk_role', 'role_id', 'roles(id)', 'CASCADE')
            ->toSql();

        $with = (new WithQuery())
            ->with('recent', 'SELECT id FROM posts', ['id'])
            ->toSql();

        $this->assertSame(
            'CREATE TABLE `users` (`id` INT(11) NOT NULL AUTO_INCREMENT, `email` VARCHAR(255) UNIQUE,'
            . ' PRIMARY KEY (`id`), INDEX `idx_email` (`email`),'
            . ' FOREIGN KEY `fk_role` (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE)'
            . ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
            $create
        );
        $this->assertSame('WITH `recent` (`id`) AS (SELECT id FROM posts)', $with);
    }

    public function testCteHandedToTheMainSelectIsQuotedExactlyOnce(): void
    {
        // WithQuery keeps the unquoted name so SelectQuery::with() can quote it; a CTE that went
        // through both would come out with doubled backticks, or be rejected outright.
        $sql = (new WithQuery())
            ->with('recent', 'SELECT id FROM posts', ['id'])
            ->select('*')
            ->from('recent')
            ->toSql();

        $this->assertSame('WITH `recent` (`id`) AS (SELECT id FROM posts) SELECT * FROM `recent`', $sql);
    }

    public function testExistsClauseEmitsASubqueryRatherThanAPseudoColumn(): void
    {
        $sql = (new SelectQuery())
            ->select('*')
            ->from('users')
            ->whereExists('SELECT 1 FROM posts WHERE posts.user_id = users.id')
            ->toSql();

        $this->assertSame(
            'SELECT * FROM `users` WHERE EXISTS (SELECT 1 FROM posts WHERE posts.user_id = users.id)',
            $sql
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

        $this->fail('Expected the builder to reject the identifier, operator or keyword.');
    }
}
