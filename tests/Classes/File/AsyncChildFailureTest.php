<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\File;

use Clover\Classes\Event\EventLoop;
use Clover\Classes\File\Handler as FileHandler;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * writeFileAsync/readFileAsync run their work in a spawned `php -r` child.
 * When that child fails, the framework used to report
 * `Child write failed for: <EventLoop.php>` — the file the closure was declared
 * in, never the file being written — and it closed the child's stderr straight
 * after spawning, so the child's own explanation was discarded. These tests pin
 * the replacement: the target path, the exit code, and the child's stderr.
 */
final class AsyncChildFailureTest extends TestCase
{
	private string $directory = '';

	protected function setUp(): void
	{
		parent::setUp();
		EventLoop::reset();

		$this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'async_child_failure_' . uniqid();
		mkdir($this->directory, 0777, true);
	}

	protected function tearDown(): void
	{
		foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
			if (is_file($file)) {
				unlink($file);
			}
		}

		if (is_dir($this->directory)) {
			rmdir($this->directory);
		}

		EventLoop::reset();
		parent::tearDown();
	}

	/**
	 * A path whose parent directory does not exist: the child runs, its
	 * file_put_contents() fails, and it reports that on stderr and stdout.
	 */
	private function unwritablePath(): string
	{
		return $this->directory . DIRECTORY_SEPARATOR . 'no_such_directory' . DIRECTORY_SEPARATOR . 'target.txt';
	}

	public function testAFailedWriteRejectsInsteadOfResolving(): void
	{
		$path = $this->unwritablePath();
		$resolved = null;
		$reason = null;

		FileHandler::writeFileAsync($path, 'payload')
			->then(function ($value) use (&$resolved): void {
				$resolved = $value;
			})
			->catch(function ($error) use (&$reason): void {
				$reason = $error;
			});

		EventLoop::run();

		$this->assertNull($resolved, 'A write that never happened must not resolve.');
		$this->assertInstanceOf(Throwable::class, $reason);
		$this->assertFileDoesNotExist($path);
	}

	public function testTheRejectionNamesTheFileTheWriteWasFor(): void
	{
		$path = $this->unwritablePath();
		$reason = null;

		FileHandler::writeFileAsync($path, 'payload')
			->catch(function ($error) use (&$reason): void {
				$reason = $error;
			});

		EventLoop::run();

		$this->assertInstanceOf(Throwable::class, $reason);
		$message = $reason->getMessage();

		$this->assertStringContainsString('target.txt', $message);
		$this->assertStringNotContainsString('EventLoop.php', $message);
	}

	public function testTheRejectionCarriesTheChildExitCode(): void
	{
		$reason = null;

		FileHandler::writeFileAsync($this->unwritablePath(), 'payload')
			->catch(function ($error) use (&$reason): void {
				$reason = $error;
			});

		EventLoop::run();

		$this->assertInstanceOf(Throwable::class, $reason);
		$this->assertStringContainsString('exit', strtolower($reason->getMessage()));
	}

	public function testTheChildStderrSurvivesAndReachesTheCaller(): void
	{
		$reason = null;

		FileHandler::writeFileAsync($this->unwritablePath(), 'payload')
			->catch(function ($error) use (&$reason): void {
				$reason = $error;
			});

		EventLoop::run();

		$this->assertInstanceOf(Throwable::class, $reason);
		$this->assertStringContainsString(
			'stderr:',
			$reason->getMessage(),
			"The child's stderr is the only place it explains itself; it must not be discarded."
		);
	}

	public function testASuccessfulWriteStillResolvesToTrue(): void
	{
		$path = $this->directory . DIRECTORY_SEPARATOR . 'ok.txt';
		$result = null;

		FileHandler::writeFileAsync($path, 'payload')
			->then(function ($value) use (&$result): void {
				$result = $value;
			});

		EventLoop::run();

		$this->assertTrue($result);
		$this->assertSame('payload', file_get_contents($path));
	}

	public function testAnEmptyWriteStillResolves(): void
	{
		$path = $this->directory . DIRECTORY_SEPARATOR . 'empty.txt';
		$result = null;

		FileHandler::writeFileAsync($path, '')
			->then(function ($value) use (&$result): void {
				$result = $value;
			});

		EventLoop::run();

		$this->assertTrue($result);
		$this->assertSame('', file_get_contents($path));
	}

	public function testAReadStillReturnsTheExactBytes(): void
	{
		$path = $this->directory . DIRECTORY_SEPARATOR . 'source.bin';
		$content = str_repeat("line\r\n\x00\xffend", 4096);
		file_put_contents($path, $content);

		$result = null;

		FileHandler::readFileAsync($path)
			->then(function ($value) use (&$result): void {
				$result = $value;
			});

		EventLoop::run();

		$this->assertSame($content, $result);
	}
}
