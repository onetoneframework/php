<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Tests\Classes\Debug;

use Clover\Classes\Debug\TraceAttributeObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * TraceAttributeObject is declared but unfinished: three private properties, no
 * constructor, no accessors, and nothing in the tree constructs it —
 * `TraceObject::parseClass()` formats attributes into a string instead. These
 * tests state exactly that, so the shape it grows into is a deliberate choice
 * rather than an accident.
 *
 * Note the name mismatch the file carries: `TraceAttributesObject.php` declares
 * `TraceAttributeObject` (singular), which is why it only loads through the
 * whole-tree classmap and not through PSR-4.
 */
class TraceAttributesObjectTest extends TestCase
{
	public function testIsInstantiable(): void
	{
		$this->assertInstanceOf(TraceAttributeObject::class, new TraceAttributeObject());
	}

	public function testItHasNoBehaviourYet(): void
	{
		$reflection = new ReflectionClass(TraceAttributeObject::class);

		$this->assertSame([], $reflection->getMethods(), 'Adding a method here means this test needs updating.');
		$this->assertNull($reflection->getConstructor());
	}

	public function testItDeclaresTheThreeAttributeFieldsAsPrivate(): void
	{
		$reflection = new ReflectionClass(TraceAttributeObject::class);
		$names = array_map(static fn ($property): string => $property->getName(), $reflection->getProperties());

		sort($names);

		$this->assertSame(['arguments', 'name', 'target'], $names);

		foreach ($reflection->getProperties() as $property) {
			$this->assertTrue($property->isPrivate(), $property->getName() . ' is private with no accessor.');
		}
	}

	public function testTheClassNameDoesNotMatchItsFileName(): void
	{
		$reflection = new ReflectionClass(TraceAttributeObject::class);

		$this->assertSame('TraceAttributeObject', $reflection->getShortName());
		$this->assertSame('TraceAttributesObject.php', basename((string) $reflection->getFileName()));
	}
}
