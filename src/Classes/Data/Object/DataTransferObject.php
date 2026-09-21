<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Exception;
use ReflectionClass;
use function sprintf;
use function get_called_class;

/**
 * Represents a generic Data Transfer Object (DTO).
 *
 * This class is used to encapsulate and transport structured data across layers
 * of an application. It may support dynamic methods for instantiating from arrays.
 *
 * @method static self from(array $arguments) Creates a new DTO instance from the given array of data.
 */
class DataTransferObject
{
    /**
     * Handle dynamic method calls on the object.
     *
     * This method is triggered when invoking inaccessible methods in an object context.
     *
     * @param string $name
     * @param array $arguments
     * 
     * @return mixed
     * 
     * @throws Exception
     */
    public function __call(string $name, array $arguments): mixed
    {
        if (!preg_match("/(get|set)([0-9A-Za-z]+)/", $name, $matches)) {
            throw new Exception(sprintf("Method '%s' is not supported", $name));
        }

        $operator = strtolower($matches[1]);
        $key = strtolower($matches[2]);
        $callerClass = get_called_class();

        $variables = ReflectionHandler::getClassPropertyMetadata($callerClass);
        $keys = $variables->map(fn($variable) => $variable['name']);

        if (!$keys->isContains($key)) {
            throw new Exception(sprintf("Variable '%s' is not exists", $key));
        }

        $reflection = new ReflectionClass($callerClass);
        foreach ($reflection->getProperties() as $property) {
            $name = $property->getName();
            if ($name !== $key) {
                continue;
            }

            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }

            if ($operator === 'get') {
                return $property->getValue($this);
            } else {
                return $property->setValue($this, $arguments[0]);
            }
        }

        return false;
    }

    /**
     * Handle static method calls on the class.
     *
     * @param string $method
     * @param array $args
     * 
     * @return object
     * 
     * @throws Exception
     */
    public static function __callStatic(string $method, array $args): object
    {
        if ($method !== 'from') {
            throw new Exception(sprintf("Static method '%s' is not supported", $method));
        }

        $callerClass = get_called_class();
        $variables = ReflectionHandler::getClassPropertyMetadata($callerClass);
        $keys = $variables->map(fn($variable) => $variable['name']);

        $object = new $callerClass();
        $reflection = new ReflectionClass($callerClass);

        foreach ($args as $arg) {
            $key = array_keys($arg)[0];
            $value = $arg[$key];

            if (!$keys->isContains($key)) {
                throw new Exception(sprintf("%s is not defined", $key));
            }

            foreach ($reflection->getProperties() as $property) {
                $name = $property->getName();

                if ($name !== $key) {
                    continue;
                }

                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $property->setAccessible(true);
                }

                $property->setValue($object, $value);
            }
        }

        return $object;
    }
}