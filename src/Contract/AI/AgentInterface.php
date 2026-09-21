<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Contract\AI;

use Clover\Classes\AI\Orchestration\AgentRequest;
use Clover\Classes\AI\Orchestration\AgentResponse;

/**
 * Defines the vendor-neutral boundary used by the AI orchestrator.
 */
interface AgentInterface
{
	/**
	 * Return the stable name used to route workflow steps.
	 */
	public function name(): string;

	/**
	 * Execute one workflow step and return a normalized response.
	 */
	public function execute(AgentRequest $request): AgentResponse;
}
