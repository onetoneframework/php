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

use Clover\Annotation\{Autowire, Autowiring, Value};
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Component\BaseController;
use Clover\Implement\CommandInterface;
use ReflectionNamedType;
use ReflectionClass;
use ReflectionProperty;
use ReflectionType;
use InvalidArgumentException;
use UnexpectedValueException;
use ReflectionException;
use RuntimeException;
use function is_string;

#endregion

/**
 * Dependency Injector Class
 * 
 * @package Clover\Classes\DependencyInjection
 */
class Injector
{
	#region function

	/**
	 * Inject dependencies into the specified class or object.
	 * 
	 * @param object|string $className The class name or object to inject dependencies into.
	 * @param object|null $classObject The object instance to inject dependencies into (if applicable).
	 * @param bool $allowStatic Whether to allow injection into static properties.
	 * @param int|null $filter
	 * 
	 * @return void
	 */
	public static function inject(object|string $className, ?object $classObject = null, bool $allowStatic = false, int|null $filter = null): void
	{
		try {
			if (is_string($className) && !class_exists($className)) {
				throw new InvalidArgumentException("Class '{$className}' does not exist.");
			}

			$base = BaseController::class;
			$commandInterface = CommandInterface::class;
			$class = new ReflectionClass($className);

			if ($className != $base && !$class->isSubclassOf($base) && !$class->isSubclassOf($commandInterface)) {
				throw new UnexpectedValueException("Class '{$className}' is not a subclass of {$commandInterface}, {$base}.");
			}
		} catch (ReflectionException $e) {
			throw new RuntimeException("Reflection failed for class '{$className}': " . $e->getMessage(), 0, $e);
		}

		$properties = $class->getProperties($filter);

		$container = ApplicationContext::getContainer();
		/**
		 * Properties to be injected into the target class.
		 *
		 * @var ReflectionProperty[] $properties
		 */
		foreach ($properties as $property) {
			if (!$property->isStatic() && !$allowStatic) {
				continue;
			}

			$autowireAnnotation = ReflectionHandler::getAnnotations($property, Autowire::class);
			if ($autowireAnnotation === []) {
				$autowireAnnotation = ReflectionHandler::getAnnotations($property, Autowiring::class);
			}
			$valueAnnotation = ReflectionHandler::getAnnotations($property, Value::class);
			$hasAutowire = isset($autowireAnnotation[0]);
			$hasValue = isset($valueAnnotation[0]);
			if (!$hasAutowire && !$hasValue) {
				continue;
			}

			if (version_compare(PHP_VERSION, '8.1.0', '<')) {
				// @phpstan-ignore-next-line
				$property->setAccessible(true);
			}

			if ($hasAutowire) {
				/**
				 * The declared type for the property (if any).
				 *
				 * @var ReflectionType $type
				 */
				$type = $property->getType();
				if ($type instanceof ReflectionNamedType) {
					$nameOfType = $type->getName();
					if ($container->has($nameOfType)) {
						$injectObject = $container->get($nameOfType);
						if ($injectObject) {
							$property->setValue($classObject, $injectObject);
						}
					}
				}
			}

			if ($hasValue) {
				$property->setValue($classObject, $valueAnnotation[0]->value);
			}
		}
	}

	#endregion
}
