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
use Clover\Classes\AI\Orchestration\ExecutionContext;
use Clover\Classes\AI\Orchestration\StepResult;
use Clover\Exception\AI\InvalidWorkflowException;
use Clover\Exception\AI\StepResultNotFoundException;
use PHPUnit\Framework\TestCase;

final class ExecutionContextTest extends TestCase
{
	public function testContextExposesInputAndOnlyProvidedDependencyResults(): void
	{
		$input = new Attributes(['topic' => 'orchestration']);
		$research = new StepResult('research', new AgentResponse('research output'));
		$context = new ExecutionContext($input, $research);

		self::assertSame($input, $context->input());
		self::assertTrue($context->hasDependencyResult('research'));
		self::assertSame($research, $context->dependencyResult('research'));
		self::assertFalse($context->hasDependencyResult('unrelated'));
	}

	public function testMissingDependencyResultThrowsTypedException(): void
	{
		$context = new ExecutionContext(new Attributes());

		$this->expectException(StepResultNotFoundException::class);
		$this->expectExceptionMessage('"research" was not found');

		$context->dependencyResult('research');
	}

	public function testDuplicateDependencyResultIsRejected(): void
	{
		$first = new StepResult('research', new AgentResponse('first'));
		$second = new StepResult('research', new AgentResponse('second'));

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('Dependency result "research" was provided more than once');

		new ExecutionContext(new Attributes(), $first, $second);
	}

	public function testInputSnapshotCannotBeChangedThroughDerivedAttributes(): void
	{
		$input = new Attributes(['topic' => 'original']);
		$context = new ExecutionContext($input);
		$derivedInput = $input->with('topic', 'changed');

		self::assertSame('original', $context->input()->get('topic'));
		self::assertSame('changed', $derivedInput->get('topic'));
		self::assertNotSame($context->input(), $derivedInput);
	}
}
