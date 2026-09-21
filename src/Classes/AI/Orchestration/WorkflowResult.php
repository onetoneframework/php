<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use ArrayIterator;
use Clover\Exception\AI\InvalidWorkflowException;
use Clover\Exception\AI\StepResultNotFoundException;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Immutable collection of completed AI workflow step results.
 *
 * @implements IteratorAggregate<string, StepResult>
 */
final class WorkflowResult implements Countable, IteratorAggregate
{
	/**
	 * @var array<string, StepResult>
	 */
	private array $results = [];

	/**
	 * Create a result collection in workflow execution order.
	 */
	public function __construct(StepResult ...$results)
	{
		foreach ($results as $result) {
			$identifier = $result->stepIdentifier();
			if (isset($this->results[$identifier])) {
				throw new InvalidWorkflowException(
					sprintf('AI workflow result "%s" was provided more than once.', $identifier)
				);
			}

			$this->results[$identifier] = $result;
		}
	}

	/**
	 * Return whether the result contains the requested step.
	 */
	public function has(string $stepIdentifier): bool
	{
		return isset($this->results[$stepIdentifier]);
	}

	/**
	 * Return a required completed step result.
	 */
	public function result(string $stepIdentifier): StepResult
	{
		if (!$this->has($stepIdentifier)) {
			throw new StepResultNotFoundException($stepIdentifier);
		}

		return $this->results[$stepIdentifier];
	}

	/**
	 * Return the number of completed steps.
	 */
	public function count(): int
	{
		return count($this->results);
	}

	/**
	 * Iterate over results in workflow execution order.
	 *
	 * @return Traversable<string, StepResult>
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->results);
	}
}
