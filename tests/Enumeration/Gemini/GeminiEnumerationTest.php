<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Gemini;

use Clover\Enumeration\Gemini\FinishReason;
use Clover\Enumeration\Gemini\Role;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class GeminiEnumerationTest extends TestCase
{
	#[DataProvider('roleProvider')]
	public function testRoleConversion(string $input, string $expected): void
	{
		$this->assertSame($expected, Role::from($input));
	}

	#[DataProvider('finishReasonProvider')]
	public function testFinishReasonConversion(string $input, string $expected): void
	{
		$this->assertSame($expected, FinishReason::from($input));
	}

	public function testUnknownRoleIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown finish reason code: assistant');

		Role::from('assistant');
	}

	public function testUnknownFinishReasonIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown finish reason code: LENGTH');

		FinishReason::from('LENGTH');
	}

	public static function roleProvider(): array
	{
		return [
			'model' => ['model', Role::MODEL],
			'user' => ['user', Role::USER],
		];
	}

	public static function finishReasonProvider(): array
	{
		return [
			'STOP' => ['STOP', FinishReason::STOP],
			'MAX_TOKENS' => ['MAX_TOKENS', FinishReason::MAX_TOKENS],
			'SAFETY' => ['SAFETY', FinishReason::SAFETY],
			'RECITATION' => ['RECITATION', FinishReason::RECITATION],
			'OTHER' => ['OTHER', FinishReason::OTHER],
		];
	}
}
