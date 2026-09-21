<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Component;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventManager;
use Clover\Classes\Logging\Logger;
use Clover\Framework\Component\CliKernel;
use Clover\Framework\Component\Mapper;
use Clover\Framework\Component\Renderer;
use Clover\Framework\Component\Resource;
use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Event\BeforeResponseSend;
use Clover\Framework\Event\OnBoot;
use Clover\Framework\Event\OnTerminate;
use Clover\Framework\Livewire\LivewireManager;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class MapperTest extends TestCase
{
	private const ASSET_VERSION_ENVIRONMENT_VARIABLE = 'ASSET_VERSION';
	private const INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE = 'APP_INTERCEPTOR_DISCOVERY';
	private const SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE = 'APP_SUBSCRIBER_DISCOVERY';
	private const ENVIRONMENT_VARIABLE_NAMES = [
		self::ASSET_VERSION_ENVIRONMENT_VARIABLE,
		self::INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE,
		self::SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE,
	];

	/** @var array<string, bool> */
	private array $environmentVariablePresence = [];

	/** @var array<string, mixed> */
	private array $environmentVariableValues = [];

	protected function setUp(): void
	{
		foreach (self::ENVIRONMENT_VARIABLE_NAMES as $environmentVariableName) {
			$isPresent = array_key_exists($environmentVariableName, $_ENV);
			$this->environmentVariablePresence[$environmentVariableName] = $isPresent;
			if ($isPresent) {
				$this->environmentVariableValues[$environmentVariableName] = $_ENV[$environmentVariableName];
			}
		}

		if (!defined('BASE_PATH')) {
			define(
				'BASE_PATH',
				__DIR__
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . '..'
					. DIRECTORY_SEPARATOR . 'root'
			);
		}

		ApplicationContext::clearEnvironment();
		ApplicationContext::clearInterceptors();
		EventManager::clearInstance();
		$_ENV[self::INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE] = 'false';
		$_ENV[self::SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE] = 'false';
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI'] = '/';
		$_SERVER['HTTP_HOST'] = 'localhost';
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['SERVER_PORT'] = '80';
	}

	protected function tearDown(): void
	{
		foreach (self::ENVIRONMENT_VARIABLE_NAMES as $environmentVariableName) {
			if ($this->environmentVariablePresence[$environmentVariableName]) {
				$_ENV[$environmentVariableName] = $this->environmentVariableValues[$environmentVariableName];
			} else {
				unset($_ENV[$environmentVariableName]);
			}
		}

		ApplicationContext::clearEnvironment();
		ApplicationContext::clearInterceptors();
		EventManager::clearInstance();
		$_SERVER = [];
	}

    public function testGetOptionsReturnsProvidedOptions(): void
    {
        $options = ['server' => 'fpm', 'custom' => 'value'];
        $mapper = new Mapper($options, []);

        $this->assertSame($options, $mapper->getOptions());
    }

    public function testMatchRunnerReturnsCliKernelInCliEnvironment(): void
    {
        $mapper = new Mapper([], []);
        $runner = $mapper->matchRunner();

        $this->assertInstanceOf(CliKernel::class, $runner);
    }

	public function testEachMapperLoadsDependencyConfiguration(): void
	{
		$firstMapper = new Mapper([], []);
		$firstRunner = $firstMapper->matchRunner();
		$secondMapper = new Mapper([], []);
		$secondRunner = $secondMapper->matchRunner();

		$this->assertInstanceOf(CliKernel::class, $firstRunner);
		$this->assertInstanceOf(CliKernel::class, $secondRunner);
	}

	public function testDisabledDiscoveryFlagsSkipInterceptorsAndSubscribers(): void
	{
		$mapper = new Mapper([], []);
		$mapper->matchRunner();

		$this->assertSame([], ApplicationContext::getInterceptors());
		$this->assertSame([], EventManager::getInstance()->getListeners(BeforeResponseSend::class));
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testEnabledDiscoveryFlagsRegisterInterceptorsAndSubscribers(): void
	{
		$_ENV[self::INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE] = 'true';
		$_ENV[self::SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE] = 'true';

		$mapper = new Mapper([], []);
		$mapper->matchRunner();

		$this->assertNotEmpty(ApplicationContext::getInterceptors());
		$this->assertNotEmpty(EventManager::getInstance()->getListeners(BeforeResponseSend::class));
	}

	public function testFrameworkServicesAndLivewireManagerAreRegisteredLazily(): void
	{
		$mapper = new Mapper([], []);
		$mapper->matchRunner();
		$container = ApplicationContext::getContainer();

		$this->assertInstanceOf(Container::class, $container);
		$this->assertTrue($container->has(Renderer::class));
		$this->assertTrue($container->has(Resource::class));
		$this->assertTrue($container->has(Logger::class));
		$this->assertTrue($container->has(LivewireManager::class));
		$this->assertArrayNotHasKey(Renderer::class, $container->getAll());
		$this->assertArrayNotHasKey(Resource::class, $container->getAll());
		$this->assertArrayNotHasKey(Logger::class, $container->getAll());
		$this->assertArrayNotHasKey(LivewireManager::class, $container->getAll());

		$renderer = $container->get(Renderer::class);
		$resource = $container->get(Resource::class);
		$logger = $container->get(Logger::class);
		$livewireManager = $container->get(LivewireManager::class);

		$this->assertInstanceOf(Renderer::class, $renderer);
		$this->assertInstanceOf(Resource::class, $resource);
		$this->assertInstanceOf(Logger::class, $logger);
		$this->assertInstanceOf(LivewireManager::class, $livewireManager);
		$this->assertSame($renderer, $container->get(Renderer::class));
		$this->assertSame($resource, $container->get(Resource::class));
		$this->assertSame($logger, $container->get(Logger::class));
		$this->assertSame($livewireManager, $container->get(LivewireManager::class));
	}

	public function testAssetVersionIsInjectedIntoTheResourceService(): void
	{
		$_ENV[self::ASSET_VERSION_ENVIRONMENT_VARIABLE] = 'release 2026/08';

		$mapper = new Mapper([], []);
		$mapper->matchRunner();
		$container = ApplicationContext::getContainer();

		$this->assertInstanceOf(Container::class, $container);
		$resource = $container->get(Resource::class);
		$this->assertInstanceOf(Resource::class, $resource);
		$resource
			->setBaseUrl('https://example.test')
			->addGenericCssFile('/assets/app.css?theme=dark#styles');

		$this->assertStringContainsString(
			'href="https://example.test/assets/app.css?theme=dark&amp;v=release%202026%2F08#styles"',
			$resource->renderHead()
		);
	}

	public function testTerminationIsPublishedAfterRunnerResolution(): void
	{
		$eventDispatcher = new Dispatcher();
		EventManager::setInstance($eventDispatcher);
		$events = [];
		$eventBus = EventManager::getEventBus();
		$eventBus->subscribe(OnBoot::class, static function () use (&$events): void {
			$events[] = 'boot';
		});
		$eventBus->subscribe(OnTerminate::class, static function () use (&$events): void {
			$events[] = 'terminate';
		});

		$mapper = new Mapper([], []);
		$mapper->matchRunner();

		$this->assertSame(['boot'], $events);

		$mapper->terminate();

		$this->assertSame(['boot', 'terminate'], $events);
	}
}
