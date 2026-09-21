<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Exception;

use Clover\Component\Exception\Handler;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HandlerTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$_ENV['APP_DEBUG'] = 'false';
	}

	protected function tearDown(): void
	{
		unset($_ENV['APP_DEBUG']);
		Application::setInstance(null);

		parent::tearDown();
	}

	public function testProductionTextResponseHidesThrowableDetails(): void
	{
		$handler = new Handler(new Application());
		$request = new Request(['HTTP_ACCEPT' => 'text/plain']);

		$response = $handler->render($request, new RuntimeException('sensitive detail'));

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame('Server Error', $response->getBody());
		$this->assertStringNotContainsString('sensitive detail', (string) $response->getBody());
	}

	public function testProductionJsonResponseHidesThrowableDetails(): void
	{
		$handler = new Handler(new Application());
		$request = new Request(['HTTP_ACCEPT' => 'application/json']);

		$response = $handler->render($request, new RuntimeException('database password leaked'));
		$payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame(500, $response->getStatusCode());
		$this->assertSame(['error' => 'Server Error'], $payload);
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testJsonAcceptHeaderMatchingIsCaseInsensitive(): void
	{
		$handler = new Handler(new Application());
		$request = new Request(['HTTP_ACCEPT' => 'Application/JSON, text/plain']);

		$response = $handler->render($request, new RuntimeException('failure'));

		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testDebugTextResponseIncludesThrowableDetails(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		$handler = new Handler(new Application());
		$request = new Request(['HTTP_ACCEPT' => 'text/plain']);

		$response = $handler->render($request, new RuntimeException('visible debug detail'));

		$this->assertSame(500, $response->getStatusCode());
		$this->assertStringContainsString(RuntimeException::class, (string) $response->getBody());
		$this->assertStringContainsString('visible debug detail', (string) $response->getBody());
	}

	public function testDebugJsonResponseIncludesThrowableMetadata(): void
	{
		$_ENV['APP_DEBUG'] = 'true';
		$handler = new Handler(new Application());
		$request = new Request(['HTTP_ACCEPT' => 'application/json']);
		$throwable = new RuntimeException('visible json detail');

		$response = $handler->render($request, $throwable);
		$payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

		$this->assertSame(RuntimeException::class, $payload['error']);
		$this->assertSame('visible json detail', $payload['message']);
		$this->assertSame($throwable->getFile(), $payload['file']);
		$this->assertSame($throwable->getLine(), $payload['line']);
		$this->assertArrayHasKey('trace', $payload);
	}

	public function testThrowableStatusCodeIsPreservedWhenValid(): void
	{
		$handler = new Handler(new Application());
		$request = new Request();

		$response = $handler->render($request, new HttpStatusException('missing', 404));

		$this->assertSame(404, $response->getStatusCode());
	}

	public function testInvalidThrowableStatusCodeFallsBackToServerError(): void
	{
		$handler = new Handler(new Application());
		$request = new Request();

		$response = $handler->render($request, new InvalidHttpStatusException('invalid'));

		$this->assertSame(500, $response->getStatusCode());
	}
}

final class HttpStatusException extends RuntimeException
{
	public function getStatusCode(): int
	{
		return $this->getCode();
	}
}

final class InvalidHttpStatusException extends RuntimeException
{
	public function getStatusCode(): string
	{
		return 'invalid';
	}
}
