<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\Hydrator;
use Clover\Framework\Livewire\Renderer;
use PHPUnit\Framework\TestCase;

final class RendererTest extends TestCase
{
	private Renderer $renderer;

	protected function setUp(): void
	{
		$this->renderer = new Renderer(new Hydrator());
	}

	public function testSingleRootReceivesWireAttributesWithoutExtraWrapper(): void
	{
		$component = new RendererFixtureComponent('<section class="box"><span>Hi</span></section>');
		$component->__livewireBind('renderer-fixture', 'wire-root');

		$result = $this->renderer->render($component);

		$this->assertStringStartsWith('<section class="box" wire:id="wire-root" wire:component="renderer-fixture"', $result['html']);
		$this->assertStringContainsString('wire:snapshot=', $result['html']);
		$this->assertStringEndsWith('</section>', $result['html']);
		$this->assertSame('wire-root', $result['snapshot']['id']);
	}

	public function testMultipleRootsAreWrappedInSingleWireDiv(): void
	{
		$component = new RendererFixtureComponent('<p>One</p><p>Two</p>');
		$component->__livewireBind('renderer-fixture', 'wire-multi');

		$html = $this->renderer->render($component)['html'];

		$this->assertStringStartsWith('<div wire:id="wire-multi"', $html);
		$this->assertStringContainsString('<p>One</p><p>Two</p>', $html);
		$this->assertStringEndsWith('</div>', $html);
	}

	public function testTextAndEmptyFragmentsAreWrapped(): void
	{
		$text = new RendererFixtureComponent('plain text');
		$text->__livewireBind('renderer-fixture', 'wire-text');
		$empty = new RendererFixtureComponent('');
		$empty->__livewireBind('renderer-fixture', 'wire-empty');

		$this->assertStringContainsString('>plain text</div>', $this->renderer->render($text)['html']);
		$this->assertStringStartsWith('<div wire:id="wire-empty"', $this->renderer->render($empty)['html']);
	}

	public function testRenderConsumesDispatchesAndIncludesThemInEffectsPayload(): void
	{
		$component = new RendererFixtureComponent('<div>event</div>');
		$component->__livewireBind('renderer-fixture', 'wire-event');
		$component->dispatch('saved', ['id' => 1], 'table');

		$first = $this->renderer->render($component);
		$second = $this->renderer->render($component);

		$this->assertSame([
			['name' => 'saved', 'params' => ['id' => 1], 'target' => 'table'],
		], $first['dispatches']);
		$this->assertSame([], $second['dispatches']);
	}

	public function testSnapshotAttributeEscapesQuotesAndMarkup(): void
	{
		$component = new RendererFixtureComponent('<div>safe</div>');
		$component->__livewireBind('renderer"<fixture', 'wire-"<id');

		$html = $this->renderer->render($component)['html'];

		$this->assertStringContainsString('wire:id="wire-&quot;&lt;id"', $html);
		$this->assertStringContainsString('wire:component="renderer&quot;&lt;fixture"', $html);
		$this->assertStringNotContainsString('wire:id="wire-"<id"', $html);
	}
}

final class RendererFixtureComponent extends Component
{
	public string $state = 'demo';

	public function __construct(private readonly string $html)
	{
	}

	public function render(): string
	{
		return $this->html;
	}
}
