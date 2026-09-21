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
use Clover\Framework\Context\ApplicationContext;
use Throwable;
use function bin2hex;
use function is_dir;
use function in_array;
use function defined;
use function method_exists;
use function random_bytes;
use function array_key_exists;

/**
 * Entry point for the Livewire-like subsystem.
 *
 * - {@see mount()} is called from templates / helpers to render a
 *   component for the first time.
 * - {@see process()} handles the wire update request sent by the
 *   browser client (usually via {@see LivewireController}).
 *
 * A single {@see LivewireManager} is registered in the DI container
 * during {@see Mapper::setContainer()} as a safe default so the
 * helper `livewire('counter')` works out of the box.
 */
class LivewireManager
{
    private ComponentRegistry $registry;
    private Hydrator $hydrator;
    private Renderer $renderer;
    private UpdateHandler $updateHandler;
    private ?Container $container;

    /** @var array<int, string> directories auto-scanned on first use */
    private array $autoloadPaths = [];

    private bool $autoloaded = false;

    public function __construct(
        ?Container $container = null,
        ?ComponentRegistry $registry = null,
        ?Hydrator $hydrator = null,
        ?Renderer $renderer = null,
        ?UpdateHandler $updateHandler = null
    ) {
        $this->container = $container;
        $this->registry = $registry ?? new ComponentRegistry();
        $this->hydrator = $hydrator ?? new Hydrator();
        $this->renderer = $renderer ?? new Renderer($this->hydrator);
        $this->updateHandler = $updateHandler ?? new UpdateHandler($this->registry, $this->hydrator, $this->renderer, $this->container);
    }

    public function registry(): ComponentRegistry
    {
        return $this->registry;
    }

    public function updateHandler(): UpdateHandler
    {
        return $this->updateHandler;
    }

    public function renderer(): Renderer
    {
        return $this->renderer;
    }

    /**
     * Register an additional directory to scan for components.
     */
    public function autoloadFrom(string $directory): self
    {
        if (!in_array($directory, $this->autoloadPaths, true)) {
            $this->autoloadPaths[] = $directory;
        }
        return $this;
    }

    /**
     * Explicit component registration shortcut.
     *
     * @param class-string<Component> $class
     */
    public function component(string $alias, string $class): self
    {
        $this->registry->register($alias, $class);
        return $this;
    }

    /**
     * Ensure registered auto-discovery paths have been scanned.
     */
    public function ensureAutoloaded(): void
    {
        if ($this->autoloaded) {
            return;
        }

        foreach ($this->autoloadPaths as $path) {
            if (is_dir($path)) {
                $this->registry->autoload($path);
            }
        }

        // Conventional default: root/App/Livewire.
        if (defined('BASE_PATH')) {
            $default = BASE_PATH . '/App/Livewire';
            if (is_dir($default)) {
                $this->registry->autoload($default);
            }
        }

        $this->autoloaded = true;
    }

    /**
     * Mount and render a component for the first time.
     *
     * @param string              $alias    Alias registered with the registry
     *                                      (or a fully qualified class name).
     * @param array<string, mixed> $params  Parameters forwarded to {@see Component::mount()}.
     *
     * @return array{html: string, snapshot: array<string, mixed>, dispatches: array<int, array<string, mixed>>}
     */
    public function mount(string $alias, array $params = []): array
    {
        $this->ensureAutoloaded();

        $class = $this->registry->resolve($alias);
        $name = $this->registry->aliasFromClass($class);
        $id = self::generateId();

        $component = $this->instantiate($class);
        $component->__livewireBind($name, $id);

        $this->invokeMount($component, $params);

        return $this->renderer->render($component);
    }

    /**
     * Helper for view templates. Returns an HTML string so it can be
     * echoed directly: `<?= livewire('counter') ?>`.
     *
     * @param array<string, mixed> $params
     */
    public function render(string $alias, array $params = []): string
    {
        return $this->mount($alias, $params)['html'];
    }

    /**
     * Create the component, resolving constructor dependencies through
     * the application container when possible.
     *
     * @param class-string<Component> $class
     */
    private function instantiate(string $class): Component
    {
        $container = $this->container ?? $this->resolveContainer();
        if ($container !== null) {
            try {
                /** @var Component $instance */
                $instance = $container->autowire($class);
                return $instance;
            } catch (Throwable) {
                // Fall through to a direct instantiation so mount still works
                // during bootstrap or outside the HTTP lifecycle.
            }
        }

        /** @var Component $instance */
        $instance = new $class();
        return $instance;
    }

    /**
     * Invoke `mount()` with named parameters, tolerating callers that
     * provide a positional array or a `params` map.
     *
     * @param array<string, mixed> $params
     */
    private function invokeMount(Component $component, array $params): void
    {
        if (!method_exists($component, 'mount')) {
            return;
        }

        try {
            $reflection = new \ReflectionMethod($component, 'mount');
            $arguments = [];
            foreach ($reflection->getParameters() as $parameter) {
                $name = $parameter->getName();
                if (array_key_exists($name, $params)) {
                    $arguments[] = $params[$name];
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
                // Unknown required parameter — let PHP raise a descriptive error.
                $arguments[] = null;
            }
            $reflection->invokeArgs($component, $arguments);
        } catch (Throwable $e) {
            // Surface the real error: mount bugs should be loud.
            throw $e;
        }
    }

    private function resolveContainer(): ?Container
    {
        try {
            return ApplicationContext::getContainer();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Generate a short, URL-safe component id. Avoids collisions for
     * the lifetime of a page because both the client and the snapshot
     * track the same value.
     */
    public static function generateId(): string
    {
        try {
            return 'wire-' . bin2hex(random_bytes(6));
        } catch (Throwable) {
            return 'wire-' . substr(sha1(uniqid('', true)), 0, 12);
        }
    }
}
