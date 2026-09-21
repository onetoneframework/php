<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Database\ActiveRecord;

use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\Database\Mock\MockPDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SqlInjectionAccount extends ActiveRecord
{
	/**
	 * @Id
	 * @Column(name="id", type="integer", nullable=false)
	 */
	protected int $id;

	/**
	 * @Column(name="username", type="string", nullable=false)
	 */
	protected string $username;

	/**
	 * @Column(name="role", type="string", nullable=false)
	 */
	protected string $role;
}

final class ActiveRecordSqlInjectionRegressionTest extends TestCase
{
	private MockPDO $database;

	protected function setUp(): void
	{
		parent::setUp();

		$this->database = new MockPDO();
		ActiveRecord::setDatabaseConnection($this->database);
		$this->database->insertRow('sql_injection_accounts', [
			'id' => 1,
			'username' => 'admin',
			'role' => 'administrator',
		]);
		$this->database->insertRow('sql_injection_accounts', [
			'id' => 2,
			'username' => 'member',
			'role' => 'user',
		]);
	}

	public static function hostileValueProvider(): array
	{
		return [
			'tautology' => ["' OR 1=1 -- "],
			'comment truncation' => ["admin' -- "],
			'union select' => ["' UNION SELECT 1, 'attacker', 'administrator' -- "],
			'stacked statement' => ["'; DROP TABLE sql_injection_accounts; -- "],
			'block comment' => ["' OR '1'='1' /*"],
			'time based predicate' => ["' OR SLEEP(1)=0 -- "],
		];
	}

	#[DataProvider('hostileValueProvider')]
	public function testWhereTreatsSqlInjectionPayloadAsBoundData(string $payload): void
	{
		$sql = SqlInjectionAccount::where('username', '=', $payload)->toSql();

		$this->assertStringContainsString('WHERE `username` = ?', $sql);
		$this->assertStringNotContainsString($payload, $sql);
		$this->assertSame([], SqlInjectionAccount::where('username', '=', $payload)->get());
		$this->assertCount(2, $this->database->getTableData('sql_injection_accounts'));
	}

	#[DataProvider('hostileValueProvider')]
	public function testWhereInTreatsSqlInjectionPayloadAsBoundData(string $payload): void
	{
		$sql = SqlInjectionAccount::whereIn('username', ['missing', $payload])->toSql();

		$this->assertStringContainsString('`username` IN (?, ?)', $sql);
		$this->assertStringNotContainsString($payload, $sql);
		$this->assertSame([], SqlInjectionAccount::whereIn('username', ['missing', $payload])->get());
		$this->assertCount(2, $this->database->getTableData('sql_injection_accounts'));
	}

	#[DataProvider('hostileValueProvider')]
	public function testPrimaryKeyLookupTreatsSqlInjectionPayloadAsBoundData(string $payload): void
	{
		$this->assertNull(SqlInjectionAccount::find($payload));
		$this->assertCount(2, $this->database->getTableData('sql_injection_accounts'));
	}

	#[DataProvider('hostileValueProvider')]
	public function testDeleteWhereCannotExpandPredicateWithSqlInjectionPayload(string $payload): void
	{
		$deleted = SqlInjectionAccount::where('username', '=', $payload)->deleteWhere();

		$this->assertSame(0, $deleted);
		$this->assertCount(2, $this->database->getTableData('sql_injection_accounts'));
	}

	#[DataProvider('hostileValueProvider')]
	public function testUpdateWhereCannotExpandPredicateWithSqlInjectionPayload(string $payload): void
	{
		$updated = SqlInjectionAccount::where('username', '=', $payload)->updateWhere2([
			'role' => 'administrator',
		]);

		$this->assertSame(0, $updated);
		$this->assertSame('user', $this->database->getTableData('sql_injection_accounts')[1]['role']);
	}
}
