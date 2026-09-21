<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Service;

use Clover\Classes\AI\Orchestration\AgentRegistry;
use Clover\Classes\AI\Orchestration\Orchestrator;
use Clover\Support\ServiceProvider;

/**
 * Registers the shared AI orchestration services.
 */
final class AIOrchestrationServiceProvider extends ServiceProvider
{
	/**
	 * Register the agent registry and orchestrator as shared services.
	 */
	public function register(): void
	{
		$agentRegistry = new AgentRegistry();
		$this->app->singleton(AgentRegistry::class, $agentRegistry);
		$this->app->singleton(Orchestrator::class, new Orchestrator($agentRegistry));

		$this->app->bind('ai.agents', AgentRegistry::class);
		$this->app->bind('ai.orchestrator', Orchestrator::class);
	}
}
