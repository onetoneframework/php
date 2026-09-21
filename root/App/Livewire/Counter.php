<?php

declare(strict_types=1);

namespace App\Livewire;

use Clover\Framework\Livewire\Component;

/**
 * Example Livewire-like component.
 *
 * Registered automatically under the alias "counter" via
 * {@see \Clover\Framework\Livewire\ComponentRegistry::autoload()}.
 *
 * Visit `/livewire-demo` to see it in action.
 */
class Counter extends Component
{
    public int $count = 0;

    public int $step = 1;

    public string $label = 'Count';

    public function mount(int $start = 0, int $step = 1): void
    {
        $this->count = $start;
        $this->step = $step;
    }

    public function increment(): void
    {
        $this->count += $this->step;
    }

    public function decrement(): void
    {
        $this->count -= $this->step;
    }

    public function reset(): void
    {
        $this->count = 0;
    }

    public function render(): string
    {
        return $this->inline(<<<'HTML'
            <div class="livewire-counter" style="font-family: system-ui, sans-serif; display: inline-flex; align-items: center; gap: .5rem; padding: .75rem 1rem; border: 1px solid #e2e8f0; border-radius: .5rem; background: #fff;">
                <button type="button" wire:click="decrement" style="min-width: 2rem;">−</button>
                <strong style="min-width: 4rem; text-align: center;">{{ $label }}: {{ $count }}</strong>
                <button type="button" wire:click="increment" style="min-width: 2rem;">+</button>
                <button type="button" wire:click="reset" style="margin-left: .5rem;">reset</button>
                <label style="margin-left: .75rem; display: inline-flex; align-items: center; gap: .25rem; font-size: .85rem; color: #475569;">
                    step
                    <input type="number" wire:model.lazy="step" value="{{ $step }}" min="1" style="width: 4rem;">
                </label>
            </div>
        HTML);
    }
}
