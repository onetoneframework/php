<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Clover\Contract\AI\AgentFactoryInterface;
use Clover\Contract\AI\AgentInterface;
use Clover\Exception\AI\AgentNotFoundException;
use Clover\Exception\AI\InvalidAgentException;
use Countable;
use Throwable;
use WeakMap;

/**
 * Registry that routes workflow steps to vendor-neutral AI agents.
 */
final class AgentRegistry implements Countable
{
	/**
	 * @var array<string, AgentFactoryInterface>
	 */
	private array $factories = [];

	/**
	 * @var WeakMap<AgentInterface, bool>
	 */
	private WeakMap $issuedAgents;

	/**
	 * Create an empty registry with agent identity tracking.
	 */
	public function __construct()
	{
		$this->issuedAgents = new WeakMap();
	}

	/**
	 * Register a run-scoped agent factory under its stable name.
	 */
	public function register(AgentFactoryInterface $factory): void
	{
		try {
			$name = (new Identifier($factory->name()))->value();
		} catch (Throwable $exception) {
			throw new InvalidAgentException('AI agent factories must provide a valid name.', previous: $exception);
		}

		if (isset($this->factories[$name])) {
			throw new InvalidAgentException(sprintf('AI agent factory "%s" is already registered.', $name));
		}

		$this->factories[$name] = $factory;
	}

	/**
	 * Return whether an agent is registered under the requested name.
	 */
	public function has(string $name): bool
	{
		return isset($this->factories[$name]);
	}

	/**
	 * Resolve a registered factory without creating an agent.
	 */
	public function factory(string $name): AgentFactoryInterface
	{
		if (!$this->has($name)) {
			throw new AgentNotFoundException($name);
		}

		return $this->factories[$name];
	}

	/**
	 * Create an isolated agent for one workflow run.
	 */
	public function createAgent(string $name): AgentInterface
	{
		$agent = $this->factory($name)->create();
		if ($agent->name() !== $name) {
			throw new InvalidAgentException('AI agent factories must create an agent with their registered name.');
		}

		if (isset($this->issuedAgents[$agent])) {
			throw new InvalidAgentException('AI agent factories must create a new agent for each workflow run.');
		}

		$this->issuedAgents[$agent] = true;

		return $agent;
	}

	/**
	 * Return the number of registered agents.
	 */
	public function count(): int
	{
		return count($this->factories);
	}
}
