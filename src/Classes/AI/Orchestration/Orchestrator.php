<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Contract\AI\AgentInterface;
use Clover\Exception\AI\AgentCreationException;
use Clover\Exception\AI\OrchestrationCancelledException;
use Clover\Exception\AI\StepExecutionException;
use Clover\Exception\AI\WorkflowDeadlineExceededException;
use Clover\Exception\AI\WorkflowResponseBudgetExceededException;
use Throwable;

/**
 * Executes validated AI workflows through registered agents.
 */
final class Orchestrator
{
	private AgentRegistry $agents;

	/**
	 * Create an orchestrator backed by the shared agent registry.
	 */
	public function __construct(AgentRegistry $agents)
	{
		$this->agents = $agents;
	}

	/**
	 * Execute a validated workflow in stable dependency order.
	 */
	public function run(Workflow $workflow, Attributes $input, ExecutionControl $control): WorkflowResult
	{
		$control->start();
		$control->assertCanContinue();
		$orderedSteps = $workflow->orderedSteps();

		/** @var array<string, AgentInterface> $agentsByStep */
		$agentsByStep = [];
		$agentNames = [];
		foreach ($orderedSteps as $step) {
			$this->agents->factory($step->agentName());
			$agentNames[$step->agentName()] = $step->agentName();
		}

		/** @var array<string, AgentInterface> $agentsByName */
		$agentsByName = [];
		foreach ($agentNames as $agentName) {
			$control->assertCanContinue();

			try {
				$agentsByName[$agentName] = $this->agents->createAgent($agentName);
			} catch (Throwable $throwable) {
				throw new AgentCreationException($agentName, $throwable);
			}

			$control->assertCanContinue();
		}

		foreach ($orderedSteps as $step) {
			$agentsByStep[$step->identifier()] = $agentsByName[$step->agentName()];
		}

		/** @var array<string, StepResult> $completedResults */
		$completedResults = [];
		$orderedResults = [];

		foreach ($orderedSteps as $step) {
			$control->assertCanContinue();
			$dependencyResults = [];
			foreach ($step->dependencies() as $dependencyIdentifier) {
				$dependencyResults[] = $completedResults[$dependencyIdentifier];
			}

			$request = new AgentRequest(
				$step,
				new ExecutionContext($input, ...$dependencyResults),
				$control
			);

			try {
				$response = $agentsByStep[$step->identifier()]->execute($request);
				$result = new StepResult($step->identifier(), $response);
				$control->recordResult($result);
			} catch (OrchestrationCancelledException $exception) {
				throw $exception;
			} catch (WorkflowDeadlineExceededException $exception) {
				throw $exception;
			} catch (WorkflowResponseBudgetExceededException $exception) {
				throw $exception;
			} catch (Throwable $throwable) {
				throw new StepExecutionException($step->identifier(), $throwable);
			}

			$completedResults[$step->identifier()] = $result;
			$orderedResults[] = $result;
		}

		return new WorkflowResult(...$orderedResults);
	}
}
