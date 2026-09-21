<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\CLI;

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputArgument;
use Clover\Classes\CLI\InputOption;
use PHPUnit\Framework\TestCase;

final class InputTest extends TestCase
{
	public function testMissingCommandDefaultsToList(): void
	{
		$input = new Input(['console']);

		self::assertSame('list', $input->command);
		self::assertSame([], $input->args);
		self::assertSame([], $input->options);
	}

	public function testPositionalArgumentsResolveByNameAndIndex(): void
	{
		$input = new Input(
			['console', 'deploy', 'production', 'eu-west'],
			[
				new InputArgument('environment'),
				new InputArgument('region', default: 'local'),
			]
		);

		self::assertSame('deploy', $input->command);
		self::assertSame('production', $input->getArgument('environment'));
		self::assertSame('eu-west', $input->getArgument('region'));
		self::assertSame('production', $input->getArgument(0));
		self::assertSame('eu-west', $input->getArgument(1));
		self::assertNull($input->getArgument(2));
	}

	public function testMissingPositionalArgumentUsesDefinitionDefault(): void
	{
		$input = new Input(
			['console', 'deploy', 'production'],
			[
				new InputArgument('environment'),
				new InputArgument('region', default: 'local'),
			]
		);

		self::assertSame('local', $input->getArgument('region'));
	}

	public function testLongOptionsSupportFlagsValuesAndEmbeddedEqualsSigns(): void
	{
		$input = new Input([
			'console',
			'database:migrate',
			'--verbose',
			'--format=json',
			'--dsn=server=primary',
		]);

		self::assertTrue($input->getOption('verbose'));
		self::assertSame('json', $input->getOption('format'));
		self::assertSame('server=primary', $input->getOption('dsn'));
	}

	public function testDeclaredOptionsReceiveDefaultsAndExplicitValuesWin(): void
	{
		$definitions = [
			new InputOption('format', default: 'text'),
			new InputOption('limit', default: 10),
		];
		$input = new Input(['console', 'report', '--format=json'], optionDefs: $definitions);

		self::assertSame('json', $input->getOption('format'));
		self::assertSame(10, $input->getOption('limit'));
		self::assertTrue($input->hasOption('format'));
		self::assertTrue($input->hasOption('limit'));
		self::assertFalse($input->hasOption('missing'));
		self::assertNull($input->getOption('missing'));
	}

	public function testOptionsDoNotConsumeOptionalPositionalArguments(): void
	{
		$input = new Input(
			['console', 'deploy', '--verbose', 'production'],
			[
				new InputArgument('environment'),
				new InputArgument('region', default: 'local'),
			]
		);

		self::assertSame('production', $input->getArgument('environment'));
		self::assertSame('local', $input->getArgument('region'));
		self::assertTrue($input->getOption('verbose'));
	}
}
