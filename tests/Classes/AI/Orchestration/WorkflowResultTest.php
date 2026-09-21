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
use Clover\Classes\AI\Orchestration\StepResult;
use Clover\Classes\AI\Orchestration\WorkflowResult;
use Clover\Exception\AI\InvalidWorkflowException;
use Clover\Exception\AI\StepResultNotFoundException;
use PHPUnit\Framework\TestCase;

final class WorkflowResultTest extends TestCase
{
	private const EXPECTED_RESULT_COUNT = 2;

	public function testResultsAreRetrievedAndIteratedInExecutionOrder(): void
	{
		$research = new StepResult('research', new AgentResponse('research output'));
		$draft = new StepResult('draft', new AgentResponse('draft output'));
		$result = new WorkflowResult($research, $draft);

		self::assertSame(self::EXPECTED_RESULT_COUNT, $result->count());
		self::assertTrue($result->has('research'));
		self::assertSame($research, $result->result('research'));
		self::assertSame([
			'research' => $research,
			'draft' => $draft,
		], iterator_to_array($result));
	}

	public function testMissingResultThrowsTypedException(): void
	{
		$result = new WorkflowResult();

		$this->expectException(StepResultNotFoundException::class);
		$this->expectExceptionMessage('"missing" was not found');

		$result->result('missing');
	}

	public function testDuplicateResultIdentifierIsRejected(): void
	{
		$first = new StepResult('research', new AgentResponse('first'));
		$second = new StepResult('research', new AgentResponse('second'));

		$this->expectException(InvalidWorkflowException::class);
		$this->expectExceptionMessage('result "research" was provided more than once');

		new WorkflowResult($first, $second);
	}
}
