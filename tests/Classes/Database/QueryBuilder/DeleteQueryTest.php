<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\DeleteQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class DeleteQueryTest extends TestCase
{
    public function testBuildsDeleteSqlWithJoinWhereAndLimit(): void
    {
        $sql = (new DeleteQuery())
            ->delete('users')
            ->join('sessions', 'sessions.user_id', '=', 'users.id')
            ->where('sessions.expired', '=', 1)
            ->orderBy('users.id', 'DESC')
            ->limit(5)
            ->toSql();

        $this->assertSame(
            'DELETE FROM `users` INNER JOIN `sessions` ON `sessions`.`user_id` = `users`.`id` WHERE `sessions`.`expired` = 1 ORDER BY `users`.`id` DESC LIMIT 5',
            $sql
        );
    }

    public function testUsingClauseAndTruncateProduceExpectedSql(): void
    {
        $usingSql = (new DeleteQuery())
            ->delete('u')
            ->using(['users', 'sessions'])
            ->where('u.id', '=', 1)
            ->toSql();

        $truncateSql = (new DeleteQuery())
            ->truncate('logs')
            ->toSql();

        $this->assertSame(
            'DELETE `u` FROM `u` USING `users`, `sessions` WHERE `u`.`id` = 1',
            $usingSql
        );
        $this->assertSame('TRUNCATE TABLE `logs`', $truncateSql);
    }

    public function testExecuteAndAffectedRowsUseConnection(): void
    {
        $connection = new class {
            public function query(string $sql): string
            {
                return $sql;
            }
            public function rowCount(): int
            {
                return 7;
            }
        };

        $query = (new DeleteQuery($connection))
            ->delete('users')
            ->where('id', '=', 1);

        $this->assertStringContainsString('DELETE FROM `users`', $query->execute());
        $this->assertSame(7, $query->getAffectedRows());
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new DeleteQuery())
            ->delete('users')
            ->where('id', '=', 1);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
