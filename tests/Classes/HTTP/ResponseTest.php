<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\HTTP;

use Clover\Classes\HTTP\Response;
use PHPUnit\Framework\TestCase;

final class ResponseTest extends TestCase
{
	public function testJsonResponseExposesStatusHeadersBodyAndEncodedContent(): void
	{
		$body = ['message' => 'ok', 'count' => 2];
		$response = new Response($body, ['X-Request-Id' => 'request-123'], 'json', 201);

		self::assertSame($body, $response->getBody());
		self::assertSame(201, $response->getStatus());
		self::assertSame('application/json; charset=utf-8', $response->getContentType());
		self::assertSame('request-123', $response->getResponseHeaders()['X-Request-Id']);
		self::assertTrue($response->isJson());
		self::assertSame('{"message":"ok","count":2}', $response->getContent());
	}

	public function testExplicitContentTypeIsPreserved(): void
	{
		$response = new Response('payload', ['Content-Type' => 'application/xml'], 'json');

		self::assertSame('application/xml', $response->getContentType());
		self::assertTrue($response->isJson());
	}

	public function testMutatorsUpdateObservableResponseState(): void
	{
		$response = new Response('before', [], 'text');

		$response->setBody('after');
		$response->setStatus(202);
		$response->setHeader('X-One', 'first');
		$response->setHeaders([
			'X-One' => 'replaced',
			'X-Two' => 'second',
		]);

		self::assertSame('after', $response->getBody());
		self::assertSame('after', $response->getContent());
		self::assertSame(202, $response->getStatus());
		self::assertSame('replaced', $response->getResponseHeaders()['X-One']);
		self::assertSame('second', $response->getResponseHeaders()['X-Two']);
	}
}
