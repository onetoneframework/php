<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Livewire;

use Clover\Framework\Livewire\Checksum;
use Clover\Framework\Livewire\Component;
use Clover\Framework\Livewire\ComponentRegistry;
use Clover\Framework\Livewire\Hydrator;
use Clover\Framework\Livewire\LivewireManager;
use Clover\Framework\Livewire\Renderer;
use Clover\Framework\Livewire\UpdateHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CounterStub extends Component
{
    public int $count = 0;
    public int $step = 1;

    public function mount(int $start = 0, int $step = 1): void
    {
        $this->count = $start;
        $this->step = $step;
    }

    public function increment(): void
    {
        $this->count += $this->step;
    }

    public function render(): string
    {
        return '<div class="stub">' . $this->count . '</div>';
    }
}

final class LivewireManagerTest extends TestCase
{
    public function testMountRendersHtmlWithSnapshotAttributes(): void
    {
        $manager = $this->makeManager();

        $result = $manager->mount(CounterStub::class, ['start' => 5, 'step' => 2]);

        self::assertArrayHasKey('html', $result);
        self::assertArrayHasKey('snapshot', $result);
        self::assertStringContainsString('wire:id=', $result['html']);
        self::assertStringContainsString('wire:component=', $result['html']);
        self::assertStringContainsString('wire:snapshot=', $result['html']);
        self::assertSame(5, $result['snapshot']['data']['count']);
        self::assertSame(2, $result['snapshot']['data']['step']);
        self::assertTrue(Checksum::verify([
            'name' => $result['snapshot']['name'],
            'id' => $result['snapshot']['id'],
            'data' => $result['snapshot']['data'],
        ], $result['snapshot']['checksum']));
    }

    public function testUpdateAppliesCallAndReturnsNewSnapshot(): void
    {
        $manager = $this->makeManager();
        $initial = $manager->mount(CounterStub::class, ['start' => 0, 'step' => 3]);

        $response = $manager->updateHandler()->handle([
            'components' => [
                [
                    'snapshot' => $initial['snapshot'],
                    'updates' => [],
                    'calls' => [
                        ['method' => 'increment', 'params' => []],
                    ],
                ],
            ],
        ]);

        self::assertSame(3, $response['components'][0]['snapshot']['data']['count']);
        self::assertStringContainsString('>3</div>', $response['components'][0]['effects']['html']);
    }

    public function testUpdateRejectsTamperedSnapshot(): void
    {
        $manager = $this->makeManager();
        $initial = $manager->mount(CounterStub::class, ['start' => 0]);

        $tampered = $initial['snapshot'];
        $tampered['data']['count'] = 9999; // client lies about state

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('checksum mismatch');

        $manager->updateHandler()->handle([
            'components' => [
                [
                    'snapshot' => $tampered,
                    'updates' => [],
                    'calls' => [['method' => 'increment', 'params' => []]],
                ],
            ],
        ]);
    }

    public function testUpdateRejectsProtectedMethod(): void
    {
        $manager = $this->makeManager();
        $initial = $manager->mount(CounterStub::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('protected');

        $manager->updateHandler()->handle([
            'components' => [
                [
                    'snapshot' => $initial['snapshot'],
                    'updates' => [],
                    'calls' => [['method' => 'render', 'params' => []]],
                ],
            ],
        ]);
    }

    public function testUpdateAppliesBoundModelChanges(): void
    {
        $manager = $this->makeManager();
        $initial = $manager->mount(CounterStub::class, ['start' => 0, 'step' => 1]);

        $response = $manager->updateHandler()->handle([
            'components' => [
                [
                    'snapshot' => $initial['snapshot'],
                    'updates' => ['step' => '7'], // client sends as string, server coerces
                    'calls' => [['method' => 'increment', 'params' => []]],
                ],
            ],
        ]);

        self::assertSame(7, $response['components'][0]['snapshot']['data']['step']);
        self::assertSame(7, $response['components'][0]['snapshot']['data']['count']);
    }

    private function makeManager(): LivewireManager
    {
        $registry = new ComponentRegistry();
        $registry->register('counter-stub', CounterStub::class);

        $hydrator = new Hydrator();
        $renderer = new Renderer($hydrator);
        $updateHandler = new UpdateHandler($registry, $hydrator, $renderer);

        return new LivewireManager(null, $registry, $hydrator, $renderer, $updateHandler);
    }
}
