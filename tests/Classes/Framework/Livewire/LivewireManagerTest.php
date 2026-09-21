<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\LivewireManager;
use PHPUnit\Framework\TestCase;

final class LivewireManagerTest extends TestCase
{
	public function testComponentShortcutRegistersAndAccessorsReturnCollaborators(): void
	{
		$manager = new LivewireManager();

		$this->assertSame($manager, $manager->component('manager-fixture', ManagerFixtureComponent::class));
		$this->assertSame(ManagerFixtureComponent::class, $manager->registry()->resolve('manager-fixture'));
		$this->assertSame($manager->updateHandler(), $manager->updateHandler());
		$this->assertSame($manager->renderer(), $manager->renderer());
	}

	public function testMountInvokesOptionalNamedMountArgumentsAndProducesSnapshot(): void
	{
		$manager = (new LivewireManager())->component('manager-fixture', ManagerFixtureComponent::class);

		$result = $manager->mount('manager-fixture', ['start' => 8, 'label' => 'hello']);

		$this->assertSame(8, $result['snapshot']['data']['count']);
		$this->assertSame('hello', $result['snapshot']['data']['label']);
		$this->assertMatchesRegularExpression('/^wire-[a-f0-9]{12}$/', $result['snapshot']['id']);
		$this->assertStringContainsString('>hello:8</div>', $result['html']);
	}

	public function testMountUsesDefaultOptionalArgumentsWhenMissing(): void
	{
		$manager = (new LivewireManager())->component('manager-fixture', ManagerFixtureComponent::class);

		$result = $manager->mount('manager-fixture');

		$this->assertSame(1, $result['snapshot']['data']['count']);
		$this->assertSame('default', $result['snapshot']['data']['label']);
	}

	public function testRenderReturnsOnlyHtml(): void
	{
		$manager = (new LivewireManager())->component('manager-fixture', ManagerFixtureComponent::class);

		$html = $manager->render('manager-fixture', ['label' => 'rendered']);

		$this->assertStringContainsString('wire:component="manager-fixture-component"', $html);
		$this->assertStringContainsString('>rendered:1</div>', $html);
	}

	public function testAutoloadFromIsFluentAndIgnoresMissingDirectories(): void
	{
		$manager = new LivewireManager();

		$this->assertSame($manager, $manager->autoloadFrom(__DIR__ . '/missing-one'));
		$this->assertSame($manager, $manager->autoloadFrom(__DIR__ . '/missing-one'));
		$manager->ensureAutoloaded();
		$manager->ensureAutoloaded();

		$this->assertSame([], $manager->registry()->all());
	}

	public function testGenerateIdUsesExpectedWirePrefixAndEntropyLength(): void
	{
		$ids = array_map(static fn (): string => LivewireManager::generateId(), range(1, 10));

		foreach ($ids as $id) {
			$this->assertMatchesRegularExpression('/^wire-[a-f0-9]{12}$/', $id);
		}
		$this->assertCount(10, array_unique($ids));
	}
}

final class ManagerFixtureComponent extends Component
{
	public int $count = 0;
	public string $label = '';

	public function mount(int $start = 1, string $label = 'default'): void
	{
		$this->count = $start;
		$this->label = $label;
	}

	public function render(): string
	{
		return '<div>' . $this->label . ':' . $this->count . '</div>';
	}
}
