<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Http;

use Clover\Component\Http\Response;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResponseTest extends TestCase
{
	public function testJsonFactoryCreatesExpectedHeadersBodyAndReasonPhrase(): void
	{
		$response = Response::json(['ok' => true], 201);

		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('Created', $response->getReasonPhrase());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type'));
		$this->assertSame('{"ok":true}', $response->getBody());
	}

	public function testRedirectResponseIncludesLocationHeader(): void
	{
		$response = Response::redirect('/login', 302);

		$this->assertSame(302, $response->getStatusCode());
		$this->assertSame('/login', $response->getHeader('location'));
		$this->assertSame('Found', $response->getReasonPhrase());
	}

	public function testSendSerializesArrayBodies(): void
	{
		$response = new Response(['value' => 10]);

		ob_start();
		$response->send();
		$output = ob_get_clean();

		$this->assertSame('{"value":10}', $output);
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type'));
	}

	public function testJsonFactoryThrowsRuntimeExceptionForRecursivePayloads(): void
	{
		$payload = [];
		$payload['self'] = &$payload;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Failed to encode response body as JSON.');

		Response::json($payload);
	}

	public function testStringBodyGetsDefaultTextContentType(): void
	{
		$response = new Response('hello');

		$this->assertSame('text/plain; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertSame('OK', $response->getReasonPhrase());
	}

	public function testArrayBodyGetsDefaultJsonContentTypeWithoutImmediateEncoding(): void
	{
		$payload = ['name' => 'clover'];
		$response = new Response($payload);

		$this->assertSame($payload, $response->getBody());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testCustomContentTypeIsPreserved(): void
	{
		$response = new Response('body', 200, ['Content-Type' => 'application/xml']);

		$this->assertSame('application/xml', $response->getHeader('content-type'));
	}

	public function testHeaderLookupAndReplacementAreCaseInsensitive(): void
	{
		$response = new Response('body', 200, ['X-Request-Id' => 'first']);

		$response->setHeader('x-request-id', 'second');

		$this->assertTrue($response->hasHeader('X-REQUEST-ID'));
		$this->assertSame('second', $response->getHeader('X-Request-Id'));
		$this->assertCount(2, $response->getHeaders());
	}

	public function testMissingHeaderReturnsProvidedDefault(): void
	{
		$response = new Response();

		$this->assertSame('fallback', $response->getHeader('x-missing', 'fallback'));
	}

	public function testUnknownStatusHasEmptyReasonPhraseUnlessCustomPhraseIsProvided(): void
	{
		$unknown = new Response('', 599);
		$custom = new Response('', 599, [], 'Network Connect Timeout');

		$this->assertSame('', $unknown->getReasonPhrase());
		$this->assertSame('Network Connect Timeout', $custom->getReasonPhrase());
	}

	public function testTextFactoryAllowsCallerHeadersToOverrideDefaults(): void
	{
		$response = Response::text('created', 201, [
			'Content-Type' => 'text/custom',
			'X-Test' => 'yes',
		]);

		$this->assertSame('text/custom', $response->getHeader('Content-Type'));
		$this->assertSame('yes', $response->getHeader('X-Test'));
	}

	public function testRedirectFactoryAllowsCallerToOverrideLocationAndContentType(): void
	{
		$response = Response::redirect('/default', 301, [
			'Location' => '/replacement',
			'Content-Type' => 'text/custom',
		]);

		$this->assertSame(301, $response->getStatusCode());
		$this->assertSame('/replacement', $response->getHeader('Location'));
		$this->assertSame('text/custom', $response->getHeader('Content-Type'));
	}

	public function testJsonFactoryPreservesUnicodeAndUnescapedSlashes(): void
	{
		$response = Response::json(['message' => 'こんにちは', 'url' => 'https://example.test/path']);

		$this->assertSame(
			'{"message":"こんにちは","url":"https://example.test/path"}',
			$response->getBody()
		);
	}
}
