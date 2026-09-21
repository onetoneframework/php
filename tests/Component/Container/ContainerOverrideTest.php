<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Component\Container;

use Clover\Component\Container\Container;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Covers the two container behaviours the custom-kernel feature depends on.
 *
 * Both were defects. An interface aliased to a framework default could never be pointed at an
 * application's own class, and `make()` could only return services that had already been
 * registered - so a kernel could not name its middleware by class.
 */
final class ContainerOverrideTest extends TestCase
{
	/**
	 * Resolution rewrites an identifier through its alias before it consults instances, so without
	 * this an alias registered first outranks every later binding of the same identifier - and the
	 * framework's default kernel could never be replaced.
	 */
	public function testConcreteBindingOutranksAnAliasRegisteredEarlier(): void
	{
		$container = new Container();
		$container->bind(ContainerOverrideContract::class, ContainerOverrideDefault::class);

		$replacement = new ContainerOverrideReplacement();
		$container->singleton(ContainerOverrideContract::class, $replacement);

		$this->assertSame($replacement, $container->make(ContainerOverrideContract::class));
	}

	public function testBindAlsoClearsAnEarlierAlias(): void
	{
		$container = new Container();
		$container->bind(ContainerOverrideContract::class, ContainerOverrideDefault::class);
		$container->bind(ContainerOverrideContract::class, ContainerOverrideReplacement::class);

		$this->assertInstanceOf(
			ContainerOverrideReplacement::class,
			$container->make(ContainerOverrideContract::class)
		);
	}

	/**
	 * A kernel lists its middleware by class name and a route names its controller by class name;
	 * neither is registered anywhere, and both still have to be constructible.
	 */
	public function testMakeBuildsAConcreteClassThatWasNeverRegistered(): void
	{
		$container = new Container();

		$this->assertInstanceOf(
			ContainerOverrideDefault::class,
			$container->make(ContainerOverrideDefault::class)
		);
	}

	public function testMakeInjectsConstructorDependenciesThatAreRegistered(): void
	{
		$container = new Container();
		$dependency = new stdClass();
		$dependency->marker = 'injected';
		$container->singleton(stdClass::class, $dependency);

		$resolved = $container->make(ContainerOverrideConsumer::class);

		$this->assertInstanceOf(ContainerOverrideConsumer::class, $resolved);
		$this->assertSame($dependency, $resolved->dependency);
	}

	/**
	 * A registered singleton must still be returned as the same instance; making `make()` able to
	 * build things must not turn every resolution into a fresh object.
	 */
	public function testRegisteredSingletonStillResolvesToTheSameInstance(): void
	{
		$container = new Container();
		$instance = new ContainerOverrideDefault();
		$container->singleton(ContainerOverrideDefault::class, $instance);

		$this->assertSame($instance, $container->make(ContainerOverrideDefault::class));
		$this->assertSame($instance, $container->make(ContainerOverrideDefault::class));
	}

	public function testUnknownIdentifierThatIsNotAClassStillFails(): void
	{
		$container = new Container();

		$this->expectExceptionMessageMatches('/Service not found/');

		$container->make('nothing.is.registered.under.this');
	}
}

interface ContainerOverrideContract
{
}

class ContainerOverrideDefault implements ContainerOverrideContract
{
}

class ContainerOverrideReplacement implements ContainerOverrideContract
{
}

class ContainerOverrideConsumer
{
	public stdClass $dependency;

	public function __construct(stdClass $dependency)
	{
		$this->dependency = $dependency;
	}
}
