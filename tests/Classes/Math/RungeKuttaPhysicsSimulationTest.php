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
use Clover\Classes\Math\RungeKuttaPhysicsSimulation;
use Clover\Classes\Math\RungeKuttaPhysicsSimulationWithImage;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RungeKuttaPhysicsSimulationTest extends TestCase
{
	public function testCanonicalSimulationPreservesItsInitialState(): void
	{
		$simulation = new RungeKuttaPhysicsSimulation(['1', '2'], ['3', '4'], '5');

		$this->assertSame(['1', '2'], $simulation->getPosition());
		$this->assertSame(['3', '4'], $simulation->getVelocity());
		$this->assertSame('5', $simulation->getTime());
		$this->assertSame([['1', '2']], $simulation->getTrajectory());
	}

	public function testCanonicalSimulationRetainsLegacyBehavior(): void
	{
		$canonical = new RungeKuttaPhysicsSimulation(['0', '0'], ['2', '-3']);
		$legacy = new RungeKuttaPhysicsSimulationWithImage(['0', '0'], ['2', '-3']);

		$this->assertInstanceOf(RungeKuttaPhysicsSimulation::class, $legacy);
		$this->assertSame(
			$legacy->motionEquation('0', ['0', '2', '0', '-3']),
			$canonical->motionEquation('0', ['0', '2', '0', '-3'])
		);
	}

	public function testLegacySimulationNameIsMarkedDeprecated(): void
	{
		$attributes = (new ReflectionClass(RungeKuttaPhysicsSimulationWithImage::class))
			->getAttributes(Deprecated::class);

		$this->assertCount(1, $attributes);
		$this->assertSame(
			'Use RungeKuttaPhysicsSimulation instead.',
			$attributes[0]->newInstance()->message
		);
	}

	public function testMotionEquationSupportsZeroAndNegativeVelocity(): void
	{
		$simulation = new RungeKuttaPhysicsSimulation(['0', '0'], ['0', '0']);

		$this->assertSame(
			['0', '0', '-10', '15.80665'],
			$simulation->motionEquation('100', ['7', '0', '4', '-10'])
		);
	}
}
