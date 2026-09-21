<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\HTTP;

use Clover\Classes\HTTP\Cookie;
use PHPUnit\Framework\TestCase;

final class CookieTest extends TestCase
{
	/** @var array<string, mixed> */
	private array $previousCookies = [];

	protected function setUp(): void
	{
		$this->previousCookies = $_COOKIE;
		$_COOKIE = [];
	}

	protected function tearDown(): void
	{
		$_COOKIE = $this->previousCookies;
	}

	public function testHasAndGetDistinguishStoredValuesFromMissingCookies(): void
	{
		$_COOKIE = [
			'session' => 'session-value',
			'zero' => '0',
			'empty' => '',
			'nullable' => null,
		];

		self::assertTrue(Cookie::has('session'));
		self::assertTrue(Cookie::has('zero'));
		self::assertTrue(Cookie::has('empty'));
		self::assertFalse(Cookie::has('nullable'));
		self::assertFalse(Cookie::has('missing'));
		self::assertSame('session-value', Cookie::get('session'));
		self::assertSame('fallback', Cookie::get('missing', 'fallback'));
		self::assertSame('fallback', Cookie::getRaw('nullable', 'fallback'));
	}

	public function testAllReturnsCurrentCookieMap(): void
	{
		$_COOKIE = ['theme' => 'dark', 'locale' => 'en-US'];

		self::assertSame($_COOKIE, Cookie::all());
	}

	public function testUnsetRemovesExistingCookieOnly(): void
	{
		$_COOKIE = ['theme' => 'dark'];

		self::assertTrue(Cookie::unset('theme'));
		self::assertFalse(Cookie::unset('theme'));
		self::assertArrayNotHasKey('theme', $_COOKIE);
	}

	public function testClearRemovesEveryCookie(): void
	{
		$_COOKIE = ['theme' => 'dark', 'locale' => 'en-US'];

		Cookie::clear();

		self::assertSame([], Cookie::all());
	}
}
