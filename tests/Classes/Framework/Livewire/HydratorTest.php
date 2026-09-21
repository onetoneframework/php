<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Framework\Livewire;

use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\Hydrator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class HydratorTest extends TestCase
{
	private mixed $previousLivewireKey;

	protected function setUp(): void
	{
		$this->previousLivewireKey = $_ENV['LIVEWIRE_KEY'] ?? null;
		$_ENV['LIVEWIRE_KEY'] = 'hydrator-test-key';
	}

	protected function tearDown(): void
	{
		if ($this->previousLivewireKey === null) {
			unset($_ENV['LIVEWIRE_KEY']);
			return;
		}

		$_ENV['LIVEWIRE_KEY'] = $this->previousLivewireKey;
	}

	public function testDehydrateIncludesIdentityPublicDataAndChecksum(): void
	{
		$component = new HydratorFixtureComponent();
		$component->__livewireBind('hydrator-fixture', 'wire-1');

		$snapshot = (new Hydrator())->dehydrate($component);

		$this->assertSame('hydrator-fixture', $snapshot['name']);
		$this->assertSame('wire-1', $snapshot['id']);
		$this->assertSame(5, $snapshot['data']['count']);
		$this->assertArrayHasKey('checksum', $snapshot);
		$this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $snapshot['checksum']);
	}

	public function testHydrateRestoresIdentityAndCoercesScalarState(): void
	{
		$hydrator = new Hydrator();
		$source = new HydratorFixtureComponent();
		$source->__livewireBind('hydrator-fixture', 'wire-2');
		$source->count = 12;
		$source->ratio = 2.5;
		$source->enabled = true;
		$source->label = 'source';
		$snapshot = $hydrator->dehydrate($source);
		$snapshot['data'] = [
			'count' => '7',
			'ratio' => '3.25',
			'enabled' => 'false',
			'label' => 100,
			'tags' => 'not-an-array',
			'nullable' => null,
		];
		$snapshot['checksum'] = \Clover\Framework\Livewire\Checksum::sign([
			'name' => $snapshot['name'],
			'id' => $snapshot['id'],
			'data' => $snapshot['data'],
		]);

		$target = new HydratorFixtureComponent();
		$hydrator->hydrate($target, $snapshot);

		$this->assertSame('hydrator-fixture', $target->__livewireName());
		$this->assertSame('wire-2', $target->__livewireId());
		$this->assertSame(7, $target->count);
		$this->assertSame(3.25, $target->ratio);
		$this->assertFalse($target->enabled);
		$this->assertSame('100', $target->label);
		$this->assertSame([], $target->tags);
		$this->assertNull($target->nullable);
	}

	public function testApplyUpdatesOnlyChangesKnownPublicProperties(): void
	{
		$component = new HydratorFixtureComponent();

		(new Hydrator())->applyUpdates($component, [
			'count' => '',
			'ratio' => '',
			'enabled' => '1',
			'unknown' => 'ignored',
			'privateValue' => 'ignored',
		]);

		$this->assertSame(0, $component->count);
		$this->assertSame(0.0, $component->ratio);
		$this->assertTrue($component->enabled);
		$this->assertSame('private', $component->privateValueForTest());
	}

	public function testHydrateRejectsTamperedSnapshot(): void
	{
		$hydrator = new Hydrator();
		$component = new HydratorFixtureComponent();
		$component->__livewireBind('hydrator-fixture', 'wire-3');
		$snapshot = $hydrator->dehydrate($component);
		$snapshot['data']['count'] = 999;

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('checksum mismatch');

		$hydrator->hydrate(new HydratorFixtureComponent(), $snapshot);
	}

	public function testHydrateRejectsNonArrayData(): void
	{
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('expected array "data"');

		(new Hydrator())->hydrate(new HydratorFixtureComponent(), [
			'name' => 'hydrator-fixture',
			'id' => 'wire-4',
			'data' => 'invalid',
			'checksum' => 'invalid',
		]);
	}

	public function testBindIdentityDelegatesToComponent(): void
	{
		$component = new HydratorFixtureComponent();

		(new Hydrator())->bindIdentity($component, 'demo', 'wire-demo');

		$this->assertSame('demo', $component->__livewireName());
		$this->assertSame('wire-demo', $component->__livewireId());
	}
}

final class HydratorFixtureComponent extends Component
{
	public int $count = 5;
	public float $ratio = 1.5;
	public bool $enabled = false;
	public string $label = 'demo';
	public array $tags = ['one'];
	public ?string $nullable = 'value';
	private string $privateValue = 'private';

	public function privateValueForTest(): string
	{
		return $this->privateValue;
	}

	public function render(): string
	{
		return '<div>hydrator</div>';
	}
}
