<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Math;

use Clover\Annotation\Deprecated;
use Clover\Classes\Math\Energy;
use Clover\Enumeration\EnergeUnit;
use Clover\Enumeration\EnergyUnit;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class EnergyTest extends TestCase
{
	public function testCanonicalUnitConvertsKilowattHoursToJoules(): void
	{
		$result = Energy::convert(2.0, EnergyUnit::KILOWATT_HOUR, EnergyUnit::JOULE);

		$this->assertSame(7_200_000.0, $result);
	}

	public function testLegacyUnitNameRetainsTheCanonicalConstants(): void
	{
		$this->assertTrue(is_subclass_of(EnergeUnit::class, EnergyUnit::class));
		$this->assertSame(EnergyUnit::BTU, EnergeUnit::BTU);
		$this->assertSame(EnergyUnit::KILOCALORIE, EnergeUnit::KILOCALORIE);
	}

	public function testLegacyUnitNameIsMarkedDeprecated(): void
	{
		$attributes = (new ReflectionClass(EnergeUnit::class))->getAttributes(Deprecated::class);

		$this->assertCount(1, $attributes);
		$this->assertSame('Use EnergyUnit instead.', $attributes[0]->newInstance()->message);
	}

	public function testUnknownUnitDoesNotProduceAConversion(): void
	{
		$this->assertNull(Energy::convert(1.0, 'unknown', EnergyUnit::JOULE));
	}
}
