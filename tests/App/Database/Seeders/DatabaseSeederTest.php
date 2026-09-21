<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\App\Database\Seeders;

use App\Database\Seeders\DatabaseSeeder;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Database\Seeder\Seeder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class DatabaseSeederTest extends TestCase
{
	public function testDefaultSeederRunsWithoutDatabaseOperations(): void
	{
		$database = $this->createMock(PHPDataObject::class);
		$database->expects($this->never())->method('executeQuery');
		$seeder = new DatabaseSeeder();

		$seeder->run($database);

		$this->assertInstanceOf(Seeder::class, $seeder);
	}

	public function testConfiguredSeedersRunInDeclarationOrder(): void
	{
		$database = $this->createDatabaseMockWithExpectedNames(['first', 'second']);
		$seeder = new DatabaseSeeder(FirstDatabaseSeederFixture::class, SecondDatabaseSeederFixture::class);

		$seeder->run($database);
	}

	public function testMissingConfiguredSeederIsRejected(): void
	{
		$database = $this->createMock(PHPDataObject::class);
		$seeder = new DatabaseSeeder('App\\Database\\Seeders\\MissingSeeder');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Seeder class not found');

		$seeder->run($database);
	}

	/**
	 * @param list<string> $expectedNames
	 */
	private function createDatabaseMockWithExpectedNames(array $expectedNames): PHPDataObject&MockObject
	{
		$invocationIndex = 0;
		$database = $this->createMock(PHPDataObject::class);
		$database->expects($this->exactly(count($expectedNames)))
			->method('setDatabase')
			->willReturnCallback(function (string $databaseName) use (&$invocationIndex, $expectedNames, $database): PHPDataObject {
				$this->assertSame($expectedNames[$invocationIndex], $databaseName);
				$invocationIndex++;

				return $database;
			});

		return $database;
	}
}

final class FirstDatabaseSeederFixture extends Seeder
{
	public function run(PHPDataObject $database): void
	{
		$database->setDatabase('first');
	}
}

final class SecondDatabaseSeederFixture extends Seeder
{
	public function run(PHPDataObject $database): void
	{
		$database->setDatabase('second');
	}
}
