<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\Attributes;
use Clover\Exception\AI\AttributeNotFoundException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AttributesTest extends TestCase
{
	private const EXPECTED_SCALAR_ATTRIBUTE_COUNT = 4;

	public function testScalarAttributesPreserveTypesAndIterationOrder(): void
	{
		$attributes = new Attributes([
			'text' => 'value',
			'count' => 0,
			'ratio' => 0.5,
			'enabled' => false,
		]);

		self::assertSame(self::EXPECTED_SCALAR_ATTRIBUTE_COUNT, $attributes->count());
		self::assertSame('value', $attributes->get('text'));
		self::assertSame(0, $attributes->get('count'));
		self::assertSame(0.5, $attributes->get('ratio'));
		self::assertFalse($attributes->get('enabled'));
		self::assertSame([
			'text' => 'value',
			'count' => 0,
			'ratio' => 0.5,
			'enabled' => false,
		], iterator_to_array($attributes));
	}

	public function testWithReturnsIndependentCollection(): void
	{
		$original = new Attributes(['topic' => 'original']);

		$changed = $original->with('topic', 'changed');

		self::assertSame('original', $original->get('topic'));
		self::assertSame('changed', $changed->get('topic'));
		self::assertNotSame($original, $changed);
	}

	public function testMissingAttributeThrowsTypedException(): void
	{
		$attributes = new Attributes();

		$this->expectException(AttributeNotFoundException::class);
		$this->expectExceptionMessage('"missing" was not found');

		$attributes->get('missing');
	}

	public function testBlankAttributeKeyIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('keys must be non-blank strings');

		new Attributes([' ' => 'value']);
	}

	public function testNonScalarAttributeValueIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('must contain scalar values');

		new Attributes(['nested' => []]);
	}

	public function testOversizedAttributeKeyIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('keys must be non-blank strings within the size limit');

		new Attributes([
			str_repeat('k', Attributes::MAXIMUM_KEY_BYTES + 1) => 'value',
		]);
	}

	public function testOversizedStringValueIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('string attributes exceed the size limit');

		new Attributes([
			'content' => str_repeat('x', Attributes::MAXIMUM_STRING_VALUE_BYTES + 1),
		]);
	}

	public function testAttributeCountAboveLimitIsRejected(): void
	{
		$values = [];
		for ($index = 0; $index <= Attributes::MAXIMUM_COUNT; $index++) {
			$values['key-' . $index] = $index;
		}

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('collection size limit');

		new Attributes($values);
	}
}
