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
use Clover\Classes\AI\Orchestration\AgentRequest;
use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Classes\AI\Orchestration\Attributes;
use Clover\Classes\AI\Orchestration\CallableAgent;
use Clover\Classes\AI\Orchestration\CallableAgentFactory;
use Clover\Classes\AI\Orchestration\ExecutionControl;
use Clover\Classes\AI\Orchestration\Orchestrator;
use Clover\Classes\AI\Orchestration\TransportLimits;
use Clover\Classes\AI\Orchestration\Workflow;
use Clover\Classes\AI\Orchestration\WorkflowLimits;
use Clover\Classes\AI\Orchestration\WorkflowStep;
use Clover\Contract\AI\AgentFactoryInterface;
use Clover\Exception\AI\AgentCreationException;
use Clover\Exception\AI\AgentNotFoundException;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidWorkflowException;
use Clover\Exception\AI\OrchestrationCancelledException;
use Clover\Exception\AI\ResponseSizeExceededException;
use Clover\Exception\AI\StepExecutionException;
use Clover\Exception\AI\WorkflowDeadlineExceededException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OrchestratorTest extends TestCase
{
	private const MINIMUM_TIMEOUT_MILLISECONDS = 1;

	private const OVERSIZED_RESPONSE_LIMIT_BYTES = 5;

	private const WORKFLOW_DEADLINE_MILLISECONDS = 100;

	public function testUnknownDependencyFailsBeforeAnyAgentInvocation(): void
	{
		$invocationLog = new AgentInvocationLog();
		$factory = new RecordingAgentFactory(
			'primary',
			$invocationLog
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('ready', 'primary', 'This step is otherwise ready.'))
			->withStep(
				(new WorkflowStep('blocked', 'primary', 'This step has an invalid dependency.'))
					->withDependencies('missing')
			);

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Unknown dependencies must fail during preflight validation.');
		} catch (InvalidWorkflowException $exception) {
			self::assertStringContainsString('unknown step "missing"', $exception->getMessage());
		}

		self::assertSame(0, $factory->creationAttempts());
		self::assertSame([], $invocationLog->entries());
	}

	public function testDependencyCycleFailsBeforeAnyAgentInvocation(): void
	{
		$invocationLog = new AgentInvocationLog();
		$factory = new RecordingAgentFactory(
			'primary',
			$invocationLog
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())
			->withStep(
				(new WorkflowStep('first', 'primary', 'Run first.'))
					->withDependencies('second')
			)
			->withStep(
				(new WorkflowStep('second', 'primary', 'Run second.'))
					->withDependencies('first')
			);

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Dependency cycles must fail during preflight validation.');
		} catch (InvalidWorkflowException $exception) {
			self::assertStringContainsString('dependency cycle', $exception->getMessage());
		}

		self::assertSame(0, $factory->creationAttempts());
		self::assertSame([], $invocationLog->entries());
	}

	public function testMissingAgentFailsBeforeAnEarlierValidStepIsInvoked(): void
	{
		$invocationLog = new AgentInvocationLog();
		$knownFactory = new RecordingAgentFactory(
			'known',
			$invocationLog
		);
		$orchestrator = new Orchestrator($this->registry($knownFactory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('known-step', 'known', 'Use the known agent.'))
			->withStep(new WorkflowStep('missing-step', 'missing', 'Use a missing agent.'));

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Missing agents must fail during preflight validation.');
		} catch (AgentNotFoundException $exception) {
			self::assertStringContainsString('"missing" is not registered', $exception->getMessage());
		}

		self::assertSame(0, $knownFactory->creationAttempts());
		self::assertSame([], $invocationLog->entries());
	}

	public function testDiamondWorkflowExecutesSharedDependencyOnceInStableOrder(): void
	{
		$invocationLog = new AgentInvocationLog();
		$sourceResponse = new AgentResponse('source output');
		$rightResponse = new AgentResponse('right output');
		$leftResponse = new AgentResponse('left output');
		$finalResponse = new AgentResponse('final output');
		$factory = new RecordingAgentFactory(
			'primary',
			$invocationLog,
			[$sourceResponse, $rightResponse, $leftResponse, $finalResponse]
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())
			->withStep(
				(new WorkflowStep('final', 'primary', 'Combine both branches.'))
					->withDependencies('left', 'right')
			)
			->withStep(
				(new WorkflowStep('right', 'primary', 'Build the right branch.'))
					->withDependencies('source')
			)
			->withStep(new WorkflowStep('source', 'primary', 'Create the shared source.'))
			->withStep(
				(new WorkflowStep('left', 'primary', 'Build the left branch.'))
					->withDependencies('source')
			);
		$input = new Attributes(['topic' => 'orchestration']);
		$control = new ExecutionControl();

		$result = $orchestrator->run($workflow, $input, $control);
		$agent = $factory->createdAgent();

		self::assertSame([
			['agent' => 'primary', 'step' => 'source'],
			['agent' => 'primary', 'step' => 'right'],
			['agent' => 'primary', 'step' => 'left'],
			['agent' => 'primary', 'step' => 'final'],
		], $invocationLog->entries());
		self::assertSame(1, $factory->creationAttempts());
		self::assertSame(4, $agent->callCount());
		self::assertSame(
			['source', 'right', 'left', 'final'],
			array_keys(iterator_to_array($result))
		);
		self::assertSame($sourceResponse, $result->result('source')->response());
		self::assertSame($finalResponse, $result->result('final')->response());

		$requests = $agent->requests();
		self::assertSame($input, $requests[0]->context()->input());
		self::assertSame($control, $requests[0]->control());
		self::assertSame($control, $requests[3]->control());
		self::assertFalse($requests[0]->context()->hasDependencyResult('source'));
		self::assertSame(
			$sourceResponse,
			$requests[1]->context()->dependencyResult('source')->response()
		);
		self::assertSame(
			$sourceResponse,
			$requests[2]->context()->dependencyResult('source')->response()
		);
		self::assertSame(
			$leftResponse,
			$requests[3]->context()->dependencyResult('left')->response()
		);
		self::assertSame(
			$rightResponse,
			$requests[3]->context()->dependencyResult('right')->response()
		);
		self::assertFalse($requests[3]->context()->hasDependencyResult('source'));
	}

	public function testMultipleAgentsAreRoutedByNameThroughNormalizedResults(): void
	{
		$invocationLog = new AgentInvocationLog();
		$planResponse = new AgentResponse('normalized plan');
		$draftResponse = new AgentResponse('normalized draft');
		$reviewResponse = new AgentResponse('normalized review');
		$plannerFactory = new RecordingAgentFactory(
			'planner',
			$invocationLog,
			[$planResponse, $reviewResponse]
		);
		$writerFactory = new RecordingAgentFactory(
			'writer',
			$invocationLog,
			[$draftResponse]
		);
		$orchestrator = new Orchestrator($this->registry($plannerFactory, $writerFactory));
		$workflow = (new Workflow())
			->withStep(
				(new WorkflowStep('review', 'planner', 'Review the draft.'))
					->withDependencies('draft')
			)
			->withStep(
				(new WorkflowStep('draft', 'writer', 'Write from the plan.'))
					->withDependencies('plan')
			)
			->withStep(new WorkflowStep('plan', 'planner', 'Create the plan.'));

		$result = $orchestrator->run($workflow, new Attributes(), new ExecutionControl());
		$planner = $plannerFactory->createdAgent();
		$writer = $writerFactory->createdAgent();

		self::assertSame([
			['agent' => 'planner', 'step' => 'plan'],
			['agent' => 'writer', 'step' => 'draft'],
			['agent' => 'planner', 'step' => 'review'],
		], $invocationLog->entries());
		self::assertSame(1, $plannerFactory->creationAttempts());
		self::assertSame(1, $writerFactory->creationAttempts());
		self::assertSame(2, $planner->callCount());
		self::assertSame(1, $writer->callCount());
		self::assertSame(
			$planResponse,
			$writer->requests()[0]->context()->dependencyResult('plan')->response()
		);
		self::assertSame(
			$draftResponse,
			$planner->requests()[1]->context()->dependencyResult('draft')->response()
		);
		self::assertFalse(
			$planner->requests()[1]->context()->hasDependencyResult('plan')
		);
		self::assertSame($reviewResponse, $result->result('review')->response());
	}

	public function testRepeatedRunsDoNotLeakInputOrDependencyResults(): void
	{
		$invocationLog = new AgentInvocationLog();
		$firstSource = new AgentResponse('first source');
		$secondSource = new AgentResponse('second source');
		$sourceFactory = new RecordingAgentFactory(
			'source-agent',
			$invocationLog,
			[$firstSource],
			[$secondSource]
		);
		$consumerFactory = new RecordingAgentFactory(
			'consumer-agent',
			$invocationLog,
			[new AgentResponse('first consumer')],
			[new AgentResponse('second consumer')]
		);
		$orchestrator = new Orchestrator($this->registry($sourceFactory, $consumerFactory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('source', 'source-agent', 'Create source data.'))
			->withStep(
				(new WorkflowStep('consumer', 'consumer-agent', 'Consume source data.'))
					->withDependencies('source')
			);

		$firstResult = $orchestrator->run(
			$workflow,
			new Attributes(['run' => 1]),
			new ExecutionControl()
		);
		$secondResult = $orchestrator->run(
			$workflow,
			new Attributes(['run' => 2]),
			new ExecutionControl()
		);

		$sourceAgents = $sourceFactory->createdAgents();
		$consumerAgents = $consumerFactory->createdAgents();
		self::assertSame(2, $sourceFactory->creationAttempts());
		self::assertSame(2, $consumerFactory->creationAttempts());
		self::assertCount(2, $sourceAgents);
		self::assertCount(2, $consumerAgents);
		self::assertNotSame($sourceAgents[0], $sourceAgents[1]);
		self::assertNotSame($consumerAgents[0], $consumerAgents[1]);
		self::assertSame(1, $sourceAgents[0]->callCount());
		self::assertSame(1, $sourceAgents[1]->callCount());
		self::assertSame(1, $consumerAgents[0]->callCount());
		self::assertSame(1, $consumerAgents[1]->callCount());

		$firstConsumerRequest = $consumerAgents[0]->requests()[0];
		$secondConsumerRequest = $consumerAgents[1]->requests()[0];
		self::assertSame(1, $firstConsumerRequest->context()->input()->get('run'));
		self::assertSame(2, $secondConsumerRequest->context()->input()->get('run'));
		self::assertSame(
			$firstSource,
			$firstConsumerRequest->context()->dependencyResult('source')->response()
		);
		self::assertSame(
			$secondSource,
			$secondConsumerRequest->context()->dependencyResult('source')->response()
		);
		self::assertNotSame(
			$firstResult->result('source'),
			$secondResult->result('source')
		);
	}

	public function testAgentFailureStopsDependentsAndPreservesTypedCause(): void
	{
		$invocationLog = new AgentInvocationLog();
		$providerFailure = new RuntimeException('provider unavailable');
		$preparationFactory = new RecordingAgentFactory(
			'preparation-agent',
			$invocationLog,
			[new AgentResponse('prepared')]
		);
		$failingFactory = new RecordingAgentFactory(
			'failing-agent',
			$invocationLog,
			[$providerFailure]
		);
		$dependentFactory = new RecordingAgentFactory(
			'dependent-agent',
			$invocationLog,
			[new AgentResponse('must not execute')]
		);
		$orchestrator = new Orchestrator($this->registry(
			$preparationFactory,
			$failingFactory,
			$dependentFactory
		));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('prepare', 'preparation-agent', 'Prepare input.'))
			->withStep(
				(new WorkflowStep('generate', 'failing-agent', 'Generate output.'))
					->withDependencies('prepare')
			)
			->withStep(
				(new WorkflowStep('publish', 'dependent-agent', 'Publish output.'))
					->withDependencies('generate')
			);

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Agent failures must stop workflow execution.');
		} catch (StepExecutionException $exception) {
			self::assertSame('generate', $exception->stepIdentifier());
			self::assertSame($providerFailure, $exception->getPrevious());
			self::assertStringNotContainsString('provider unavailable', $exception->getMessage());
		}

		$preparationAgent = $preparationFactory->createdAgent();
		$failingAgent = $failingFactory->createdAgent();
		$dependentAgent = $dependentFactory->createdAgent();
		self::assertSame(1, $preparationAgent->callCount());
		self::assertSame(1, $failingAgent->callCount());
		self::assertSame(0, $dependentAgent->callCount());
		self::assertSame([
			['agent' => 'preparation-agent', 'step' => 'prepare'],
			['agent' => 'failing-agent', 'step' => 'generate'],
		], $invocationLog->entries());
	}

	public function testCancellationBeforeExecutionDoesNotInvokeAnAgent(): void
	{
		$invocationLog = new AgentInvocationLog();
		$factory = new RecordingAgentFactory(
			'primary',
			$invocationLog
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('generate', 'primary', 'Generate output.')
		);
		$control = new ExecutionControl();
		$control->cancel();

		try {
			$orchestrator->run($workflow, new Attributes(), $control);
			self::fail('A cancelled execution must stop before agent invocation.');
		} catch (OrchestrationCancelledException $exception) {
			self::assertSame('AI workflow execution was cancelled.', $exception->getMessage());
		}

		self::assertSame(0, $factory->creationAttempts());
		self::assertSame([], $invocationLog->entries());
	}

	public function testCancellationRequestedByAgentStopsFollowingStep(): void
	{
		$cancellingAgentCalls = 0;
		$cancellingAgent = new CallableAgent(
			'cancelling-agent',
			static function (AgentRequest $request) use (&$cancellingAgentCalls): AgentResponse {
				$cancellingAgentCalls++;
				$request->control()->cancel();

				return new AgentResponse('discarded output');
			}
		);
		$cancellingFactory = new CallableAgentFactory(
			'cancelling-agent',
			static fn (): CallableAgent => $cancellingAgent
		);
		$invocationLog = new AgentInvocationLog();
		$dependentFactory = new RecordingAgentFactory(
			'dependent-agent',
			$invocationLog,
			[new AgentResponse('must not execute')]
		);
		$orchestrator = new Orchestrator($this->registry($cancellingFactory, $dependentFactory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('cancel', 'cancelling-agent', 'Request cancellation.'))
			->withStep(
				(new WorkflowStep('dependent', 'dependent-agent', 'Run after cancellation.'))
					->withDependencies('cancel')
			);

		$this->expectException(OrchestrationCancelledException::class);

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
		} finally {
			$dependentAgent = $dependentFactory->createdAgent();
			self::assertSame(1, $cancellingAgentCalls);
			self::assertSame(1, $dependentFactory->creationAttempts());
			self::assertSame(0, $dependentAgent->callCount());
			self::assertSame([], $invocationLog->entries());
		}
	}

	public function testOversizedResponseIsWrappedWithTypedPrivateCause(): void
	{
		$invocationLog = new AgentInvocationLog();
		$oversizedContent = 'content exceeds limit';
		$factory = new RecordingAgentFactory(
			'primary',
			$invocationLog,
			[new AgentResponse($oversizedContent)]
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('generate', 'primary', 'Generate output.')
		);
		$control = new ExecutionControl(
			new TransportLimits(
				self::MINIMUM_TIMEOUT_MILLISECONDS,
				self::MINIMUM_TIMEOUT_MILLISECONDS,
				self::OVERSIZED_RESPONSE_LIMIT_BYTES
			),
			new WorkflowLimits()
		);

		try {
			$orchestrator->run($workflow, new Attributes(), $control);
			self::fail('Responses above the execution limit must be rejected.');
		} catch (StepExecutionException $exception) {
			self::assertSame('generate', $exception->stepIdentifier());
			self::assertInstanceOf(ResponseSizeExceededException::class, $exception->getPrevious());
			self::assertStringNotContainsString($oversizedContent, $exception->getMessage());
		}

		$agent = $factory->createdAgent();
		self::assertSame(1, $agent->callCount());
	}

	public function testWorkflowDeadlineReachedDuringAgentExecutionStopsDependents(): void
	{
		$clock = new FakeMonotonicClock();
		$deadlineAgentCalls = 0;
		$deadlineAgent = new CallableAgent(
			'deadline-agent',
			static function (AgentRequest $request) use ($clock, &$deadlineAgentCalls): AgentResponse {
				$deadlineAgentCalls++;
				$clock->advanceMilliseconds(self::WORKFLOW_DEADLINE_MILLISECONDS);

				return new AgentResponse($request->instruction());
			}
		);
		$deadlineFactory = new CallableAgentFactory(
			'deadline-agent',
			static fn (): CallableAgent => $deadlineAgent
		);
		$invocationLog = new AgentInvocationLog();
		$dependentFactory = new RecordingAgentFactory(
			'dependent-agent',
			$invocationLog,
			[new AgentResponse('must not execute')]
		);
		$orchestrator = new Orchestrator($this->registry($deadlineFactory, $dependentFactory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('deadline', 'deadline-agent', 'Exhaust the deadline.'))
			->withStep(
				(new WorkflowStep('dependent', 'dependent-agent', 'Run after the deadline.'))
					->withDependencies('deadline')
			);
		$control = new ExecutionControl(
			workflowLimits: new WorkflowLimits(self::WORKFLOW_DEADLINE_MILLISECONDS),
			clock: $clock
		);

		try {
			$orchestrator->run($workflow, new Attributes(), $control);
			self::fail('The total workflow deadline must stop execution.');
		} catch (WorkflowDeadlineExceededException $exception) {
			self::assertSame(
				'AI workflow execution exceeded its deadline.',
				$exception->getMessage()
			);
		}

		$dependentAgent = $dependentFactory->createdAgent();
		self::assertSame(1, $deadlineAgentCalls);
		self::assertSame(0, $dependentAgent->callCount());
		self::assertSame(0, $control->consumedResponseBytes());
		self::assertSame([], $invocationLog->entries());
	}

	public function testFactoryFailureIsWrappedWithGenericTypedException(): void
	{
		$factoryFailure = new RuntimeException('factory credential detail');
		$factory = new CallableAgentFactory(
			'broken-agent',
			static function () use ($factoryFailure): CallableAgent {
				throw $factoryFailure;
			}
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('generate', 'broken-agent', 'Generate output.')
		);

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Factory failures must use the agent creation exception boundary.');
		} catch (AgentCreationException $exception) {
			self::assertSame('broken-agent', $exception->agentName());
			self::assertSame($factoryFailure, $exception->getPrevious());
			self::assertSame('AI agent creation failed.', $exception->getMessage());
			self::assertStringNotContainsString('credential', $exception->getMessage());
		}
	}

	public function testFactoryCannotReuseAgentIdentityAcrossWorkflowRuns(): void
	{
		$invocationLog = new AgentInvocationLog();
		$agent = new RecordingAgent(
			'primary',
			$invocationLog,
			new AgentResponse('first output'),
			new AgentResponse('must not execute')
		);
		$creationAttempts = 0;
		$factory = new CallableAgentFactory(
			'primary',
			static function () use ($agent, &$creationAttempts): RecordingAgent {
				$creationAttempts++;

				return $agent;
			}
		);
		$orchestrator = new Orchestrator($this->registry($factory));
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('generate', 'primary', 'Generate output.')
		);

		$firstResult = $orchestrator->run(
			$workflow,
			new Attributes(),
			new ExecutionControl()
		);

		self::assertSame('first output', $firstResult->result('generate')->response()->content());
		self::assertSame(1, $agent->callCount());

		try {
			$orchestrator->run($workflow, new Attributes(), new ExecutionControl());
			self::fail('Factories must not reuse an agent from an earlier workflow run.');
		} catch (AgentCreationException $exception) {
			self::assertSame('primary', $exception->agentName());
			self::assertInstanceOf(InvalidAgentException::class, $exception->getPrevious());
			self::assertSame('AI agent creation failed.', $exception->getMessage());
		}

		self::assertSame(2, $creationAttempts);
		self::assertSame(1, $agent->callCount());
	}

	public function testCancellationDuringFactoryCreationStopsBeforeAgentExecution(): void
	{
		$control = new ExecutionControl();
		$createdAgentCalls = 0;
		$factory = new CallableAgentFactory(
			'cancelling-agent',
			static function () use ($control, &$createdAgentCalls): CallableAgent {
				$control->cancel();

				return new CallableAgent(
					'cancelling-agent',
					static function (AgentRequest $request) use (&$createdAgentCalls): AgentResponse {
						$createdAgentCalls++;

						return new AgentResponse($request->instruction());
					}
				);
			}
		);
		$invocationLog = new AgentInvocationLog();
		$laterFactory = new RecordingAgentFactory(
			'later-agent',
			$invocationLog,
			[new AgentResponse('must not execute')]
		);
		$orchestrator = new Orchestrator($this->registry($factory, $laterFactory));
		$workflow = (new Workflow())
			->withStep(new WorkflowStep('cancel', 'cancelling-agent', 'Cancel creation.'))
			->withStep(
				(new WorkflowStep('later', 'later-agent', 'Run later.'))
					->withDependencies('cancel')
			);

		try {
			$orchestrator->run($workflow, new Attributes(), $control);
			self::fail('Cancellation during factory creation must stop execution.');
		} catch (OrchestrationCancelledException $exception) {
			self::assertSame('AI workflow execution was cancelled.', $exception->getMessage());
		}

		self::assertSame(0, $createdAgentCalls);
		self::assertSame(0, $laterFactory->creationAttempts());
		self::assertSame([], $invocationLog->entries());
	}

	private function registry(AgentFactoryInterface ...$factories): AgentRegistry
	{
		$registry = new AgentRegistry();
		foreach ($factories as $factory) {
			$registry->register($factory);
		}

		return $registry;
	}
}
