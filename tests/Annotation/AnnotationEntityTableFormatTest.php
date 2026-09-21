<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Annotation;

use Attribute;
use Clover\Annotation\Entity\Format;
use Clover\Annotation\Entity\Table;
use Clover\Classes\Database\ActiveRecord;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @Table(name="legacy_table_records")
 */
#[Table(name: 'attribute_table_records')]
final class AttributeTableRecordFixture extends ActiveRecord
{
}

/**
 * @Table(name="legacy_table_records")
 */
final class LegacyTableRecordFixture extends ActiveRecord
{
}

final class FormatAttributeRecordFixture extends ActiveRecord
{
	/**
	 * @Format(value="legacy-format")
	 */
	#[Format(value: 'Y-m-d')]
	public string $createdAt;
}

final class AnnotationEntityTableFormatTest extends TestCase
{
	public function testTableAttributeOverridesLegacyDocblock(): void
	{
		$record = new AttributeTableRecordFixture();

		$this->assertSame('attribute_table_records', $record->getTable());
	}

	public function testLegacyTableDocblockRemainsSupported(): void
	{
		$record = new LegacyTableRecordFixture();

		$this->assertSame('legacy_table_records', $record->getTable());
	}

	public function testFormatAttributeCanBeReadFromAProperty(): void
	{
		$record = new FormatAttributeRecordFixture();
		$property = (new ReflectionClass($record))->getProperty('createdAt');
		$attributes = $property->getAttributes(Format::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('Y-m-d', $attributes[0]->newInstance()->value);
	}

	public function testAttributesDeclareDomainSpecificTargets(): void
	{
		$tableMetadata = (new ReflectionClass(Table::class))->getAttributes(Attribute::class)[0]->newInstance();
		$formatMetadata = (new ReflectionClass(Format::class))->getAttributes(Attribute::class)[0]->newInstance();

		$this->assertSame(Attribute::TARGET_CLASS, $tableMetadata->flags);
		$this->assertSame(Attribute::TARGET_PROPERTY, $formatMetadata->flags);
	}
}
