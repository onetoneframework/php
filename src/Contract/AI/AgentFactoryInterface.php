<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Contract\AI;

/**
 * Creates an isolated agent instance for one workflow run.
 */
interface AgentFactoryInterface
{
	public function name(): string;

	public function create(): AgentInterface;
}
