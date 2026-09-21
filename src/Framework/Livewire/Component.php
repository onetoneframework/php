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
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Framework\Context\ApplicationContext;
use ReflectionClass;
use ReflectionProperty;
use function htmlspecialchars;
use function is_scalar;
use function sprintf;
use function array_key_exists;

/**
 * Livewire-style component base class.
 *
 * Subclasses declare public properties as reactive state. The public
 * properties are serialized to the client on initial render, sent back
 * on each update, and re-applied before actions execute (hydration).
 *
 * A single {@see render()} method returns the HTML fragment to mount.
 * The returned fragment is wrapped by {@see Renderer} in a `wire:id`
 * container element together with a signed snapshot of the state so
 * subsequent client-side updates can be verified.
 *
 * Typical usage:
 * ```php
 * use Clover\Framework\Livewire\Component;
 *
 * class Counter extends Component
 * {
 *     public int $count = 0;
 *
 *     public function increment(): void { $this->count++; }
 *
 *     public function render(): string
 *     {
 *         return $this->view('/App/View/Livewire/counter.php');
 *     }
 * }
 * ```
 */
abstract class Component
{
    /** @var string|null assigned by {@see LivewireManager::mount()} / {@see UpdateHandler::hydrate()} */
    private ?string $__livewireId = null;

    /** @var string|null component alias registered in the {@see ComponentRegistry} */
    private ?string $__livewireName = null;

    /** @var array<int, array{name: string, params: array<int, mixed>, target: string|null}> */
    private array $__livewireDispatches = [];

    /**
     * Called on the very first render of the component.
     *
     * Subclasses may receive constructor-like parameters declared on the
     * `@livewire(...)` call and use them to seed the public properties.
     * Default implementation is a no-op.
     */
    public function mount(): void {}

    /**
     * Return the HTML fragment for this component.
     *
     * The returned string should contain at least one root element.
     * If multiple siblings are returned, {@see Renderer} wraps them in
     * a `<div wire:id>` automatically. Prefer a single root element so
     * the client-side DOM morpher stays simple.
     */
    abstract public function render(): string;

    /**
     * Render a PHP view file and return the generated HTML.
     *
     * Behaves like {@see \Clover\Framework\Component\Renderer::render()}
     * but automatically exposes every public component property to the
     * view, so `<?= $count ?>` just works.
     *
     * @param string              $template Path relative to BASE_PATH.
     * @param array<string, mixed> $data     Additional variables exposed to the view.
     * @return string The rendered HTML from the view.
     */
    public function view(string $template, array $data = []): string
    {
        $path = sprintf('%s%s', BASE_PATH, $template);
        $merged = $this->getPublicProperties();
        foreach ($data as $key => $value) {
            $merged[$key] = $value;
        }
        $merged['_component'] = $this;

        return (string) FileFunctions::getInterpretedContent($path, $merged);
    }

    /**
     * Render an inline string template. Supports `{{ $variable }}`
     * interpolation for simple cases. More complex templates should
     * use {@see view()}.
     *
     * @param string $template The template string containing `{{ $variable }}` placeholders.
     * @param array<string, mixed> $data Additional variables (merged into public state).
     * 
     * @return string The rendered template with placeholders replaced by variable values.
     */
    public function inline(string $template, array $data = []): string
    {
        $merged = $this->getPublicProperties();
        foreach ($data as $key => $value) {
            $merged[$key] = $value;
        }

        return (string) preg_replace_callback('/\{\{\s*\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/u', static function (array $match) use ($merged): string {
            $key = $match[1];
            if (!array_key_exists($key, $merged)) {
                return '';
            }
            $value = $merged[$key];
            if ($value === null) {
                return '';
            }
            if (!is_scalar($value)) {
                $value = json_encode($value);
            }
            return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }, $template);
    }

    /**
     * Queue a browser-visible event dispatch. The JS client listens
     * for the `livewire:event` bus and forwards to other components.
     *
     * @param string $name Event name, e.g. `user-updated`.
     * @param array<int, mixed> $params
     * @param string|null $toComponent Optional target component alias. If null, the event is global.
     */
    public function dispatch(string $name, array $params = [], ?string $toComponent = null): void
    {
        $this->__livewireDispatches[] = [
            'name' => $name,
            'params' => $params,
            'target' => $toComponent,
        ];
    }

    /**
     * Resolve a service from the application container from inside a component. Returns null when the container is not wired (e.g. in isolated unit tests).
     * 
     * @return Container|null
     */
    protected function container(): ?Container
    {
        try {
            return ApplicationContext::getContainer();
        } catch (\Throwable) {
            return null;
        }
    }

    /** 
     * Framework hook: assigns stable identity bookkeeping.
     * 
     * @param string $name component alias registered in the {@see ComponentRegistry}
      *@param string $id instance id, unique per mounted component
     **/
    public function __livewireBind(string $name, string $id): void
    {
        $this->__livewireName = $name;
        $this->__livewireId = $id;
    }

    /** 
     * Framework hook: returns the component alias. 
     * @return string|null
     **/
    public function __livewireName(): ?string
    {
        return $this->__livewireName;
    }

    /** 
     * Framework hook: returns the instance id. 
     **/
    public function __livewireId(): ?string
    {
        return $this->__livewireId;
    }

    /**
     * Framework hook: returns queued dispatches and clears them.
     *
     * @return array<int, array{name: string, params: array<int, mixed>, target: string|null}>
     */
    public function __livewireConsumeDispatches(): array
    {
        $dispatches = $this->__livewireDispatches;
        $this->__livewireDispatches = [];

        return $dispatches;
    }

    /**
     * Snapshot of public, non-static properties of this component.
     *
     * @return array<string, mixed>
     */
    public function getPublicProperties(): array
    {
        $data = [];
        $reflection = new ReflectionClass($this);
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }
            $name = $property->getName();
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }
            if (!$property->isInitialized($this)) {
                continue;
            }
            $data[$name] = $property->getValue($this);
        }

        return $data;
    }
}
