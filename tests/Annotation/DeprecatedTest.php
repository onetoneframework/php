<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Annotation;

use Clover\Annotation\Deprecated;
use Clover\Plugin\NaverPapago;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class DeprecatedTest extends TestCase
{
	public function testClassAttributeExposesDeprecationMessage(): void
	{
		$attributes = (new ReflectionClass(DeprecatedClassFixture::class))->getAttributes(Deprecated::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('Use the replacement class.', $attributes[0]->newInstance()->message);
	}

	public function testMethodAttributeSupportsAnEmptyMessage(): void
	{
		$attributes = (new ReflectionMethod(DeprecatedClassFixture::class, 'legacyMethod'))->getAttributes(Deprecated::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('', $attributes[0]->newInstance()->message);
	}

	public function testNaverPapagoDeprecationAttributesAreInstantiable(): void
	{
		$classAttributes = (new ReflectionClass(NaverPapago::class))->getAttributes(Deprecated::class);
		$methodAttributes = (new ReflectionMethod(NaverPapago::class, 'translate'))->getAttributes(Deprecated::class);

		$this->assertInstanceOf(Deprecated::class, $classAttributes[0]->newInstance());
		$this->assertInstanceOf(Deprecated::class, $methodAttributes[0]->newInstance());
	}
}

#[Deprecated('Use the replacement class.')]
final class DeprecatedClassFixture
{
	#[Deprecated]
	public function legacyMethod(): void
	{
	}
}
