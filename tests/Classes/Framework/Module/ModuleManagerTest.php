<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Module;

use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Routing\Router;
use Clover\Framework\Module\ModuleManager;
use Clover\Framework\Module\ModuleRegistry;
use Clover\Implement\ModuleInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModuleManagerTest extends TestCase
{
	public function testHasAndGetDelegateToModuleRegistry(): void
	{
		$registry = new ModuleRegistry();
		$module = new ModuleManagerFixtureModule('board', []);
		$registry->register($module);
		$manager = new ModuleManager($registry, new Container());

		$this->assertTrue($manager->has('board'));
		$this->assertFalse($manager->has('missing'));
		$this->assertSame($module, $manager->get('board'));
	}

	public function testServiceReturnsExportedRegisteredObject(): void
	{
		$service = new ModuleManagerFixtureService();
		$container = new Container();
		$container->set(ModuleManagerFixtureContract::class, $service);
		$manager = $this->createManager([ModuleManagerFixtureContract::class], $container);

		$this->assertSame($service, $manager->service('board', ModuleManagerFixtureContract::class));
	}

	public function testServiceSupportsExportedOpaqueIdentifiers(): void
	{
		$identifier = 'board.publisher';
		$service = new ModuleManagerFixtureService();
		$container = new Container();
		$container->set($identifier, $service);
		$manager = $this->createManager([$identifier], $container);

		$this->assertSame($service, $manager->service('board', $identifier));
	}

	public function testServiceRejectsIdentifierNotExportedByModule(): void
	{
		$manager = $this->createManager([], new Container());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Service "board.publisher" is not exported by module "board".');

		$manager->service('board', 'board.publisher');
	}

	public function testServiceRejectsExportedIdentifierMissingFromContainer(): void
	{
		$manager = $this->createManager(['board.publisher'], new Container());

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Service "board.publisher" is not registered for module "board".');

		$manager->service('board', 'board.publisher');
	}

	public function testServiceRejectsResolvedNonObjectValue(): void
	{
		$identifier = 'board.publisher';
		$container = new Container();
		$container->set($identifier, 'scalar-value');
		$manager = $this->createManager([$identifier], $container);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Service "board.publisher" resolved to a non-object value.');

		$manager->service('board', $identifier);
	}

	public function testServiceRejectsObjectIncompatibleWithExportedType(): void
	{
		$container = new Container();
		$container->set(ModuleManagerFixtureContract::class, new ModuleManagerIncompatibleService());
		$manager = $this->createManager([ModuleManagerFixtureContract::class], $container);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('resolved to an incompatible object for module "board"');

		$manager->service('board', ModuleManagerFixtureContract::class);
	}

	/**
	 * Build a manager with one registered fixture module.
	 */
	private function createManager(array $exportedServiceIdentifiers, Container $container): ModuleManager
	{
		$registry = new ModuleRegistry();
		$registry->register(new ModuleManagerFixtureModule('board', $exportedServiceIdentifiers));

		return new ModuleManager($registry, $container);
	}
}

interface ModuleManagerFixtureContract
{
}

final class ModuleManagerFixtureService implements ModuleManagerFixtureContract
{
}

final class ModuleManagerIncompatibleService
{
}

final class ModuleManagerFixtureModule implements ModuleInterface
{
	/**
	 * @param string[] $exportedServiceIdentifiers
	 */
	public function __construct(
		private readonly string $name,
		private readonly array $exportedServiceIdentifiers,
	) {
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDependencies(): array
	{
		return [];
	}

	public function getExportedServiceIdentifiers(): array
	{
		return $this->exportedServiceIdentifiers;
	}

	public function register(Container $container): void
	{
	}

	public function boot(Container $container): void
	{
	}

	public function registerRoutes(Router $router): void
	{
	}
}
