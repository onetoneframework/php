<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentRegistry;
use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Contract\AI\AgentFactoryInterface;
use Clover\Contract\AI\AgentInterface;
use Clover\Exception\AI\AgentNotFoundException;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;

final class AgentRegistryTest extends TestCase
{
	public function testRegisteredFactoryCanBeResolvedAndCreateAgent(): void
	{
		$factory = new RecordingAgentFactory(
			'primary',
			new AgentInvocationLog(),
			[new AgentResponse('unused')]
		);
		$registry = new AgentRegistry();

		$registry->register($factory);
		$agent = $registry->createAgent('primary');

		self::assertSame(1, $registry->count());
		self::assertTrue($registry->has('primary'));
		self::assertSame($factory, $registry->factory('primary'));
		self::assertSame($factory->createdAgent(), $agent);
		self::assertSame('primary', $agent->name());
		self::assertSame(1, $factory->creationAttempts());
	}

	public function testDuplicateFactoryNameIsRejected(): void
	{
		$registry = new AgentRegistry();
		$registry->register(new RecordingAgentFactory(
			'primary',
			new AgentInvocationLog(),
			[new AgentResponse('first')]
		));

		$this->expectException(InvalidAgentException::class);
		$this->expectExceptionMessage('factory "primary" is already registered');

		$registry->register(new RecordingAgentFactory(
			'primary',
			new AgentInvocationLog(),
			[new AgentResponse('second')]
		));
	}

	public function testUnknownFactoryThrowsTypedException(): void
	{
		$registry = new AgentRegistry();

		$this->expectException(AgentNotFoundException::class);
		$this->expectExceptionMessage('"missing" is not registered');

		$registry->factory('missing');
	}

	public function testInvalidFactoryNameIsConvertedToAgentException(): void
	{
		$factory = new RecordingAgentFactory(
			'invalid/factory',
			new AgentInvocationLog(),
			[new AgentResponse('unused')]
		);
		$registry = new AgentRegistry();

		try {
			$registry->register($factory);
			self::fail('Invalid factory names must use the agent exception boundary.');
		} catch (InvalidAgentException $exception) {
			self::assertInstanceOf(InvalidIdentifierException::class, $exception->getPrevious());
		}
	}

	public function testFactoryAgentNameMismatchIsRejected(): void
	{
		$factory = new class implements AgentFactoryInterface {
			public function name(): string
			{
				return 'primary';
			}

			public function create(): AgentInterface
			{
				return new RecordingAgent(
					'secondary',
					new AgentInvocationLog(),
					new AgentResponse('unused')
				);
			}
		};
		$registry = new AgentRegistry();
		$registry->register($factory);

		$this->expectException(InvalidAgentException::class);
		$this->expectExceptionMessage('registered name');

		$registry->createAgent('primary');
	}
}
