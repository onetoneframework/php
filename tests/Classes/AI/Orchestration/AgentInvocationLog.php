<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

/**
 * Records cross-agent invocation order for deterministic orchestration tests.
 */
final class AgentInvocationLog
{
	/**
	 * @var list<array{agent: string, step: string}>
	 */
	private array $entries = [];

	/**
	 * Record one normalized agent invocation.
	 */
	public function record(string $agentName, string $stepIdentifier): void
	{
		$this->entries[] = [
			'agent' => $agentName,
			'step' => $stepIdentifier,
		];
	}

	/**
	 * Return invocations in execution order.
	 *
	 * @return list<array{agent: string, step: string}>
	 */
	public function entries(): array
	{
		return $this->entries;
	}
}
