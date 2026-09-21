<?php

declare(strict_types=1);

namespace Clover\Classes\Logging;

use Clover\Enumeration\LoggingLevel;
use Clover\Framework\Component\TraceContext;
use function date;
use function filter_var;
use function is_string;
use function json_encode;
use const DATE_ATOM;
use const FILTER_VALIDATE_BOOL;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

final class StructuredKernelLogger
{
	/** @var Logger|null $logger */
	private static ?Logger $logger = null;

	/**
	 * Log an informational event with structured data.
	 * 
	 * @param array<string, mixed> $payload
	 */
	public static function info(string $event, array $payload = []): void
	{
		self::write($event, $payload, LoggingLevel::INFORMATION);
	}

	/**
	 * Log an error event with structured data.
	 * 
	 * @param array<string, mixed> $payload
	 */
	public static function error(string $event, array $payload = []): void
	{
		self::write($event, $payload, LoggingLevel::ERROR);
	}

	/**
	 * Internal method to write a structured log entry.
	 * 
	 * @param array<string, mixed> $payload
	 */
	private static function write(string $event, array $payload, string $level): void
	{
		if (!self::isEnabled()) {
			return;
		}

		$line = json_encode([
			'at' => date(DATE_ATOM),
			'event' => $event,
			'level' => $level,
			'trace' => TraceContext::asLogContext(),
			'payload' => $payload,
		], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		if (!is_string($line)) {
			return;
		}

		self::logger()->write($line, $level, 'kernel');
	}

	/**
	 * Get the logger instance, initializing it if necessary.
	 * 
	 * @return Logger
	 */
	private static function logger(): Logger
	{
		if (self::$logger === null) {
			self::$logger = new Logger(
				fileLocation: BASE_PATH . '/App/Cache/events/structured-kernel.log',
				logFormat: '{message}'
			);
		}

		return self::$logger;
	}

	/**
	 * Check if structured logging is enabled via environment variable.
	 * 
	 * @return bool
	 */
	private static function isEnabled(): bool
	{
		return filter_var($_ENV['STRUCTURED_LOG_ENABLED'] ?? true, FILTER_VALIDATE_BOOL) === true;
	}
}
