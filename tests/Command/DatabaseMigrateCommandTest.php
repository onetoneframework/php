<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use DatabaseMigrateCommand;
use PHPUnit\Framework\TestCase;

final class DatabaseMigrateCommandTest extends TestCase
{
	public function testConfigureExposesMigrationLifecycleOptions(): void
	{
		$command = new DatabaseMigrateCommand();
		$command->configure();

		$options = [];
		foreach ($command->options as $option) {
			$options[$option->name] = $option->default;
		}

		$this->assertSame('App/Database/Migrations', $options['path']);
		$this->assertSame('migrations', $options['table']);
		$this->assertFalse($options['pretend']);
		$this->assertSame('migrate', $options['action']);
		$this->assertSame(1, $options['steps']);
	}
}
