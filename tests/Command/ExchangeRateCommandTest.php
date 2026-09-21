<?php

declare(strict_types=1);

namespace Clover\Tests\Command;

use Clover\Classes\CLI\Input;
use Command\ExchangeRateCommand;
use PHPUnit\Framework\TestCase;

final class ExchangeRateCommandTest extends TestCase
{
	public function testSameCurrencyConversionIsHandledWithoutFetchingRates(): void
	{
		$command = new ExchangeRateCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'exchange:rates', '--convert=12.5 usd->usd'],
			[],
			$command->options
		);

		$this->expectOutputString('12.5 USD = 12.5 USD' . PHP_EOL);

		$this->assertTrue($command->run($input));
	}

	public function testInvalidConversionExpressionFailsBeforeAnyFetch(): void
	{
		$command = new ExchangeRateCommand();
		$command->configure();
		$input = new Input(
			['php_console', 'exchange:rates', '--convert=not-a-conversion'],
			[],
			$command->options
		);

		$this->expectOutputString(
			'Invalid --convert. Examples: 1THB->USD   10.5 EUR -> USD' . PHP_EOL
		);

		$this->assertFalse($command->run($input));
	}

	public function testConversionCannotBeCombinedWithExplicitUrl(): void
	{
		$command = new ExchangeRateCommand();
		$command->configure();
		$input = new Input(
			[
				'php_console',
				'exchange:rates',
				'--convert=1 USD->EUR',
				'--url=https://example.invalid/rates',
			],
			[],
			$command->options
		);

		$this->expectOutputString('--convert cannot be used together with --url.' . PHP_EOL);

		$this->assertFalse($command->run($input));
	}
}
