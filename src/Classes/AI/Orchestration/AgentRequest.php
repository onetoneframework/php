<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

/**
 * Vendor-neutral request passed from the orchestrator to an agent.
 */
final class AgentRequest
{
	private WorkflowStep $step;

	private ExecutionContext $context;

	private ExecutionControl $control;

	/**
	 * Create a request for the current workflow step.
	 */
	public function __construct(WorkflowStep $step, ExecutionContext $context, ExecutionControl $control)
	{
		$this->step = $step;
		$this->context = $context;
		$this->control = $control;
	}

	/**
	 * Return the current workflow step identifier.
	 */
	public function stepIdentifier(): string
	{
		return $this->step->identifier();
	}

	/**
	 * Return the instruction defined by the current workflow step.
	 */
	public function instruction(): string
	{
		return $this->step->instruction();
	}

	/**
	 * Return the input and declared dependency results for this step.
	 */
	public function context(): ExecutionContext
	{
		return $this->context;
	}

	/**
	 * Return transport limits and cancellation state for this execution.
	 */
	public function control(): ExecutionControl
	{
		return $this->control;
	}
}
