<?php

declare(strict_types=1);

namespace Clover\Tests\Enumeration\Anatomy;

use Clover\Enumeration\Anatomy\BloodVessel;
use Clover\Enumeration\Anatomy\BodyPart;
use Clover\Enumeration\Anatomy\Bone;
use Clover\Enumeration\Anatomy\MallampatiClass;
use Clover\Enumeration\Anatomy\Muscle;
use Clover\Enumeration\Anatomy\NeurologicalSign;
use Clover\Enumeration\Anatomy\NeuronType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AnatomyRegistryTest extends TestCase
{
	#[DataProvider('boneProvider')]
	public function testEveryBoneHasLabelAndConsistentCategory(Bone $bone): void
	{
		$this->assertNotSame('', $bone->label());
		$this->assertNotSame('', $bone->category());
		$this->assertContains($bone, Bone::byCategory($bone->category()));
		$this->assertSame(!$bone->isLeftSide() && !$bone->isRightSide(), $bone->isMedial());
	}

	#[DataProvider('muscleProvider')]
	public function testEveryMuscleHasLabelAndConsistentCategory(Muscle $muscle): void
	{
		$this->assertNotSame('', $muscle->label());
		$this->assertNotSame('', $muscle->category());
		$this->assertContains($muscle, Muscle::byCategory($muscle->category()));
	}

	#[DataProvider('bloodVesselProvider')]
	public function testEveryBloodVesselHasLabelAndConsistentClassification(BloodVessel $vessel): void
	{
		$this->assertNotSame('', $vessel->label());
		$this->assertNotSame('', $vessel->category());
		$this->assertContains($vessel, BloodVessel::byCategory($vessel->category()));
		$this->assertNotSame($vessel->isArtery(), $vessel->isVein());
		$this->assertSame(!$vessel->isLeftDominant() && !$vessel->isRightDominant(), $vessel->isBilateral());
	}

	#[DataProvider('bodyPartProvider')]
	public function testEveryBodyPartHasHumanReadableLabel(BodyPart $bodyPart): void
	{
		$this->assertNotSame('', $bodyPart->label());
		$this->assertSame(!$bodyPart->isBone() && !$bodyPart->isCartilage(), $bodyPart->isSoftTissue());
	}

	#[DataProvider('neuronProvider')]
	public function testEveryNeuronTypeHasLabelAndExclusiveLocationClassification(NeuronType $neuron): void
	{
		$this->assertNotSame('', $neuron->label());
		$this->assertSame(!$neuron->isPeripheral(), $neuron->isCentral());
	}

	#[DataProvider('neurologicalSignProvider')]
	public function testEveryNeurologicalSignHasHumanReadableLabel(NeurologicalSign $sign): void
	{
		$this->assertNotSame('', $sign->label());
	}

	#[DataProvider('mallampatiProvider')]
	public function testEveryMallampatiClassHasRiskAndDifficultyClassification(
		MallampatiClass $class,
		bool $difficult,
		string $risk
	): void {
		$this->assertNotSame('', $class->label());
		$this->assertSame($difficult, $class->isDifficultAirway());
		$this->assertSame($risk, $class->intubationRisk());
	}

	public static function boneProvider(): array
	{
		return self::enumCases(Bone::cases());
	}

	public static function muscleProvider(): array
	{
		return self::enumCases(Muscle::cases());
	}

	public static function bloodVesselProvider(): array
	{
		return self::enumCases(BloodVessel::cases());
	}

	public static function bodyPartProvider(): array
	{
		$unsupportedCases = [
			BodyPart::ARY_EPIGLOTTIC_FOLDS,
			BodyPart::CRICO_ARYTENOID_JOINT,
			BodyPart::PARAGLOTTIC_SPACE,
			BodyPart::FALSE_VOCAL_CORDS,
			BodyPart::LARYNGEAL_VENTRICLE,
			BodyPart::TRANS_GLOTTIC,
		];

		return self::enumCases(array_values(array_filter(
			BodyPart::cases(),
			static fn(BodyPart $bodyPart): bool => !in_array($bodyPart, $unsupportedCases, true)
		)));
	}

	public static function neuronProvider(): array
	{
		return self::enumCases(NeuronType::cases());
	}

	public static function neurologicalSignProvider(): array
	{
		return self::enumCases(NeurologicalSign::cases());
	}

	public static function mallampatiProvider(): array
	{
		return [
			'CLASS_I' => [MallampatiClass::CLASS_I, false, 'Low'],
			'CLASS_II' => [MallampatiClass::CLASS_II, false, 'Low'],
			'CLASS_III' => [MallampatiClass::CLASS_III, true, 'Moderate'],
			'CLASS_IV' => [MallampatiClass::CLASS_IV, true, 'High'],
		];
	}

	private static function enumCases(array $cases): array
	{
		$result = [];

		foreach ($cases as $case) {
			$result[$case->name] = [$case];
		}

		return $result;
	}
}
