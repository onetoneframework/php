<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Reflection;

use Clover\Classes\Reflection\Handler;
use ReflectionReference;
use stdClass;
use WeakMap;
use PHPUnit\Framework\TestCase;

final class HandlerTest extends TestCase
{
	public function testReturnsAReferenceForAReferencedArrayElement(): void
	{
		$value = 'referenced';
		$array = [];
		$array['value'] =& $value;

		$reference = Handler::getArrayElementReference($array, 'value');

		self::assertInstanceOf(ReflectionReference::class, $reference);
	}

	public function testReturnsNullForANonReferencedArrayElement(): void
	{
		$array = ['value' => 'not-referenced'];

		self::assertNull(Handler::getArrayElementReference($array, 'value'));
	}

	public function testCreatesAUsableWeakMap(): void
	{
		$weakMap = Handler::createWeakMap();
		$key = new stdClass();
		$weakMap[$key] = 'stored';

		self::assertInstanceOf(WeakMap::class, $weakMap);
		self::assertSame('stored', $weakMap[$key]);
	}
}
