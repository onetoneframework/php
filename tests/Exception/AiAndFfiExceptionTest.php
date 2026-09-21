<?php

declare(strict_types=1);

namespace Clover\Tests\Exception;

use Clover\Exception\AI\AgentCreationException;
use Clover\Exception\AI\AgentNotFoundException;
use Clover\Exception\AI\AttributeNotFoundException;
use Clover\Exception\AI\OrchestrationCancelledException;
use Clover\Exception\AI\ResponseSizeExceededException;
use Clover\Exception\AI\StepExecutionException;
use Clover\Exception\AI\StepResultNotFoundException;
use Clover\Exception\AI\WorkflowDeadlineExceededException;
use Clover\Exception\AI\WorkflowResponseBudgetExceededException;
use Clover\Exception\Broadcasting\InvalidChannelNameException;
use Clover\Exception\Broadcasting\UnknownBroadcasterException;
use Clover\Exception\FFI\BindingInitializationException;
use Clover\Exception\FFI\ExtensionNotLoadedException;
use Clover\Exception\FFI\FFIException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AiAndFfiExceptionTest extends TestCase
{
	public function testStepExecutionExceptionPreservesStepAndPreviousFailure(): void
	{
		$previous = new RuntimeException('transport failed');
		$exception = new StepExecutionException('summarize', $previous);

		$this->assertSame('summarize', $exception->stepIdentifier());
		$this->assertSame('AI workflow step "summarize" failed.', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}

	public function testAgentCreationExceptionPreservesAgentAndPreviousFailure(): void
	{
		$previous = new RuntimeException('factory failed');
		$exception = new AgentCreationException('planner', $previous);

		$this->assertSame('planner', $exception->agentName());
		$this->assertSame('AI agent creation failed.', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}

	public function testAiLookupAndBudgetExceptionsExposeStableMessages(): void
	{
		$this->assertSame('AI agent "reviewer" is not registered.', (new AgentNotFoundException('reviewer'))->getMessage());
		$this->assertSame('AI orchestration attribute "locale" was not found.', (new AttributeNotFoundException('locale'))->getMessage());
		$this->assertSame('AI workflow step result "draft" was not found.', (new StepResultNotFoundException('draft'))->getMessage());
		$this->assertSame('AI workflow step "draft" exceeded the response size limit.', (new ResponseSizeExceededException('draft'))->getMessage());
		$this->assertSame('AI workflow execution was cancelled.', (new OrchestrationCancelledException())->getMessage());
		$this->assertSame('AI workflow execution exceeded its deadline.', (new WorkflowDeadlineExceededException())->getMessage());
		$this->assertSame('AI workflow execution exhausted its response budget.', (new WorkflowResponseBudgetExceededException())->getMessage());
	}

	public function testBroadcastingExceptionsIncludeInvalidInput(): void
	{
		$this->assertStringContainsString('bad channel', (new InvalidChannelNameException('bad channel'))->getMessage());
		$this->assertStringContainsString('redis-cluster', (new UnknownBroadcasterException('redis-cluster'))->getMessage());
	}

	public function testExtensionNotLoadedFactoryReturnsFfiExceptionWithBindingName(): void
	{
		$exception = ExtensionNotLoadedException::forBinding('WindowsAudio');

		$this->assertInstanceOf(FFIException::class, $exception);
		$this->assertStringContainsString('WindowsAudio', $exception->getMessage());
		$this->assertStringContainsString('ffi.enable', $exception->getMessage());
	}

	public function testBindingInitializationFactoryPreservesContextAndPreviousFailure(): void
	{
		$previous = new RuntimeException('library missing');
		$exception = BindingInitializationException::forBinding('OpenAL', 'C:\\native\\openal.dll', $previous);

		$this->assertInstanceOf(FFIException::class, $exception);
		$this->assertStringContainsString('OpenAL', $exception->getMessage());
		$this->assertStringContainsString('C:\\native\\openal.dll', $exception->getMessage());
		$this->assertStringContainsString('library missing', $exception->getMessage());
		$this->assertSame($previous, $exception->getPrevious());
	}
}
