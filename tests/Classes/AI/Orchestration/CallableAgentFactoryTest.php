<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentRequest;
use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Classes\AI\Orchestration\CallableAgent;
use Clover\Classes\AI\Orchestration\CallableAgentFactory;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

final class CallableAgentFactoryTest extends TestCase
{
	public function testCallableCreatesExpectedAgent(): void
	{
		$factory = new CallableAgentFactory(
			'primary',
			static fn (): CallableAgent => new CallableAgent(
				'primary',
				static fn (AgentRequest $request): AgentResponse => new AgentResponse(
					$request->instruction()
				)
			)
		);
		$firstAgent = $factory->create();
		$secondAgent = $factory->create();

		self::assertSame('primary', $factory->name());
		self::assertSame('primary', $firstAgent->name());
		self::assertSame('primary', $secondAgent->name());
		self::assertNotSame($firstAgent, $secondAgent);
	}

	public function testCallableFailureIsPreserved(): void
	{
		$failure = new RuntimeException('factory failed');
		$factory = new CallableAgentFactory(
			'primary',
			static function () use ($failure): CallableAgent {
				throw $failure;
			}
		);

		try {
			$factory->create();
			self::fail('Factory callable failures must be preserved.');
		} catch (RuntimeException $exception) {
			self::assertSame($failure, $exception);
		}
	}

	public function testCallableReturningWrongTypeIsRejected(): void
	{
		$factory = new CallableAgentFactory('primary', static fn (): stdClass => new stdClass());

		$this->expectException(InvalidAgentException::class);
		$this->expectExceptionMessage('must create an agent with their registered name');

		$factory->create();
	}

	public function testCallableReturningMismatchedAgentNameIsRejected(): void
	{
		$agent = new CallableAgent(
			'secondary',
			static fn (AgentRequest $request): AgentResponse => new AgentResponse($request->instruction())
		);
		$factory = new CallableAgentFactory('primary', static fn (): CallableAgent => $agent);

		$this->expectException(InvalidAgentException::class);
		$this->expectExceptionMessage('registered name');

		$factory->create();
	}

	public function testInvalidFactoryNameIsConvertedToAgentException(): void
	{
		try {
			new CallableAgentFactory(
				'invalid/factory',
				static fn (): CallableAgent => throw new RuntimeException('must not execute')
			);
			self::fail('Invalid callable factory names must use the agent exception boundary.');
		} catch (InvalidAgentException $exception) {
			self::assertInstanceOf(InvalidIdentifierException::class, $exception->getPrevious());
		}
	}
}
