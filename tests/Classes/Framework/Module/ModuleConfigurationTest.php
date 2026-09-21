<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Module;

use Clover\Framework\Module\ModuleConfiguration;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ModuleConfigurationTest extends TestCase
{
	public function testDefaultsEnableEveryDiscoveredModule(): void
	{
		$configuration = ModuleConfiguration::fromArray([]);

		$this->assertTrue($configuration->isEnabled('board'));
		$this->assertSame([], $configuration->disabled);
		$this->assertSame([], $configuration->order);
	}

	public function testDisabledListOverridesEnabledAllowlist(): void
	{
		$configuration = ModuleConfiguration::fromArray([
			'enabled' => ['board', 'comment', 'comment'],
			'disabled' => ['comment'],
			'order' => ['board', 'comment'],
		]);

		$this->assertTrue($configuration->isEnabled('board'));
		$this->assertFalse($configuration->isEnabled('comment'));
		$this->assertFalse($configuration->isEnabled('payment'));
		$this->assertSame(['board', 'comment'], $configuration->enabled);
	}

	public function testInvalidConfigurationListIsRejected(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('disabled');

		ModuleConfiguration::fromArray(['disabled' => false]);
	}
}
