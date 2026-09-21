<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Module;

use Clover\Framework\Module\Exception\ModuleNotFoundException;
use Clover\Framework\Module\ModuleRegistry;
use Clover\Implement\ModuleInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModuleRegistryTest extends TestCase
{
	public function testRegistersAndRetrievesModulesByName(): void
	{
		$registry = new ModuleRegistry();
		$module = new ModuleRegistryFixtureModule('board');

		$registry->register($module);

		$this->assertTrue($registry->has('board'));
		$this->assertSame($module, $registry->get('board'));
		$this->assertSame(['board'], $registry->names());
	}

	public function testMissingModuleThrowsNotFoundException(): void
	{
		$registry = new ModuleRegistry();

		$this->expectException(ModuleNotFoundException::class);

		$registry->get('missing');
	}

	public function testDuplicateModuleNameIsRejected(): void
	{
		$registry = new ModuleRegistry();
		$registry->register(new ModuleRegistryFixtureModule('board'));

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('already registered');

		$registry->register(new ModuleRegistryFixtureModule('board'));
	}
}

final class ModuleRegistryFixtureModule implements ModuleInterface
{
	public function __construct(private readonly string $name)
	{
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
		return [];
	}

	public function register(\Clover\Classes\DependencyInjection\Container $container): void
	{
	}

	public function boot(\Clover\Classes\DependencyInjection\Container $container): void
	{
	}

	public function registerRoutes(\Clover\Classes\Routing\Router $router): void
	{
	}
}
