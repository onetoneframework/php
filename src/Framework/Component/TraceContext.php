<?php

declare(strict_types=1);

namespace Clover\Framework\Component;

use function bin2hex;
use function error_log;
use function hash;
use function is_string;
use function preg_match;
use function random_bytes;
use function strtolower;
use function substr;
use function trim;
use function uniqid;

final class TraceContext
{
	private const RESPONSE_TRACE_HEADER = 'X-Trace-Id';
	private const TRACE_ID_PATTERN = '/\A[A-Za-z0-9][A-Za-z0-9._:-]{0,127}\z/D';
	private const TRACE_PARENT_PATTERN = '/\A(?!ff)([0-9a-f]{2})-((?!0{32})[0-9a-f]{32})-((?!0{16})[0-9a-f]{16})-([0-9a-f]{2})\z/D';
	private static ?string $traceId = null;

	/**
	 * @param array<string, mixed> $server
	 */
	public static function bootstrap(array $server = []): string
	{
		$traceId = self::extractIncomingTraceId($server);
		if ($traceId === null) {
			$traceId = self::generateTraceId();
		}

		self::$traceId = $traceId;
		return $traceId;
	}

	public static function getTraceId(): string
	{
		if (self::$traceId === null) {
			self::$traceId = self::generateTraceId();
		}

		return self::$traceId;
	}

	/**
	 * @param array<string, mixed> $payload
	 * @return array<string, mixed>
	 */
	public static function appendTrace(array $payload = []): array
	{
		$payload['trace_id'] = self::getTraceId();
		return $payload;
	}

	/**
	 * @return array<string, string>
	 */
	public static function asLogContext(): array
	{
		return [
			'trace_id' => self::getTraceId(),
		];
	}

	public static function getResponseHeaderName(): string
	{
		return self::RESPONSE_TRACE_HEADER;
	}

	/**
	 * @param array<string, mixed> $server
	 */
	private static function extractIncomingTraceId(array $server): ?string
	{
		$traceId = $server['HTTP_X_TRACE_ID'] ?? null;
		if (is_string($traceId)) {
			$traceId = trim($traceId);
			if (preg_match(self::TRACE_ID_PATTERN, $traceId) === 1) {
				return $traceId;
			}
		}

		$traceParent = $server['HTTP_TRACEPARENT'] ?? null;
		if (!is_string($traceParent) || trim($traceParent) === '') {
			return null;
		}

		$matches = [];
		if (preg_match(self::TRACE_PARENT_PATTERN, strtolower(trim($traceParent)), $matches) !== 1) {
			return null;
		}

		return $matches[2];
	}

	private static function generateTraceId(): string
	{
		try {
			return bin2hex(random_bytes(16));
		} catch (\Throwable $exception) {
			error_log('Unable to generate a cryptographically secure trace identifier: ' . $exception::class);

			return substr(hash('sha256', uniqid('trace_', true)), 0, 32);
		}
	}
}
