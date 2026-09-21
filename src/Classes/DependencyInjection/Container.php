<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\DependencyInjection;

#region use

use Closure;
use Clover\Classes\BaseClass;
use Clover\Classes\OperationSystem;
use Exception;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionParameter;
use RuntimeException;
use function array_key_exists;
use function is_array;
use function is_object;
use function is_string;

#endregion

/**
 * Service Container Class
 *
 * @package Clover\Classes\DependencyInjection
 */
class Container extends BaseClass
{
    #region Properties

    /**
     * Registered service container entries keyed by identifier.
     *
     * @var array<string, object>
     */
    private array $instances = [];

    /**
     * Service definitions (class names, closures, etc.)
     *
     * @var array<string, mixed>
     */
    private array $definitions = [];

    /**
     * Interface to implementation mapping
     *
     * @var array<string, string>
     */
    private array $aliases = [];

    /**
     * Constructor arguments for services keyed by identifier.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $arguments = [];

    /**
     * For circular dependency detection
     *
     * @var array<string, bool>
     */
    private array $loading = [];

    #endregion

    #region function

    /**
     * Set constructor arguments for a specific service identifier.
     *
     * @param string $identifier The service identifier.
     * @param array<string, mixed> $value The constructor arguments to set.
     *
     * @return bool True if the arguments were set successfully, false if the service identifier does not exist in the container.
     */
    public function setPassArguments(string $identifier, array $value): bool
    {
        if (!$this->has($identifier) && !class_exists($identifier)) {
            return false;
        }

        $this->arguments[$identifier] = $value;
        return true;
    }

    /**
     * Register a service in the container.
     *
     * @param string $identifier The service identifier.
     * @param object|string|callable $value The service instance, class name, or factory.
     * @param bool $override Whether to override existing service.
     *
     * @return bool True if the service was registered successfully, false if it already exists and override is false.
     */
	public function set(string $identifier, object|string|callable $value, bool $override = true): bool
	{
		if (!$override && $this->has($identifier)) {
			return false;
		}

		if (is_object($value) && !($value instanceof Closure)) {
			unset($this->definitions[$identifier]);
			$this->instances[$identifier] = $value;
		} else {
			// Class names and factories remain lazy until the service is requested.
			unset($this->instances[$identifier]);
			$this->definitions[$identifier] = $value;
		}

		return true;
	}

    /**
     * Binds an interface to an implementation.
     * 
     * @param string $interface The interface name.
     * @param string $implementation The implementation class name.
     * @return void
     */
    public function bind(string $interface, string $implementation): void
    {
        $this->aliases[$interface] = $implementation;
    }

    /**
     * Check whether an identifier is aliased to another one.
     *
     * @param string $interface The identifier to test.
     *
     * @return bool True when an alias is registered for the identifier.
     */
    public function hasAlias(string $interface): bool
    {
        return isset($this->aliases[$interface]);
    }

    /**
     * Remove the alias registered for an identifier.
     *
     * Resolution rewrites an identifier through its alias before it looks at instances or
     * definitions, so an alias registered early outranks anything later bound under the same
     * identifier. Without a way to remove one, the first binding of an interface would be its
     * last, and an application could never replace a default the framework had aliased.
     *
     * Removing an alias that was never registered is not an error; the identifier simply has none.
     *
     * @param string $interface The identifier whose alias should be dropped.
     *
     * @return void
     */
    public function removeAlias(string $interface): void
    {
        unset($this->aliases[$interface]);
    }

    /**
     * Check if a service is registered in the container.
     *
     * @param string $id The service identifier.
     *
     * @return bool True if the service is registered, false otherwise.
     */
    public function has(string $id): bool
    {
        return isset($this->instances[$id])
            || isset($this->definitions[$id])
            || isset($this->aliases[$id]);
    }

    /**
     * Get a service by its type.
     *
     * @param string $type The class type of the service.
     *
     * @return object|null The service instance if found, null otherwise.
     */
    public function getByType(string $type): ?object
    {
        foreach ($this->instances as $container) {
            if ($container instanceof $type) {
                return $container;
            }
        }

        return null;
    }

    /**
     * Get all registered services in the container.
     *
     * @return array<string, object> The array of all registered services.
     */
    public function getAll(): array
    {
        return $this->instances;
    }

    /**
     * Get a service by its identifier.
     *
     * @param string $id The service identifier.
     *
     * @return mixed The service instance.
     *
     * @throws Exception If service is not found or circular dependency detected.
     */
    public function get(string $id): mixed
    {
        $id = $this->normalizeIdentifier($id);

        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (!$this->has($id)) {
            throw new class ("Service not found: $id") extends Exception {};
        }

        if (isset($this->loading[$id])) {
            throw new class ("Circular dependency detected for service: $id") extends Exception {};
        }

        $this->loading[$id] = true;
        $object = null;

        try {
            $object = $this->resolve($id);
            if ($object === false) {
                throw new class ("Could not resolve service: $id") extends Exception {};
            }
            $this->instances[$id] = $object;
        } finally {
            unset($this->loading[$id]);
        }
        return $object;
    }

    /**
     * Autowire dependencies for the specified class.
     *
     * @param string $class The class name to autowire.
     *
     * @return object|null The instantiated class with dependencies injected.
     */
    public function autowire(string $class): ?object
    {
        return $this->buildClass($class, $this->arguments[$class] ?? []);
    }

    /**
     * Resolve a service by its identifier.
     *
     * @param string $identifier The service identifier.
     *
     * @return mixed The resolved service instance if successful, false otherwise.
     */
    public function resolve(string $identifier): mixed
    {
        $identifier = $this->normalizeIdentifier($identifier);

        // Check definitions first
        if (isset($this->definitions[$identifier])) {
            $definition = $this->definitions[$identifier];

            if ($definition instanceof Closure) {
                return $definition($this);
            }

            if (is_string($definition) && class_exists($definition)) {
                $arguments = $this->arguments[$identifier] ?? $this->arguments[$definition] ?? [];
                return $this->buildClass($definition, $arguments);
            }

            return $definition;
        }

        // If it's a class that exists but not explicitly defined, try to autowire it
        if (class_exists($identifier)) {
            return $this->buildClass($identifier, $this->arguments[$identifier] ?? []);
        }

        return false;
    }

    /**
     * Resolves constructor parameters.
     * 
     * @param ReflectionParameter $parameter
     * 
     * @return mixed The resolved parameter value.
     */
    public function resolveParameter(ReflectionParameter $parameter): mixed
    {
        return $this->resolveParameterValue($parameter, [], $parameter->getDeclaringClass()?->getName() ?? 'unknown');
    }

    /**
     * Normalize identifiers so aliases resolve to their canonical concrete type.
     *
     * @param string $identifier The requested identifier or alias.
     *
     * @return string The canonical identifier.
     */
    protected function normalizeIdentifier(string $identifier): string
    {
        if (isset($this->aliases[$identifier])) {
            return $this->aliases[$identifier];
        }

        return $identifier;
    }

    /**
     * Build a class instance using registered or ad-hoc constructor arguments.
     *
     * @param string $class The class to instantiate.
     * @param array<string, mixed> $arguments Constructor arguments keyed by parameter name.
     *
     * @return object|null The instantiated object, or null when the class does not exist.
     *
     * @throws Exception When a required dependency cannot be resolved.
     */
    protected function buildClass(string $class, array $arguments = []): ?object
    {
        if (!class_exists($class)) {
            return null;
        }

        $reflection = new ReflectionClass($class);
		if (!$reflection->isInstantiable()) {
			throw new RuntimeException("Class is not instantiable");
		}

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            if (method_exists($reflection, 'newInstanceWithoutConstructor')) {
                return $reflection->newInstanceWithoutConstructor();
            }

            if (!is_callable($class)) {
                throw new RuntimeException("Class is not callable");
            }

            return new $class();
        }

        $dependencies = [];

        $isDeprecated = $constructor->isDeprecated();
        if ($isDeprecated) {
            throw new RuntimeException("Cannot instantiate deprecated class '{$class}'");
        }

        if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            foreach ($constructor->getParameters() as $parameter) {
                if ($parameter->isVariadic() && !array_key_exists($parameter->getName(), $arguments)) {
                    continue;
                }

                $resolvedParameter = $this->resolveParameterValue($parameter, $arguments, $class);
                if ($parameter->isVariadic() && is_array($resolvedParameter)) {
                    foreach ($resolvedParameter as $variadicValue) {
                        $dependencies[] = $variadicValue;
                    }

                    continue;
                }

                $dependencies[] = $resolvedParameter;
            }
        } else {
            $parameters = $constructor->getParameters();

            foreach ($parameters as $parameter) {
                $name = $parameter->getName();
                $hasType = $parameter->hasType();
                $type = $parameter->getType();

                if ($hasType && ($type instanceof ReflectionNamedType) && !$type->isBuiltin()) {
                    $name = $type->getName();
                }

                if (array_key_exists($name, $arguments)) {
                    $dependencies[] = $arguments[$name];
                    continue;
                }

                // @phpstan-ignore-next-line
                $dependency = $parameter->getClass();

                if ($dependency !== null && $this->has($dependency->name)) {
                    $dependencies[] = $this->get($dependency->name);
                    continue;
                }

                $isDefaultValueAvailable = $parameter->isDefaultValueAvailable();
                if ($isDefaultValueAvailable) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                $allowNull = $parameter->allowsNull();
                if ($allowNull) {
                    $dependencies[] = null;
                    continue;
                }

                $optional = $parameter->isOptional();
                if ($optional) {
                    continue;
                }

                throw new RuntimeException("Cannot resolve parameter '{$parameter->getName()}' of class '{$class}'");
            }
        }
        
        if ($dependencies === []) {
            return $reflection->newInstance();
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    /**
     * Resolve an individual constructor parameter.
     *
     * @param ReflectionParameter $parameter The parameter to resolve.
     * @param array<string, mixed> $arguments Constructor arguments keyed by parameter name.
     * @param string $className The class currently being instantiated.
     *
     * @return mixed The resolved argument value.
     *
     * @throws Exception When a required parameter cannot be resolved.
     */
    protected function resolveParameterValue(ReflectionParameter $parameter, array $arguments, string $className): mixed
    {
        $parameterName = $parameter->getName();
        if (array_key_exists($parameterName, $arguments)) {
            return $arguments[$parameterName];
        }

        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
            try {
                return $this->get($type->getName());
            } catch (Exception $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }

                if ($parameter->allowsNull()) {
                    return null;
                }

                throw $e;
            }
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->allowsNull()) {
            return null;
        }

        if ($parameter->isOptional()) {
            return false;
        }

        throw new Exception("Cannot resolve parameter '{$parameterName}' of class '{$className}'");
    }

    #endregion
}
