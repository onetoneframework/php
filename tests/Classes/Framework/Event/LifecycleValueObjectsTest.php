<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Event;

use Clover\Framework\Component\Response;
use Clover\Framework\Event\BeforeResponseSend;
use Clover\Framework\Event\DatabaseLifecycleEvent;
use Clover\Framework\Event\HttpClientLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\RoutingLifecycleEvent;
use PHPUnit\Framework\TestCase;

final class LifecycleValueObjectsTest extends TestCase
{
	public function testRoutingLifecycleEventExposesStableTypesAndPayload(): void
	{
		$event = new RoutingLifecycleEvent(RoutingLifecycleEvent::CACHE_MISS, ['reason' => 'stale']);

		$this->assertSame('routing.middleware.started', RoutingLifecycleEvent::MIDDLEWARE_STARTED);
		$this->assertSame('routing.middleware.finished', RoutingLifecycleEvent::MIDDLEWARE_FINISHED);
		$this->assertSame('routing.cache.hit', RoutingLifecycleEvent::CACHE_HIT);
		$this->assertSame('routing.cache.miss', RoutingLifecycleEvent::CACHE_MISS);
		$this->assertSame('routing.cache.write_started', RoutingLifecycleEvent::CACHE_WRITE_STARTED);
		$this->assertSame('routing.cache.write_finished', RoutingLifecycleEvent::CACHE_WRITE_FINISHED);
		$this->assertSame('routing.fallback.triggered', RoutingLifecycleEvent::FALLBACK_TRIGGERED);
		$this->assertSame('routing.response.resolved', RoutingLifecycleEvent::RESPONSE_RESOLVED);
		$this->assertSame(RoutingLifecycleEvent::CACHE_MISS, $event->type);
		$this->assertSame(['reason' => 'stale'], $event->payload);
	}

	public function testHttpClientLifecycleEventExposesStableTypesAndPayload(): void
	{
		$event = new HttpClientLifecycleEvent(HttpClientLifecycleEvent::REQUEST_FAILED, ['host' => 'example.test']);

		$this->assertSame('http_client.request.started', HttpClientLifecycleEvent::REQUEST_STARTED);
		$this->assertSame('http_client.request.finished', HttpClientLifecycleEvent::REQUEST_FINISHED);
		$this->assertSame('http_client.request.failed', HttpClientLifecycleEvent::REQUEST_FAILED);
		$this->assertSame(HttpClientLifecycleEvent::REQUEST_FAILED, $event->type);
		$this->assertSame(['host' => 'example.test'], $event->payload);
	}

	public function testDatabaseLifecycleEventExposesStableTypesAndPayload(): void
	{
		$event = new DatabaseLifecycleEvent(DatabaseLifecycleEvent::QUERY_FINISHED, ['duration' => 12]);

		$this->assertSame('database.query.started', DatabaseLifecycleEvent::QUERY_STARTED);
		$this->assertSame('database.query.finished', DatabaseLifecycleEvent::QUERY_FINISHED);
		$this->assertSame('database.query.failed', DatabaseLifecycleEvent::QUERY_FAILED);
		$this->assertSame(DatabaseLifecycleEvent::QUERY_FINISHED, $event->type);
		$this->assertSame(['duration' => 12], $event->payload);
	}

	public function testKernelSpanStartedRetainsSpanMetadata(): void
	{
		$event = new KernelSpanStarted('pf_123', 'Kernel::RoutingScan', 'RouteRegistry::load');

		$this->assertSame('pf_123', $event->token);
		$this->assertSame('Kernel::RoutingScan', $event->call);
		$this->assertSame('RouteRegistry::load', $event->location);
	}

	public function testKernelSpanStartedDefaultsLocationToEmptyString(): void
	{
		$event = new KernelSpanStarted('pf_123', 'Kernel::RoutingScan');

		$this->assertSame('', $event->location);
	}

	public function testKernelSpanFinishedRetainsToken(): void
	{
		$event = new KernelSpanFinished('pf_123');

		$this->assertSame('pf_123', $event->token);
	}

	public function testBeforeResponseSendRetainsExactResponseInstance(): void
	{
		$response = new Response('body');

		$event = new BeforeResponseSend($response);

		$this->assertSame($response, $event->response);
	}
}
