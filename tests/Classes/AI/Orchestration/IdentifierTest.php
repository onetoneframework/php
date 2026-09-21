<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\Identifier;
use Clover\Exception\AI\InvalidIdentifierException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
	private const OVERSIZED_IDENTIFIER_LENGTH = 129;

	public function testValidIdentifierPreservesValue(): void
	{
		$identifier = new Identifier('Agent.primary-1:review');

		self::assertSame('Agent.primary-1:review', $identifier->value());
	}

	#[DataProvider('invalidIdentifierProvider')]
	public function testInvalidIdentifierThrowsTypedException(string $value): void
	{
		$this->expectException(InvalidIdentifierException::class);

		new Identifier($value);
	}

	/**
	 * Return malformed identifier fixtures.
	 *
	 * @return iterable<string, array{string}>
	 */
	public static function invalidIdentifierProvider(): iterable
	{
		yield 'empty' => [''];
		yield 'leading number' => ['1agent'];
		yield 'leading whitespace' => [' agent'];
		yield 'trailing whitespace' => ['agent '];
		yield 'unsupported character' => ['agent/value'];
		yield 'oversized' => [str_repeat('a', self::OVERSIZED_IDENTIFIER_LENGTH)];
	}
}
