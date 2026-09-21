<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Contract\AI\MonotonicClockInterface;
use Clover\Exception\AI\OrchestrationCancelledException;
use Clover\Exception\AI\ResponseSizeExceededException;
use Clover\Exception\AI\WorkflowDeadlineExceededException;
use Clover\Exception\AI\WorkflowResponseBudgetExceededException;
use LogicException;

/**
 * Carries bounded transport settings and cooperative cancellation state.
 */
final class ExecutionControl
{
	private TransportLimits $transportLimits;

	private WorkflowLimits $workflowLimits;

	private MonotonicClockInterface $clock;

	private bool $cancellationRequested = false;

	private ?float $startedAtMilliseconds = null;

	private int $consumedResponseBytes = 0;

	public function __construct(
		?TransportLimits $transportLimits = null,
		?WorkflowLimits $workflowLimits = null,
		?MonotonicClockInterface $clock = null
	) {
		$this->transportLimits = $transportLimits ?? new TransportLimits();
		$this->workflowLimits = $workflowLimits ?? new WorkflowLimits();
		$this->clock = $clock ?? new SystemMonotonicClock();
	}

	public function start(): void
	{
		if ($this->startedAtMilliseconds !== null) {
			throw new LogicException('AI execution controls can only be used for one workflow run.');
		}

		$this->startedAtMilliseconds = $this->clock->nowMilliseconds();
	}

	public function effectiveTransportLimits(): TransportLimits
	{
		$this->throwIfCancellationRequested();
		$remainingWorkflowMilliseconds = $this->remainingWorkflowMilliseconds();
		$remainingResponseBytes = $this->remainingResponseBytes();
		if ($remainingWorkflowMilliseconds === 0) {
			throw new WorkflowDeadlineExceededException();
		}

		if ($remainingResponseBytes === 0) {
			throw new WorkflowResponseBudgetExceededException();
		}

		return new TransportLimits(
			min($this->transportLimits->connectionTimeoutMilliseconds(), $remainingWorkflowMilliseconds),
			min($this->transportLimits->responseTimeoutMilliseconds(), $remainingWorkflowMilliseconds),
			min($this->transportLimits->maximumResponseBytes(), $remainingResponseBytes)
		);
	}

	public function remainingWorkflowMilliseconds(): int
	{
		if ($this->startedAtMilliseconds === null) {
			return $this->workflowLimits->timeoutMilliseconds();
		}

		$elapsedMilliseconds = max(
			0.0,
			$this->clock->nowMilliseconds() - $this->startedAtMilliseconds
		);
		$timeoutMilliseconds = $this->workflowLimits->timeoutMilliseconds();
		if ($elapsedMilliseconds >= $timeoutMilliseconds) {
			return 0;
		}

		return $timeoutMilliseconds - (int) ceil($elapsedMilliseconds);
	}

	public function consumedResponseBytes(): int
	{
		return $this->consumedResponseBytes;
	}

	public function remainingResponseBytes(): int
	{
		return $this->workflowLimits->maximumResponseBytes() - $this->consumedResponseBytes;
	}

	public function recordResult(StepResult $result): void
	{
		$this->assertCanContinue();
		$responseBytes = $result->response()->sizeInBytes();
		$remainingWorkflowBytes = $this->remainingResponseBytes();

		if (
			$responseBytes > $this->transportLimits->maximumResponseBytes()
			|| $responseBytes > $remainingWorkflowBytes
		) {
			throw new ResponseSizeExceededException($result->stepIdentifier());
		}

		$this->consumedResponseBytes += $responseBytes;
	}

	public function cancel(): void
	{
		$this->cancellationRequested = true;
	}

	public function isCancellationRequested(): bool
	{
		return $this->cancellationRequested;
	}

	public function throwIfCancellationRequested(): void
	{
		if ($this->cancellationRequested) {
			throw new OrchestrationCancelledException();
		}
	}

	public function assertCanContinue(): void
	{
		$this->throwIfCancellationRequested();

		if ($this->remainingWorkflowMilliseconds() === 0) {
			throw new WorkflowDeadlineExceededException();
		}

		if ($this->remainingResponseBytes() === 0) {
			throw new WorkflowResponseBudgetExceededException();
		}
	}

}
