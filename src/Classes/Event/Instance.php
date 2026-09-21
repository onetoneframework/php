<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

/**
 * Event Instance class
 */
class Instance
{
    protected array $properties = [];

    protected bool $propagationStopped = false;

    /**
     * Constructor
     *
     * @param array $properties Initial properties for the event
     */
    public function __construct(array $properties = [])
    {
        $this->properties = $properties;
    }

    /**
     * Stop the propagation of the event
     *
     * @return void
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if propagation has been stopped
     *
     * @return bool True if propagation is stopped, false otherwise
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    /**
     * Set a property value
     *
     * @param string $name The property name
     * @param mixed $value The property value
     * 
     * @return $this
     */
    public function set(string $name, mixed $value): self
    {
        $this->properties[$name] = $value;
        return $this;
    }

    /**
     * Get a property value
     *
     * @param string $name The property name
     * @param mixed $default The default value if the property is not set
     * 
     * @return mixed The property value or the default value
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    /**
     * Check if a property exists
     *
     * @param string $name The property name
     * 
     * @return bool True if the property exists, false otherwise
     */
    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * Get all properties as an associative array
     *
     * @return array The properties array
     */
    public function all(): array
    {
        return $this->properties;
    }

    // ArrayAccess implementation
    public function offsetExists(mixed $offset): bool
    {
        return $this->has($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }

}
