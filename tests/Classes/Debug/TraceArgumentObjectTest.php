<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Tests\Classes\Debug;

use Clover\Classes\Debug\TraceArgumentObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SensitiveParameter;

/**
 * TraceArgumentObject holds one reflected parameter for a rendered stack frame.
 * It exposes setters but getters only for the argument list, so the remaining
 * state is read back through reflection — that is the whole public contract
 * there is to pin.
 */
class TraceArgumentObjectTest extends TestCase
{
	/**
	 * Read a private property, since the class has no getter for most of them.
	 */
	private function propertyOf(TraceArgumentObject $argument, string $name): mixed
	{
		$property = new ReflectionProperty(TraceArgumentObject::class, $name);
		$property->setAccessible(true);

		return $property->isInitialized($argument) ? $property->getValue($argument) : null;
	}

	public function testIsInstantiable(): void
	{
		$this->assertInstanceOf(TraceArgumentObject::class, new TraceArgumentObject());
	}

	public function testAFreshArgumentHasNoArguments(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertFalse($argument->hasArguments());
		$this->assertSame([], $argument->getArguments());
		$this->assertSame([], $argument->getArgumentText());
	}

	public function testSetVariadic(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertFalse($this->propertyOf($argument, 'variadic'), 'Not variadic by default.');
		$argument->setVariadic(true);
		$this->assertTrue($this->propertyOf($argument, 'variadic'));
	}

	public function testSetSensitiveAttributes(): void
	{
		$argument = new TraceArgumentObject();
		$attributes = (new \ReflectionMethod(SensitiveArgumentFixture::class, 'login'))
			->getParameters()[1]
			->getAttributes(SensitiveParameter::class);

		$this->assertSame([], $this->propertyOf($argument, 'sensitiveAttributes'));
		$this->assertNotSame([], $attributes, 'The fixture parameter carries #[SensitiveParameter].');

		$argument->setSensitiveAttributes($attributes);

		$this->assertSame($attributes, $this->propertyOf($argument, 'sensitiveAttributes'));
	}

	public function testSetType(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertNull($this->propertyOf($argument, 'type'), 'The type property starts uninitialised.');
		$argument->setType('?string');
		$this->assertSame('?string', $this->propertyOf($argument, 'type'));
	}

	public function testSetName(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertNull($this->propertyOf($argument, 'name'));
		$argument->setName('password');
		$this->assertSame('password', $this->propertyOf($argument, 'name'));
	}

	public function testSetPassedByReference(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertFalse($this->propertyOf($argument, 'passedByReference'));
		$argument->setPassedByReference(true);
		$this->assertTrue($this->propertyOf($argument, 'passedByReference'));
	}

	public function testSetDefaultValue(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertNull($this->propertyOf($argument, 'defaultValue'));
		$argument->setDefaultValue(['a' => 1]);
		$this->assertSame(['a' => 1], $this->propertyOf($argument, 'defaultValue'));

		$argument->setDefaultValue(null);
		$this->assertNull($this->propertyOf($argument, 'defaultValue'));
	}

	public function testSetArguments(): void
	{
		$argument = new TraceArgumentObject();

		// setArguments() appends rather than replaces, despite the name.
		$argument->setArguments('string');
		$argument->setArguments('$value');

		$this->assertSame(['string', '$value'], $argument->getArguments());
	}

	public function testHasArguments(): void
	{
		$argument = new TraceArgumentObject();

		$this->assertFalse($argument->hasArguments());
		$argument->setArguments('string');
		$this->assertTrue($argument->hasArguments());
	}

	public function testAnAppendedEmptyArrayStillCountsAsAnArgument(): void
	{
		$argument = new TraceArgumentObject();
		$argument->setArguments([]);

		$this->assertTrue($argument->hasArguments());
		$this->assertSame([[]], $argument->getArguments());
	}

	public function testGetArguments(): void
	{
		$argument = new TraceArgumentObject();
		$argument->setArguments(['int', '$count']);

		$this->assertSame([['int', '$count']], $argument->getArguments());
	}

	public function testGetArgumentText(): void
	{
		$argument = new TraceArgumentObject();
		$argument->setArguments(['int', '$count']);
		$argument->setArguments('$plain');

		$this->assertSame(['int, $count', '$plain'], $argument->getArgumentText());
	}
}

class SensitiveArgumentFixture
{
	public function login(string $user, #[SensitiveParameter] string $password): void
	{
	}
}
