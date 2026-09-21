<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes;

use ArrayObject as NativeArrayObject;
use Clover\Classes\ArrayObject;
use PHPUnit\Framework\TestCase;

final class ArrayObjectTest extends TestCase
{
	public function testRecognizesAnArrayAccessImplementation(): void
	{
		self::assertTrue(ArrayObject::isAccessible(new NativeArrayObject()));
	}

	public function testRejectsANonArrayValue(): void
	{
		self::assertFalse(ArrayObject::isAccessible('not-an-array'));
	}
}
