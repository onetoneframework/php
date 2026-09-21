<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Medical;

use Clover\Enumeration\Medical\DSMVDisorder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DSMVDisorderTest extends TestCase
{
	#[DataProvider('disorderProvider')]
	public function testEveryDisorderHasLabelAndRoundTripsThroughItsCategory(DSMVDisorder $disorder): void
	{
		$this->assertNotSame('', $disorder->label());
		$this->assertNotSame('', $disorder->category());
		$this->assertContains($disorder, DSMVDisorder::byCategory($disorder->category()));
	}

	#[DataProvider('disorderProvider')]
	public function testMoodAndPsychoticPredicatesMatchCategoryRanges(DSMVDisorder $disorder): void
	{
		$this->assertSame(
			$disorder->value >= 200 && $disorder->value < 300,
			$disorder->isPsychotic()
		);
		$this->assertSame(
			$disorder->value >= 300 && $disorder->value < 500,
			$disorder->isMoodDisorder()
		);
	}

	public static function disorderProvider(): array
	{
		$result = [];

		foreach (DSMVDisorder::cases() as $disorder) {
			$result[$disorder->name] = [$disorder];
		}

		return $result;
	}
}
