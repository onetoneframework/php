<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\UpdateQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class UpdateQueryTest extends TestCase
{
    public function testBuildsUpdateSqlWithSetJoinWhereOrderAndLimit(): void
    {
        $sql = (new UpdateQuery())
            ->update('users')
            ->leftJoin('profiles', 'profiles.user_id', '=', 'users.id')
            ->set(['name' => 'John'])
            ->setNow('updated_at')
            ->where('users.id', '=', 1)
            ->orderBy('users.id', 'DESC')
            ->limit(1)
            ->toSql();

        $this->assertSame(
            "UPDATE `users` LEFT JOIN `profiles` ON `profiles`.`user_id` = `users`.`id` SET `name` = 'John', `updated_at` = NOW() WHERE `users`.`id` = 1 ORDER BY `users`.`id` DESC LIMIT 1",
            $sql
        );
    }

    public function testIncrementDecrementAndNullSettersAreReflectedInSql(): void
    {
        $sql = (new UpdateQuery())
            ->update('products')
            ->increment('stock', 5)
            ->decrement('sold', 1)
            ->setNull('deleted_at')
            ->toSql();

        $this->assertStringContainsString('`stock` = `stock` + 5', $sql);
        $this->assertStringContainsString('`sold` = `sold` - 1', $sql);
        $this->assertStringContainsString('`deleted_at` = NULL', $sql);
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
                return 3;
            }
        };

        $query = (new UpdateQuery($connection))
            ->update('users')
            ->set('name', 'Jane')
            ->where('id', '=', 10);

        $this->assertStringContainsString('UPDATE `users` SET `name` = \'Jane\'', $query->execute());
        $this->assertSame(3, $query->getAffectedRows());
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new UpdateQuery())
            ->update('users')
            ->set('name', 'NoConn');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
