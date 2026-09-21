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
use Clover\Exception\AI\StepResultNotFoundException;

/**
 * Immutable input and declared dependency results visible to one step.
 */
final class ExecutionContext
{
	private Attributes $input;

	/**
	 * @var array<string, StepResult>
	 */
	private array $dependencyResults = [];

	/**
	 * Scope a step to its workflow input and declared dependencies.
	 */
	public function __construct(Attributes $input, StepResult ...$dependencyResults)
	{
		$this->input = $input;

		foreach ($dependencyResults as $dependencyResult) {
			$identifier = $dependencyResult->stepIdentifier();
			if (isset($this->dependencyResults[$identifier])) {
				throw new InvalidWorkflowException(
					sprintf('Dependency result "%s" was provided more than once.', $identifier)
				);
			}

			$this->dependencyResults[$identifier] = $dependencyResult;
		}
	}

	/**
	 * Return the shared workflow input attributes.
	 */
	public function input(): Attributes
	{
		return $this->input;
	}

	/**
	 * Return whether the current step declares the requested result.
	 */
	public function hasDependencyResult(string $stepIdentifier): bool
	{
		return isset($this->dependencyResults[$stepIdentifier]);
	}

	/**
	 * Return a required declared dependency result.
	 */
	public function dependencyResult(string $stepIdentifier): StepResult
	{
		if (!$this->hasDependencyResult($stepIdentifier)) {
			throw new StepResultNotFoundException($stepIdentifier);
		}

		return $this->dependencyResults[$stepIdentifier];
	}
}
