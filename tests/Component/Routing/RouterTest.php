<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Routing;

use Clover\Component\Container\Container;
use Clover\Component\Http\Request;
use Clover\Component\Http\Response;
use Clover\Component\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
	public function testParameterizedRouteInjectsRequestAndCastsScalars(): void
	{
		$router = new Router();
		$router->get('/users/{id}', static function (Request $request, int $id): array {
			return [
				'method' => $request->getMethod(),
				'id' => $id,
			];
		});

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/users/7']));

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('application/json; charset=utf-8', $response->getHeader('content-type'));
		$this->assertSame('{"method":"GET","id":7}', $response->getBody());
	}

	public function testMethodNotAllowedResponseIncludesAllowHeader(): void
	{
		$router = new Router();
		$router->get('/users/{id}', static fn() => 'ok');

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/users/9']));

		$this->assertSame(405, $response->getStatusCode());
		$this->assertSame('GET', $response->getHeader('allow'));
		$this->assertSame('Method Not Allowed', $response->getBody());
	}

	public function testArrayControllerActionAndOptionalRouteSegmentAreSupported(): void
	{
		$router = new Router();
		$router->get('/posts/{slug}', [ComponentRouterFixtureController::class, 'show']);
		$router->get('/hello/{name?}', static fn(?string $name = 'guest') => $name ?? 'guest');

		$postResponse = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/posts/example-post']));
		$helloResponse = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/hello']));

		$this->assertSame('post:example-post', $postResponse->getBody());
		$this->assertSame('guest', $helloResponse->getBody());
	}

	public function testHeadFallsBackToGetRouteWhenNoHeadRouteExists(): void
	{
		$router = new Router();
		$router->get('/status', static fn(Request $request): string => $request->getMethod());

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'HEAD', 'REQUEST_URI' => '/status']));

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('HEAD', $response->getBody());
	}

	public function testExplicitHeadRouteTakesPrecedenceOverGetFallback(): void
	{
		$router = new Router();
		$router->get('/status', static fn(): string => 'get');
		$router->head('/status', static fn(): string => 'head');

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'HEAD', 'REQUEST_URI' => '/status']));

		$this->assertSame('head', $response->getBody());
	}

	public function testRouteMetadataCanBeConfiguredAndResolvedByName(): void
	{
		$router = new Router();
		$route = $router->get('admin/', static fn(): string => 'admin')
			->middleware('auth')
			->middleware(['audit', 'throttle'])
			->name('admin.index');

		$this->assertSame('GET', $route->getMethod());
		$this->assertSame('/admin', $route->getUri());
		$this->assertSame(['auth', 'audit', 'throttle'], $route->getMiddleware());
		$this->assertSame('admin.index', $route->getName());
		$this->assertSame($route, $router->getRouteByName('admin.index'));
		$this->assertNull($router->getRouteByName('missing'));
		$this->assertSame([$route], $router->getRoutes());
	}

	public function testControllerCanBeResolvedThroughContainerAfterRouterConstruction(): void
	{
		$container = new Container();
		$dependency = new ComponentRouterFixtureDependency('resolved');
		$container->singleton(ComponentRouterFixtureDependency::class, $dependency);
		$router = new Router();
		$router->setContainer($container);
		$router->get('/container', [ComponentRouterInjectedController::class, 'show']);

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/container']));

		$this->assertSame('resolved', $response->getBody());
	}

	public function testUnknownPathReturnsNotFoundInsteadOfMethodNotAllowed(): void
	{
		$router = new Router();
		$router->get('/known', static fn(): string => 'known');

		$response = $router->dispatch(new Request(['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/missing']));

		$this->assertSame(404, $response->getStatusCode());
		$this->assertSame('Not Found', $response->getBody());
		$this->assertNull($response->getHeader('allow'));
	}
}

final class ComponentRouterFixtureController
{
	public function show(string $slug): string
	{
		return 'post:' . $slug;
	}
}

final class ComponentRouterFixtureDependency
{
	public function __construct(public string $value)
	{
	}
}

final class ComponentRouterInjectedController
{
	public function __construct(private ComponentRouterFixtureDependency $dependency)
	{
	}

	public function show(): string
	{
		return $this->dependency->value;
	}
}
