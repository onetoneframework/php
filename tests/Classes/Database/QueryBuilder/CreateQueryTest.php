<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\CreateQuery;
use Exception;
use PHPUnit\Framework\TestCase;

final class CreateQueryTest extends TestCase
{
    public function testBuildsExpectedCreateTableSql(): void
    {
        $sql = (new CreateQuery())
            ->createTable('users')
            ->ifNotExists()
            ->integer('id', 11, ['auto_increment' => true, 'nullable' => false])
            ->string('email', 255, ['unique' => true])
            ->primaryKey('id')
            ->toSql();

        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `users`', $sql);
        $this->assertStringContainsString('`id` INT(11) NOT NULL AUTO_INCREMENT', $sql);
        $this->assertStringContainsString('`email` VARCHAR(255) UNIQUE', $sql);
        $this->assertStringContainsString('PRIMARY KEY (`id`)', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB', $sql);
    }

    public function testExecuteCallsConnectionQueryWhenConnectionExists(): void
    {
        $connection = new class {
            public string $lastQuery = '';
            public function query(string $sql): string
            {
                $this->lastQuery = $sql;
                return 'ok';
            }
        };

        $query = (new CreateQuery($connection))
            ->createTable('audit_logs')
            ->integer('id', 11, ['auto_increment' => true, 'nullable' => false])
            ->primaryKey('id');

        $result = $query->execute();

        $this->assertSame('ok', $result);
        $this->assertStringContainsString('CREATE TABLE `audit_logs`', $connection->lastQuery);
    }

    public function testExecuteThrowsWhenConnectionMissing(): void
    {
        $query = (new CreateQuery())
            ->createTable('t1')
            ->integer('id', 11);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No database connection available');
        $query->execute();
    }
}
