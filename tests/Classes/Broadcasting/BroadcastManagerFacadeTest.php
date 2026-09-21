<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Broadcasting;

use Clover\Classes\Broadcasting\BroadcastManager;
use Clover\Classes\Broadcasting\BroadcastManagerFacade;
use Clover\Classes\DependencyInjection\Container;
use Clover\Implement\BroadcastManagerInterface;
use PHPUnit\Framework\TestCase;

final class BroadcastManagerFacadeTest extends TestCase
{
	protected function setUp(): void
	{
		BroadcastManagerFacade::reset();
	}

	protected function tearDown(): void
	{
		BroadcastManagerFacade::reset();
	}

	public function testReturnsSingletonWhenNoContainerRegistered(): void
	{
		$first = BroadcastManagerFacade::getManager();
		$second = BroadcastManagerFacade::getManager();

		$this->assertSame($first, $second);
		$this->assertInstanceOf(BroadcastManager::class, $first);
	}

	public function testSetInstanceOverridesSingleton(): void
	{
		$custom = new BroadcastManager();
		BroadcastManagerFacade::setInstance($custom);

		$this->assertSame($custom, BroadcastManagerFacade::getManager());
	}

	public function testContainerResolutionTakesPrecedenceOverSingleton(): void
	{
		$singletonOnly = new BroadcastManager();
		BroadcastManagerFacade::setInstance($singletonOnly);

		$containerBound = new BroadcastManager();
		$container = new Container();
		$container->set(BroadcastManager::class, $containerBound);
		$container->bind(BroadcastManagerInterface::class, BroadcastManager::class);
		BroadcastManagerFacade::setContainer($container);

		$resolved = BroadcastManagerFacade::getManager();

		$this->assertSame($containerBound, $resolved);
		$this->assertNotSame($singletonOnly, $resolved);
	}

	public function testContainerResolutionFallsBackToSingletonOnFailure(): void
	{
		$fallback = new BroadcastManager();
		BroadcastManagerFacade::setInstance($fallback);

		$emptyContainer = new Container();
		BroadcastManagerFacade::setContainer($emptyContainer);

		$this->assertSame($fallback, BroadcastManagerFacade::getManager());
	}
}
