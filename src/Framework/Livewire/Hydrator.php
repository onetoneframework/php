<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;
use function settype;

/**
 * Serialises and re-applies component state across HTTP boundaries.
 *
 * Only public, non-static properties participate — this mirrors
 * Laravel Livewire and means sensitive state kept as private/readonly
 * never reaches the browser.
 *
 * Hydration is type-aware: scalar properties declared as `int`,
 * `bool`, `float`, or `string` are cast back to their declared type
 * so a request that arrives as `{"count": "3"}` (from a text input)
 * still lands as `(int) 3` on the server.
 */
final class Hydrator
{
    /**
     * Extract a snapshot from a component instance.
     * 
     * @param Component $component The component instance to dehydrate.
     * @return array{name: string, id: string, data: array<string, mixed>, checksum: string}
     */
    public function dehydrate(Component $component): array
    {
        $snapshot = [
            'name' => (string) $component->__livewireName(),
            'id' => (string) $component->__livewireId(),
            'data' => $component->getPublicProperties(),
        ];
        $snapshot['checksum'] = Checksum::sign($snapshot);

        return $snapshot;
    }

    /**
     * Apply a state snapshot to an instance, verifying the checksum
     * first. Throws when the snapshot has been tampered with.
     *
     * @param array{name: string, id: string, data: array<string, mixed>, checksum: string} $snapshot The snapshot to apply.
     * @param Component $component The component instance to hydrate.
     * @throws RuntimeException if the snapshot is malformed or the checksum verification fails.
     */
    public function hydrate(Component $component, array $snapshot): void
    {
        $data = $snapshot['data'] ?? [];
        $name = (string) ($snapshot['name'] ?? '');
        $id = (string) ($snapshot['id'] ?? '');
        $checksum = (string) ($snapshot['checksum'] ?? '');

        if (!is_array($data)) {
            throw new RuntimeException('Livewire snapshot payload is malformed: expected array "data".');
        }

        $candidate = ['name' => $name, 'id' => $id, 'data' => $data];
        if (!Checksum::verify($candidate, $checksum)) {
            throw new RuntimeException('Livewire snapshot checksum mismatch; refusing to hydrate.');
        }

        $component->__livewireBind($name, $id);
        $this->applyProperties($component, $data);
    }

    /**
     * Apply a partial data update to a hydrated component.
     *
     * This is the method called for `wire:model` changes — we only
     * overwrite keys that are present in `$updates` and leave every
     * other piece of state untouched.
     *
     * @param Component $component The component instance to update.
     * @param array<string, mixed> $updates
     */
    public function applyUpdates(Component $component, array $updates): void
    {
        $this->applyProperties($component, $updates);
    }

    /**
     * Apply an array of data to the component's public, non-static properties.
     * 
     * @param Component $component The component instance to update.
     * @param array<string, mixed> $data
     */
    private function applyProperties(Component $component, array $data): void
    {
        $reflection = new ReflectionClass($component);

        foreach ($data as $name => $value) {
            if (!is_string($name)) {
                continue;
            }
            if (!$reflection->hasProperty($name)) {
                continue;
            }
            $property = $reflection->getProperty($name);
            if (!$property->isPublic() || $property->isStatic()) {
                continue;
            }

            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }
            $property->setValue($component, $this->coerce($property, $value));
        }
    }

    /**
     * Coerce a value to the type declared on the property, if any.
     * 
     * For scalar types (`int`, `bool`, `float`, `string`), this performs a best-effort coercion. For other types (classes, `array`, union types, etc.) it returns the value as-is and relies on PHP's native type system to enforce correctness.
     *
     * @param ReflectionProperty $property The property whose type to coerce to.
     * @param mixed $value The value to coerce.
     * @return mixed The coerced value.
     */
    private function coerce(ReflectionProperty $property, mixed $value): mixed
    {
        $type = $property->getType();
        if (!$type instanceof ReflectionNamedType) {
            return $value;
        }

        $typeName = $type->getName();
        if ($value === null && $type->allowsNull()) {
            return null;
        }

        switch ($typeName) {
            case 'int':
                if (is_int($value)) {
                    return $value;
                }
                if (is_string($value) && $value === '') {
                    return 0;
                }
                settype($value, 'int');
                return $value;
            case 'float':
                if (is_float($value)) {
                    return $value;
                }
                if (is_string($value) && $value === '') {
                    return 0.0;
                }
                settype($value, 'float');
                return $value;
            case 'bool':
                if (is_bool($value)) {
                    return $value;
                }
                if (is_string($value)) {
                    return $value !== '' && $value !== '0' && strtolower($value) !== 'false';
                }
                return (bool) $value;
            case 'string':
                return is_string($value) ? $value : (string) (is_scalar($value) ? $value : json_encode($value));
            case 'array':
                return is_array($value) ? $value : [];
            default:
                return $value;
        }
    }

    /**
     * Ensure the instance has a stable `wire:id` bound even if the
     * component code forgot to set one. The caller generates the id.
     */
    public function bindIdentity(Component $component, string $name, string $id): void
    {
        $component->__livewireBind($name, $id);
    }
}
