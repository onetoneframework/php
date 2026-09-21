<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

use Closure;
use Clover\Contract\AI\AgentFactoryInterface;
use Clover\Contract\AI\AgentInterface;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidIdentifierException;

/**
 * Adapts an application factory to the run-scoped agent contract.
 */
final class CallableAgentFactory implements AgentFactoryInterface
{
	private string $name;

	/**
	 * @var Closure(): AgentInterface
	 */
	private Closure $factory;

	/**
	 * @param callable(): AgentInterface $factory
	 */
	public function __construct(string $name, callable $factory)
	{
		try {
			$this->name = (new Identifier($name))->value();
		} catch (InvalidIdentifierException $exception) {
			throw new InvalidAgentException('Callable AI agent factories must use a valid name.', previous: $exception);
		}

		$this->factory = Closure::fromCallable($factory);
	}

	public function name(): string
	{
		return $this->name;
	}

	public function create(): AgentInterface
	{
		$agent = ($this->factory)();
		if (!$agent instanceof AgentInterface || $agent->name() !== $this->name) {
			throw new InvalidAgentException('AI agent factories must create an agent with their registered name.');
		}

		return $agent;
	}
}
