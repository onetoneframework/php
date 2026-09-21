<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\Workflow;
use Clover\Classes\AI\Orchestration\WorkflowStep;
use Clover\Exception\AI\InvalidIdentifierException;
use Clover\Exception\AI\InvalidWorkflowException;
use PHPUnit\Framework\TestCase;

final class WorkflowTest extends TestCase
{
	public function testEmptyWorkflowIsRejected(): void
	{
		$workflow = new Workflow();

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('must contain at least one step');

		$workflow->orderedSteps();
	}

	public function testDuplicateStepIdentifierIsRejected(): void
	{
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('research', 'primary', 'Research the subject.')
		);

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('"research" is already defined');

		$workflow->withStep(new WorkflowStep('research', 'secondary', 'Repeat the research.'));
	}

	public function testInvalidStepIdentifierIsConvertedToWorkflowException(): void
	{
		try {
			new WorkflowStep('invalid/step', 'primary', 'Run the step.');
			self::fail('Invalid step identifiers must use the workflow exception boundary.');
		} catch (InvalidWorkflowException $exception) {
			self::assertInstanceOf(InvalidIdentifierException::class, $exception->getPrevious());
		}
	}

	public function testSelfDependencyIsRejected(): void
	{
		$step = new WorkflowStep('research', 'primary', 'Research the subject.');

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('cannot depend on itself');

		$step->withDependencies('research');
	}

	public function testDuplicateDependencyIsRejected(): void
	{
		$step = new WorkflowStep('draft', 'primary', 'Draft the response.');

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('declares dependency "research" more than once');

		$step->withDependencies('research', 'research');
	}

	public function testUnknownDependencyIsRejectedBeforeOrdering(): void
	{
		$workflow = (new Workflow())->withStep(
			(new WorkflowStep('draft', 'primary', 'Draft the response.'))
				->withDependencies('research')
		);

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('depends on unknown step "research"');

		$workflow->orderedSteps();
	}

	public function testIndirectDependencyCycleIsRejected(): void
	{
		$workflow = (new Workflow())
			->withStep(
				(new WorkflowStep('research', 'primary', 'Research the subject.'))
					->withDependencies('review')
			)
			->withStep(
				(new WorkflowStep('draft', 'primary', 'Draft the response.'))
					->withDependencies('research')
			)
			->withStep(
				(new WorkflowStep('review', 'primary', 'Review the response.'))
					->withDependencies('draft')
			);

		try {
			$workflow->orderedSteps();
			self::fail('Dependency cycles must be rejected.');
		} catch (InvalidWorkflowException $exception) {
			self::assertStringContainsString('dependency cycle', $exception->getMessage());
			self::assertStringContainsString('research', $exception->getMessage());
			self::assertStringContainsString('draft', $exception->getMessage());
			self::assertStringContainsString('review', $exception->getMessage());
		}
	}

	public function testTopologicalOrderIsStableForNewlyReadySteps(): void
	{
		$workflow = (new Workflow())
			->withStep(
				(new WorkflowStep('third', 'primary', 'Run third.'))
					->withDependencies('first')
			)
			->withStep(new WorkflowStep('second', 'primary', 'Run second.'))
			->withStep(new WorkflowStep('first', 'primary', 'Run first.'))
			->withStep(new WorkflowStep('fourth', 'primary', 'Run fourth.'));

		$identifiers = array_map(
			static fn (WorkflowStep $step): string => $step->identifier(),
			$workflow->orderedSteps()
		);

		self::assertSame(['second', 'first', 'third', 'fourth'], $identifiers);
	}

	public function testInstructionSizeBoundaryIsEnforced(): void
	{
		$maximumInstruction = str_repeat('x', WorkflowStep::MAXIMUM_INSTRUCTION_BYTES);
		$step = new WorkflowStep('maximum', 'primary', $maximumInstruction);

		self::assertSame(WorkflowStep::MAXIMUM_INSTRUCTION_BYTES, strlen($step->instruction()));

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('remain within the size limit');

		new WorkflowStep('oversized', 'primary', $maximumInstruction . 'x');
	}

	public function testWorkflowStepCountAboveMaximumIsRejected(): void
	{
		$workflow = new Workflow();
		for ($index = 0; $index < Workflow::MAXIMUM_STEPS; $index++) {
			$workflow = $workflow->withStep(
				new WorkflowStep('step-' . $index, 'primary', 'Run the step.')
			);
		}

		self::assertSame(Workflow::MAXIMUM_STEPS, $workflow->count());
		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('step limit');

		$workflow->withStep(new WorkflowStep('overflow', 'primary', 'Run one more step.'));
	}
}
