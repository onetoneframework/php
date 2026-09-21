<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\QueryBuilder;

use Clover\Classes\Database\QueryBuilder\DeleteQuery;
use Clover\Classes\Database\QueryBuilder\InsertQuery;
use Clover\Classes\Database\QueryBuilder\SelectQuery;
use Clover\Classes\Database\QueryBuilder\UpdateQuery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SqlInjectionStatementSpy
{
	public function __construct(
		private SqlInjectionConnectionSpy $connection,
		private string $sql
	) {
	}

	public function execute(array $bindings = []): bool
	{
		$this->connection->recordPreparedExecution($this->sql, $bindings);
		return true;
	}

	public function fetchAll(): array
	{
		return [];
	}

	public function fetch(): false
	{
		return false;
	}
}

final class SqlInjectionConnectionSpy
{
	public array $directQueries = [];
	public array $preparedExecutions = [];

	public function query(string $sql): SqlInjectionStatementSpy
	{
		$this->directQueries[] = $sql;
		return new SqlInjectionStatementSpy($this, $sql);
	}

	public function prepare(string $sql): SqlInjectionStatementSpy
	{
		return new SqlInjectionStatementSpy($this, $sql);
	}

	public function recordPreparedExecution(string $sql, array $bindings): void
	{
		$this->preparedExecutions[] = [
			'sql' => $sql,
			'bindings' => $bindings,
		];
	}
}

final class QueryBuilderSqlInjectionRegressionTest extends TestCase
{
	public static function hostileValueProvider(): array
	{
		return [
			'tautology' => ["' OR 1=1 -- "],
			'comment truncation' => ["admin' -- "],
			'union select' => ["' UNION SELECT 1, 'attacker', 'administrator' -- "],
			'stacked statement' => ["'; DROP TABLE users; -- "],
			'backslash quote escape' => ["\\'; DROP TABLE users; -- "],
			'time based predicate' => ["' OR SLEEP(1)=0 -- "],
		];
	}

	#[DataProvider('hostileValueProvider')]
	public function testSelectWhereUsesPreparedBindingForHostileValue(string $payload): void
	{
		$connection = new SqlInjectionConnectionSpy();

		(new SelectQuery($connection))
			->select('*')
			->from('users')
			->where('username', '=', $payload)
			->execute();

		$this->assertPreparedPayload($connection, $payload);
	}

	#[DataProvider('hostileValueProvider')]
	public function testDeleteWhereInUsesPreparedBindingsForHostileValue(string $payload): void
	{
		$connection = new SqlInjectionConnectionSpy();

		(new DeleteQuery($connection))
			->delete('users')
			->whereIn('username', ['safe-user', $payload])
			->execute();

		$this->assertSame([], $connection->directQueries);
		$this->assertCount(1, $connection->preparedExecutions);
		$this->assertSame(['safe-user', $payload], $connection->preparedExecutions[0]['bindings']);
		$this->assertStringNotContainsString($payload, $connection->preparedExecutions[0]['sql']);
	}

	#[DataProvider('hostileValueProvider')]
	public function testUpdateSetUsesPreparedBindingForHostileValue(string $payload): void
	{
		$connection = new SqlInjectionConnectionSpy();

		(new UpdateQuery($connection))
			->update('users')
			->set('display_name', $payload)
			->where('id', '=', 1)
			->execute();

		$this->assertSame([], $connection->directQueries);
		$this->assertCount(1, $connection->preparedExecutions);
		$this->assertSame([$payload, 1], $connection->preparedExecutions[0]['bindings']);
		$this->assertStringNotContainsString($payload, $connection->preparedExecutions[0]['sql']);
	}

	#[DataProvider('hostileValueProvider')]
	public function testDeleteWhereUsesPreparedBindingForHostileValue(string $payload): void
	{
		$connection = new SqlInjectionConnectionSpy();

		(new DeleteQuery($connection))
			->delete('users')
			->where('username', '=', $payload)
			->execute();

		$this->assertPreparedPayload($connection, $payload);
	}

	#[DataProvider('hostileValueProvider')]
	public function testInsertValuesUsePreparedBindingsForHostileValue(string $payload): void
	{
		$connection = new SqlInjectionConnectionSpy();

		(new InsertQuery($connection))
			->insert('users')
			->values([
				'username' => $payload,
				'role' => 'user',
			])
			->execute();

		$this->assertSame([], $connection->directQueries);
		$this->assertCount(1, $connection->preparedExecutions);
		$this->assertSame([$payload, 'user'], $connection->preparedExecutions[0]['bindings']);
		$this->assertStringNotContainsString($payload, $connection->preparedExecutions[0]['sql']);
	}

	private function assertPreparedPayload(SqlInjectionConnectionSpy $connection, string $payload): void
	{
		$this->assertSame([], $connection->directQueries);
		$this->assertCount(1, $connection->preparedExecutions);
		$this->assertSame([$payload], $connection->preparedExecutions[0]['bindings']);
		$this->assertStringNotContainsString($payload, $connection->preparedExecutions[0]['sql']);
	}
}
