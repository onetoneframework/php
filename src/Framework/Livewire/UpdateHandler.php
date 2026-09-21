<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use Clover\Classes\DependencyInjection\Container;
use ReflectionMethod;
use RuntimeException;
use Throwable;
use function array_key_exists;
use function in_array;
use function is_array;
use function is_string;
use function method_exists;
use function sprintf;

/**
 * Applies a single "message" (update batch) coming from the browser
 * client to a component instance and returns the new snapshot + HTML.
 *
 * Protocol (simplified Livewire v3):
 * ```json
 * {
 *   "components": [
 *     {
 *       "snapshot": { "name":"counter", "id":"wire-..", "data":{...}, "checksum":"..." },
 *       "updates":  { "count": 7 },
 *       "calls":    [ { "method":"increment", "params":[] } ]
 *     }
 *   ]
 * }
 * ```
 *
 * The response mirrors the structure:
 * ```json
 * {
 *   "components": [
 *     { "snapshot": {...}, "effects": { "html":"...", "dispatches":[...] } }
 *   ]
 * }
 * ```
 */
class UpdateHandler
{
    /** Methods that cannot be invoked from the browser. */
    private const PROTECTED_METHODS = [
        'mount',
        'render',
        'view',
        'inline',
        'dispatch',
        'container',
        'getPublicProperties',
    ];

    public function __construct(
        private readonly ComponentRegistry $registry,
        private readonly Hydrator $hydrator,
        private readonly Renderer $renderer,
        private readonly ?Container $container = null
    ) {}

    /**
     * Handle a full request body.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function handle(array $payload): array
    {
        $components = $payload['components'] ?? null;
        if (!is_array($components) || $components === []) {
            throw new RuntimeException('Livewire update payload is missing "components".');
        }

        $out = [];
        foreach ($components as $component) {
            if (!is_array($component)) {
                continue;
            }
            $out[] = $this->handleComponent($component);
        }

        return ['components' => $out];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function handleComponent(array $payload): array
    {
        $snapshot = $payload['snapshot'] ?? null;
        if (!is_array($snapshot)) {
            throw new RuntimeException('Missing component snapshot.');
        }

        $class = $this->registry->resolve((string) ($snapshot['name'] ?? ''));
        $instance = $this->instantiate($class);

        $this->hydrator->hydrate($instance, $snapshot);

        // Apply bound `wire:model` changes before running actions so
        // callbacks see the new state. Livewire does the same.
        $updates = $payload['updates'] ?? [];
        if (is_array($updates)) {
            $this->hydrator->applyUpdates($instance, $updates);
        }

        $calls = $payload['calls'] ?? [];
        if (is_array($calls)) {
            foreach ($calls as $call) {
                if (!is_array($call)) {
                    continue;
                }
                $this->invokeAction($instance, $call);
            }
        }

        $render = $this->renderer->render($instance);

        return [
            'snapshot' => $render['snapshot'],
            'effects' => [
                'html' => $render['html'],
                'dispatches' => $render['dispatches'],
            ],
        ];
    }

    /**
     * Safely invoke a `wire:click`-style method.
     *
     * @param array<string, mixed> $call
     */
    private function invokeAction(Component $component, array $call): void
    {
        $method = $call['method'] ?? null;
        if (!is_string($method) || $method === '') {
            return;
        }
        if (str_starts_with($method, '__')) {
            throw new RuntimeException('Magic methods are not callable from the client.');
        }
        if (in_array($method, self::PROTECTED_METHODS, true)) {
            throw new RuntimeException(sprintf('Method "%s" is protected and cannot be invoked remotely.', $method));
        }
        if (!method_exists($component, $method)) {
            throw new RuntimeException(sprintf('Method "%s" does not exist on component.', $method));
        }

        $reflection = new ReflectionMethod($component, $method);
        if (!$reflection->isPublic() || $reflection->isStatic()) {
            throw new RuntimeException(sprintf('Method "%s" must be public and non-static to be callable.', $method));
        }

        $params = $call['params'] ?? [];
        if (!is_array($params)) {
            $params = [];
        }

        $arguments = [];
        $positional = self::isList($params);
        foreach ($reflection->getParameters() as $index => $parameter) {
            $name = $parameter->getName();
            if (!$positional && array_key_exists($name, $params)) {
                $arguments[] = $params[$name];
                continue;
            }
            if ($positional && array_key_exists($index, $params)) {
                $arguments[] = $params[$index];
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }
            if ($parameter->allowsNull()) {
                $arguments[] = null;
                continue;
            }
            $arguments[] = null;
        }

        try {
            $reflection->invokeArgs($component, $arguments);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Livewire action "%s" failed: %s', $method, $e->getMessage()), 0, $e);
        }
    }

    /**
     * PHP 8.0 compatible replacement for {@see array_is_list()} (8.1+).
     *
     * @param array<int|string, mixed> $array
     */
    private static function isList(array $array): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($array);
        }
        $i = 0;
        foreach ($array as $key => $_) {
            if ($key !== $i) {
                return false;
            }
            $i++;
        }
        return true;
    }

    /**
     * @param class-string<Component> $class
     */
    private function instantiate(string $class): Component
    {
        if ($this->container !== null) {
            try {
                /** @var Component $instance */
                $instance = $this->container->autowire($class);
                return $instance;
            } catch (Throwable) {
                // Fall back to a plain instantiation.
            }
        }

        /** @var Component $instance */
        $instance = new $class();
        return $instance;
    }
}
