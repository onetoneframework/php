<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Array;

use ArrayObject as NativeArrayObject;
use ArrayIterator;
use Clover\Classes\ArrayObject;
use PHPUnit\Framework\TestCase;

final class ArrayObjectTest extends TestCase
{
	public function testKeyAndValueHelpersPreserveExpectedArraySemantics(): void
	{
		$values = ['first' => 'alpha', 'second' => 'beta', 'third' => 'alpha'];

		$this->assertSame(['first', 'third'], ArrayObject::getKeys($values, 'alpha', true));
		$this->assertSame('first', ArrayObject::getFirstKey($values));
		$this->assertSame('third', ArrayObject::getLastKey($values));
		$this->assertSame(['alpha', 'beta', 'alpha'], ArrayObject::getAllValues($values));
		$this->assertSame('second', ArrayObject::getIndexByValue('beta', $values, true));
		$this->assertFalse(ArrayObject::getIndexByValue('missing', $values, true));
		$this->assertSame('second', ArrayObject::getKeyByValue($values, 'beta'));
	}

	public function testAccessibilityAndTraversalDetection(): void
	{
		$this->assertTrue(ArrayObject::isAccessible(['value']));
		$this->assertTrue(ArrayObject::isAccessible(new NativeArrayObject(['value'])));
		$this->assertFalse(ArrayObject::isAccessible('value'));

		$this->assertTrue(ArrayObject::isTraversable(new ArrayIterator([1, 2, 3])));
		$this->assertFalse(ArrayObject::isTraversable([1, 2, 3]));
		$this->assertTrue(ArrayObject::isArray([]));
		$this->assertFalse(ArrayObject::isArray(new NativeArrayObject()));
	}

	public function testSetDeepCopyCreatesMissingNestedPath(): void
	{
		$data = [];

		ArrayObject::setDeepCopy($data, ['user', 'profile', 'name'], 'Ada');

		$this->assertSame(
			['user' => ['profile' => ['name' => 'Ada']]],
			$data
		);
	}

	public function testKeyExistenceRecognizesNullValues(): void
	{
		$this->assertTrue(ArrayObject::isKeyExists(['value' => null], 'value'));
		$this->assertFalse(ArrayObject::isKeyExists(['value' => null], 'missing'));
	}
}
