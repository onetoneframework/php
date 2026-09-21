<?php

declare(strict_types=1);

namespace Clover\Tests\Component\Container;

use Clover\Component\Container\Container;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
	public function testMakeResolvesClassWithNamedParameters(): void
	{
		$container = new Container();

		$first = $container->make(ParameterAwareService::class, ['name' => 'alpha', 'count' => 3]);
		$second = $container->make(ParameterAwareService::class, ['name' => 'beta', 'count' => 7]);

		$this->assertInstanceOf(ParameterAwareService::class, $first);
		$this->assertInstanceOf(ParameterAwareService::class, $second);
		$this->assertSame('alpha', $first->name);
		$this->assertSame(3, $first->count);
		$this->assertSame('beta', $second->name);
		$this->assertSame(7, $second->count);
		$this->assertNotSame($first, $second);
	}

	public function testMakeResolvesAliasedBindingsWithParameters(): void
	{
		$container = new Container();
		$container->bind(ParameterAwareInterface::class, ParameterAwareImplementation::class);

		$service = $container->make(ParameterAwareInterface::class, ['name' => 'gamma']);

		$this->assertInstanceOf(ParameterAwareInterface::class, $service);
		$this->assertSame('gamma', $service->name);
	}

	public function testMakePreservesFalsyConstructorParameters(): void
	{
		$container = new Container();

		$service = $container->make(FalsyParameterAwareService::class, [
			'enabled' => false,
			'count' => 0,
			'label' => null,
			'name' => '',
		]);

		$this->assertFalse($service->enabled);
		$this->assertSame(0, $service->count);
		$this->assertNull($service->label);
		$this->assertSame('', $service->name);
	}

	public function testMakeDoesNotPassSyntheticArgumentToEmptyConstructor(): void
	{
		$container = new Container();
		$container->bind(EmptyConstructorService::class);

		$service = $container->make(EmptyConstructorService::class);

		$this->assertSame(0, $service->argumentCount);
	}

	public function testSingletonFactoryIsLazyAndResolvedOnlyOnce(): void
	{
		$container = new Container();
		$factoryCalls = 0;
		$container->singleton(SingletonFactoryService::class, static function (Container $resolvedContainer) use (&$factoryCalls): SingletonFactoryService {
			$factoryCalls++;

			return new SingletonFactoryService($resolvedContainer);
		});

		$this->assertSame(0, $factoryCalls);

		$first = $container->make(SingletonFactoryService::class);
		$second = $container->make(SingletonFactoryService::class);

		$this->assertSame(1, $factoryCalls);
		$this->assertSame($first, $second);
		$this->assertSame($container, $first->container);
	}

	public function testSingletonWithoutExplicitConcreteBuildsAndCachesItsClass(): void
	{
		$container = new Container();
		$container->singleton(DefaultSingletonService::class);

		$first = $container->make(DefaultSingletonService::class);
		$second = $container->make(DefaultSingletonService::class);

		$this->assertInstanceOf(DefaultSingletonService::class, $first);
		$this->assertSame($first, $second);
	}
}

interface ParameterAwareInterface
{
}

final class ParameterAwareImplementation implements ParameterAwareInterface
{
	public function __construct(public string $name)
	{
	}
}

final class ParameterAwareService
{
	public function __construct(public string $name, public int $count)
	{
	}
}

final class FalsyParameterAwareService
{
	public function __construct(
		public bool $enabled,
		public int $count,
		public ?string $label,
		public string $name
	) {
	}
}

final class EmptyConstructorService
{
	public int $argumentCount;

	public function __construct()
	{
		$this->argumentCount = func_num_args();
	}
}

final class SingletonFactoryService
{
	public function __construct(public Container $container)
	{
	}
}

final class DefaultSingletonService
{
}
