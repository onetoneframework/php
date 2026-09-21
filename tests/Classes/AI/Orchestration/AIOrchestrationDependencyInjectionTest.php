<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentRegistry;
use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Classes\AI\Orchestration\Attributes;
use Clover\Classes\AI\Orchestration\ExecutionControl;
use Clover\Classes\AI\Orchestration\Orchestrator;
use Clover\Classes\AI\Orchestration\Workflow;
use Clover\Classes\AI\Orchestration\WorkflowStep;
use Clover\Classes\DependencyInjection\Container as RuntimeContainer;
use Clover\Component\Foundation\Application;
use Clover\Component\Kernel\HttpKernel;
use PHPUnit\Framework\TestCase;

final class AIOrchestrationDependencyInjectionTest extends TestCase
{
	protected function tearDown(): void
	{
		Application::setInstance(null);
	}

	public function testApplicationDependencyConfigurationRegistersSharedServices(): void
	{
		$platformPath = dirname(__DIR__, 4);
		$configurationPath = $platformPath
			. DIRECTORY_SEPARATOR . 'root'
			. DIRECTORY_SEPARATOR . 'App'
			. DIRECTORY_SEPARATOR . 'Configure'
			. DIRECTORY_SEPARATOR . 'dependencies.php';
		$configureDependencies = require $configurationPath;
		$container = new RuntimeContainer();

		self::assertIsCallable($configureDependencies);
		$configureDependencies($container);

		$registry = $container->get(AgentRegistry::class);
		$orchestrator = $container->get(Orchestrator::class);

		self::assertInstanceOf(AgentRegistry::class, $registry);
		self::assertInstanceOf(Orchestrator::class, $orchestrator);
		self::assertSame($registry, $container->get('ai.agents'));
		self::assertSame($orchestrator, $container->get('ai.orchestrator'));

		$invocationLog = new AgentInvocationLog();
		$factory = new RecordingAgentFactory(
			'container-agent',
			$invocationLog,
			[new AgentResponse('container output')]
		);
		$registry->register($factory);
		$workflow = (new Workflow())->withStep(
			new WorkflowStep('generate', 'container-agent', 'Generate through the container.')
		);

		$result = $orchestrator->run($workflow, new Attributes(), new ExecutionControl());

		self::assertSame('container output', $result->result('generate')->response()->content());
		self::assertSame(1, $factory->creationAttempts());
		self::assertSame([
			['agent' => 'container-agent', 'step' => 'generate'],
		], $invocationLog->entries());
	}

	public function testHttpKernelBootRegistersSharedServicesThroughProvider(): void
	{
		$application = new Application();
		$kernel = new HttpKernel($application);

		$kernel->boot();
		$kernel->boot();

		$registry = $application->make(AgentRegistry::class);
		$orchestrator = $application->make(Orchestrator::class);

		self::assertInstanceOf(AgentRegistry::class, $registry);
		self::assertInstanceOf(Orchestrator::class, $orchestrator);
		self::assertSame($registry, $application->make('ai.agents'));
		self::assertSame($orchestrator, $application->make('ai.orchestrator'));
	}
}
