<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Routing\Route;
use ErrorException;
use PHPUnit\Framework\TestCase;

/**
 * Drives complete route patterns through Route::match().
 *
 * The sibling RouteArgumentValidationTest calls isValidArgument() directly, so nothing before this
 * file exercised the parsing of `{name}`, `{name}?`, `{name}=default` and `{name}:(type)` on the
 * live match path. Every assertion here runs under an error handler that turns an E_WARNING into a
 * failure, because the defect these tests guard against announced itself only as a diagnostic.
 */
final class RoutePatternMatchingTest extends TestCase
{
	public function testPlainParameterSegmentMatchesWithoutDiagnostics(): void
	{
		$route = new Route('/user/{id}', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['user', '7']));
		self::assertSame(['7'], $this->argumentsOf($route));
	}

	public function testOptionalParameterSegmentMatchesShortUrlWithoutDiagnostics(): void
	{
		$route = new Route('/user/{id}?', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['user']));
		self::assertSame([''], $this->argumentsOf($route));
	}

	public function testDefaultedSegmentMatchesWithoutDiagnostics(): void
	{
		$route = new Route('/user/{id}?=5', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['user']));
	}

	public function testTypedSegmentMatchesWithoutDiagnostics(): void
	{
		$route = new Route('/posts/{id}:(\d+)', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['posts', '12']));
	}

	public function testDeclaredDefaultIsSuppliedWhenOptionalSegmentIsOmitted(): void
	{
		$route = new Route('/user/{id}?=5', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['user']));
		self::assertSame(['5'], $this->argumentsOf($route));
	}

	public function testTypeConstraintRejectsSegmentThatDoesNotSatisfyIt(): void
	{
		$route = new Route('/posts/{id}?:(\d+)', static fn (): string => 'matched');

		self::assertFalse($this->matchStrictly($route, ['posts', 'abc']));
	}

	public function testTypeConstraintAcceptsSegmentThatSatisfiesIt(): void
	{
		$route = new Route('/posts/{id}?:(\d+)', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['posts', '12']));
		self::assertSame(['12'], $this->argumentsOf($route));
	}

	public function testOptionalTypedSegmentMatchesUrlThatOmitsIt(): void
	{
		$route = new Route('/posts/{id}?:(\d+)', static fn (): string => 'matched');

		self::assertTrue($this->matchStrictly($route, ['posts']));
		self::assertSame([''], $this->argumentsOf($route));
	}

	public function testRequiredParameterSegmentRejectsUrlThatOmitsIt(): void
	{
		$route = new Route('/user/{id}', static fn (): string => 'matched');

		self::assertFalse($this->matchStrictly($route, ['user']));
	}

	public function testLiteralSegmentMismatchIsRejected(): void
	{
		$route = new Route('/user/profile', static fn (): string => 'matched');

		self::assertFalse($this->matchStrictly($route, ['user', 'settings']));
	}

	public function testSurplusUrlSegmentIsRejected(): void
	{
		$route = new Route('/posts/{id}:(\d+)', static fn (): string => 'matched');

		self::assertFalse($this->matchStrictly($route, ['posts', '12', 'comments']));
	}

	/**
	 * Run Route::match() with PHP diagnostics promoted to exceptions.
	 *
	 * Reading a capture group that did not participate raises `Undefined array key`, an E_WARNING
	 * that PHPUnit reports without failing the test under this project's configuration. Promoting
	 * it here is what makes these tests able to catch the defect rather than merely survive it.
	 *
	 * @param Route $route The route under test.
	 * @param string[] $urlSegments The URL segments to match against it.
	 *
	 * @return bool The result reported by Route::match().
	 */
	private function matchStrictly(Route $route, array $urlSegments): bool
	{
		set_error_handler(
			static function (int $severity, string $message, string $file, int $line): bool {
				throw new ErrorException($message, 0, $severity, $file, $line);
			},
			E_ALL
		);

		try {
			return $route->match(new ArrayObject($urlSegments));
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * Normalise the arguments a matched route captured.
	 *
	 * Matched URL segments arrive as StringObject instances while substituted defaults arrive as
	 * plain strings, so the comparison is made on their string values.
	 *
	 * @param Route $route The route whose captured arguments should be read.
	 *
	 * @return string[] The captured arguments as strings, in order.
	 */
	private function argumentsOf(Route $route): array
	{
		return array_map(
			static fn (mixed $argument): string => (string) $argument,
			$route->getArguments()
		);
	}
}
