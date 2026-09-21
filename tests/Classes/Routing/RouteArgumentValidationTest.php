<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Routing\Route;
use Clover\Enumeration\Regex;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RouteArgumentValidationTest extends TestCase
{
	#[DataProvider('validBuiltInConstraintProvider')]
	public function testBuiltInConstraintAcceptsValidValue(string $type, string $value): void
	{
		$route = new Route('/test', static fn (): string => 'matched');

		$this->assertTrue($route->isValidArgument($type, $value));
	}

	#[DataProvider('invalidBuiltInConstraintProvider')]
	public function testBuiltInConstraintRejectsInvalidValue(string $type, string $value): void
	{
		$route = new Route('/test', static fn (): string => 'matched');

		$this->assertFalse($route->isValidArgument($type, $value));
	}

	public function testConstraintNamesAreCaseInsensitive(): void
	{
		$route = new Route('/test', static fn (): string => 'matched');

		$this->assertTrue($route->isValidArgument('NUMBER', '12345'));
	}

	public function testCustomConstraintPreservesRawExpressionMatching(): void
	{
		$route = new Route('/test', static fn (): string => 'matched');

		$this->assertTrue($route->isValidArgument('[A-F]{2}[0-9]{2}', 'prefixAB12suffix'));
	}

	public function testInvalidCustomConstraintIsRejected(): void
	{
		$route = new Route('/test', static fn (): string => 'matched');

		$this->assertFalse($route->isValidArgument('[A-', 'value'));
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function validBuiltInConstraintProvider(): array
	{
		return [
			'slug' => ['SLUG', 'release-2026'],
			'word slug' => ['USLUG', 'release_name'],
			'date' => ['DATE', '2026-07-11'],
			'alphabet' => [Regex::ALPHABET, 'Framework'],
			'alphanumeric' => [Regex::ALPHABET_NUMBER, 'Framework2026'],
			'base64 data URI' => [Regex::BASE64, 'data:text/plain;base64,SGVsbG8='],
			'email' => [Regex::EMAIL, 'user@example.com'],
			'hiragana' => [Regex::HIRAGANA, "\u{3042}"],
			'Japanese' => [Regex::JAPANESE, "\u{3042}"],
			'kanji' => [Regex::KANJI, "\u{6F22}"],
			'katakana' => [Regex::KATAKANA, "\u{30AB}"],
			'Korean' => [Regex::KOREAN, "\u{D55C}\u{AE00}"],
			'Korean and English' => [Regex::KOREAN_ENGLISH, "Framework\u{D55C}\u{AE00}"],
			'number' => [Regex::NUMBER, '123456'],
			'phone number' => [Regex::PHONE_NUMBER, '02-1234-5678'],
		];
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function invalidBuiltInConstraintProvider(): array
	{
		return [
			'slug' => ['SLUG', 'Invalid Slug'],
			'word slug' => ['USLUG', 'invalid/slug'],
			'date' => ['DATE', '2026-7-11'],
			'alphabet' => [Regex::ALPHABET, 'Framework2'],
			'alphanumeric' => [Regex::ALPHABET_NUMBER, 'Framework-2026'],
			'base64 data URI' => [Regex::BASE64, 'SGVsbG8='],
			'email' => [Regex::EMAIL, 'invalid-email'],
			'hiragana' => [Regex::HIRAGANA, "\u{30AB}"],
			'Japanese' => [Regex::JAPANESE, 'A'],
			'kanji' => [Regex::KANJI, "\u{3042}"],
			'katakana' => [Regex::KATAKANA, "\u{3042}"],
			'Korean' => [Regex::KOREAN, 'Framework'],
			'Korean and English' => [Regex::KOREAN_ENGLISH, "Framework-\u{D55C}\u{AE00}"],
			'number' => [Regex::NUMBER, '12a'],
			'phone number' => [Regex::PHONE_NUMBER, '02-123-45'],
		];
	}
}
