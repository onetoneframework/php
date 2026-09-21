<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use PHPUnit\Framework\TestCase;

final class ComponentTest extends TestCase
{
	public function testPublicPropertySnapshotExcludesPrivateProtectedStaticAndUninitialisedState(): void
	{
		$component = new ComponentFixture();

		$this->assertSame([
			'count' => 2,
			'label' => 'demo',
			'nullable' => null,
			'payload' => ['a' => 1],
		], $component->getPublicProperties());
	}

	public function testInlineInterpolatesAndEscapesPublicStateAndAdditionalData(): void
	{
		$component = new ComponentFixture();

		$html = $component->inline(
			'{{ $label }}|{{ $count }}|{{ $missing }}|{{ $nullable }}|{{ $payload }}|{{ $custom }}',
			['label' => '<b>override</b>', 'custom' => '"quoted"']
		);

		$this->assertSame(
			'&lt;b&gt;override&lt;/b&gt;|2|||{&quot;a&quot;:1}|&quot;quoted&quot;',
			$html
		);
	}

	public function testDispatchQueueIsConsumedExactlyOnce(): void
	{
		$component = new ComponentFixture();
		$component->dispatch('saved', [10, 'ok']);
		$component->dispatch('refresh', [], 'table');

		$this->assertSame([
			['name' => 'saved', 'params' => [10, 'ok'], 'target' => null],
			['name' => 'refresh', 'params' => [], 'target' => 'table'],
		], $component->__livewireConsumeDispatches());
		$this->assertSame([], $component->__livewireConsumeDispatches());
	}

	public function testLivewireIdentityCanBeBoundAndRead(): void
	{
		$component = new ComponentFixture();

		$this->assertNull($component->__livewireName());
		$this->assertNull($component->__livewireId());

		$component->__livewireBind('component-fixture', 'wire-abc');

		$this->assertSame('component-fixture', $component->__livewireName());
		$this->assertSame('wire-abc', $component->__livewireId());
	}
}

final class ComponentFixture extends Component
{
	public static string $staticValue = 'ignored';
	public int $count = 2;
	public string $label = 'demo';
	public ?string $nullable = null;
	public array $payload = ['a' => 1];
	public string $uninitialised;
	protected string $protectedValue = 'ignored';
	private string $privateValue = 'ignored';

	public function render(): string
	{
		return '<div>fixture</div>';
	}
}
