<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\AI;

use RuntimeException;
use Throwable;

/**
 * Wraps a failure with the workflow step that caused it.
 */
final class StepExecutionException extends RuntimeException
{
	private string $stepIdentifier;

	/**
	 * Preserve both the failed step identifier and the original error.
	 */
	public function __construct(string $stepIdentifier, Throwable $previous)
	{
		$this->stepIdentifier = $stepIdentifier;

		parent::__construct(
			sprintf('AI workflow step "%s" failed.', $stepIdentifier),
			previous: $previous
		);
	}

	/**
	 * Return the identifier of the failed step.
	 */
	public function stepIdentifier(): string
	{
		return $this->stepIdentifier;
	}
}
