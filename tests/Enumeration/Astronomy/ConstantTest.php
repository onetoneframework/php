<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Astronomy;

use Clover\Enumeration\Astronomy\Constant;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ConstantTest extends TestCase
{
	#[DataProvider('constantProvider')]
	public function testEveryAstronomyConstantHasPositiveFiniteValue(Constant $constant): void
	{
		$value = $constant->value();

		$this->assertGreaterThan(0.0, $value);
		$this->assertFalse(is_infinite($value));
		$this->assertFalse(is_nan($value));
	}

	public function testDerivedHalleyDistancesUseAstronomicalUnitConstant(): void
	{
		$this->assertEqualsWithDelta(
			Constant::HalleyPerihelionAu->value() * Constant::AuMeters->value(),
			Constant::HalleyPerihelionMeters->value(),
			1.0
		);
		$this->assertEqualsWithDelta(
			Constant::HalleySemiMajorAxisAu->value() * Constant::AuMeters->value(),
			Constant::HalleySemiMajorAxisMeters->value(),
			1.0
		);
	}

	public static function constantProvider(): array
	{
		$result = [];

		foreach (Constant::cases() as $constant) {
			$result[$constant->name] = [$constant];
		}

		return $result;
	}
}
