<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Framework\Component\BaseController;
use PHPUnit\Framework\TestCase;

final class BaseControllerTest extends TestCase
{
	public function testResponseCreatesHtmlResponseWithResources(): void
	{
		$controller = new BaseController();
		$resources = ['styles' => ['/app.css']];

		$response = $controller->response('<main>content</main>', $resources);

		$this->assertSame('html', $response->getType());
		$this->assertSame('<main>content</main>', $response->getBody());
		$this->assertSame($resources, $response->getResource());
	}

	public function testResponseTextCreatesPlainTextResponse(): void
	{
		$response = (new BaseController())->responseText('plain text');

		$this->assertSame('text', $response->getType());
		$this->assertSame('plain text', $response->getBody());
		$this->assertSame('text/plain; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testResponseXmlPreservesStructuredBody(): void
	{
		$body = ['status' => 'ok'];

		$response = (new BaseController())->responseXml($body);

		$this->assertSame('xml', $response->getType());
		$this->assertSame($body, $response->getBody());
		$this->assertSame('application/xml; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testResponseImageCreatesBinaryResponseType(): void
	{
		$response = (new BaseController())->responseImage('/images/logo.png');

		$this->assertSame('image', $response->getType());
		$this->assertSame('/images/logo.png', $response->getBody());
		$this->assertSame('application/octet-stream', $response->getHeader('Content-Type'));
	}

	public function testResponseJsonEncodesPayloadAndPreservesResources(): void
	{
		$resources = ['scripts' => ['/app.js']];

		$response = (new BaseController())->responseJson(['ok' => true, 'count' => 2], $resources);

		$this->assertSame('json', $response->getType());
		$this->assertSame('{"ok":true,"count":2}', $response->getBody());
		$this->assertSame($resources, $response->getResource());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
	}

	public function testRedirectCreatesRedirectTypedResponseWithLocationBody(): void
	{
		$response = (new BaseController())->redirect('/account/login');

		$this->assertSame('redirect', $response->getType());
		$this->assertSame('/account/login', $response->getBody());
	}
}
