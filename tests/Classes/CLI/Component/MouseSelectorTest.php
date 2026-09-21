<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\CLI\Component;

use Clover\Classes\CLI\Component\MouseSelector;
use Clover\Classes\CLI\TerminalUI;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MouseSelectorTest extends TestCase
{
	public function testPrimaryMouseClickSelectsTheMatchingItemRow(): void
	{
		$terminalUi = $this->createMock(TerminalUI::class);
		$terminalUi->expects(self::once())->method('clearScreen');
		$terminalUi->expects(self::once())->method('hideCursor');
		$terminalUi->expects(self::once())->method('showCursor');
		$terminalUi->expects(self::once())
			->method('getKey')
			->willReturn("\033[<0;8;2M");
		$terminalUi->expects(self::exactly(2))
			->method('color')
			->willReturnCallback(static function (string $text, string $color): string {
				self::assertSame('cyan', $color);

				return $text;
			});

		$this->expectOutputString(
			"\033[?1000h\033[?1006h"
			. "\033[H > first\n   second\n"
			. "\033[H   first\n > second\n"
			. "\033[?1000l\033[?1006l"
		);

		$selector = new MouseSelector(['first', 'second'], $terminalUi);

		self::assertSame('second', $selector->show());
	}

	public function testUnsupportedMouseEventsAreIgnoredUntilAnItemIsSelected(): void
	{
		$terminalUi = $this->createMock(TerminalUI::class);
		$terminalUi->expects(self::once())->method('clearScreen');
		$terminalUi->expects(self::once())->method('hideCursor');
		$terminalUi->expects(self::once())->method('showCursor');
		$terminalUi->expects(self::exactly(3))
			->method('getKey')
			->willReturnOnConsecutiveCalls(
				"\033[<2;8;1M",
				"\033[<0;8;99M",
				"\033[<0;8;1M"
			);
		$terminalUi->method('color')
			->willReturnCallback(static fn (string $text, string $color): string => $text);

		$this->expectOutputString(
			"\033[?1000h\033[?1006h"
			. "\033[H > first\n   second\n"
			. "\033[H > first\n   second\n"
			. "\033[?1000l\033[?1006l"
		);

		$selector = new MouseSelector(['first', 'second'], $terminalUi);

		self::assertSame('first', $selector->show());
	}

	public function testEmptyItemListIsRejected(): void
	{
		$terminalUi = $this->createStub(TerminalUI::class);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Mouse selector items cannot be empty.');

		new MouseSelector([], $terminalUi);
	}

	public function testNonStringItemIsRejected(): void
	{
		$terminalUi = $this->createStub(TerminalUI::class);

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Mouse selector items must be strings.');

		new MouseSelector(['first', 2], $terminalUi);
	}

	public function testUnreadableInputRestoresTerminalState(): void
	{
		$terminalUi = $this->createMock(TerminalUI::class);
		$terminalUi->expects(self::once())->method('clearScreen');
		$terminalUi->expects(self::once())->method('hideCursor');
		$terminalUi->expects(self::once())->method('showCursor');
		$terminalUi->expects(self::once())->method('getKey')->willReturn(false);
		$terminalUi->method('color')->willReturnArgument(0);

		$this->expectOutputString(
			"\033[?1000h\033[?1006h"
			. "\033[H > first\n"
			. "\033[?1000l\033[?1006l"
		);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to read mouse selector input.');

		$selector = new MouseSelector(['first'], $terminalUi);
		$selector->show();
	}
}
