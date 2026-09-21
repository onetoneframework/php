<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\ComponentRegistry;
use Clover\Framework\Livewire\Hydrator;
use Clover\Framework\Livewire\Renderer;
use Clover\Framework\Livewire\UpdateHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class UpdateHandlerTest extends TestCase
{
	private Hydrator $hydrator;
	private UpdateHandler $handler;

	protected function setUp(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('update-fixture', UpdateFixtureComponent::class);
		$this->hydrator = new Hydrator();
		$renderer = new Renderer($this->hydrator);
		$this->handler = new UpdateHandler($registry, $this->hydrator, $renderer);
	}

	public function testHandleAppliesUpdatesThenInvokesPositionalAction(): void
	{
		$response = $this->handler->handle([
			'components' => [[
				'snapshot' => $this->snapshot(),
				'updates' => ['count' => '7'],
				'calls' => [['method' => 'increase', 'params' => [3]]],
			]],
		]);

		$component = $response['components'][0];
		$this->assertSame(10, $component['snapshot']['data']['count']);
		$this->assertStringContainsString('>10:initial</div>', $component['effects']['html']);
	}

	public function testHandleSupportsNamedParametersAndDefaultValues(): void
	{
		$response = $this->handler->handle([
			'components' => [[
				'snapshot' => $this->snapshot(),
				'calls' => [['method' => 'rename', 'params' => ['prefix' => 'named']]],
			]],
		]);

		$this->assertSame('named-default', $response['components'][0]['snapshot']['data']['label']);
	}

	public function testHandleReturnsQueuedDispatches(): void
	{
		$response = $this->handler->handle([
			'components' => [[
				'snapshot' => $this->snapshot(),
				'calls' => [['method' => 'save', 'params' => []]],
			]],
		]);

		$this->assertSame([
			['name' => 'saved', 'params' => ['initial'], 'target' => null],
		], $response['components'][0]['effects']['dispatches']);
	}

	public function testHandleSkipsNonArrayEntries(): void
	{
		$response = $this->handler->handle([
			'components' => ['invalid', ['snapshot' => $this->snapshot()]],
		]);

		$this->assertCount(1, $response['components']);
	}

	public function testMissingComponentsPayloadIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('missing "components"');

		$this->handler->handle([]);
	}

	public function testMissingSnapshotIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Missing component snapshot');

		$this->handler->handle(['components' => [['updates' => []]]]);
	}

	public function testMagicActionIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Magic methods are not callable');

		$this->callMethod('__livewireBind');
	}

	public function testFrameworkProtectedActionIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('is protected and cannot be invoked remotely');

		$this->callMethod('render');
	}

	public function testMissingActionIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('does not exist on component');

		$this->callMethod('missingAction');
	}

	public function testNonPublicActionIsRejected(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('must be public and non-static');

		$this->callMethod('hiddenAction');
	}

	public function testActionExceptionIsWrappedWithMethodName(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Livewire action "explode" failed: boom');

		$this->callMethod('explode');
	}

	private function snapshot(): array
	{
		$component = new UpdateFixtureComponent();
		$component->__livewireBind('update-fixture', 'wire-update');
		return $this->hydrator->dehydrate($component);
	}

	private function callMethod(string $method): void
	{
		$this->handler->handle([
			'components' => [[
				'snapshot' => $this->snapshot(),
				'calls' => [['method' => $method, 'params' => []]],
			]],
		]);
	}
}

final class UpdateFixtureComponent extends Component
{
	public int $count = 1;
	public string $label = 'initial';

	public function increase(int $amount = 1): void
	{
		$this->count += $amount;
	}

	public function rename(string $prefix, string $suffix = 'default'): void
	{
		$this->label = $prefix . '-' . $suffix;
	}

	public function save(): void
	{
		$this->dispatch('saved', [$this->label]);
	}

	public function explode(): void
	{
		throw new RuntimeException('boom');
	}

	protected function hiddenAction(): void
	{
	}

	public function render(): string
	{
		return '<div>' . $this->count . ':' . $this->label . '</div>';
	}
}
