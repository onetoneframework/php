<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Middleware;

use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Middleware\StackRequestHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class StackRequestHandlerTest extends TestCase
{
	protected function tearDown(): void
	{
		unset($_ENV['PROFILER_ENABLED']);
	}

    public function testUsesFallbackWhenNoMiddlewares(): void
    {
        $fallback = new class implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response('fallback', [], 'text', 200);
            }
        };

        $handler = new StackRequestHandler([], $fallback);
        $response = $handler->handle(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('fallback', $response->getBody());
    }

    public function testExecutesMiddlewaresInOrderThenFallback(): void
    {
        $state = new \stdClass();
        $state->log = [];

        $m1 = new class($state) implements MiddlewareInterface {
            public function __construct(private \stdClass $state)
            {
            }

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $this->state->log[] = 'm1-before';
                $response = $handler->handle($request);
                $this->state->log[] = 'm1-after';

                return $response;
            }
        };

        $m2 = new class($state) implements MiddlewareInterface {
            public function __construct(private \stdClass $state)
            {
            }

            public function process(Request $request, RequestHandlerInterface $handler): Response
            {
                $this->state->log[] = 'm2-before';
                $response = $handler->handle($request);
                $this->state->log[] = 'm2-after';

                return $response;
            }
        };

        $fallback = new class($state) implements RequestHandlerInterface {
            public function __construct(private \stdClass $state)
            {
            }

            public function handle(Request $request): Response
            {
                $this->state->log[] = 'fallback';

                return new Response('ok', [], 'text', 200);
            }
        };

        $handler = new StackRequestHandler([$m1, $m2], $fallback);
        $response = $handler->handle(new Request());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('ok', $response->getBody());
        $this->assertSame(['m1-before', 'm2-before', 'fallback', 'm2-after', 'm1-after'], $state->log);
    }

	public function testDoesNotDispatchProfilerSpansWhenProfilerIsDisabled(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'false';
		$events = [];
		$eventDispatcher = new EventDispatcherAdapter(static function (object $event) use (&$events): void {
			$events[] = $event;
		});
		$fallback = new class implements RequestHandlerInterface {
			public function handle(Request $request): Response
			{
				return new Response('fallback', [], 'text', 200);
			}
		};

		$handler = new StackRequestHandler([], $fallback, $eventDispatcher);
		$response = $handler->handle(new Request());

		self::assertSame(200, $response->getStatusCode());
		self::assertSame([], $events);
	}

	public function testClosesFallbackSpanWhenFallbackThrows(): void
	{
		$_ENV['PROFILER_ENABLED'] = 'true';
		$events = [];
		$eventDispatcher = new EventDispatcherAdapter(static function (object $event) use (&$events): void {
			$events[] = $event;
		});
		$fallback = new class implements RequestHandlerInterface {
			public function handle(Request $request): Response
			{
				throw new RuntimeException('Fallback failed.');
			}
		};
		$handler = new StackRequestHandler([], $fallback, $eventDispatcher);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Fallback failed.');

		try {
			$handler->handle(new Request());
		} finally {
			self::assertCount(2, $events);
			self::assertInstanceOf(KernelSpanStarted::class, $events[0]);
			self::assertInstanceOf(KernelSpanFinished::class, $events[1]);
			self::assertSame($events[0]->token, $events[1]->token);
		}
	}
}
