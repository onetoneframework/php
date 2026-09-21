<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Livewire;

use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\File\Functions as FileFunctions;
use RuntimeException;
use function class_exists;
use function is_subclass_of;
use function preg_replace;
use function strtolower;
use function sprintf;

/**
 * Maps short component aliases ("counter") to concrete class names
 * ("App\\Livewire\\Counter"). The registry is the single source of
 * truth for both initial rendering and update requests — the client
 * only sends aliases, never class names, so server classes cannot be
 * instantiated directly from browser input.
 */
class ComponentRegistry
{
    /** @var array<string, class-string<Component>> */
    private array $map = [];

    /** @var array<string, bool> */
    private array $autoloadedDirectories = [];

    /**
     * Explicitly register a component alias.
     *
     * @param class-string<Component> $class
     * @param string $alias The alias to register, e.g. "counter". Aliases are case-insensitive and normalized to kebab-case.
     */
    public function register(string $alias, string $class): void
    {
        $alias = $this->normalize($alias);
        $this->map[$alias] = $class;
    }

    /**
     * Auto-discover every subclass of {@see Component} in a directory
     * and register it under a kebab-case alias derived from the class
     * short name (e.g. "UserProfile" -> "user-profile").
     *
     * Each directory is scanned at most once per request.
     * 
     * @param string $directory Absolute path to the directory to scan. Subdirectories are included.
     */
    public function autoload(string $directory): void
    {
        if (isset($this->autoloadedDirectories[$directory])) {
            return;
        }
        $this->autoloadedDirectories[$directory] = true;

        if (!is_dir($directory)) {
            return;
        }

        $files = DirectoryHandler::getList($directory, 'file', true, true, ['php']);
        foreach ($files as $file) {
            $classes = FileFunctions::getClassNames($file);
            if (empty($classes)) {
                continue;
            }

            foreach ($classes as $class) {
                if (!class_exists($class)) {
                    require_once $file;
                }
                if (!class_exists($class)) {
                    continue;
                }
                if (!is_subclass_of($class, Component::class)) {
                    continue;
                }

                $alias = $this->aliasFromClass($class);
                // Explicit registrations win over autoloaded ones.
                if (!isset($this->map[$alias])) {
                    $this->map[$alias] = $class;
                }
            }
        }
    }

    /**
     * Resolve an alias to a class name.
     *
     * @return class-string<Component> $alias
     * @throws RuntimeException if the alias is not registered and cannot be resolved to a class name.
     */
    public function resolve(string $alias): string
    {
        $key = $this->normalize($alias);
        if (!isset($this->map[$key])) {
            // Fall back to class-name resolution for explicit "App\Livewire\..." usage.
            if (class_exists($alias) && is_subclass_of($alias, Component::class)) {
                $this->map[$this->aliasFromClass($alias)] = $alias;
                return $alias;
            }

            throw new RuntimeException(sprintf('Livewire component "%s" is not registered.', $alias));
        }

        return $this->map[$key];
    }

    /** 
     * Get the entire alias-to-class map. Primarily for debugging and testing.
      *
     * @return array<string, class-string<Component>> 
     **/
    public function all(): array
    {
        return $this->map;
    }

    /**
     * Canonicalise alias lookups (case-insensitive kebab-case).
     * E.g. "UserProfile", "user-profile", and "USER_PROFILE" all normalize to "user-profile".
     * @param string $alias
     * @return string
     */
    public function normalize(string $alias): string
    {
        // Convert PascalCase/camelCase to kebab-case, then lowercase.
        $kebab = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $alias);
        $kebab = preg_replace('/[\\\\_\s]+/', '-', (string) $kebab);
        return strtolower((string) $kebab);
    }

    /**
     * Derive a kebab-case alias from a fully qualified class name.
     *
     * @param class-string<Component> $class
     * @return string
     */
    public function aliasFromClass(string $class): string
    {
        $short = $class;
        $pos = strrpos($class, '\\');
        if ($pos !== false) {
            $short = substr($class, $pos + 1);
        }

        return $this->normalize($short);
    }
}
