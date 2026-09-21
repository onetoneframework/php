<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Annotation;

use Clover\Annotation\Autowire;
use Clover\Annotation\Autowiring;
use Clover\Annotation\Deprecated;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\DependencyInjection\Injector;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Context\ApplicationContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class AutowireTest extends TestCase
{
	protected function tearDown(): void
	{
		ApplicationContext::setContainer(new Container());

		parent::tearDown();
	}

	public function testCanonicalAttributeExposesTheServiceIdentifier(): void
	{
		$attribute = new Autowire('service.identifier');

		$this->assertSame('service.identifier', $attribute->value);
	}

	public function testCanonicalAttributeInjectsARegisteredDependency(): void
	{
		$dependency = new stdClass();
		$fixture = new CanonicalAutowireFixture();
		$this->configureContainer($dependency);

		Injector::inject($fixture, $fixture, true);

		$this->assertSame($dependency, $fixture->dependency());
	}

	public function testLegacyAttributeRemainsCompatibleAndDeprecated(): void
	{
		$dependency = new stdClass();
		$fixture = new LegacyAutowiringFixture();
		$this->configureContainer($dependency);

		Injector::inject($fixture, $fixture, true);

		$attributes = (new ReflectionClass(Autowiring::class))->getAttributes(Deprecated::class);
		$this->assertSame($dependency, $fixture->dependency());
		$this->assertCount(1, $attributes);
		$this->assertSame('Use Autowire instead.', $attributes[0]->newInstance()->message);
	}

	public function testMissingDependencyLeavesThePropertyUninitialized(): void
	{
		$fixture = new CanonicalAutowireFixture();
		ApplicationContext::setContainer(new Container());

		Injector::inject($fixture, $fixture, true);

		$this->assertFalse($fixture->hasDependency());
	}

	private function configureContainer(stdClass $dependency): void
	{
		$container = new Container();
		$container->set(stdClass::class, $dependency);
		ApplicationContext::setContainer($container);
	}
}

final class CanonicalAutowireFixture extends BaseController
{
	#[Autowire]
	private stdClass $dependency;

	public function dependency(): stdClass
	{
		return $this->dependency;
	}

	public function hasDependency(): bool
	{
		return isset($this->dependency);
	}
}

final class LegacyAutowiringFixture extends BaseController
{
	#[Autowiring]
	private stdClass $dependency;

	public function dependency(): stdClass
	{
		return $this->dependency;
	}
}
