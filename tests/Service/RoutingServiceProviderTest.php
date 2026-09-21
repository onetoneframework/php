<?php

declare(strict_types=1);

namespace Clover\Tests\Service;

use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Component\Container\Container;
use Clover\Component\Foundation\Application;
use Clover\Component\Http\Request;
use Clover\Component\Routing\Router;
use Clover\Service\RoutingServiceProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RoutingServiceProviderTest extends TestCase
{
	private string $temporaryDirectory = '';

	protected function tearDown(): void
	{
		Application::setInstance(null);

		if ($this->temporaryDirectory !== '' && DirectoryHandler::exists($this->temporaryDirectory)) {
			DirectoryHandler::delete($this->temporaryDirectory);
		}

		parent::tearDown();
	}

	public function testRegisterCreatesSharedRouter(): void
	{
		$application = new Application();
		$provider = new RoutingServiceProvider($application);

		$provider->register();

		$router = $application->make(Router::class);
		$this->assertInstanceOf(Router::class, $router);
		$this->assertSame($router, $application->make(Router::class));
	}

	public function testBootDoesNothingForNonApplicationContainer(): void
	{
		$container = new Container();
		$provider = new RoutingServiceProvider($container);

		$provider->register();
		$provider->boot();

		$this->assertInstanceOf(Router::class, $container->make(Router::class));
	}

	public function testBootAllowsMissingRouteFile(): void
	{
		$application = $this->createApplication();
		$provider = new RoutingServiceProvider($application);
		$provider->register();

		$provider->boot();

		$router = $application->make(Router::class);
		$this->assertSame([], $router->getRoutes());
	}

	public function testBootLoadsRoutesReturnedByConfigurationFile(): void
	{
		$application = $this->createApplication();
		$this->writeRouteFile(<<<'PHP'
<?php

use Clover\Component\Foundation\Application;
use Clover\Component\Routing\Router;

return static function (Router $router, Application $application): void {
	$router->get('/health', static fn(): string => $application->getBasePath());
};
PHP);
		$provider = new RoutingServiceProvider($application);
		$provider->register();

		$provider->boot();

		$router = $application->make(Router::class);
		$response = $router->dispatch(new Request([
			'REQUEST_METHOD' => 'GET',
			'REQUEST_URI' => '/health',
		]));
		$this->assertSame($application->getBasePath(), $response->getBody());
	}

	public function testBootRejectsRouteFileThatDoesNotReturnCallable(): void
	{
		$application = $this->createApplication();
		$routeFile = $this->writeRouteFile('<?php return ["invalid"];');
		$provider = new RoutingServiceProvider($application);
		$provider->register();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage($routeFile);

		$provider->boot();
	}

	private function createApplication(): Application
	{
		$this->temporaryDirectory = DirectoryHandler::createTemporary('routing-provider-');
		DirectoryHandler::make(
			$this->temporaryDirectory
			. DIRECTORY_SEPARATOR
			. 'App'
			. DIRECTORY_SEPARATOR
			. 'Configure'
		);

		return new Application($this->temporaryDirectory);
	}

	private function writeRouteFile(string $content): string
	{
		$path = $this->temporaryDirectory
			. DIRECTORY_SEPARATOR
			. 'App'
			. DIRECTORY_SEPARATOR
			. 'Configure'
			. DIRECTORY_SEPARATOR
			. RoutingServiceProvider::ROUTE_FILE;
		$this->assertTrue(FileHandler::write($path, $content));

		return $path;
	}
}
