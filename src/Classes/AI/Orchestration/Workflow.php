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
use Countable;

/**
 * Immutable directed acyclic workflow of AI agent steps.
 */
final class Workflow implements Countable
{
	public const MAXIMUM_STEPS = 256;

	/**
	 * @var array<string, WorkflowStep>
	 */
	private array $steps = [];

	/**
	 * @var list<string>
	 */
	private array $registrationOrder = [];

	/**
	 * Return a new workflow containing the provided step.
	 */
	public function withStep(WorkflowStep $step): self
	{
		if (count($this->steps) >= self::MAXIMUM_STEPS) {
			throw new InvalidWorkflowException('AI workflows exceed the supported step limit.');
		}

		$identifier = $step->identifier();
		if (isset($this->steps[$identifier])) {
			throw new InvalidWorkflowException(
				sprintf('AI workflow step "%s" is already defined.', $identifier)
			);
		}

		$workflow = clone $this;
		$workflow->steps[$identifier] = $step;
		$workflow->registrationOrder[] = $identifier;

		return $workflow;
	}

	/**
	 * Return a validated stable topological execution order.
	 *
	 * Ready steps retain their original registration order.
	 *
	 * @return list<WorkflowStep>
	 */
	public function orderedSteps(): array
	{
		if ($this->steps === []) {
			throw new InvalidWorkflowException('AI workflows must contain at least one step.');
		}

		foreach ($this->steps as $step) {
			foreach ($step->dependencies() as $dependencyIdentifier) {
				if (!isset($this->steps[$dependencyIdentifier])) {
					throw new InvalidWorkflowException(
						sprintf(
							'AI workflow step "%s" depends on unknown step "%s".',
							$step->identifier(),
							$dependencyIdentifier
						)
					);
				}
			}
		}

		$orderedSteps = [];
		$resolvedIdentifiers = [];

		// Each pass resolves exactly one ready step, so the loop is bounded by the step count.
		while (count($orderedSteps) < count($this->steps)) {
			$resolvedStep = false;

			foreach ($this->registrationOrder as $identifier) {
				if (isset($resolvedIdentifiers[$identifier])) {
					continue;
				}

				$step = $this->steps[$identifier];
				$dependenciesResolved = true;

				foreach ($step->dependencies() as $dependencyIdentifier) {
					if (!isset($resolvedIdentifiers[$dependencyIdentifier])) {
						$dependenciesResolved = false;
						break;
					}
				}

				if (!$dependenciesResolved) {
					continue;
				}

				$orderedSteps[] = $step;
				$resolvedIdentifiers[$identifier] = true;
				$resolvedStep = true;
				break;
			}

			if (!$resolvedStep) {
				$unresolvedIdentifiers = [];
				foreach ($this->registrationOrder as $identifier) {
					if (!isset($resolvedIdentifiers[$identifier])) {
						$unresolvedIdentifiers[] = $identifier;
					}
				}

				throw new InvalidWorkflowException(
					sprintf('AI workflow steps are unresolved because of a dependency cycle: %s.', implode(', ', $unresolvedIdentifiers))
				);
			}
		}

		return $orderedSteps;
	}

	/**
	 * Return the number of defined workflow steps.
	 */
	public function count(): int
	{
		return count($this->steps);
	}
}
