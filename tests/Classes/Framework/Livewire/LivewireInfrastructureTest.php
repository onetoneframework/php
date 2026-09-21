<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Classes\Routing\Router;
use Clover\Framework\Livewire\LivewireController;
use Clover\Framework\Livewire\LivewireRouteProvider;
use PHPUnit\Framework\TestCase;

final class LivewireInfrastructureTest extends TestCase
{
	public function testRouteProviderRegistersScriptAndUpdateEndpointsOnly(): void
	{
		$router = new Router();

		LivewireRouteProvider::register($router);

		$this->assertSame([
			'GET' => ['/livewire/livewire.js'],
			'POST' => ['/livewire/update'],
		], $router->listRoutes());
		$this->assertSame(2, $router->count());
	}

	public function testRouteProviderTargetsTheLivewireControllerMethods(): void
	{
		$router = new Router();
		LivewireRouteProvider::register($router);

		$this->assertSame([
			[
				'pattern' => '/livewire/livewire.js',
				'callback' => LivewireController::class . '::script',
				'middleware' => [],
				'host' => '*',
				'contentType' => '*',
				'requiredQuery' => [],
				'pathQueryKey' => '',
			],
		], $router->toArray()['GET']);
		$this->assertSame(LivewireController::class . '::update', $router->toArray()['POST'][0]['callback']);
	}

	public function testScriptEndpointServesBundledJavascriptWithCacheHeader(): void
	{
		$response = (new LivewireController())->script();

		$this->assertSame(200, $response->getStatusCode());
		$this->assertSame('javascript', $response->getType());
		$this->assertSame('application/javascript; charset=utf-8', $response->getHeader('Content-Type'));
		$this->assertSame('public, max-age=300', $response->getHeader('Cache-Control'));
		$this->assertIsString($response->getBody());
		$this->assertStringContainsString('Onetone Livewire client runtime', $response->getBody());
		$this->assertStringContainsString('/livewire/update', $response->getBody());
	}
}
