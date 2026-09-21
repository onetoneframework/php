<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Livewire;

use Clover\Framework\Livewire\Checksum;
use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\ComponentRegistry;
use Clover\Framework\Livewire\Hydrator;
use Clover\Framework\Livewire\Renderer;
use Clover\Framework\Livewire\UpdateHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LivewireCoreTest extends TestCase
{
	private ?string $previousLivewireKey = null;
	private ?string $previousApplicationKey = null;
	private string|false $previousLivewireEnvironmentKey = false;
	private string|false $previousApplicationEnvironmentKey = false;

	protected function setUp(): void
	{
		$this->previousLivewireKey = $_ENV['LIVEWIRE_KEY'] ?? null;
		$this->previousApplicationKey = $_ENV['APP_KEY'] ?? null;
		$this->previousLivewireEnvironmentKey = getenv('LIVEWIRE_KEY');
		$this->previousApplicationEnvironmentKey = getenv('APP_KEY');

		$_ENV['LIVEWIRE_KEY'] = 'livewire-core-test-key';
		unset($_ENV['APP_KEY']);
		putenv('LIVEWIRE_KEY=livewire-core-test-key');
		putenv('APP_KEY');
	}

	protected function tearDown(): void
	{
		$this->restoreEnvironmentValue('LIVEWIRE_KEY', $this->previousLivewireKey, $this->previousLivewireEnvironmentKey);
		$this->restoreEnvironmentValue('APP_KEY', $this->previousApplicationKey, $this->previousApplicationEnvironmentKey);
	}

	public function testRegistryNormalizesAliasesAndResolvesClassNames(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('UserProfile', LivewireCoreTypedComponent::class);

		self::assertSame('user-profile', $registry->normalize('USER_PROFILE'));
		self::assertSame('livewire-core-text-component', $registry->aliasFromClass(LivewireCoreTextComponent::class));
		self::assertSame(LivewireCoreTypedComponent::class, $registry->resolve('user_profile'));
		self::assertSame(LivewireCoreTextComponent::class, $registry->resolve(LivewireCoreTextComponent::class));
		self::assertArrayHasKey('livewire-core-text-component', $registry->all());
	}

	public function testRegistryThrowsForUnknownAlias(): void
	{
		$registry = new ComponentRegistry();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('not registered');

		$registry->resolve('missing-component');
	}

	public function testChecksumIsCanonicalAndRejectsInvalidValues(): void
	{
		$firstSnapshot = ['name' => 'demo', 'id' => 'wire-1', 'data' => ['b' => 2, 'a' => 1]];
		$secondSnapshot = ['name' => 'demo', 'id' => 'wire-1', 'data' => ['a' => 1, 'b' => 2]];

		$checksum = Checksum::sign($firstSnapshot);

		self::assertSame($checksum, Checksum::sign($secondSnapshot));
		self::assertTrue(Checksum::verify($secondSnapshot, $checksum));
		self::assertFalse(Checksum::verify(['name' => 'demo', 'id' => 'wire-1', 'data' => ['a' => 9]], $checksum));
		self::assertFalse(Checksum::verify($secondSnapshot, ''));
	}

	public function testHydratorDehydratesPublicStateAndCoercesTypedUpdates(): void
	{
		$component = new LivewireCoreTypedComponent();
		$component->__livewireBind('typed', 'wire-typed');
		$component->title = 'Initial';
		$hydrator = new Hydrator();

		$dehydrated = $hydrator->dehydrate($component);

		self::assertSame('Initial', $dehydrated['data']['title']);
		self::assertArrayNotHasKey('secret', $dehydrated['data']);
		self::assertArrayNotHasKey('staticState', $dehydrated['data']);
		self::assertArrayNotHasKey('uninitialized', $dehydrated['data']);

		$data = [
			'count' => '12',
			'enabled' => 'false',
			'ratio' => '2.5',
			'title' => ['unsafe' => '<b>tag</b>'],
			'items' => 'not-array',
			'optional' => null,
			'secret' => 'client-secret',
			'missing' => 'ignored',
		];
		$snapshot = [
			'name' => 'typed',
			'id' => 'wire-typed',
			'data' => $data,
			'checksum' => Checksum::sign(['name' => 'typed', 'id' => 'wire-typed', 'data' => $data]),
		];

		$hydrator->hydrate($component, $snapshot);

		self::assertSame(12, $component->count);
		self::assertFalse($component->enabled);
		self::assertSame(2.5, $component->ratio);
		self::assertSame('{"unsafe":"<b>tag<\/b>"}', $component->title);
		self::assertSame([], $component->items);
		self::assertNull($component->optional);
		self::assertSame('server-secret', $component->secretValue());
	}

	public function testHydratorRejectsMalformedAndTamperedSnapshots(): void
	{
		$hydrator = new Hydrator();
		$component = new LivewireCoreTypedComponent();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('malformed');

		$hydrator->hydrate($component, [
			'name' => 'typed',
			'id' => 'wire-typed',
			'data' => 'bad',
			'checksum' => 'checksum',
		]);
	}

	public function testHydratorRejectsChecksumMismatch(): void
	{
		$hydrator = new Hydrator();
		$component = new LivewireCoreTypedComponent();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('checksum mismatch');

		$hydrator->hydrate($component, [
			'name' => 'typed',
			'id' => 'wire-typed',
			'data' => ['count' => 4],
			'checksum' => 'bad-checksum',
		]);
	}

	public function testRendererInjectsSingleRootAttributesAndWrapsFragments(): void
	{
		$renderer = new Renderer(new Hydrator());

		$singleRoot = new LivewireCoreTextComponent('<article class="card">Body</article>');
		$singleRoot->__livewireBind('single-root', 'wire-single');
		$singleRootResult = $renderer->render($singleRoot);

		self::assertStringStartsWith('<article class="card" wire:id="wire-single"', $singleRootResult['html']);
		self::assertStringContainsString('wire:component="single-root"', $singleRootResult['html']);

		$textFragment = new LivewireCoreTextComponent('Plain text');
		$textFragment->__livewireBind('text-fragment', 'wire-text');
		$textFragmentResult = $renderer->render($textFragment);

		self::assertStringStartsWith('<div wire:id="wire-text"', $textFragmentResult['html']);
		self::assertStringEndsWith('>Plain text</div>', $textFragmentResult['html']);

		$siblingFragment = new LivewireCoreTextComponent('<span>One</span><span>Two</span>');
		$siblingFragment->__livewireBind('sibling-fragment', 'wire-sibling');
		$siblingFragmentResult = $renderer->render($siblingFragment);

		self::assertStringStartsWith('<div wire:id="wire-sibling"', $siblingFragmentResult['html']);
		self::assertStringContainsString('<span>One</span><span>Two</span>', $siblingFragmentResult['html']);

		$nestedRoot = new LivewireCoreTextComponent('<div><div>Nested</div></div>');
		$nestedRoot->__livewireBind('nested-root', 'wire-nested');
		$nestedRootResult = $renderer->render($nestedRoot);

		self::assertStringStartsWith('<div wire:id="wire-nested"', $nestedRootResult['html']);
		self::assertStringContainsString('<div>Nested</div></div>', $nestedRootResult['html']);
	}

	public function testUpdateHandlerInvokesNamedAndPositionalActionsAndReturnsDispatches(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('action-fixture', LivewireCoreActionComponent::class);
		$hydrator = new Hydrator();
		$renderer = new Renderer($hydrator);
		$handler = new UpdateHandler($registry, $hydrator, $renderer);
		$component = new LivewireCoreActionComponent();
		$component->__livewireBind('action-fixture', 'wire-action');
		$snapshot = $hydrator->dehydrate($component);

		$response = $handler->handle([
			'components' => [
				[
					'snapshot' => $snapshot,
					'updates' => [],
					'calls' => [
						['method' => 'setValues', 'params' => ['title' => 'Alpha', 'count' => 5]],
						['method' => 'add', 'params' => [2, 3]],
						['method' => 'dispatchSaved', 'params' => []],
					],
				],
			],
		]);

		self::assertSame('Alpha', $response['components'][0]['snapshot']['data']['title']);
		self::assertSame(10, $response['components'][0]['snapshot']['data']['count']);
		self::assertSame('saved', $response['components'][0]['effects']['dispatches'][0]['name']);
		self::assertSame(['Alpha', 10], $response['components'][0]['effects']['dispatches'][0]['params']);
		self::assertStringContainsString('Alpha:10', $response['components'][0]['effects']['html']);
	}

	public function testUpdateHandlerRejectsMagicMethods(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('action-fixture', LivewireCoreActionComponent::class);
		$hydrator = new Hydrator();
		$handler = new UpdateHandler($registry, $hydrator, new Renderer($hydrator));
		$component = new LivewireCoreActionComponent();
		$component->__livewireBind('action-fixture', 'wire-action');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Magic methods');

		$handler->handle([
			'components' => [
				[
					'snapshot' => $hydrator->dehydrate($component),
					'updates' => [],
					'calls' => [['method' => '__debugInfo', 'params' => []]],
				],
			],
		]);
	}

	public function testUpdateHandlerRejectsStaticMethods(): void
	{
		$registry = new ComponentRegistry();
		$registry->register('action-fixture', LivewireCoreActionComponent::class);
		$hydrator = new Hydrator();
		$handler = new UpdateHandler($registry, $hydrator, new Renderer($hydrator));
		$component = new LivewireCoreActionComponent();
		$component->__livewireBind('action-fixture', 'wire-action');

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('non-static');

		$handler->handle([
			'components' => [
				[
					'snapshot' => $hydrator->dehydrate($component),
					'updates' => [],
					'calls' => [['method' => 'staticAction', 'params' => []]],
				],
			],
		]);
	}

	private function restoreEnvironmentValue(string $name, ?string $arrayValue, string|false $environmentValue): void
	{
		if ($arrayValue === null) {
			unset($_ENV[$name]);
		} else {
			$_ENV[$name] = $arrayValue;
		}

		if ($environmentValue === false) {
			putenv($name);
			return;
		}

		putenv($name . '=' . $environmentValue);
	}
}

final class LivewireCoreTypedComponent extends Component
{
	public int $count = 0;
	public bool $enabled = true;
	public float $ratio = 0.0;
	public string $title = '';
	public array $items = [];
	public ?string $optional = 'available';
	public string $uninitialized;
	public static string $staticState = 'static';
	private string $secret = 'server-secret';

	public function render(): string
	{
		return '<section>' . $this->title . '</section>';
	}

	public function secretValue(): string
	{
		return $this->secret;
	}
}

final class LivewireCoreTextComponent extends Component
{
	public function __construct(private readonly string $html = '<div>Text</div>')
	{
	}

	public function render(): string
	{
		return $this->html;
	}
}

final class LivewireCoreActionComponent extends Component
{
	public string $title = '';
	public int $count = 0;

	public function render(): string
	{
		return '<div>' . $this->title . ':' . $this->count . '</div>';
	}

	public function setValues(string $title, int $count): void
	{
		$this->title = $title;
		$this->count = $count;
	}

	public function add(int $left, int $right = 1): void
	{
		$this->count += $left + $right;
	}

	public function dispatchSaved(): void
	{
		$this->dispatch('saved', [$this->title, $this->count], 'notifications');
	}

	public static function staticAction(): void
	{
	}
}
