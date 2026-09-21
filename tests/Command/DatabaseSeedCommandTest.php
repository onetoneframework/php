<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use DatabaseSeedCommand;
use PHPUnit\Framework\TestCase;

final class DatabaseSeedCommandTest extends TestCase
{
	public function testCommandMetadataIdentifiesDatabaseSeeding(): void
	{
		$command = new DatabaseSeedCommand();

		$this->assertSame('database:seed', $command->getName());
		$this->assertSame('Run database seeders', $command->getDescription());
	}

	public function testConfigureDeclaresSeederClassAndForceOption(): void
	{
		$command = new DatabaseSeedCommand();

		$command->configure();

		$this->assertCount(1, $command->arguments);
		$this->assertSame('class', $command->arguments[0]->name);
		$this->assertSame('App\\Database\\Seeders\\DatabaseSeeder', $command->arguments[0]->default);
		$this->assertCount(1, $command->options);
		$this->assertSame('force', $command->options[0]->name);
		$this->assertFalse($command->options[0]->default);
	}
}
