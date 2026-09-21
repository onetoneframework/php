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
use Clover\Classes\AI\Orchestration\Attributes;
use Clover\Classes\AI\Orchestration\CallableAgent;
use Clover\Classes\AI\Orchestration\ExecutionContext;
use Clover\Classes\AI\Orchestration\ExecutionControl;
use Clover\Classes\AI\Orchestration\WorkflowStep;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidIdentifierException;
use PHPUnit\Framework\TestCase;

final class CallableAgentTest extends TestCase
{
	public function testHandlerReceivesSameRequestAndReturnsNormalizedResponse(): void
	{
		$request = new AgentRequest(
			new WorkflowStep('generate', 'callable', 'Generate output.'),
			new ExecutionContext(new Attributes()),
			new ExecutionControl()
		);
		$expectedResponse = new AgentResponse('normalized output');
		$receivedRequests = [];
		$agent = new CallableAgent(
			'callable',
			static function (AgentRequest $receivedRequest) use (&$receivedRequests, $expectedResponse): AgentResponse {
				$receivedRequests[] = $receivedRequest;

				return $expectedResponse;
			}
		);

		$response = $agent->execute($request);

		self::assertSame([$request], $receivedRequests);
		self::assertSame($expectedResponse, $response);
	}

	public function testInvalidNameIsConvertedToAgentException(): void
	{
		try {
			new CallableAgent(
				'invalid/agent',
				static fn (AgentRequest $request): AgentResponse => new AgentResponse($request->instruction())
			);
			self::fail('Invalid callable agent names must use the agent exception boundary.');
		} catch (InvalidAgentException $exception) {
			self::assertInstanceOf(InvalidIdentifierException::class, $exception->getPrevious());
		}
	}
}
