<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Http;

use Clover\Component\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
	protected function tearDown(): void
	{
		$_SERVER = [];
		$_GET = [];
		$_POST = [];
		$_COOKIE = [];
		$_FILES = [];
	}

	public function testRequestNormalizesUriParsesHeadersAndSupportsMethodOverride(): void
	{
		$request = new Request(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/users/42?tab=profile',
				'HTTP_ACCEPT' => 'application/json',
				'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH',
				'CONTENT_TYPE' => 'application/json',
			],
			[],
			[],
			['sid' => 'abc123'],
			['avatar' => ['name' => 'avatar.png']],
			'{"active":true}'
		);

		$this->assertSame('PATCH', $request->getMethod());
		$this->assertSame('/users/42', $request->getUri());
		$this->assertSame(['tab' => 'profile'], $request->getQueryParams());
		$this->assertSame('application/json', $request->getHeader('accept'));
		$this->assertSame(['active' => true], $request->getParsedBody());
		$this->assertSame(['sid' => 'abc123'], $request->getCookies());
		$this->assertArrayHasKey('avatar', $request->getFiles());
	}

	public function testInputAttributesAndGlobalsFactoryRemainAccessible(): void
	{
		$_SERVER = ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/articles?page=2'];
		$_GET = ['page' => '2'];
		$_POST = ['title' => 'hello'];
		$_COOKIE = ['token' => 'abc'];
		$_FILES = ['upload' => ['name' => 'doc.txt']];

		$request = Request::createFromGlobals();
		$request->setAttribute('route', 'articles.index');

		$this->assertSame('/articles', $request->getPath());
		$this->assertSame('hello', $request->input('title'));
		$this->assertSame('2', $request->input('page'));
		$this->assertSame('abc', $request->getCookies()['token']);
		$this->assertTrue($request->hasAttribute('route'));
		$this->assertSame('articles.index', $request->getAttribute('route'));
		$this->assertSame($_SERVER, $request->getServerParams());
	}

	public function testDefaultsProduceGetRequestForRootPath(): void
	{
		$request = new Request();

		$this->assertSame('GET', $request->getMethod());
		$this->assertSame('/', $request->getUri());
		$this->assertSame([], $request->getQueryParams());
		$this->assertSame([], $request->getHeaders());
		$this->assertSame('', $request->getRawContent());
	}

	public function testExplicitQueryParametersTakePrecedenceOverUriQueryString(): void
	{
		$request = new Request(
			['REQUEST_URI' => '/search?page=1&sort=old'],
			['page' => '9', 'sort' => 'new']
		);

		$this->assertSame(['page' => '9', 'sort' => 'new'], $request->getQueryParams());
	}

	public function testPostBodyMethodOverrideIsUsedWhenHeaderIsAbsent(): void
	{
		$request = new Request(
			['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/resource'],
			[],
			['_method' => 'delete']
		);

		$this->assertSame('DELETE', $request->getMethod());
	}

	public function testMethodOverrideHeaderTakesPrecedenceOverBodyOverride(): void
	{
		$request = new Request(
			[
				'REQUEST_METHOD' => 'POST',
				'REQUEST_URI' => '/resource',
				'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH',
			],
			[],
			['_method' => 'DELETE']
		);

		$this->assertSame('PATCH', $request->getMethod());
	}

	public function testNonPostMethodIgnoresMethodOverrideInputs(): void
	{
		$request = new Request(
			[
				'REQUEST_METHOD' => 'PUT',
				'REQUEST_URI' => '/resource',
				'HTTP_X_HTTP_METHOD_OVERRIDE' => 'PATCH',
			],
			[],
			['_method' => 'DELETE']
		);

		$this->assertSame('PUT', $request->getMethod());
	}

	public function testUriNormalizationRemovesTrailingSlashButPreservesRoot(): void
	{
		$nested = new Request(['REQUEST_URI' => '/users/42/']);
		$root = new Request(['REQUEST_URI' => '/']);

		$this->assertSame('/users/42', $nested->getPath());
		$this->assertSame('/', $root->getPath());
	}

	public function testContentHeadersAreNormalizedAlongsideHttpHeaders(): void
	{
		$request = new Request([
			'HTTP_X_REQUEST_ID' => 'request-123',
			'CONTENT_TYPE' => 'text/plain',
			'CONTENT_LENGTH' => '12',
			'CONTENT_MD5' => 'checksum',
		]);

		$this->assertSame('request-123', $request->getHeader('X-Request-Id'));
		$this->assertSame('text/plain', $request->getHeader('CONTENT-TYPE'));
		$this->assertSame('12', $request->getHeader('content-length'));
		$this->assertSame('checksum', $request->getHeader('content-md5'));
	}

	public function testExplicitParsedBodyTakesPrecedenceOverJsonRawContent(): void
	{
		$request = new Request(
			['CONTENT_TYPE' => 'application/json'],
			[],
			['source' => 'parsed'],
			[],
			[],
			'{"source":"raw"}'
		);

		$this->assertSame(['source' => 'parsed'], $request->getParsedBody());
	}

	public function testJsonNullPayloadBecomesNullParsedBody(): void
	{
		$request = new Request(
			['CONTENT_TYPE' => 'application/json'],
			[],
			[],
			[],
			[],
			'null'
		);

		$this->assertNull($request->getParsedBody());
		$this->assertSame('fallback', $request->getParsedBodyValue('missing', 'fallback'));
	}

	public function testInvalidJsonLeavesExplicitEmptyParsedBodyUntouched(): void
	{
		$request = new Request(
			['CONTENT_TYPE' => 'application/json'],
			[],
			[],
			[],
			[],
			'{invalid json'
		);

		$this->assertSame([], $request->getParsedBody());
	}

	public function testInputPrefersParsedBodyAndFallsBackToQueryThenDefault(): void
	{
		$request = new Request(
			['REQUEST_URI' => '/search?shared=query&queryOnly=value'],
			[],
			['shared' => 'body', 'bodyOnly' => 'value']
		);

		$this->assertSame('body', $request->input('shared'));
		$this->assertSame('value', $request->input('bodyOnly'));
		$this->assertSame('value', $request->input('queryOnly'));
		$this->assertSame('fallback', $request->input('missing', 'fallback'));
	}

	public function testAttributeLookupPreservesNullValuesAsExistingAttributes(): void
	{
		$request = new Request();
		$request->setAttribute('nullable', null);

		$this->assertTrue($request->hasAttribute('nullable'));
		$this->assertNull($request->getAttribute('nullable', 'fallback'));
		$this->assertSame(['nullable' => null], $request->getAttributes());
	}
}
