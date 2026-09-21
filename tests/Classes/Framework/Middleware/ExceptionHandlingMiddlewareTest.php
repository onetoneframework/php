<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Middleware;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\TraceContext;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Middleware\ExceptionHandlingMiddleware;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExceptionHandlingMiddlewareTest extends TestCase
{
	/** @var array<string, string|null> */
	private array $previousEnvironment = [];

	protected function setUp(): void
	{
		foreach (['APP_DEBUG', 'IS_DEBUGGABLE', 'STRUCTURED_LOG_ENABLED'] as $key) {
			$this->previousEnvironment[$key] = $_ENV[$key] ?? null;
		}

		$_ENV['APP_DEBUG'] = 'false';
		$_ENV['IS_DEBUGGABLE'] = 'false';
		$_ENV['STRUCTURED_LOG_ENABLED'] = 'false';
	}

	protected function tearDown(): void
	{
		foreach ($this->previousEnvironment as $key => $value) {
			if ($value === null) {
				unset($_ENV[$key]);
			} else {
				$_ENV[$key] = $value;
			}
		}
	}

	public function testReturnsSuccessfulResponseWithoutModification(): void
	{
		$expectedResponse = new Response('ok', [], 'text', 200);
		$handler = new class ($expectedResponse) implements RequestHandlerInterface {
			public function __construct(private readonly Response $response)
			{
			}

			public function handle(Request $request): Response
			{
				return $this->response;
			}
		};

		$response = (new ExceptionHandlingMiddleware())->process(new Request(), $handler);

		$this->assertSame($expectedResponse, $response);
	}

	public function testReturnsProblemDetailsForJsonRequests(): void
	{
		TraceContext::bootstrap(['HTTP_X_TRACE_ID' => 'json-error-trace']);
		$request = new Request(server: [
			'REQUEST_METHOD' => 'POST',
			'REQUEST_URI' => '/api/orders',
			'HTTP_ACCEPT' => 'application/json',
		]);
		$handler = $this->failingHandler();

		$response = (new ExceptionHandlingMiddleware())->process($request, $handler);
		$payload = json_decode(
			json: (string) $response->getBody(),
			associative: true,
			flags: JSON_THROW_ON_ERROR
		);

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('application/problem+json; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertSame('no-store', $response->getHeader('Cache-Control'));
		$this->assertSame('json-error-trace', $response->getHeader('X-Trace-Id'));
		$this->assertSame('Internal Server Error', $payload['title']);
		$this->assertSame('json-error-trace', $payload['trace_id']);
		$this->assertStringNotContainsString('private failure details', (string) $response->getBody());
	}

	public function testReturnsSafeHtmlForBrowserRequests(): void
	{
		TraceContext::bootstrap(['HTTP_X_TRACE_ID' => 'browser-error-trace']);
		$request = new Request(server: ['HTTP_ACCEPT' => 'text/html']);

		$response = (new ExceptionHandlingMiddleware())->process($request, $this->failingHandler());

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('html', $response->getType());
		$this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertStringContainsString('Reference: browser-error-trace', (string) $response->getBody());
		$this->assertStringNotContainsString('private failure details', (string) $response->getBody());
	}

	public function testReturnsDetailedHtmlForBrowserRequestsWhenDebuggingIsEnabled(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		TraceContext::bootstrap(['HTTP_X_TRACE_ID' => 'browser-debug-trace']);
		$request = new Request(server: ['HTTP_ACCEPT' => 'text/html']);

		$response = (new ExceptionHandlingMiddleware())->process($request, $this->failingHandler());

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('html', $response->getType());
		$this->assertSame('text/html; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertStringContainsString('private failure details', (string) $response->getBody());
		$this->assertStringContainsString(RuntimeException::class, (string) $response->getBody());
	}

	private function failingHandler(): RequestHandlerInterface
	{
		return new class () implements RequestHandlerInterface {
			public function handle(Request $request): Response
			{
				throw new RuntimeException('private failure details');
			}
		};
	}
}
