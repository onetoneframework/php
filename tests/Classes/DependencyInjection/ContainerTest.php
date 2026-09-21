<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\DependencyInjection;

use Clover\Component\Container\Container;
use Clover\Contract\ContainerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    public function testItImplementsPsrContainerInterface(): void
    {
        $container = new Container();
        $this->assertInstanceOf(ContainerInterface::class, $container);
    }

    public function testSetAndGet(): void
    {
        $container = new Container();
        $object = new stdClass();
        $object->foo = 'bar';

        $container->set('my_service', $object);

        $this->assertTrue($container->has('my_service'));
        $this->assertSame($object, $container->get('my_service'));
    }

    public function testGetThrowsNotFoundException(): void
    {
        $container = new Container();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service not found: non_existent_service');
        $container->get('non_existent_service');
    }

    public function testAutowiring(): void
    {
        $container = new Container();

        // Register a dependency
        $dependency = new stdClass();
        $dependency->name = 'dependency';
        $container->set(stdClass::class, $dependency);

        // ClassWithDependency needs stdClass
        $instance = $container->autowire(ClassWithDependency::class);

        $this->assertInstanceOf(ClassWithDependency::class, $instance);
        $this->assertSame($dependency, $instance->dependency);
    }

    public function testSingletonBindingReturnsSameInstance(): void
    {
        $container = new Container();
        $config = new stdClass();
        $config->app = 'framework';

        $container->singleton('config', $config);
        $first = $container->make('config');
        $second = $container->make('config');

        $this->assertSame($first, $second);
        $this->assertSame('framework', $first->app);
    }

    public function testBindUsesAbstractWhenConcreteIsNull(): void
    {
        $container = new Container();
        $container->bind(ClassWithoutDependency::class);

        $service = $container->make(ClassWithoutDependency::class);

        $this->assertInstanceOf(ClassWithoutDependency::class, $service);
    }
}

class ClassWithDependency
{
    public function __construct(public stdClass $dependency)
    {
    }
}

class ClassWithoutDependency
{
}
