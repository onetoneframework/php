<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Log;

use Clover\Component\Log\Logger;
use PHPUnit\Framework\TestCase;
use Stringable;

final class LoggerTest extends TestCase
{
	private string $logDirectory = '';
	private string $logFile = '';

	protected function setUp(): void
	{
		$this->logDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'component-logger-' . uniqid('', true);
		$this->logFile = $this->logDirectory . DIRECTORY_SEPARATOR . 'application.log';
	}

	protected function tearDown(): void
	{
		if ($this->logDirectory === '') {
			return;
		}

		$files = glob($this->logDirectory . DIRECTORY_SEPARATOR . '*');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}

		rmdir($this->logDirectory);
		$this->logDirectory = '';
		$this->logFile = '';
	}

	public function testLoggerCreatesDirectoryAndInterpolatesContext(): void
	{
		$logger = new Logger($this->logFile);

		$logger->info('User {id} logged in', ['id' => 7]);

		$this->assertFileExists($this->logFile);
		$contents = (string) file_get_contents($this->logFile);
		$this->assertStringContainsString('INFO: User 7 logged in', $contents);
	}

	public function testLoggerSerializesArrayContextValues(): void
	{
		$logger = new Logger($this->logFile);

		$logger->warning('Payload {payload}', ['payload' => ['ok' => true]]);

		$contents = (string) file_get_contents($this->logFile);
		$this->assertStringContainsString('WARNING: Payload {"ok":true}', $contents);
	}

	public function testLoggerInterpolatesStringableMessagesAndContextValues(): void
	{
		$logger = new Logger($this->logFile);

		$logger->debug(
			new LoggerStringable('State {state}'),
			['state' => new LoggerStringable('ready')]
		);

		$contents = (string) file_get_contents($this->logFile);
		$this->assertStringContainsString('DEBUG: State ready', $contents);
	}

	public function testLoggerUsesStableFallbackForUnserializableContext(): void
	{
		$logger = new Logger($this->logFile);
		$recursive = [];
		$recursive['self'] = &$recursive;

		$logger->error('Payload {payload}', ['payload' => $recursive]);

		$contents = (string) file_get_contents($this->logFile);
		$this->assertStringContainsString('ERROR: Payload [unserializable:array]', $contents);
	}
}

final class LoggerStringable implements Stringable
{
	public function __construct(private string $value)
	{
	}

	public function __toString(): string
	{
		return $this->value;
	}
}
