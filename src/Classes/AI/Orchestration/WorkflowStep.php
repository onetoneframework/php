<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Exception\AI\InvalidWorkflowException;
use Clover\Exception\AI\InvalidIdentifierException;

/**
 * Immutable definition of one agent invocation in a workflow.
 */
final class WorkflowStep
{
	public const MAXIMUM_INSTRUCTION_BYTES = 1_048_576;

	private string $identifier;

	private string $agentName;

	private string $instruction;

	/**
	 * @var array<string, string>
	 */
	private array $dependencies = [];

	/**
	 * Define the step identity, routed agent, and instruction.
	 */
	public function __construct(string $identifier, string $agentName, string $instruction)
	{
		$this->identifier = self::validatedIdentifier($identifier);
		$this->agentName = self::validatedIdentifier($agentName);

		if (trim($instruction) === '' || strlen($instruction) > self::MAXIMUM_INSTRUCTION_BYTES) {
			throw new InvalidWorkflowException('AI workflow step instructions must be non-blank and remain within the size limit.');
		}

		$this->instruction = $instruction;
	}

	/**
	 * Return the stable step identifier.
	 */
	public function identifier(): string
	{
		return $this->identifier;
	}

	/**
	 * Return the registered agent name used by this step.
	 */
	public function agentName(): string
	{
		return $this->agentName;
	}

	/**
	 * Return the instruction sent to the selected agent.
	 */
	public function instruction(): string
	{
		return $this->instruction;
	}

	/**
	 * Return a new step with the provided dependencies.
	 */
	public function withDependencies(string ...$dependencyIdentifiers): self
	{
		$step = clone $this;

		foreach ($dependencyIdentifiers as $dependencyIdentifier) {
			$validatedIdentifier = self::validatedIdentifier($dependencyIdentifier);

			if ($validatedIdentifier === $this->identifier) {
				throw new InvalidWorkflowException(
					sprintf('AI workflow step "%s" cannot depend on itself.', $this->identifier)
				);
			}

			if (isset($step->dependencies[$validatedIdentifier])) {
				throw new InvalidWorkflowException(
					sprintf('AI workflow step "%s" declares dependency "%s" more than once.', $this->identifier, $validatedIdentifier)
				);
			}

			$step->dependencies[$validatedIdentifier] = $validatedIdentifier;
		}

		return $step;
	}

	/**
	 * Return dependency identifiers in declaration order.
	 *
	 * @return list<string>
	 */
	public function dependencies(): array
	{
		return array_values($this->dependencies);
	}

	private static function validatedIdentifier(string $identifier): string
	{
		try {
			return (new Identifier($identifier))->value();
		} catch (InvalidIdentifierException $exception) {
			throw new InvalidWorkflowException('AI workflow steps must use valid identifiers.', previous: $exception);
		}
	}
}
