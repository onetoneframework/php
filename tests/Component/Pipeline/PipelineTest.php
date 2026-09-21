<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Component\Pipeline;

use Clover\Component\Contract\MiddlewareInterface;
use Clover\Component\Contract\RequestHandlerInterface;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Component\Pipeline\Pipeline;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
{
	public function testEmptyPipelineRunsTheDestination(): void
	{
		$pipeline = new Pipeline([], new PipelineDestination('destination'));

		$this->assertSame('destination', $pipeline->handle($this->request())->getBody());
	}

	/**
	 * Layers wrap: the first listed is entered first and left last, so it sees what every layer
	 * after it produced.
	 */
	public function testLayersAreEnteredInOrderAndLeftInReverse(): void
	{
		$log = new PipelineLog();
		$pipeline = new Pipeline(
			[new PipelineRecorder('outer', $log), new PipelineRecorder('inner', $log)],
			new PipelineDestination('end')
		);

		$response = $pipeline->handle($this->request());

		$this->assertSame(['outer:in', 'inner:in', 'inner:out', 'outer:out'], $log->entries);
		$this->assertSame('end', $response->getBody());
	}

	/**
	 * A layer that never calls the handler stops everything after it. An authentication or
	 * maintenance layer is useless otherwise.
	 */
	public function testALayerThatDoesNotDelegateStopsTheRest(): void
	{
		$log = new PipelineLog();
		$pipeline = new Pipeline(
			[new PipelineShortCircuit(), new PipelineRecorder('never', $log)],
			new PipelineDestination('end')
		);

		$response = $pipeline->handle($this->request());

		$this->assertSame(403, $response->getStatusCode());
		$this->assertSame([], $log->entries);
	}

	public function testALayerCanReplaceTheResponseOnTheWayBack(): void
	{
		$pipeline = new Pipeline([new PipelineHeaderStamp()], new PipelineDestination('end'));

		$this->assertSame('yes', $pipeline->handle($this->request())->getHeader('x-stamped'));
	}

	public function testPipelineCanBeReusedWithoutSkippingMiddleware(): void
	{
		$log = new PipelineLog();
		$pipeline = new Pipeline(
			[new PipelineRecorder('outer', $log), new PipelineRecorder('inner', $log)],
			new PipelineDestination('end')
		);

		$first = $pipeline->handle($this->request());
		$second = $pipeline->handle($this->request());

		$this->assertSame('end', $first->getBody());
		$this->assertSame('end', $second->getBody());
		$this->assertSame([
			'outer:in',
			'inner:in',
			'inner:out',
			'outer:out',
			'outer:in',
			'inner:in',
			'inner:out',
			'outer:out',
		], $log->entries);
	}

	public function testNonContiguousMiddlewareKeysPreserveDeclaredOrder(): void
	{
		$log = new PipelineLog();
		$pipeline = new Pipeline(
			[3 => new PipelineRecorder('first', $log), 9 => new PipelineRecorder('second', $log)],
			new PipelineDestination('end')
		);

		$pipeline->handle($this->request());

		$this->assertSame(['first:in', 'second:in', 'second:out', 'first:out'], $log->entries);
	}

	private function request(): Request
	{
		return new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/']);
	}
}

final class PipelineLog
{
	/** @var array<int, string> */
	public array $entries = [];
}

final class PipelineDestination implements RequestHandlerInterface
{
	public function __construct(private string $body)
	{
	}

	public function handle(Request $request): Response
	{
		return Response::text($this->body);
	}
}

final class PipelineRecorder implements MiddlewareInterface
{
	public function __construct(private string $label, private PipelineLog $log)
	{
	}

	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		$this->log->entries[] = $this->label . ':in';
		$response = $handler->handle($request);
		$this->log->entries[] = $this->label . ':out';

		return $response;
	}
}

final class PipelineShortCircuit implements MiddlewareInterface
{
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		return Response::text('Forbidden', 403);
	}
}

final class PipelineHeaderStamp implements MiddlewareInterface
{
	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		return $handler->handle($request)->setHeader('X-Stamped', 'yes');
	}
}
