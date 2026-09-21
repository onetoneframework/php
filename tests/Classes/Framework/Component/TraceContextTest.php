<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Component;

use Clover\Framework\Component\TraceContext;
use PHPUnit\Framework\TestCase;

final class TraceContextTest extends TestCase
{
	public function testBootstrapsFromXTraceIdHeader(): void
	{
		$traceId = TraceContext::bootstrap([
			'HTTP_X_TRACE_ID' => 'custom-trace-id',
		]);

		$this->assertSame('custom-trace-id', $traceId);
		$this->assertSame('custom-trace-id', TraceContext::getTraceId());
	}

	public function testAppendsTraceToPayload(): void
	{
		TraceContext::bootstrap([]);
		$payload = TraceContext::appendTrace(['event' => 'test']);

		$this->assertArrayHasKey('trace_id', $payload);
		$this->assertSame('test', $payload['event']);
		$this->assertNotSame('', $payload['trace_id']);
	}

	public function testRejectsUnsafeIncomingTraceIdentifier(): void
	{
		$traceId = TraceContext::bootstrap([
			'HTTP_X_TRACE_ID' => "unsafe\r\nInjected-Header: value",
		]);

		$this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $traceId);
		$this->assertStringNotContainsString('Injected-Header', $traceId);
	}

	public function testExtractsValidatedTraceParentIdentifier(): void
	{
		$traceId = TraceContext::bootstrap([
			'HTTP_TRACEPARENT' => '00-4bf92f3577b34da6a3ce929d0e0e4736-00f067aa0ba902b7-01',
		]);

		$this->assertSame('4bf92f3577b34da6a3ce929d0e0e4736', $traceId);
	}

	public function testRejectsAllZeroTraceParentIdentifier(): void
	{
		$traceId = TraceContext::bootstrap([
			'HTTP_TRACEPARENT' => '00-00000000000000000000000000000000-00f067aa0ba902b7-01',
		]);

		$this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $traceId);
		$this->assertNotSame('00000000000000000000000000000000', $traceId);
	}
}
