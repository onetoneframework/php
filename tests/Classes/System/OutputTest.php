<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\System;

use Clover\Classes\System\Output;
use PHPUnit\Framework\TestCase;

final class OutputTest extends TestCase
{
	public function testPrintWritesContentWithoutNewline(): void
	{
		$this->expectOutputString('hello');

		Output::print('hello');
	}

	public function testPrintLineAppendsPlatformNewline(): void
	{
		$this->expectOutputString('hello' . PHP_EOL);

		Output::printLine('hello');
	}

	public function testPrintFormatDelegatesFormattingArguments(): void
	{
		$this->expectOutputString('item=widget count=3');

		Output::printFormat('item=%s count=%d', 'widget', 3);
	}
}
