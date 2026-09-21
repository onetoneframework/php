<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Container;

use Clover\Contract\ContainerInterface;
use Clover\Classes\DependencyInjection\Container as BaseContainer;
use function class_exists;
use function is_string;

/**
 * Container Class
 *
 * Implements a dependency injection container.
 * Manages class instances and their dependencies.
 */
class Container extends BaseContainer implements ContainerInterface
{
    /**
     * @var array<string, mixed> Array of singleton instances.
     */
    protected array $singletons = [];

    /**
     * Register a shared binding in the container.
     *
     * @param string $abstract The abstract type or alias.
     * @param mixed|null $concrete The concrete implementation or value.
     * @return void
     */
    public function singleton(string $abstract, mixed $concrete = null): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // A concrete binding must outrank an alias on the same identifier, or an interface
        // aliased to a framework default could never be pointed at an application's own class.
        $this->removeAlias($abstract);

        $this->singletons[$abstract] = $concrete;
        $this->set($abstract, $concrete);
    }

    /**
     * Register a binding with the container.
     *
     * @param string $abstract The abstract type or alias.
     * @param mixed|null $concrete The concrete implementation or value.
     * @param bool $shared Whether the binding should be treated as a singleton.
     * @return void
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        // Two distinct class names without "shared" => interface/alias binding on the base container.
        if (!$shared && is_string($concrete) && $abstract !== $concrete) {
            parent::bind($abstract, $concrete);

            return;
        }

        $this->removeAlias($abstract);

        if ($shared) {
            $this->singletons[$abstract] = $concrete;
        }

        $this->set($abstract, $concrete);
    }

    /**
     * Get the concrete type for a given abstract.
     *
     * @param string $abstract The abstract type or alias to resolve.
     * @param array $parameters Optional parameters for resolution.
     * @return mixed The resolved instance or value.
     */
    public function make(string $abstract, array $parameters = []): mixed
    {
        if ($parameters !== []) {
            $identifier = $this->normalizeIdentifier($abstract);
            $this->setPassArguments($identifier, $parameters);

            $resolved = $this->resolve($identifier);
            if ($resolved !== false) {
                return $resolved;
            }
        }

        // Follow any alias first. `has()` answers true for an identifier that is *only* an alias,
        // so testing the abstract directly would send an alias pointing at an unregistered class
        // into `get()`, which refuses to build one - and the interface would look registered while
        // being unresolvable.
        $identifier = $this->normalizeIdentifier($abstract);

        if ($this->has($identifier)) {
            return $this->get($identifier);
        }

        // Nothing concrete is registered under the resolved identifier. `get()` would refuse here,
        // but a container asked for a class it can build is expected to build it - a kernel names
        // its middleware and a route names its controller by class, and neither should have to be
        // registered first just to be constructible.
        if (class_exists($identifier)) {
            $resolved = $this->resolve($identifier);

            if ($resolved !== false) {
                return $resolved;
            }
        }

        // Not registered and not constructible: let the container raise its own not-found, so the
        // failure reads the same whichever way the identifier was asked for.
        return $this->get($abstract);
    }
}
