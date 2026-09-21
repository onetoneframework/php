<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Livewire\LivewireManager;

if (!function_exists('livewire')) {
    /**
     * Render a Livewire-like component from inside a PHP view.
     *
     * Usage in templates:
     * ```php
     * <?= livewire('counter', ['start' => 5]) ?>
     * ```
     *
     * Falls back to a transient manager when the application container
     * hasn't been bootstrapped yet (CLI tools, isolated tests).
     *
     * @param array<string, mixed> $params
     */
    function livewire(string $alias, array $params = []): string
    {
        $manager = null;

        try {
            $container = ApplicationContext::getContainer();
            if ($container !== null && $container->has(LivewireManager::class)) {
                /** @var LivewireManager $manager */
                $manager = $container->get(LivewireManager::class);
            }
        } catch (\Throwable) {
            // Container not wired — fall through to an ad-hoc manager.
        }

        if ($manager === null) {
            $manager = new LivewireManager();
        }

        return $manager->render($alias, $params);
    }
}

if (!function_exists('livewire_scripts')) {
    /**
     * Emit the `<script>` tag that loads the Livewire browser runtime.
     */
    function livewire_scripts(): string
    {
        return '<script src="/App/Frontend/livewire.js" defer></script>';
    }
}
