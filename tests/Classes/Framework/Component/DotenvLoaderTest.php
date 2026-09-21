<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\DotenvLoader;
use PHPUnit\Framework\TestCase;

final class DotenvLoaderTest extends TestCase
{
	/**
	 * @var array<int, string> Directories created by the running test.
	 */
	private array $directories = [];

	/**
	 * @var array<int, string> Environment keys the running test introduced.
	 */
	private array $keys = [];

	protected function setUp(): void
	{
		DotenvLoader::reset();
	}

	protected function tearDown(): void
	{
		DotenvLoader::reset();

		foreach ($this->keys as $key) {
			unset($_ENV[$key], $_SERVER[$key]);
		}

		foreach ($this->directories as $directory) {
			$environmentFile = $directory . DIRECTORY_SEPARATOR . '.env';

			if (file_exists($environmentFile)) {
				unlink($environmentFile);
			}

			if (is_dir($directory)) {
				rmdir($directory);
			}
		}

		$this->directories = [];
		$this->keys = [];
	}

	public function testLoadReadsTheEnvironmentFile(): void
	{
		$key = $this->uniqueKey();
		$directory = $this->makeEnvironmentDirectory($key . '=loaded');

		$this->assertTrue(DotenvLoader::load($directory));
		$this->assertSame('loaded', $_ENV[$key] ?? null);
	}

	public function testMissingEnvironmentFileIsReportedRatherThanThrown(): void
	{
		$directory = $this->makeEnvironmentDirectory(null);

		$this->assertFalse(DotenvLoader::load($directory));
	}

	/**
	 * `root/index.php` loads the environment so it can choose a kernel, and `Runtime::run()` then
	 * loads it again. The second call must not re-read the filesystem, or a path that only the
	 * second call knows about could quietly override what the entry point already decided on.
	 */
	public function testSecondLoadIsANoOpAndKeepsTheFirstResult(): void
	{
		$firstKey = $this->uniqueKey();
		$secondKey = $this->uniqueKey();
		$first = $this->makeEnvironmentDirectory($firstKey . '=first');
		$second = $this->makeEnvironmentDirectory($secondKey . '=second');

		$this->assertTrue(DotenvLoader::load($first));
		$this->assertTrue(DotenvLoader::load($second));

		$this->assertSame('first', $_ENV[$firstKey] ?? null);
		$this->assertArrayNotHasKey($secondKey, $_ENV);
	}

	public function testResetAllowsTheFilesToBeReadAgain(): void
	{
		$firstKey = $this->uniqueKey();
		$secondKey = $this->uniqueKey();
		$first = $this->makeEnvironmentDirectory($firstKey . '=first');
		$second = $this->makeEnvironmentDirectory($secondKey . '=second');

		$this->assertTrue(DotenvLoader::load($first));
		DotenvLoader::reset();
		$this->assertTrue(DotenvLoader::load($second));

		$this->assertSame('second', $_ENV[$secondKey] ?? null);
	}

	/**
	 * A failed attempt must not be remembered as a success.
	 */
	public function testFailureIsRepeatedRatherThanCachedAsSuccess(): void
	{
		$directory = $this->makeEnvironmentDirectory(null);

		$this->assertFalse(DotenvLoader::load($directory));
		$this->assertFalse(DotenvLoader::load($directory));
	}

	/**
	 * Build a directory holding an optional `.env` file.
	 *
	 * @param string|null $contents Body of the `.env` file, or null to leave the directory empty.
	 *
	 * @return string
	 */
	private function makeEnvironmentDirectory(?string $contents): string
	{
		$directory = sys_get_temp_dir()
			. DIRECTORY_SEPARATOR
			. uniqid('clover_dotenv_', true);

		mkdir($directory);
		$this->directories[] = $directory;

		if ($contents !== null) {
			file_put_contents($directory . DIRECTORY_SEPARATOR . '.env', $contents . "\n");
		}

		return $directory;
	}

	/**
	 * An environment key no other test or `.env` file uses.
	 *
	 * @return string
	 */
	private function uniqueKey(): string
	{
		$key = 'CLOVER_DOTENV_TEST_' . strtoupper(bin2hex(random_bytes(6)));
		$this->keys[] = $key;

		return $key;
	}
}
