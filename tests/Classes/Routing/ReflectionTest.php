<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Reflection\Handler as ReflectionHandler;
use PHPUnit\Framework\TestCase;

class MagicMethod
{
    public function __call($name, $args)
    {
    }
}

class ReflectiveClass extends MagicMethod
{
    private int $intProperty = 1;
    private int $nonDefaultProperty;

    public function __construct()
    {
    }

    public final function final() {

    }

    public function test(): bool
    {
        return true;
    }

    public function requriedCountOfMethod($a, $b, $c = null)
    {
    }

    public function parameterCountOfMethod(int $a, $b, $c)
    {
    }
}

class ReflectionTest extends TestCase
{
    private $reflectiveClass;

    public function setUp(): void
    {
        $this->reflectiveClass = new ReflectiveClass();
    }

    public function testReflection(): void
    {
        $this->assertEquals('int', ReflectionHandler::getClassPropertyMetadata($this->reflectiveClass)->get(0)->get('type'));
        $this->assertEquals('intProperty', ReflectionHandler::getClassInnerVariables($this->reflectiveClass)['intProperty']->getName());
        $this->assertEquals(MagicMethod::class, ReflectionHandler::getInheritanceChain($this->reflectiveClass)[0]);
        $this->assertEquals(true, ReflectionHandler::isMethodFinal($this->reflectiveClass, 'final'));
        $this->assertEquals('bool', ReflectionHandler::getMethodReturnType($this->reflectiveClass, 'test'));
        $this->assertEquals(1, ReflectionHandler::getPropertyValue($this->reflectiveClass, 'intProperty'));
        $this->assertEquals('private', ReflectionHandler::getVisibility($this->reflectiveClass, 'intProperty'));
        $this->assertEquals(MagicMethod::class, ReflectionHandler::getParentClassName($this->reflectiveClass));
        $this->assertEquals('a', ReflectionHandler::getMethodParameterNames($this->reflectiveClass, 'parameterCountOfMethod', false)[0]?->getName());
        $this->assertEquals(true, ReflectionHandler::isSubClassOf($this->reflectiveClass, MagicMethod::class));
        $this->assertEquals(false, ReflectionHandler::isMethodExists($this->reflectiveClass, 'nonExistMethod'));
        $this->assertEquals(false, ReflectionHandler::hasDefaultValue($this->reflectiveClass, 'nonDefaultProperty'));
        $this->assertEquals('intProperty', ReflectionHandler::getProperty($this->reflectiveClass, 'intProperty')->getName());
        $this->assertEquals('test', ReflectionHandler::getMethodByName($this->reflectiveClass, 'test')->getName());
        $this->assertEquals('__call', ReflectionHandler::getAvailableMagicMethodsInParentClass($this->reflectiveClass)[0]);
        $this->assertEquals('intProperty', ReflectionHandler::getClassPropertiesNames($this->reflectiveClass)[0]);
        $this->assertEquals(2, ReflectionHandler::getRequiredParametersCountOfClassMethod($this->reflectiveClass, 'requriedCountOfMethod'));
        $this->assertEquals(3, ReflectionHandler::getParametersCountOfClassMethod($this->reflectiveClass, 'parameterCountOfMethod'));
        $this->assertEquals('ReflectiveClass', ReflectionHandler::getClassShortName($this->reflectiveClass));
        $this->assertEquals('ReflectiveClass', ReflectionHandler::getClassBasename($this->reflectiveClass));
    }
}
