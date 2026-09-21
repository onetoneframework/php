<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Event\EventManager;
use Clover\Classes\Routing\Router;
use Clover\Framework\Component\Response;
use PHPUnit\Framework\TestCase;

/**
 * The router's built-in responders — redirect, CORS preflight, json, text,
 * download, status, static and the websocket refusal — used to write headers
 * with header()/echo and then call exit. That killed the process inside the
 * routing middleware, so the response never reached normalizeRouterResult(),
 * the security-header and trace-id middleware never ran, and none of these
 * paths could be tested at all. They return a Response now; these tests are
 * what that makes possible.
 */
final class RouterResponseTest extends TestCase
{
	/** @var string[] Temporary files created by fileFixture(). */
	private array $fixtures = [];

	private string $fixtureDirectory = '';

	protected function setUp(): void
	{
		parent::setUp();
		$_SERVER = [
			'HTTP_HOST' => 'localhost',
			'REQUEST_METHOD' => 'GET',
			'CONTENT_TYPE' => 'text/plain',
			'REQUEST_URI' => '/',
		];
	}

	protected function tearDown(): void
	{
		foreach ($this->fixtures as $path) {
			if (is_file($path)) {
				unlink($path);
			}
		}

		if ($this->fixtureDirectory !== '' && is_dir($this->fixtureDirectory)) {
			rmdir($this->fixtureDirectory);
		}

		$this->fixtures = [];
		$this->fixtureDirectory = '';
		$_SERVER = [];
		EventManager::clearInstance();
		parent::tearDown();
	}

	/**
	 * Point the request at a path and hand back a fresh router for it.
	 */
	private function requestFor(string $uri, string $method = 'GET'): Router
	{
		$_SERVER['REQUEST_URI'] = $uri;
		$_SERVER['REQUEST_METHOD'] = $method;

		return new Router();
	}

	private function fileFixture(string $name, string $content): string
	{
		if ($this->fixtureDirectory === '') {
			$this->fixtureDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'router_response_' . uniqid();
			mkdir($this->fixtureDirectory, 0777, true);
		}

		$path = $this->fixtureDirectory . DIRECTORY_SEPARATOR . $name;
		file_put_contents($path, $content);
		$this->fixtures[] = $path;

		return $path;
	}

	public function testRedirectReturnsALocationResponse(): void
	{
		$router = $this->requestFor('/old');
		$router->redirect('/old', '/new');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(302, $response->getStatusCode());
		$this->assertSame('/new', $response->getHeader('Location'));
	}

	public function testRedirectHonoursTheGivenStatus(): void
	{
		$router = $this->requestFor('/old');
		$router->redirect('/old', '/new', 307);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(307, $response->getStatusCode());
	}

	public function testConditionalRedirectRedirectsWhenTheConditionHolds(): void
	{
		$router = $this->requestFor('/maybe');
		$router->conditionalRedirect('/maybe', '/yes', static fn (): bool => true);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('/yes', $response->getHeader('Location'));
	}

	public function testConditionalRedirectFallsThroughWhenTheConditionFails(): void
	{
		$router = $this->requestFor('/maybe');
		$router->conditionalRedirect('/maybe', '/yes', static fn (): bool => false);

		$this->assertNull($router->handle());
	}

	public function testCorsPreflightAnswersWithTwoZeroFourAndTheConfiguredHeaders(): void
	{
		$router = $this->requestFor('/api/items', 'OPTIONS');
		$router->cors('/api/items', ['origin' => 'https://example.test', 'credentials' => true]);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(204, $response->getStatusCode());
		$this->assertSame('https://example.test', $response->getHeader('Access-Control-Allow-Origin'));
		$this->assertSame('true', $response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertSame('86400', $response->getHeader('Access-Control-Max-Age'));
	}

	public function testCorsPreflightOmitsCredentialsWhenNotRequested(): void
	{
		$router = $this->requestFor('/api/items', 'OPTIONS');
		$router->cors('/api/items');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertNull($response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertSame('*', $response->getHeader('Access-Control-Allow-Origin'));
	}

	public function testJsonEncodesTheBodyAndKeepsExtraHeaders(): void
	{
		$router = $this->requestFor('/api/ping');
		$router->json('/api/ping', ['pong' => true], 201, ['X-Request-Id' => 'abc']);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(201, $response->getStatusCode());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertSame('abc', $response->getHeader('X-Request-Id'));
		$this->assertSame(['pong' => true], json_decode((string) $response->getBody(), true));
	}

	public function testTextReturnsAPlainTextResponse(): void
	{
		$router = $this->requestFor('/robots.txt');
		$router->text('/robots.txt', "User-agent: *\nDisallow:", 200);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('text/plain; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertSame("User-agent: *\nDisallow:", $response->getBody());
	}

	public function testStatusReturnsTheCodeAndMessage(): void
	{
		$router = $this->requestFor('/gone');
		$router->status('/gone', 410, 'Gone for good');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(410, $response->getStatusCode());
		$this->assertSame('Gone for good', $response->getBody());
	}

	public function testDownloadSendsTheFileAsAnAttachment(): void
	{
		$path = $this->fileFixture('report.csv', "a,b\n1,2\n");
		$router = $this->requestFor('/report');
		$router->download('/report', $path, 'monthly.csv');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame("a,b\n1,2\n", $response->getBody());
		$this->assertStringContainsString('monthly.csv', (string) $response->getHeader('Content-Disposition'));
		$this->assertSame('no-cache, no-store, must-revalidate', $response->getHeader('Cache-Control'));
	}

	public function testDownloadOfAMissingFileIsAFourZeroFour(): void
	{
		$router = $this->requestFor('/report');
		$router->download('/report', $this->fixtureDirectory . '/nope.csv');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(404, $response->getStatusCode());
	}

	public function testStaticServesAnAllowedFile(): void
	{
		$this->fileFixture('app.css', 'body{color:red}');
		$router = $this->requestFor('/assets/app.css');
		$router->static('/assets', $this->fixtureDirectory);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('body{color:red}', $response->getBody());
		$this->assertSame('15', $response->getHeader('Content-Length'));
		$this->assertStringContainsString('max-age=86400', (string) $response->getHeader('Cache-Control'));
	}

	public function testStaticRefusesAnExtensionOutsideTheAllowList(): void
	{
		$this->fileFixture('secrets.env', 'TOKEN=1');
		$router = $this->requestFor('/assets/secrets.env');
		$router->static('/assets', $this->fixtureDirectory);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(403, $response->getStatusCode());
		$this->assertSame('Forbidden file type', $response->getBody());
	}

	/**
	 * realpath() of a file that does not exist returns false, so a missing asset
	 * is refused by the traversal guard before the existence check is reached.
	 * The 404 branch is therefore only reachable for a path that resolves but is
	 * not a regular file.
	 */
	public function testStaticRefusesAMissingFileAtTheTraversalGuard(): void
	{
		$this->fileFixture('app.css', 'body{}');
		$router = $this->requestFor('/assets/absent.css');
		$router->static('/assets', $this->fixtureDirectory);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(403, $response->getStatusCode(), 'realpath() of a missing file fails the traversal guard first.');
	}

	public function testStaticAnswersThreeZeroFourWhenTheEtagMatches(): void
	{
		$path = $this->fileFixture('app.css', 'body{color:red}');
		$_SERVER['HTTP_IF_NONE_MATCH'] = '"' . md5_file($path) . '"';

		$router = $this->requestFor('/assets/app.css');
		$router->static('/assets', $this->fixtureDirectory);

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(304, $response->getStatusCode());
		$this->assertSame('', $response->getBody());
	}

	public function testWebsocketRefusesANonUpgradeRequestWithFourTwoSix(): void
	{
		$router = $this->requestFor('/ws');
		$router->websocket('/ws', static fn (): string => 'never');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(426, $response->getStatusCode());
		$this->assertSame('websocket', $response->getHeader('Upgrade'));
	}

	public function testForceHttpsRedirectsAnInsecureRequest(): void
	{
		$_SERVER['SERVER_PORT'] = 80;
		$router = $this->requestFor('/secure');
		$router->forceHttps();
		$router->get('/secure', static fn (): string => 'never reached');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(301, $response->getStatusCode());
		$this->assertSame('https://localhost/secure', $response->getHeader('Location'));
	}

	public function testForceHttpsLetsASecureRequestThrough(): void
	{
		$_SERVER['HTTPS'] = 'on';
		$router = $this->requestFor('/secure');
		$router->forceHttps();
		$router->get('/secure', static fn (): string => 'handled');

		$this->assertSame('handled', $router->handle());
	}

	public function testStripTrailingSlashRedirectsAndKeepsTheQueryString(): void
	{
		$router = $this->requestFor('/users/?page=2');
		$router->stripTrailingSlash();
		$router->get('/users', static fn (): string => 'never reached');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(301, $response->getStatusCode());
		$this->assertSame('/users?page=2', $response->getHeader('Location'));
	}

	public function testStripTrailingSlashLeavesACleanPathAlone(): void
	{
		$router = $this->requestFor('/users');
		$router->stripTrailingSlash();
		$router->get('/users', static fn (): string => 'handled');

		$this->assertSame('handled', $router->handle());
	}

	public function testForceWwwRedirectsANakedHost(): void
	{
		$_SERVER['HTTP_HOST'] = 'example.test';
		$router = $this->requestFor('/page');
		$router->forceWww();
		$router->get('/page', static fn (): string => 'never reached');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('http://www.example.test/page', $response->getHeader('Location'));
	}

	public function testStripWwwRedirectsAWwwHost(): void
	{
		$_SERVER['HTTP_HOST'] = 'www.example.test';
		$router = $this->requestFor('/page');
		$router->stripWww();
		$router->get('/page', static fn (): string => 'never reached');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame('http://example.test/page', $response->getHeader('Location'));
	}

	public function testABeforeHookReturningFalseStillAbortsRouting(): void
	{
		$router = $this->requestFor('/blocked');
		$router->before(static fn (): bool => false);
		$router->get('/blocked', static fn (): string => 'never reached');

		$this->assertFalse($router->handle(), 'false keeps its "fall through to the next middleware" meaning.');
	}
}
