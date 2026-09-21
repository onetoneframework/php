<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\Request;
use PHPUnit\Framework\TestCase;

final class RequestComponentTest extends TestCase
{
	protected function tearDown(): void
	{
		$_GET = [];
		$_POST = [];
		$_FILES = [];
		$_COOKIE = [];
		$_SERVER = [];
	}

    public function testConstructorStoresAllPayloadParts(): void
    {
        $request = new Request(
            ['q' => 'abc'],
            ['name' => 'john'],
            ['f' => ['name' => 'a.txt']],
            ['sid' => 'x'],
            ['REQUEST_METHOD' => 'POST'],
            '{"x":1}'
        );

        $this->assertSame(['q' => 'abc'], $request->get);
        $this->assertSame(['name' => 'john'], $request->post);
        $this->assertArrayHasKey('f', $request->files);
        $this->assertSame(['sid' => 'x'], $request->cookie);
        $this->assertSame('POST', $request->server['REQUEST_METHOD']);
        $this->assertSame('{"x":1}', $request->content);
    }

	public function testCreateFromGlobalsUsesPhpSuperglobals(): void
	{
        $_GET = ['page' => '1'];
        $_POST = ['title' => 'hello'];
        $_FILES = ['upload' => ['name' => 'f.txt']];
        $_COOKIE = ['token' => 'abc'];
        $_SERVER = ['REQUEST_URI' => '/unit'];

        $request = Request::createFromGlobals();

        $this->assertSame($_GET, $request->get);
        $this->assertSame($_POST, $request->post);
        $this->assertSame($_FILES, $request->files);
        $this->assertSame($_COOKIE, $request->cookie);
		$this->assertSame($_SERVER, $request->server);
	}

	public function testAccessorsNormalizeTransportSpecificServerInput(): void
	{
		$request = new Request(server: [
			'request_method' => 'post',
			'request_uri' => '/orders/42?expand=items',
			'http_accept_language' => 'en-US',
		]);

		$this->assertSame('POST', $request->getMethod());
		$this->assertSame('/orders/42?expand=items', $request->getUri());
		$this->assertSame('/orders/42', $request->getPath());
		$this->assertSame('en-US', $request->getHeader('Accept-Language'));
	}

	public function testWantsJsonRecognizesJsonFamilyMediaTypes(): void
	{
		$standardRequest = new Request(server: ['HTTP_ACCEPT' => 'application/json']);
		$vendorRequest = new Request(server: ['HTTP_ACCEPT' => 'application/vnd.onetone.resource+json; version=2']);
		$ajaxRequest = new Request(server: ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);

		$this->assertTrue($standardRequest->wantsJson());
		$this->assertTrue($vendorRequest->wantsJson());
		$this->assertTrue($ajaxRequest->wantsJson());
	}

	public function testWantsJsonRejectsHtmlOnlyRequests(): void
	{
		$request = new Request(server: ['HTTP_ACCEPT' => 'text/html,application/xhtml+xml']);

		$this->assertFalse($request->wantsJson());
	}
}
