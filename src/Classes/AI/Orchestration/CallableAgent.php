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
use Clover\Contract\AI\AgentInterface;
use Clover\Exception\AI\InvalidAgentException;
use Clover\Exception\AI\InvalidIdentifierException;

/**
 * Adapts a callable or an existing vendor client to the agent contract.
 */
final class CallableAgent implements AgentInterface
{
	private string $name;

	/**
	 * @var Closure(AgentRequest): AgentResponse
	 */
	private Closure $handler;

	/**
	 * Create an agent from a normalized request handler.
	 *
	 * @param callable(AgentRequest): AgentResponse $handler
	 */
	public function __construct(string $name, callable $handler)
	{
		try {
			$this->name = (new Identifier($name))->value();
		} catch (InvalidIdentifierException $exception) {
			throw new InvalidAgentException('Callable AI agents must use a valid name.', previous: $exception);
		}

		$this->handler = Closure::fromCallable($handler);
	}

	/**
	 * Return the stable routing name.
	 */
	public function name(): string
	{
		return $this->name;
	}

	/**
	 * Invoke the adapted handler.
	 */
	public function execute(AgentRequest $request): AgentResponse
	{
		return ($this->handler)($request);
	}
}
