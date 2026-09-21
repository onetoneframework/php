<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Logging;

use Clover\Classes\Logging\Logger;
use Clover\Enumeration\LoggingLevel;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LoggerTest extends TestCase
{
	private string $logDirectory = '';

	protected function setUp(): void
	{
		$this->logDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'clover-logging-' . uniqid('', true);
		mkdir($this->logDirectory, 0777, true);
	}

	protected function tearDown(): void
	{
		$files = glob($this->logDirectory . DIRECTORY_SEPARATOR . '*');
		if (is_array($files)) {
			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				}
			}
		}

		if (is_dir($this->logDirectory)) {
			rmdir($this->logDirectory);
		}
	}

	public function testWriteInterpolatesContextAndUsesConfiguredFormat(): void
	{
		$path = $this->logDirectory . DIRECTORY_SEPARATOR . 'app.log';
		$logger = new Logger(
			new DateTimeZone('UTC'),
			$path,
			LoggingLevel::DEBUG,
			logFormat: '{level}|{namespace}|{message}|{context}'
		);

		$this->assertTrue($logger->write('User {id}', LoggingLevel::ERROR, 'auth', ['id' => 7]));
		$this->assertSame("error|auth|User 7|{\"id\":7}\n", file_get_contents($path));
	}

	public function testMinimumLevelFiltersLowerSeverityEntries(): void
	{
		$path = $this->logDirectory . DIRECTORY_SEPARATOR . 'levels.log';
		$logger = new Logger(
			new DateTimeZone('UTC'),
			$path,
			LoggingLevel::WARNING,
			logFormat: '{level}|{message}'
		);

		$this->assertFalse($logger->write('ignored', LoggingLevel::INFORMATION));
		$this->assertFileDoesNotExist($path);
		$this->assertTrue($logger->write('kept', LoggingLevel::ERROR));
		$this->assertSame("error|kept\n", file_get_contents($path));
	}

	public function testGlobalContextCanBeUsedForInterpolationAndOverriddenLocally(): void
	{
		$path = $this->logDirectory . DIRECTORY_SEPARATOR . 'context.log';
		$logger = new Logger(
			new DateTimeZone('UTC'),
			$path,
			logFormat: '{message}|{context}',
			globalContext: ['request' => 'global', 'app' => 'clover']
		);
		$logger->addGlobalContext('region', 'kr');

		$logger->write('Request {request} from {region}', context: ['request' => 'local']);

		$this->assertSame(
			"Request local from kr|{\"request\":\"local\",\"app\":\"clover\",\"region\":\"kr\"}\n",
			file_get_contents($path)
		);
	}

	public function testInvalidMinimumLevelIsRejected(): void
	{
		$logger = new Logger(fileLocation: $this->logDirectory . DIRECTORY_SEPARATOR . 'invalid.log');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Invalid logging level: invalid');

		$logger->setMinimumLevel('invalid');
	}
}
