<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentRequest;
use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Contract\AI\AgentInterface;
use LogicException;
use Throwable;

/**
 * Vendor-neutral agent fake with queued outcomes and captured requests.
 */
final class RecordingAgent implements AgentInterface
{
	private string $agentName;

	private AgentInvocationLog $invocationLog;

	/**
	 * @var list<AgentResponse|Throwable>
	 */
	private array $outcomes;

	/**
	 * @var list<AgentRequest>
	 */
	private array $requests = [];

	/**
	 * Create an agent with one queued response or failure per invocation.
	 */
	public function __construct(
		string $agentName,
		AgentInvocationLog $invocationLog,
		AgentResponse|Throwable ...$outcomes
	) {
		$this->agentName = $agentName;
		$this->invocationLog = $invocationLog;
		$this->outcomes = $outcomes;
	}

	public function name(): string
	{
		return $this->agentName;
	}

	public function execute(AgentRequest $request): AgentResponse
	{
		$this->requests[] = $request;
		$this->invocationLog->record($this->agentName, $request->stepIdentifier());

		if ($this->outcomes === []) {
			throw new LogicException(
				sprintf('No fake outcome remains for agent "%s".', $this->agentName)
			);
		}

		$outcome = array_shift($this->outcomes);
		if ($outcome instanceof Throwable) {
			throw $outcome;
		}

		return $outcome;
	}

	/**
	 * Return captured requests in invocation order.
	 *
	 * @return list<AgentRequest>
	 */
	public function requests(): array
	{
		return $this->requests;
	}

	/**
	 * Return the number of attempted invocations.
	 */
	public function callCount(): int
	{
		return count($this->requests);
	}
}
