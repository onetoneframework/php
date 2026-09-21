<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Classes\AI\Orchestration\Attributes;
use Clover\Classes\AI\Orchestration\ExecutionControl;
use Clover\Classes\AI\Orchestration\StepResult;
use Clover\Classes\AI\Orchestration\TransportLimits;
use Clover\Classes\AI\Orchestration\WorkflowLimits;
use Clover\Exception\AI\OrchestrationCancelledException;
use Clover\Exception\AI\ResponseSizeExceededException;
use Clover\Exception\AI\WorkflowDeadlineExceededException;
use LogicException;
use PHPUnit\Framework\TestCase;

final class ExecutionControlTest extends TestCase
{
	private const INITIAL_TIME_MILLISECONDS = 10_000;

	private const WORKFLOW_TIMEOUT_MILLISECONDS = 6_000;

	private const ELAPSED_TIME_MILLISECONDS = 4_000;

	private const REMAINING_TIME_MILLISECONDS = 2_000;

	private const SUB_MILLISECOND_ELAPSED = 0.25;

	private const CONNECTION_TIMEOUT_MILLISECONDS = 5_000;

	private const RESPONSE_TIMEOUT_MILLISECONDS = 5_500;

	private const SINGLE_RESPONSE_BYTES = 10;

	private const FIRST_RESPONSE_BYTES = 6;

	private const SECOND_RESPONSE_BYTES = 5;

	private const WORKFLOW_RESPONSE_BUDGET_BYTES = 10;

	public function testDefaultEffectiveTransportLimitsAreAvailableBeforeStart(): void
	{
		$control = new ExecutionControl();

		$limits = $control->effectiveTransportLimits();

		self::assertSame(
			TransportLimits::DEFAULT_CONNECTION_TIMEOUT_MILLISECONDS,
			$limits->connectionTimeoutMilliseconds()
		);
		self::assertSame(
			TransportLimits::DEFAULT_RESPONSE_TIMEOUT_MILLISECONDS,
			$limits->responseTimeoutMilliseconds()
		);
		self::assertSame(
			TransportLimits::DEFAULT_MAXIMUM_RESPONSE_BYTES,
			$limits->maximumResponseBytes()
		);
		self::assertSame(0, $control->consumedResponseBytes());
		self::assertFalse($control->isCancellationRequested());
	}

	public function testCancellationPreventsContinuation(): void
	{
		$control = new ExecutionControl();
		$control->cancel();

		self::assertTrue($control->isCancellationRequested());
		$this->expectException(OrchestrationCancelledException::class);

		$control->assertCanContinue();
	}

	public function testRemainingWorkflowTimeUsesMonotonicElapsedTime(): void
	{
		$clock = new FakeMonotonicClock(self::INITIAL_TIME_MILLISECONDS);
		$control = new ExecutionControl(
			workflowLimits: new WorkflowLimits(self::WORKFLOW_TIMEOUT_MILLISECONDS),
			clock: $clock
		);

		self::assertSame(
			self::WORKFLOW_TIMEOUT_MILLISECONDS,
			$control->remainingWorkflowMilliseconds()
		);

		$control->start();
		$clock->advanceMilliseconds(self::ELAPSED_TIME_MILLISECONDS);

		self::assertSame(
			self::REMAINING_TIME_MILLISECONDS,
			$control->remainingWorkflowMilliseconds()
		);
	}

	public function testWorkflowDeadlinePreventsContinuation(): void
	{
		$clock = new FakeMonotonicClock(self::INITIAL_TIME_MILLISECONDS);
		$control = new ExecutionControl(
			workflowLimits: new WorkflowLimits(self::WORKFLOW_TIMEOUT_MILLISECONDS),
			clock: $clock
		);
		$control->start();
		$clock->advanceMilliseconds(self::WORKFLOW_TIMEOUT_MILLISECONDS);

		self::assertSame(0, $control->remainingWorkflowMilliseconds());
		$this->expectException(WorkflowDeadlineExceededException::class);

		$control->assertCanContinue();
	}

	public function testSubMillisecondElapsedTimeRoundsAgainstCallerBudget(): void
	{
		$clock = new FakeMonotonicClock(self::INITIAL_TIME_MILLISECONDS);
		$control = new ExecutionControl(
			workflowLimits: new WorkflowLimits(self::WORKFLOW_TIMEOUT_MILLISECONDS),
			clock: $clock
		);
		$control->start();
		$clock->advanceMilliseconds(self::SUB_MILLISECOND_ELAPSED);

		self::assertSame(
			self::WORKFLOW_TIMEOUT_MILLISECONDS - 1,
			$control->remainingWorkflowMilliseconds()
		);
	}

	public function testEffectiveTransportLimitsClampTimeoutsToRemainingBudget(): void
	{
		$clock = new FakeMonotonicClock(self::INITIAL_TIME_MILLISECONDS);
		$transportLimits = new TransportLimits(
			self::CONNECTION_TIMEOUT_MILLISECONDS,
			self::RESPONSE_TIMEOUT_MILLISECONDS
		);
		$control = new ExecutionControl(
			$transportLimits,
			new WorkflowLimits(self::WORKFLOW_TIMEOUT_MILLISECONDS),
			$clock
		);
		$control->start();
		$clock->advanceMilliseconds(self::ELAPSED_TIME_MILLISECONDS);

		$effectiveLimits = $control->effectiveTransportLimits();

		self::assertSame(
			self::REMAINING_TIME_MILLISECONDS,
			$effectiveLimits->connectionTimeoutMilliseconds()
		);
		self::assertSame(
			self::REMAINING_TIME_MILLISECONDS,
			$effectiveLimits->responseTimeoutMilliseconds()
		);
		self::assertSame(
			$transportLimits->maximumResponseBytes(),
			$effectiveLimits->maximumResponseBytes()
		);
	}

	public function testExecutionControlCannotBeStartedTwice(): void
	{
		$control = new ExecutionControl(clock: new FakeMonotonicClock());
		$control->start();

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('can only be used for one workflow run');

		$control->start();
	}

	public function testSingleResponseLimitIncludesMetadataBytes(): void
	{
		$response = new AgentResponse('abc', new Attributes(['model' => 'xy']));
		$control = new ExecutionControl(
			new TransportLimits(1, 1, self::SINGLE_RESPONSE_BYTES - 1),
			new WorkflowLimits(
				self::WORKFLOW_TIMEOUT_MILLISECONDS,
				self::SINGLE_RESPONSE_BYTES
			),
			new FakeMonotonicClock()
		);
		$control->start();

		self::assertSame(self::SINGLE_RESPONSE_BYTES, $response->sizeInBytes());

		try {
			$control->recordResult(new StepResult('generate', $response));
			self::fail('Metadata must count toward the single response size limit.');
		} catch (ResponseSizeExceededException $exception) {
			self::assertStringContainsString('"generate"', $exception->getMessage());
		}

		self::assertSame(0, $control->consumedResponseBytes());
	}

	public function testCumulativeResponseBudgetRejectsOverflowWithoutConsumingIt(): void
	{
		$control = new ExecutionControl(
			new TransportLimits(1, 1, self::FIRST_RESPONSE_BYTES),
			new WorkflowLimits(
				self::WORKFLOW_TIMEOUT_MILLISECONDS,
				self::WORKFLOW_RESPONSE_BUDGET_BYTES
			),
			new FakeMonotonicClock()
		);
		$control->start();
		$control->recordResult(new StepResult(
			'first',
			new AgentResponse('a', new Attributes(['key' => 'xy']))
		));

		self::assertSame(self::FIRST_RESPONSE_BYTES, $control->consumedResponseBytes());
		self::assertSame(
			self::WORKFLOW_RESPONSE_BUDGET_BYTES - self::FIRST_RESPONSE_BYTES,
			$control->remainingResponseBytes()
		);
		self::assertSame(
			self::WORKFLOW_RESPONSE_BUDGET_BYTES - self::FIRST_RESPONSE_BYTES,
			$control->effectiveTransportLimits()->maximumResponseBytes()
		);

		try {
			$control->recordResult(new StepResult(
				'second',
				new AgentResponse('b', new Attributes(['tag' => 'x']))
			));
			self::fail('Cumulative responses must remain within the workflow budget.');
		} catch (ResponseSizeExceededException $exception) {
			self::assertStringContainsString('"second"', $exception->getMessage());
		}

		self::assertSame(self::FIRST_RESPONSE_BYTES, $control->consumedResponseBytes());
		self::assertSame(
			self::WORKFLOW_RESPONSE_BUDGET_BYTES - self::FIRST_RESPONSE_BYTES,
			$control->remainingResponseBytes()
		);
	}
}
