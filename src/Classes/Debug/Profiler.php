<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

declare(strict_types=1);

namespace Clover\Classes\Debug;

use Throwable;
use function random_bytes;
use function filter_var;
use const FILTER_VALIDATE_BOOL;

final class Profiler
{
	private const MAX_EXCEPTION_FLOW_STEPS = 4096;

	private static ?ProfilerTimeline $timeline = null;

	private static ?ProfilerStorage $storage = null;

	private static ?ProfilerTraceFlowNormalizer $traceFlowNormalizer = null;

	public static function isEnabled(): bool
	{
		return filter_var($_ENV['PROFILER_ENABLED'] ?? false, FILTER_VALIDATE_BOOL) === true;
	}

	/**
	 * @param array $traces Parsed traces from ErrorHandler
	 */
	public static function recordException(Throwable $e, array $traces): ?string
	{
		$flow = self::traceFlowNormalizer()->normalize($traces, self::MAX_EXCEPTION_FLOW_STEPS);

		return self::storage()->storeExceptionProfile($e, $flow);
	}

	/**
	 * @param array<int, array{step:int,call:string,location:string,durationMs:float}> $flow
	 */
	public static function recordRequestProfile(float $durationMs, array $flow): ?string
	{
		return self::storage()->storeRequestProfile($durationMs, $flow);
	}

	public static function resetTimeline(): void
	{
		self::timeline()->reset();
	}

	public static function beginSpan(string $call, string $location = ''): string
	{
		$token = bin2hex(random_bytes(6));
		self::startSpanToken($token, $call, $location);

		return $token;
	}

	public static function endSpan(string $token): void
	{
		self::finishSpanToken($token);
	}

	/**
	 * @return array<int, array{step:int,call:string,location:string,durationMs:float,memoryUsageBytes:int,memoryPeakBytes:int,memoryDeltaBytes:int}>
	 */
	public static function getTimelineFlow(): array
	{
		return self::timeline()->getTimelineFlow();
	}

	public static function startSpanToken(string $token, string $call, string $location = ''): void
	{
		self::timeline()->startSpanToken($token, $call, $location);
	}

	public static function finishSpanToken(string $token): void
	{
		self::timeline()->finishSpanToken($token);
	}

	public static function loadProfile(string $id): ?array
	{
		return self::storage()->loadProfile($id);
	}

	public static function latestProfileId(): ?string
	{
		return self::storage()->latestProfileId();
	}

	private static function timeline(): ProfilerTimeline
	{
		if (!(self::$timeline instanceof ProfilerTimeline)) {
			self::$timeline = new ProfilerTimeline();
		}

		return self::$timeline;
	}

	private static function storage(): ProfilerStorage
	{
		if (!(self::$storage instanceof ProfilerStorage)) {
			self::$storage = new ProfilerStorage();
		}

		return self::$storage;
	}

	private static function traceFlowNormalizer(): ProfilerTraceFlowNormalizer
	{
		if (!(self::$traceFlowNormalizer instanceof ProfilerTraceFlowNormalizer)) {
			self::$traceFlowNormalizer = new ProfilerTraceFlowNormalizer();
		}

		return self::$traceFlowNormalizer;
	}
}

