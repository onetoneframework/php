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
use Clover\Contract\AI\AgentFactoryInterface;
use Clover\Contract\AI\AgentInterface;
use LogicException;
use Throwable;

/**
 * Creates observable, isolated recording agents for deterministic workflow runs.
 */
final class RecordingAgentFactory implements AgentFactoryInterface
{
	private string $agentName;

	private AgentInvocationLog $invocationLog;

	/**
	 * @var list<list<AgentResponse|Throwable>>
	 */
	private array $creationOutcomes;

	/**
	 * @var list<RecordingAgent>
	 */
	private array $createdAgents = [];

	private int $creationAttempts = 0;

	/**
	 * @param list<AgentResponse|Throwable> ...$creationOutcomes
	 */
	public function __construct(
		string $agentName,
		AgentInvocationLog $invocationLog,
		array ...$creationOutcomes
	) {
		$this->agentName = $agentName;
		$this->invocationLog = $invocationLog;
		$this->creationOutcomes = $creationOutcomes;
	}

	public function name(): string
	{
		return $this->agentName;
	}

	public function create(): AgentInterface
	{
		$this->creationAttempts++;
		if ($this->creationOutcomes === []) {
			throw new LogicException(
				sprintf('No fake creation outcome remains for agent "%s".', $this->agentName)
			);
		}

		$outcomes = array_shift($this->creationOutcomes);
		$agent = new RecordingAgent($this->agentName, $this->invocationLog, ...$outcomes);
		$this->createdAgents[] = $agent;

		return $agent;
	}

	public function creationAttempts(): int
	{
		return $this->creationAttempts;
	}

	/**
	 * @return list<RecordingAgent>
	 */
	public function createdAgents(): array
	{
		return $this->createdAgents;
	}

	public function createdAgent(int $index = 0): RecordingAgent
	{
		if (!isset($this->createdAgents[$index])) {
			throw new LogicException('The requested fake agent was not created.');
		}

		return $this->createdAgents[$index];
	}
}
