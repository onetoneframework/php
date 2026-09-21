<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\DependencyInjection;

use Clover\Classes\DependencyInjection\Container;
use Exception;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RuntimeContainerTest extends TestCase
{
	public function testObjectRegistrationReturnsSameInstanceAndSupportsTypeLookup(): void
	{
		$container = new Container();
		$service = new RuntimeContainerDependency();

		self::assertTrue($container->set('dependency', $service));
		self::assertSame($service, $container->get('dependency'));
		self::assertSame($service, $container->getByType(RuntimeContainerDependency::class));
		self::assertSame(['dependency' => $service], $container->getAll());
	}

	public function testFactoryReceivesContainerAndRunsOnlyOnce(): void
	{
		$container = new Container();
		$factoryCalls = 0;
		$container->set('factory', static function (Container $receivedContainer) use ($container, &$factoryCalls): stdClass {
			self::assertSame($container, $receivedContainer);
			$factoryCalls++;

			return new stdClass();
		});

		$first = $container->get('factory');
		$second = $container->get('factory');

		self::assertSame($first, $second);
		self::assertSame(1, $factoryCalls);
	}

	public function testClassDefinitionAutowiresRegisteredDependencyAndIsCached(): void
	{
		$container = new Container();
		$dependency = new RuntimeContainerDependency();
		$container->set(RuntimeContainerDependency::class, $dependency);
		$container->set(RuntimeContainerService::class, RuntimeContainerService::class);

		$service = $container->get(RuntimeContainerService::class);

		self::assertInstanceOf(RuntimeContainerService::class, $service);
		self::assertSame($dependency, $service->dependency);
		self::assertSame($service, $container->get(RuntimeContainerService::class));
	}

	public function testAliasResolvesCanonicalConcreteService(): void
	{
		$container = new Container();
		$container->set(RuntimeContainerImplementation::class, RuntimeContainerImplementation::class);
		$container->bind(RuntimeContainerContract::class, RuntimeContainerImplementation::class);

		$fromAlias = $container->get(RuntimeContainerContract::class);

		self::assertInstanceOf(RuntimeContainerImplementation::class, $fromAlias);
		self::assertSame($fromAlias, $container->get(RuntimeContainerImplementation::class));
	}

	public function testObjectRegistrationCanBeOverriddenByFactory(): void
	{
		$container = new Container();
		$first = new stdClass();
		$second = new stdClass();
		$factoryCalls = 0;
		$container->set('service', $first);

		self::assertTrue($container->set(
			'service',
			static function (Container $receivedContainer) use ($container, $second, &$factoryCalls): stdClass {
				self::assertSame($container, $receivedContainer);
				$factoryCalls++;

				return $second;
			}
		));

		self::assertSame($second, $container->get('service'));
		self::assertSame($second, $container->get('service'));
		self::assertSame(1, $factoryCalls);
	}

	public function testResolvedFactoryCanBeOverriddenByObject(): void
	{
		$container = new Container();
		$first = new stdClass();
		$second = new stdClass();
		$container->set('service', static fn(Container $receivedContainer): stdClass => $first);

		self::assertSame($first, $container->get('service'));
		self::assertTrue($container->set('service', $second));
		self::assertSame($second, $container->get('service'));
	}

	public function testRegistrationCanPreserveExistingInstanceWhenFactoryOverrideIsDisabled(): void
	{
		$container = new Container();
		$first = new stdClass();
		$factoryCalls = 0;
		$container->set('service', $first);

		self::assertFalse($container->set(
			'service',
			static function (Container $receivedContainer) use ($container, &$factoryCalls): stdClass {
				self::assertSame($container, $receivedContainer);
				$factoryCalls++;

				return new stdClass();
			},
			false
		));
		self::assertSame($first, $container->get('service'));
		self::assertSame(0, $factoryCalls);
	}

	public function testNamedArgumentsPreserveFalsyValues(): void
	{
		$container = new Container();
		$container->set(RuntimeContainerConfiguredService::class, RuntimeContainerConfiguredService::class);
		self::assertTrue($container->setPassArguments(RuntimeContainerConfiguredService::class, [
			'enabled' => false,
			'count' => 0,
			'label' => '',
			'note' => null,
		]));

		$service = $container->get(RuntimeContainerConfiguredService::class);

		self::assertFalse($service->enabled);
		self::assertSame(0, $service->count);
		self::assertSame('', $service->label);
		self::assertNull($service->note);
	}

	public function testDefaultNullableAndVariadicParametersAreResolved(): void
	{
		$container = new Container();
		$container->set(RuntimeContainerDefaultService::class, RuntimeContainerDefaultService::class);
		$container->set(RuntimeContainerVariadicService::class, RuntimeContainerVariadicService::class);
		$container->setPassArguments(RuntimeContainerVariadicService::class, ['items' => ['first', 'second']]);

		$defaultService = $container->get(RuntimeContainerDefaultService::class);
		$variadicService = $container->get(RuntimeContainerVariadicService::class);

		self::assertSame('default', $defaultService->name);
		self::assertNull($defaultService->dependency);
		self::assertSame(['first', 'second'], $variadicService->items);
	}

	public function testCircularDependencyFailureDoesNotPoisonLaterResolution(): void
	{
		$container = new Container();
		$container->set(RuntimeContainerCircularFirst::class, RuntimeContainerCircularFirst::class);
		$container->set(RuntimeContainerCircularSecond::class, RuntimeContainerCircularSecond::class);

		try {
			$container->get(RuntimeContainerCircularFirst::class);
			self::fail('Circular dependencies must be rejected.');
		} catch (Exception $exception) {
			self::assertStringContainsString('Circular dependency detected', $exception->getMessage());
		}

		$safeService = new stdClass();
		$container->set('safe', $safeService);
		self::assertSame($safeService, $container->get('safe'));
	}

	public function testRequiredScalarDependencyIsRejected(): void
	{
		$container = new Container();
		$container->set(RuntimeContainerScalarService::class, RuntimeContainerScalarService::class);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Cannot resolve parameter 'name'");

		$container->get(RuntimeContainerScalarService::class);
	}
}

final class RuntimeContainerDependency
{
}

final class RuntimeContainerService
{
	public function __construct(public readonly RuntimeContainerDependency $dependency)
	{
	}
}

interface RuntimeContainerContract
{
}

final class RuntimeContainerImplementation implements RuntimeContainerContract
{
}

final class RuntimeContainerConfiguredService
{
	public function __construct(
		public readonly bool $enabled,
		public readonly int $count,
		public readonly string $label,
		public readonly ?string $note
	) {
	}
}

final class RuntimeContainerDefaultService
{
	public function __construct(
		public readonly string $name = 'default',
		public readonly ?RuntimeContainerDependency $dependency = null
	) {
	}
}

final class RuntimeContainerVariadicService
{
	/** @var list<string> */
	public readonly array $items;

	public function __construct(string ...$items)
	{
		$this->items = $items;
	}
}

final class RuntimeContainerCircularFirst
{
	public function __construct(public readonly RuntimeContainerCircularSecond $second)
	{
	}
}

final class RuntimeContainerCircularSecond
{
	public function __construct(public readonly RuntimeContainerCircularFirst $first)
	{
	}
}

final class RuntimeContainerScalarService
{
	public function __construct(public readonly string $name)
	{
	}
}
