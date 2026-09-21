<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Framework\Enumeration;

use Clover\Framework\Enumeration\EntryPoint;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class EntryPointTest extends TestCase
{
	/**
	 * @var array<string, string|false> The environment entries this test replaces.
	 */
	private array $originalEnvironment = [];

	protected function setUp(): void
	{
		$this->originalEnvironment = [
			'env' => $_ENV[EntryPoint::ENVIRONMENT_KEY] ?? false,
			'putenv' => getenv(EntryPoint::ENVIRONMENT_KEY),
		];

		unset($_ENV[EntryPoint::ENVIRONMENT_KEY]);
		putenv(EntryPoint::ENVIRONMENT_KEY);
	}

	protected function tearDown(): void
	{
		unset($_ENV[EntryPoint::ENVIRONMENT_KEY]);
		putenv(EntryPoint::ENVIRONMENT_KEY);

		if (is_string($this->originalEnvironment['env'])) {
			$_ENV[EntryPoint::ENVIRONMENT_KEY] = $this->originalEnvironment['env'];
		}

		if (is_string($this->originalEnvironment['putenv'])) {
			putenv(EntryPoint::ENVIRONMENT_KEY . '=' . $this->originalEnvironment['putenv']);
		}
	}

	public function testRuntimeIsTheDefault(): void
	{
		$this->assertSame(EntryPoint::RUNTIME, EntryPoint::default());
	}

	public function testBothEntryPointsAreSelectable(): void
	{
		$this->assertSame(EntryPoint::RUNTIME, EntryPoint::fromString('runtime'));
		$this->assertSame(EntryPoint::APPLICATION, EntryPoint::fromString('application'));
	}

	public function testNameIsCaseInsensitiveAndTrimmed(): void
	{
		$this->assertSame(EntryPoint::APPLICATION, EntryPoint::fromString('  APPLICATION  '));
	}

	public function testUnknownNameIsRefusedAndNamesTheAllowedValues(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown APP_ENTRY_POINT value `runtim`; expected one of: runtime, application.');

		EntryPoint::fromString('runtim');
	}

	public function testUnsetEnvironmentSelectsTheDefault(): void
	{
		$this->assertSame(EntryPoint::RUNTIME, EntryPoint::fromEnvironment());
	}

	public function testEmptyEnvironmentValueSelectsTheDefault(): void
	{
		$_ENV[EntryPoint::ENVIRONMENT_KEY] = '   ';

		$this->assertSame(EntryPoint::RUNTIME, EntryPoint::fromEnvironment());
	}

	public function testEnvironmentValueSelectsTheApplicationStack(): void
	{
		$_ENV[EntryPoint::ENVIRONMENT_KEY] = 'application';

		$this->assertSame(EntryPoint::APPLICATION, EntryPoint::fromEnvironment());
	}

	/**
	 * Dotenv populates `$_ENV` rather than `putenv()`, so a real OS-level variable is the only
	 * way to select the entry point where no `.env` is present - a container, typically.
	 */
	public function testProcessEnvironmentVariableIsHonouredWhenDotenvDidNotSetOne(): void
	{
		putenv(EntryPoint::ENVIRONMENT_KEY . '=application');

		$this->assertSame(EntryPoint::APPLICATION, EntryPoint::fromEnvironment());
	}

	public function testUnknownEnvironmentValueIsRefusedRatherThanIgnored(): void
	{
		$_ENV[EntryPoint::ENVIRONMENT_KEY] = 'legacy';

		$this->expectException(InvalidArgumentException::class);

		EntryPoint::fromEnvironment();
	}
}
