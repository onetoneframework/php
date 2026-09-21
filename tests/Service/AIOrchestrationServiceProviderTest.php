<?php

declare(strict_types=1);

namespace Clover\Tests\Service;

use Clover\Classes\AI\Orchestration\AgentRegistry;
use Clover\Classes\AI\Orchestration\Orchestrator;
use Clover\Component\Foundation\Application;
use Clover\Service\AIOrchestrationServiceProvider;
use PHPUnit\Framework\TestCase;

final class AIOrchestrationServiceProviderTest extends TestCase
{
	protected function tearDown(): void
	{
		Application::setInstance(null);

		parent::tearDown();
	}

	public function testRegisterCreatesSharedAgentRegistryWithAlias(): void
	{
		$application = new Application();
		$provider = new AIOrchestrationServiceProvider($application);

		$provider->register();

		$registry = $application->make(AgentRegistry::class);
		$this->assertInstanceOf(AgentRegistry::class, $registry);
		$this->assertSame($registry, $application->make(AgentRegistry::class));
		$this->assertSame($registry, $application->make('ai.agents'));
	}

	public function testRegisterCreatesSharedOrchestratorWithAlias(): void
	{
		$application = new Application();
		$provider = new AIOrchestrationServiceProvider($application);

		$provider->register();

		$orchestrator = $application->make(Orchestrator::class);
		$this->assertInstanceOf(Orchestrator::class, $orchestrator);
		$this->assertSame($orchestrator, $application->make(Orchestrator::class));
		$this->assertSame($orchestrator, $application->make('ai.orchestrator'));
	}
}
