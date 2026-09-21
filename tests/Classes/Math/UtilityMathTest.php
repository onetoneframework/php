<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Math;

use Clover\Classes\Math\ActivationFunctions;
use Clover\Classes\Math\Area;
use Clover\Classes\Math\Derivatives;
use Clover\Classes\Math\Mass;
use Clover\Classes\Math\PrimeNumber;
use Clover\Classes\Math\Search;
use Clover\Classes\Math\Sequences;
use Clover\Classes\Math\Series;
use Clover\Classes\Math\Temperature;
use Clover\Enumeration\AreaUnit;
use Clover\Enumeration\MessUnit;
use Clover\Enumeration\TemperatureUnit;
use PHPUnit\Framework\TestCase;

final class UtilityMathTest extends TestCase
{
	public function testPrimeUtilitiesCoverBoundaryCompositeAndPseudoprimeCases(): void
	{
		$this->assertFalse(PrimeNumber::isPrime(-1));
		$this->assertFalse(PrimeNumber::isPrime(1));
		$this->assertTrue(PrimeNumber::isPrime(2));
		$this->assertTrue(PrimeNumber::isPrime(97));
		$this->assertFalse(PrimeNumber::isPrime(99));
		$this->assertSame(6, PrimeNumber::gcd(-54, 24));
		$this->assertTrue(PrimeNumber::isFermatPseudoprime(341, 2));
		$this->assertFalse(PrimeNumber::isFermatPseudoprime(13, 2));
	}

	public function testSequenceAndSeriesHelpersHandleIncreasingAndConstantSeries(): void
	{
		$this->assertSame(14, Sequences::arithmeticSequenceGeneralTerm(2, 3, 5));
		$this->assertSame(162.0, Sequences::geometricSequenceGeneralTerm(2, 3, 5));
		$this->assertSame(35, Series::arithmeticSeriesSum(5, 1, 3));
		$this->assertSame(30.0, Series::geometricSeriesSum(3, 10, 1));
		$this->assertSame(30.0, Series::geometricSeriesSum(4, 2, 2));
	}

	public function testTemperatureConversionsUseOffsetsAndRejectUnknownUnits(): void
	{
		$this->assertEqualsWithDelta(32.0, Temperature::convert(0, TemperatureUnit::CELSIUS, TemperatureUnit::FAHRENHEIT), 1e-12);
		$this->assertEqualsWithDelta(100.0, Temperature::convert(373.15, TemperatureUnit::KELVIN, TemperatureUnit::CELSIUS), 1e-12);
		$this->assertEqualsWithDelta(491.67, Temperature::convert(0, TemperatureUnit::CELSIUS, TemperatureUnit::RANKINE), 1e-12);
		$this->assertSame(12.5, Temperature::convert(12.5, TemperatureUnit::CELSIUS, TemperatureUnit::CELSIUS));
		$this->assertNull(Temperature::convert(10, 'unknown', TemperatureUnit::CELSIUS));
		$this->assertNull(Temperature::convert(10, TemperatureUnit::CELSIUS, 'unknown'));
	}

	public function testAreaAndMassConversionsExposeKnownFactorsAndInvalidUnitContract(): void
	{
		$this->assertSame(1.0, Area::getConversionFactor(AreaUnit::HECTARE, AreaUnit::HECTARE));
		$this->assertEqualsWithDelta(10_000.0, Area::convert(1, AreaUnit::HECTARE, AreaUnit::SQUARE_METER), 1e-12);
		$this->assertEqualsWithDelta(4_046.856_422_4, Area::convert(1, AreaUnit::ACRE, AreaUnit::SQUARE_METER), 1e-9);
		$this->assertNull(Area::convert(1, 'unknown', AreaUnit::SQUARE_METER));

		$this->assertSame(1.0, Mass::getConversionFactor(MessUnit::GRAM, MessUnit::GRAM));
		$this->assertEqualsWithDelta(453.59237, Mass::convert(1, MessUnit::POUND, MessUnit::GRAM), 1e-9);
		$this->assertEqualsWithDelta(1_000.0, Mass::convert(1, MessUnit::KILLOGRAM, MessUnit::GRAM), 1e-12);
		$this->assertNull(Mass::getConversionFactor('unknown', MessUnit::GRAM));
	}

	public function testActivationHelpersCoverProbabilityAndErrorContracts(): void
	{
		$softmax = ActivationFunctions::softmax([0, 0, 0]);

		$this->assertCount(3, $softmax);
		$this->assertEqualsWithDelta(1.0, array_sum($softmax), 1e-12);
		$this->assertEqualsWithDelta(0.5, ActivationFunctions::sigmoid(0), 1e-12);
		$this->assertSame(0.0, ActivationFunctions::relu(-2));
		$this->assertSame(3.0, ActivationFunctions::relu(3));
		$this->assertSame(3.0, ActivationFunctions::maxout([-2, 3, 1]));
		$this->assertNull(ActivationFunctions::maxout([]));
		$this->assertNull(ActivationFunctions::crossEntropy([0.5], [1, 0]));
		$this->assertNull(ActivationFunctions::derivative('unknown', 1));
		$this->assertNull(ActivationFunctions::partialDerivative(static fn(array $x): float => $x[0] ** 2, [2.0], 3));
	}

	public function testMatrixAndDerivativeHelpersProduceExpectedValues(): void
	{
		$this->assertSame([[19, 22], [43, 50]], ActivationFunctions::matmul([[1, 2], [3, 4]], [[5, 6], [7, 8]]));
		$this->assertEqualsWithDelta(12.0, Derivatives::powerRuleDerivative(2, 3), 1e-12);
		$this->assertEqualsWithDelta(4.0, Derivatives::numericalDerivative(static fn(float $x): float => $x * $x, 2.0), 0.00001);
	}

	public function testGraphSearchReturnsDistancesShortestPathAndNoPath(): void
	{
		$graph = [
			0 => [1, 2],
			1 => [0, 3],
			2 => [0],
			3 => [1],
			4 => [],
		];

		$this->assertSame([0 => 0, 1 => 1, 2 => 1, 3 => 2, 4 => -1], Search::bfs($graph, 0));

		$weighted = [
			0 => [1 => 1, 2 => 5],
			1 => [2 => 1, 3 => 4],
			2 => [3 => 1],
			3 => [],
			4 => [],
		];
		$coordinates = [
			0 => [0, 0],
			1 => [1, 0],
			2 => [2, 0],
			3 => [3, 0],
			4 => [10, 10],
		];

		$this->assertSame([0, 1, 2, 3], Search::aStar($weighted, $coordinates, 0, 3));
		$this->assertNull(Search::aStar($weighted, $coordinates, 0, 4));
	}
}
