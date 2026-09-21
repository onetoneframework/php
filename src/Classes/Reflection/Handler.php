<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Reflection;

#region use

use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Debug\TraceObject;
use Clover\Classes\DependencyInjection\{Container, Injector};
use Clover\Classes\OperationSystem;
use Clover\Exception\Argument\ArgumentEmptyException;
use __PHP_Incomplete_Class;
use ArrayIterator;
use Closure;
use Exception;
use Generator;
use InvalidArgumentException;
use Reflection;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionEnum;
use ReflectionEnumBackedCase;
use ReflectionEnumUnitCase;
use ReflectionException;
use ReflectionExtension;
use ReflectionFiber;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionGenerator;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionReference;
use ReflectionType;
use ReflectionUnionType;
use ReflectionZendExtension;
use Reflector;
use RuntimeException;
use WeakMap;
use function array_key_exists;
use function call_user_func_array;
use function call_user_func;
use function count;
use function get_called_class;
use function get_class;
use function in_array;
use function interface_exists;
use function is_array;
use function is_null;
use function is_object;
use function is_string;
use function sprintf;
use const PHP_VERSION_ID;

/**
 * Handles reflection-based operations within the Clover Framework.
 *
 * This class provides methods and utilities for inspecting and manipulating
 * classes, methods, and properties using PHP's reflection capabilities.
 *
 * @package Clover-Framework\Reflection
 */
class Handler
{
    #region Properties

    /** @var array<string> List of magic methods in PHP. */
    private static $magicMethods = ['construct', 'call', 'callStatic', 'toString', 'invoke', 'set_state', 'set', 'isset', 'unset', 'clone', 'serialize', 'unserialize', 'sleep', 'wakeup', 'destruct', 'get'];

    /** @var array<string> Scalar types include: int, float, string, and bool. */
    private static $scalarTypes = ['bool', 'int', 'float', 'string'];

    /** @var array<string> Value types include: true false. */
    private static $valueTypes = ['true', 'false'];

    /** @var array<string> Relative class types include: self, parent, static */
    private static $relativeClassTypes = ['self', 'parent', 'static'];

    /** @var array<string> Built-in types include: true, false, bool, int, float, string, null, array, object, resource, never, void */
    private static $builtInTypes = ['true', 'false', 'bool', 'int', 'float', 'string', 'null', 'array', 'object', 'resource', 'never', 'void'];

    /** @var array<string> Atomic types include: true, false, bool, int, float, string, null, array, object, resource, never, void, self, parent, static */
    private static $atomicTypes = ['true', 'false', 'bool', 'int', 'float', 'string', 'null', 'array', 'object', 'resource', 'never', 'void', 'self', 'parent', 'static'];
    /** @var array<string, array{0:string,1:string}> */
    private static array $callMethodCache = [];
    /** @var array<string, object[]> */
    private static array $annotationCache = [];

    #region function

    /**
     * Retrieves a reflection of a Zend extension by its name.
     *
     * @param string $name The name of the Zend extension.
     * 
     * @return ReflectionZendExtension The reflection of the specified Zend extension.
     */
    public static function getZendExtension(string $name): ReflectionZendExtension
    {
        return new ReflectionZendExtension($name);
    }

    /**
     * Retrieves the version of a specified Zend extension.
     *
     * @param string $name The name of the Zend extension.
     * 
     * @return string The version of the specified Zend extension.
     */
    public static function getZendEngineVersion(string $name): string
    {
        $rze = new ReflectionZendExtension($name);
        return $rze->getVersion();
    }

    /**
     * Determines if the provided class type string represents a relative class type.
     *
     * A relative class type is typically a class name that is not fully qualified
     * (i.e., does not include a leading backslash or namespace).
     *
     * @param string|null $type The class type string to check. Can be null.
     * 
     * @return bool True if the class type is relative, false otherwise.
     */
    public static function isRelativeClassType(?string $type = null): bool
    {
        return isset(self::$relativeClassTypes[$type]);
    }

    /**
     * Retrieves the executing file of a generator.
     *
     * @param Generator $generator The generator instance to inspect.
     * 
     * @return string The file path where the generator is executing.
     */
    public static function getGeneratorExecutingFile(Generator $generator): string
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getExecutingFile();
    }

    /**
     * Retrieves the short name of a given class.
     *
     * @param object|string $objectOrClass The class instance, class name as string.
     * 
     * @return string The short name of the class.
     */
    public static function getClassShortName(object|string $objectOrClass): string
    {
        return (new ReflectionClass($objectOrClass))->getShortName();
    }

    /**
     * Retrieves the base name of the given class object.
     *
     * If no object is provided, the method may use a default or handle null accordingly.
     *
     * @param object|null $class The object whose class base name is to be retrieved. If null, behavior depends on implementation.
     * 
     * @return string The base name of the class.
     */
    public static function getClassBasename(?object $class = null): string
    {
        return basename(str_replace('\\', '/', $class::class));
    }

    /**
     * Determines if the given type is a scalar type.
     *
     * Scalar types include: int, float, string, and bool.
     *
     * @param string|null $type The type name to check. If null, returns false.
     * 
     * @return bool True if the type is scalar, false otherwise.
     */
    public static function isScalarType(?string $type = null): bool
    {
        return isset(self::$scalarTypes[$type]);
    }

    /**
     * Retrieves the namespace of the caller.
     *
     * This static method inspects the call stack to determine the namespace
     * from which it was invoked. Returns the namespace as a string, or null
     * if it cannot be determined.
     *
     * @return string|null The caller's namespace, or null if unavailable.
     */
    public static function getCallerNamespace(): ?string
    {
        return get_called_class();
    }

    /**
     * Retrieves the name of the class that was called in the current context.
     *
     * @return string The fully qualified name of the called class.
     */
    public static function getCalledClass(): string
    {
        $backtrace = debug_backtrace();
        return get_class($backtrace[2]['object']);
    }

    /**
     * Determines if the given type is a value type.
     *
     * Value types include: true false.
     *
     * @param string|null $type The type name to check. If null, returns false.
     * 
     * @return bool True if the type is value, false otherwise.
     */
    public static function isValueType(?string $type = null): bool
    {
        return isset(self::$valueTypes[$type]);
    }

    /**
     * Determines if the given type is a atomic type.
     *
     * Atomic types include: true, false, bool, int, float, string, null, array, object, resource, never, void, self, parent, static
     *
     * @param string|null $type The type name to check. If null, returns false.
     * 
     * @return bool True if the type is atomic, false otherwise.
     */
    public static function isAtomicTypes(?string $type = null): bool
    {
        return isset(self::$atomicTypes[$type]);
    }

    /**
     * Determines if the given type is a bullet-in type.
     *
     * Bullet-in types include: true, false, bool, int, float, string, null, array, object, resource, never, void
     *
     * @param string|null $type The type name to check. If null, returns false.
     * 
     * @return bool True if the type is bullet-in, false otherwise.
     */
    public static function isBuiltInTypes(?string $type = null): bool
    {
        return isset(self::$builtInTypes[$type]);
    }

    /**
     * Exports the reflection of a method.
     * 
     * @param object|string|null $objectOrClass The class instance, class name, or null.
     * @param string|null $method The method name or null.
     * 
     * @return string The exported method reflection.
     */
    public static function exportMethod(object|string|null $objectOrClass = null, string|null $method = null): string
    {
        if (OperationSystem::comparePHPVersion('7.4.0', '>=')) {
            $ref = new ReflectionMethod($objectOrClass, $method);

            $modifiers = implode(' ', Reflection::getModifierNames($ref->getModifiers()));
            $returnType = $ref->hasReturnType() ? ': ' . $ref->getReturnType() : '';

            $params = [];
            foreach ($ref->getParameters() as $param) {
                $paramStr = '';

                if ($param->hasType()) {
                    $paramStr .= $param->getType() . ' ';
                }

                if ($param->isVariadic()) {
                    $paramStr .= '...';
                }

                $paramStr .= '$' . $param->getName();

                if ($param->isOptional() && $param->isDefaultValueAvailable()) {
                    $default = var_export($param->getDefaultValue(), true);
                    $paramStr .= ' = ' . $default;
                }

                $params[] = $paramStr;
            }

            $paramList = implode(', ', $params);

            return sprintf("%s function %s(%s)%s", $modifiers, $ref->getName(), $paramList, $returnType);
        }

        // @phpstan-ignore-next-line
        return ReflectionMethod::export($objectOrClass, $method);
    }

    /**
     * Creates a reflection handler instance from a given class and method name.
     *
     * @param object|string|null $objectOrClass The class instance, class name, or null.
     * @param string|null $method The method name or null.
     * 
     * @return ReflectionMethod Returns an instance of the handler.
     */
    public static function createFromMethodName(object|string|null $objectOrClass = null, string|null $method = null): ReflectionMethod
    {
        if (OperationSystem::comparePHPVersion('8.3.0', '>=')) {
            if ($objectOrClass === null) {
                /** @noinspection PhpUndefinedMethodInspection */
                $method = ReflectionMethod::createFromMethodName($method);
            } else {
                if (is_object($objectOrClass)) {
                    $objectOrClass = $objectOrClass::class;
                }

                /** @noinspection PhpUndefinedMethodInspection */
                $method = ReflectionMethod::createFromMethodName(sprintf("%s::%s", $objectOrClass, $method));
            }
        } else {
            $method = new ReflectionMethod($objectOrClass, $method);
        }

        return $method;
    }

    /**
     * Binding a closure to a specific object context.
     * 
     * @param closure $closure
     * @param object $obj
     * 
     * @return Closure|null
     */
    public static function bindClosureToObject(Closure $closure, object $obj): Closure|null
    {
        $ref = new ReflectionFunction($closure);
        $scope = $ref->getClosureScopeClass();
        if ($scope && $scope->isInstance($obj)) {
            return $closure->bindTo($obj, $scope->getName());
        }

        return $closure->bindTo($obj);
    }

    /**
     * Retrieves the parameters count of the class method
     * 
     * @param object|string|null $objectOrClass
     * @param string $method
     * 
     * @return int
     */
    public static function getParametersCountOfClassMethod(object|string|null $objectOrClass, string $method): int
    {
        return self::createFromMethodName($objectOrClass, $method)->getNumberOfParameters();
    }

    /**
     * Retrieves the required parameters count of the class method
     * 
     * @param object|string|null $objectOrClass
     * @param string $method
     * 
     * @return int
     */
    public static function getRequiredParametersCountOfClassMethod(object|string|null $objectOrClass, string $method): int
    {
        return self::createFromMethodName($objectOrClass, $method)->getNumberOfRequiredParameters();
    }

    /**
     * Retrieves the names of the properties for a given class.
     *
     * @param object|string $objectOrClass The class to reflect. Can be an object instance, a class name as a string, or null.
     * 
     * @return array<int, string> An array containing the names of the class properties.
     */
    public static function getClassPropertiesNames(object|string $objectOrClass): array
    {
        $reflectionClass = new ReflectionClass($objectOrClass);
        $properties = $reflectionClass->getProperties();
        $names = [];

        /** @var ReflectionProperty $property */
        foreach ($properties as $property) {
            $names[] = $property->getName();
        }

        return $names;
    }

    /**
     * Checks if the specified class has the given attribute.
     *
     * @param object|string $objectOrClass The fully qualified name of the class to check, or null.
     * @param string $attributeName The name of the attribute to look for, or null.
     * 
     * @return bool Returns true if the attribute exists on the class, false otherwise.
     */
    public static function hasAttribute(object|string $objectOrClass, string $attributeName): bool
    {
        $reflectionClass = new ReflectionClass($objectOrClass);

        foreach ($reflectionClass->getAttributes($attributeName) as $attribute) {
            if ($attribute->getName() !== $attributeName) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Determines if the given class is incomplete.
     *
     * @param mixed $class The class to check for incompleteness.
     * 
     * @return bool Returns true if the class is incomplete, false otherwise.
     */
    public static function isIncompleteClass(mixed $class): bool
    {
        return $class instanceof __PHP_Incomplete_Class;
    }

    /**
     * Retrieves the available magic methods in the parent class of the given object or class.
     *
     * @param object|string|null $objectOrClass The object instance or class name to inspect. If null, the current context may be used.
     * 
     * @return array|string An array of available magic methods, or a string if an error or special case occurs.
     */
    public static function getAvailableMagicMethodsInParentClass(null|object|string $objectOrClass = null): array|string
    {
        $methods = [];
        $parent = get_parent_class($objectOrClass);

        foreach (self::$magicMethods as $method) {
            $methodName = sprintf("__%s", $method);

            if (!$parent || !method_exists($parent, $methodName)) {
                continue;
            }

            $reflectionMethod = self::createFromMethodName($parent, $methodName);
            if ($reflectionMethod->isAbstract() || $reflectionMethod->isPrivate()) {
                continue;
            }

            $methods[] = $methodName;
        }

        return $methods;
    }

    /**
     * Retrieves the static class constructor if available.
     *
     * @param object|string|null $objectOrClass
     * 
     * @return bool|ReflectionMethod Returns a ReflectionMethod instance representing the static constructor,
     *                               or false if no static constructor exists.
     */
    public static function getStaticClassConstructor(object|string|null $objectOrClass = null): bool|ReflectionMethod
    {
        if (!self::hasStaticClassConstructor()) {
            return false;
        }

        return self::getMethod($objectOrClass ?? static::class, '__construct');
    }

    /**
     * Retrieves a ReflectionMethod instance for the specified method name of a given class.
     *
     * @param string|object $class The class name or object instance to reflect.
     * @param string $methodName The name of the method to retrieve.
     * 
     * @return ReflectionMethod|null Returns the ReflectionMethod if found, or null if the method does not exist.
     * 
     * @throws ReflectionException If the class or method does not exist.
     */
    public static function getMethodByName(string|object $class, string $methodName): ReflectionMethod
    {
        $reflection = new ReflectionClass($class);
        if (!$reflection->hasMethod($methodName)) {
            throw new Exception('Method is not exists');
        }

        return $reflection->getMethod($methodName);
    }

    /**
     * Determines if the given ReflectionClass instance represents a cloneable class.
     *
     * @param ReflectionClass|null $reflectionClass The reflection of the class to check, or null.
     * 
     * @return bool True if the class is cloneable, false otherwise.
     */
    public static function isCloneableClass(?ReflectionClass $reflectionClass = null): bool
    {
        return $reflectionClass->isCloneable() && !$reflectionClass->hasMethod('__clone') && !$reflectionClass->isSubclassOf(ArrayIterator::class);
    }

    /**
     * Determines if the given ReflectionClass instance represents a cloneable class.
     *
     * @param ReflectionClass|null $reflectionClass The reflection of the class to check, or null.
     * 
     * @return bool True if the class is cloneable, false otherwise.
     */
    public static function hasInternalAncestors(?ReflectionClass $reflectionClass = null): bool
    {
        do {
            if ($reflectionClass->isInternal()) {
                return true;
            }

            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass);

        return false;
    }

    /**
     * Returns an array of all declared traits
     * 
     * @return string[]|null
     */
    public static function getDeclaredTraits(): array|null
    {
        if (function_exists('get_declared_traits')) {
            return get_declared_traits();
        }

        return null;
    }

    /**
     * Returns an array with the name of the defined classes
     * 
     * @return string[]
     */
    public static function getDeclaredClasses(): array
    {
        return get_declared_classes();
    }

    /**
     * Checks if there are top-level functions (methods outside of classes) in PHP code.
     *
     * @param string $code The PHP code to analyze.
     * 
     * @return bool True if top-level functions are found, false otherwise.
     */
    public static function hasTopLevelFunctions(string $code): bool
    {
        $code = preg_replace('/\/\*[\s\S]*?\*\/|\/\/.*?\n/', '', $code);
        $code = preg_replace('/#.*?\n/', '', $code);
        $code = preg_replace('/"([^"\\\\]*|\\\\.)*"|\'([^\'\\\\]*|\\\\.)*\'/', '', $code);
        $pattern = '/^\s*function\s+[a-zA-Z_][a-zA-Z0-9_]*\s*\(.*?\)\s*\{/m';

        return preg_match($pattern, $code) === 1;
    }

    public static function changeComment($code, $function, $comment): void
    {
        $pattern = "/^(\s+|\t+|)((?:(public\s+|private\s+|protected\s+)(?:static\s+)?)?function\s+([a-zA-Z0-9_]+))\([a-z0-9\$\, ]{1,}\)$/";
    }

    /**
     *  Removes classes matching a given name from PHP code.
     *
     *  @param string $code      The PHP code to process.
     *  @param string $className The name of the class to remove.  Case-sensitive.
     * 
     *  @return string The modified PHP code with the class removed.
     */
    public static function removeClass(string $code, string $className): string
    {
        $pattern = '/(?:abstract\s+)?(?:final\s+)?class\s+' . preg_quote($className, '/') . '\s+(?:extends\s+[a-zA-Z0-9_\\\]+\s*)?(?:implements\s+[a-zA-Z0-9_\\\, ]+\s*)?\{[^}]*\}/si'; //Case-insensitive and dot matches newline
        return preg_replace($pattern, '', $code);
    }

    /**
     * Extracts an array of method names and their code from a class, or top-level.
     *
     * @param string $code       The PHP code to analyze.
     * @param string|null $className Optional class name to extract methods from.  If null, extracts top-level functions.
     * 
     * @return array<string, string> An associative array where keys are method names and values are the method code.  Empty array if no methods found.
     */
    public static function extractMethods(string $code, ?string $className = null): array
    {
        $methods = [];

        if ($className === null) {
            $pattern = '/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*?)\)\s*\{([\s\S]*?)\}/';
            if (preg_match_all($pattern, $code, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $methods[$match[1]] = $match[0];
                }
            }
        } else {
            $classPattern = '/(?:abstract\s+)?(?:final\s+)?class\s+' . preg_quote($className, '/') . '\s+(?:extends\s+[a-zA-Z0-9_\\\]+\s*)?(?:implements\s+[a-zA-Z0-9_\\\, ]+\s*)?\{([\s\S]*?)\}/si'; //Extract content between class braces.   
            if (preg_match($classPattern, $code, $classMatches)) {
                $classContent = $classMatches[1];

                $methodPattern = '/(?:public|protected|private)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\((.*?)\)\s*\{([\s\S]*?)\}/'; //Non-greedy match on the method body.  Includes visibility.
                if (preg_match_all($methodPattern, $classContent, $matches, PREG_SET_ORDER)) {
                    foreach ($matches as $match) {
                        $methods[$match[1]] = $match[0];
                    }
                }
            }
        }

        return $methods;
    }

    /**
     * Retrieve all declared classes that are subclasses of the given class name.
     *
     * This method iterates through all declared classes, checks whether each one
     * is a subclass of the specified `$className`, and ensures the class exists
     * before adding it to the result list.
     *
     * @param ReflectionClass|string $className The parent class name to check against.
     * 
     * @return array List of subclass names that extend the given class.
     */
    public static function findDeclaredSubClasses(ReflectionClass|string $className): array
    {
        $classes = [];

        foreach (self::getDeclaredClasses() as $class) {
            if (!self::isSubClassOf($class, $className)) {
                continue;
            }

            if (!class_exists($class)) {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }

    /**
     * Returns an array of all defined functions
     * 
     * @return string[]
     */
    public static function getDeclaredFunctions(): array
    {
        return get_defined_functions();
    }

    /**
     * Retrieves an array of matched namespaces from the declared classes.
     *
     * If a class name is provided, the method will filter the namespaces to those
     * that match the given class. If no class name is provided, it returns all matched namespaces.
     *
     * @param string|null $class Optional class name to filter namespaces.
     * 
     * @return string[] An array of matched namespaces.
     */
    public static function getMatchedNameSpacesFromDeclaredClasses(?string $class = null): array
    {
        return array_filter(self::getDeclaredClasses(), function ($classes) use ($class) {
            $split = explode("\\", $classes);
            return $split[count($split) - 1] === $class;
        });
    }

    /**
     * Retrieves the class and method information from a callback string.
     *
     * @param mixed $callback The callback to parse, which can be a string, array, or other callable type.
     * 
     * @return string[] An array containing the class and method extracted from the callback.
     */
    public static function getCallMethodFromString(mixed $callback = null): array
    {
        $cacheKey = self::buildCallMethodCacheKey($callback);
        if (isset(self::$callMethodCache[$cacheKey])) {
            return self::$callMethodCache[$cacheKey];
        }

        if (self::isStaticMethodString($callback)) {
            [$class, $method] = explode('::', $callback);
        } else if (is_array($callback)) {
            [$class, $method] = $callback;
        }

        if (!isset($class)) {
            return throw new Exception('Target class is empty');
        }

        if (count(explode("\\", $class)) == 1) {
            $matchNamespace = self::getMatchedNameSpacesFromDeclaredClasses($class);

            if (count($matchNamespace) === 1) {
                $class = array_pop($matchNamespace);
            }
        }

        self::$callMethodCache[$cacheKey] = [$class, $method];

        return self::$callMethodCache[$cacheKey];
    }

    /**
     * Converts the given Reflector instance to its string representation.
     *
     * @param Reflector|null $reflector The Reflector instance to convert, or null.
     * 
     * @return string|null The string representation of the reflector, or null if none provided.
     */
    public static function toString(?Reflector $reflector = null): ?string
    {
        if ($reflector instanceof ReflectionClass) {
            return $reflector->name;
        }

        if ($reflector instanceof ReflectionMethod) {
            return sprintf("%s::%s()", $reflector->getDeclaringClass()->name, $reflector->name);
        }

        if ($reflector instanceof ReflectionFunction) {
            return sprintf("%s()", $reflector->name);
        }

        if ($reflector instanceof ReflectionProperty) {
            return self::getPropertyDeclaringClass($reflector)->name . '::$' . $reflector->name;
        }

        if ($reflector instanceof ReflectionParameter) {
            return sprintf("$%s in %s", $reflector->name, self::toString($reflector->getDeclaringFunction()));
        }

        return null;
    }

    /**
     * Checks if a given trait exists.
     *
     * @param string|null $trait The name of the trait to check. If null, no trait will be checked.
     * @param bool $autoload Whether to autoload the trait if it is not already loaded. Default is true.
     * 
     * @return bool Returns true if the trait exists, false otherwise.
     */
    public static function isTraitExists(?string $trait = null, bool $autoload = true): bool
    {
        return trait_exists($trait, $autoload);
    }

    /**
     * Retrieves a reflection of a property from the given object or class.
     *
     * @param object|string|null $objectOrClass The object instance or class name to reflect. If null, uses the default context.
     * @param string $name The name of the property to reflect. If null, uses the default property.
     * 
     * @return ReflectionProperty The reflection of the specified property.
     * 
     * @throws ReflectionException If the property does not exist or cannot be accessed.
     */
    public static function getProperty(null|object|string $objectOrClass, string $name): ReflectionProperty
    {
        $reflection = new ReflectionClass($objectOrClass);
        $property = $reflection->getProperty($name);

        return $property;
    }

    /**
     * Sets the value of a property on a given object or class.
     *
     * @param null|object|string $objectOrClass The object instance or class name where the property exists.
     * @param string|null $name The name of the property to set.
     * @param mixed $value The value to assign to the property.
     * @param object|null $object Optional object instance if $objectOrClass is a class name.
     *
     * @return void
     */
    public static function setValueProperty(null|object|string $objectOrClass = null, ?string $name = null, mixed $value = null, ?object $object = null): void
    {
        $property = self::getProperty($objectOrClass, $name);
        $property->setValue($object, $value);
    }

    /**
     * Determines if the specified property or parameter has a default value.
     *
     * @param null|object|string $objectOrClass The object instance, class name, or null to inspect.
     * @param string|null $name The name of the property or parameter to check for a default value.
     * 
     * @return bool Returns true if a default value exists, false otherwise.
     */
    public static function hasDefaultValue(null|object|string $objectOrClass = null, ?string $name = null): bool
    {
        $property = self::getProperty($objectOrClass, $name);
        return $property->hasDefaultValue();
    }

    /**
     * Sets the accessibility of a property for a given object or class.
     *
     * @param object|string|null $objectOrClass The object instance or class name containing the property.
     * @param string|null $name The name of the property to set accessibility for.
     * @param bool $accessible Whether the property should be accessible (true) or not (false).
     *
     * @return void
     */
    public static function setAccessibleProperty(null|object|string $objectOrClass = null, ?string $name = null, bool $accessible = true): void
    {
        $property = self::getProperty($objectOrClass, $name);
        if (method_exists($property, 'setAccessible')) {
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible($accessible);
            }
        }
    }

    /**
     * Retrieves the namespace name of the class that declares the given reflection method.
     *
     * @param ReflectionMethod $reflectionMethod The reflection method instance to inspect.
     * 
     * @return string The namespace name of the declaring class.
     */
    public static function getNamespaceNameOfDeclaringClass(ReflectionMethod $reflectionMethod): string
    {
        return self::getDeclaringClass($reflectionMethod)->getNamespaceName();
    }

    /**
     * Retrieves the namespace name of the specified class.
     *
     * @param object|string $classOrObject The fully qualified class name.
     * 
     * @return string The namespace name of the class.
     */
    public static function getClassNamespaceName(object|string $classOrObject): string
    {
        $classOrObject = self::getClass($classOrObject);
        return $classOrObject->getNamespaceName();
    }

    /**
     * Retrieves the declaring class of the provided ReflectionMethod instance.
     *
     * @param ReflectionMethod $reflectionMethod The reflection method to inspect.
     * 
     * @return ReflectionClass The class that declares the given method.
     */
    public static function getDeclaringClass(ReflectionMethod $reflectionMethod): ReflectionClass
    {
        return $reflectionMethod->getDeclaringClass();
    }

    /**
     * Determines whether a given property is initialized on an object.
     *
     * @param ReflectionProperty $reflectionProperty The reflection of the property to check.
     * @param object|null $object The object instance to check the property on. If null, checks static properties.
     * 
     * @return bool True if the property is initialized, false otherwise.
     */
    public static function isInitializedProperty(ReflectionProperty $reflectionProperty, ?object $object = null): bool
    {
        return $reflectionProperty->isInitialized($object);
    }

    /**
     * Determines if the given ReflectionProperty represents a default property.
     *
     * @param ReflectionProperty $reflectionProperty The reflection property to check.
     * 
     * @return bool True if the property is a default property, false otherwise.
     */
    public static function isDefaultProperty(ReflectionProperty $reflectionProperty): bool
    {
        return $reflectionProperty->isDefault();
    }

    /**
     * Determines if the given ReflectionProperty represents a static property.
     *
     * @param ReflectionProperty $reflectionProperty The reflection property to check.
     * 
     * @return bool True if the property is a static property, false otherwise.
     */
    public static function isStaticProperty(ReflectionProperty $reflectionProperty): bool
    {
        return $reflectionProperty->isStatic();
    }

    /**
     * Retrieves the declaring class of a given reflection property.
     *
     * @param ReflectionProperty|null $property The reflection property to inspect. If null, behavior depends on implementation.
     * 
     * @return ReflectionClass The reflection class that declares the property.
     */
    public static function getPropertyDeclaringClass(?ReflectionProperty $property = null): ReflectionClass
    {
        $name = $property->name;
        $declaringClass = $property->getDeclaringClass();

        foreach ($declaringClass->getTraits() as $trait) {
            if (!self::isTraitExists($trait->getName())) {
                continue;
            }

            if (!$trait->hasProperty($name)) {
                continue;
            }

            if ($trait->getProperty($name)->getDocComment() !== $property->getDocComment()) {
                continue;
            }

            return self::getPropertyDeclaringClass($trait->getProperty($name));
        }

        return $declaringClass;
    }

    /**
     * Determines if the provided value represents a static method in string format.
     *
     * @param mixed $method The value to check, typically a string representing a method.
     * 
     * @return bool True if the value is a string representing a static method, false otherwise.
     */
    public static function isStaticMethodString(mixed $method = null): bool
    {
        return !is_callable($method) && is_string($method) && !empty($method) && strpos($method, '::') > 0 && str_contains($method, '::');
    }

    /**
     * Checks if the class method exists
     * 
     * @param null|object|string $object_or_class
     * @param null|string $method
     * 
     * @return bool
     */
    public static function isMethodExists(null|object|string $object_or_class = null, ?string $method = null): bool
    {
        return method_exists($object_or_class, $method);
    }

    /**
     * Determines if the current class has a static constructor method defined.
     * 
     * @return bool
     */
    public static function hasStaticClassConstructor(): bool
    {
        return method_exists(static::class, '__construct');
    }

    /**
     * Gets an array of methods for the class.
     * 
     * @param ReflectionClass $class
     * 
     * @return ReflectionMethod[]
     */
    public static function getClassMethods(?ReflectionClass $class = null): array
    {
        return $class->getMethods();
    }

    /**
     * Checks if the class implements the given interface
     * 
     * @param ReflectionClass|null $class
     * @param null|ReflectionClass|string $interface
     * 
     * @return bool
     */
    public static function isClassImplementsInterface(?ReflectionClass $class = null, null|ReflectionClass|string $interface = null): bool
    {
        return $class->implementsInterface($interface);
    }

    /**
     * Retrieves all methods from the given object or class that match the specified filter.
     *
     * @param object|string $object_or_class The object instance or class name to reflect.
     * @param int $filter Bitmask of method visibility filters (e.g., ReflectionMethod::IS_PUBLIC, ReflectionMethod::IS_PROTECTED, ReflectionMethod::IS_PRIVATE).
     * 
     * @return array<string, ReflectionMethod> An array of ReflectionMethod instances matching the filter.
     */
    public static function getMethodsWithName(object|string $object_or_class, int $filter = ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED | ReflectionMethod::IS_PRIVATE): array
    {
        $methods = [];

        if (is_object($object_or_class) || class_exists($object_or_class)) {
            $reflectedClass = new ReflectionClass($object_or_class);
            $reflectedMethods = $reflectedClass->getMethods($filter);

            foreach ($reflectedMethods as $method) {
                $methods[$method->getName()] = $method;
            }
        }

        return $methods;
    }

    /**
     * Checks if the given interface has been defined.
     * 
     * @param mixed $interface
     * @param mixed $autoload
     * 
     * @return bool
     */
    public static function isInterfaceExists(?string $interface, $autoload = false): bool
    {
        return interface_exists($interface, $autoload);
    }

    /**
     * Checks if a subclass
     * 
     * @param mixed $class
     * @param null|ReflectionClass|string $className
     * @param bool $allow_string
     * 
     * @return bool
     */
    public static function isSubClassOf(mixed $class, null|ReflectionClass|string $className = null, bool $allow_string = true): bool
    {
        if ($class instanceof ReflectionClass) {
            return $class->isSubclassOf($className);

        }

        return is_subclass_of($class, $className, $allow_string);
    }

    /**
     * Checks if the class is an interface
     * 
     * @param ?ReflectionClass $class
     * 
     * @return bool
     */
    public static function isInterfaceClassDescriptor(?ReflectionClass $class = null): bool
    {
        return $class->isInterface();
    }

    /**
     * Determines if a class uses traits, including traits used by its parent classes.
     * 
     * @param mixed $class
     * 
     * @return array
     */
    public static function isClassUsesRecursive(mixed $class): array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $results = [];

        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += static::isClassUsesRecursive($class);
        }

        return array_unique($results);
    }

    /**
     * Determines if a class uses traits, including traits used by its parent classes.
     * 
     * @param object|string $trait
     * @param bool $autoload
     * 
     * @return array|bool
     */
    public static function isTraitUsesRecursive(object|string $trait, bool $autoload = true): array|bool
    {
        $traits = class_uses($trait, $autoload);

        foreach ($traits as $trait) {
            $traits += static::isTraitUsesRecursive($trait);
        }

        return $traits;
    }

    /**
     * Gets an array of private properties for the class.
     * 
     * @param ReflectionClass $class
     * 
     * @return string[]
     */
    public function getPrivateClassProperties(ReflectionClass $class): array
    {
        return self::getClassProperties($class, ReflectionProperty::IS_PRIVATE);
    }

    /**
     * Gets an array of public properties for the class.
     * 
     * @param ReflectionClass $class
     * 
     * @return string[]
     */
    public function getPublicClassProperties(ReflectionClass $class): array
    {
        return self::getClassProperties($class, ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Gets an array of properties for the class with filter.
     * 
     * @param ReflectionClass $class
     * @param int $filter
     * 
     * @return array
     */
    public static function getClassProperties(ReflectionClass $class, int $filter = ReflectionProperty::IS_PUBLIC): array
    {
        return array_map(
            fn($property) => $property,
            $class->getProperties($filter),
        );
    }

    /**
     * Retrieves an array of public properties for the specified class.
     *
     * @param ReflectionClass $class The reflection of the class to inspect.
     * @param Closure $filter
     * 
     * @return array An array containing the names and/or details of the public properties.
     */
    public static function getSpecifyClassInterfaces(ReflectionClass $class, Closure $filter): array
    {
        return array_map(
            fn($interface) => $filter($interface),
            $class->getInterfaces()
        );
    }

    /**
     * Get all interfaces implemented by a class as name => ReflectionClass pairs.
     *
     * @param object|string $objectOrClass
     *
     * @return array<string, ReflectionClass>
     */
    public static function getClassInterfaces(object|string $objectOrClass): array
    {
        return (new ReflectionClass($objectOrClass))->getInterfaces();
    }

    /**
     * Checks if the class is abstract
     * 
     * @param ReflectionClass $class
     * 
     * @return bool
     */
    public static function isAbstractClassDescriptor(?ReflectionClass $class = null): bool
    {
        return $class->isAbstract();
    }

    /**
     * Check if has document comments
     * 
     * @param ReflectionClass|ReflectionMethod|ReflectionProperty $reflector
     * 
     * @return string|bool
     */
    public static function hasDocumentComment(?Reflector $reflector = null): string|bool
    {
        return $reflector->getDocComment() == false;
    }

    /**
     * Get a root directory path
     * 
     * @param object $object
     * 
     * @return string
     */
    public static function getRootDirectory(object $object): string
    {
        $reflectionObject = new ReflectionObject($object);
        $directory = dirname($reflectionObject->getFileName());

        return $directory;
    }

    /**
     * Gets the document comments
     * 
     * @param ReflectionClass|ReflectionMethod|ReflectionProperty $reflector
     * 
     * @return string[]|bool
     */
    public static function getDocumentComment(null|ReflectionClass|ReflectionMethod|ReflectionProperty $reflector = null): array|bool
    {
        $rawComment = $reflector->getDocComment();
        if (!$rawComment) {
            return false;
        }

        $comments = explode("\n", $rawComment);

        foreach ($comments as &$comment) {
            $comment = ltrim($comment);
            $comment = ltrim($comment, "*");
        }

        return $comments;
    }

    /**
     * Gets an array of annotation for the class
     * 
     * @param ReflectionClass|ReflectionMethod|ReflectionProperty $reflector
     * @param null|string $annotationName
     * 
     * @return object[]
     */
    public static function getAnnotations(ReflectionClass|ReflectionMethod|ReflectionProperty $reflector, ?string $annotationName = null): array
    {
        $cacheKey = self::buildAnnotationCacheKey($reflector, $annotationName);
        if (isset(self::$annotationCache[$cacheKey])) {
            return self::$annotationCache[$cacheKey];
        }

        $result = [];

        if (8 === PHP_MAJOR_VERSION || 8 < PHP_MAJOR_VERSION) {
            $attributes = $reflector->getAttributes($annotationName);

            if (empty($attributes)) {
                return $result;
            }

            /**
             * Reflection attributes retrieved from the reflector.
             *
             * @var ReflectionAttribute[] $attributes
             */
            foreach ($attributes as $attribute) {
                $result[] = $attribute->newInstance();
            }
        }

        self::$annotationCache[$cacheKey] = $result;

        return self::$annotationCache[$cacheKey];
    }

    /**
     * Gets an array of annotations from class property
     * 
     * @param null|ReflectionProperty $property
     * @param null|string $annotationName
     * 
     * @return object[]
     */
    public static function getPropertyAnnotations(null|ReflectionProperty $property = null, ?string $annotationName = null): array
    {
        if ($property === null) {
            return [];
        }

        return self::getAnnotations($property, $annotationName);
    }

    /**
     * Get all annotations from class properties
     * 
     * @param string $class
     * @param int|null $filter
     * 
     * @return array<string, object[]>
     */
    public static function getAllAnnotations(string $class, int|null $filter = ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED): array
    {
        $reflectionClass = new ReflectionClass($class);
        $properties = $reflectionClass->getProperties($filter);

        /**
         * Annotations grouped by property name.
         *
         * @var object[] $annotations
         */
        $annotations = [];

        /**
         * Array of reflection properties to inspect.
         *
         * @var ReflectionProperty[] $properties
         */
        foreach ($properties as $property) {
            foreach (self::getAnnotations($property) as $annotationInstance) {
                $annotations[$property->getName()][] = $annotationInstance;
            }
        }

        return $annotations;
    }

    private static function buildCallMethodCacheKey(mixed $callback): string
    {
        if (is_string($callback)) {
            return 's:' . $callback;
        }
        if (is_array($callback)) {
            $classPart = $callback[0] ?? '';
            $methodPart = (string) ($callback[1] ?? '');
            if (is_object($classPart)) {
                $classPart = $classPart::class;
            }

            return 'a:' . (string) $classPart . '::' . $methodPart;
        }
        if ($callback instanceof Closure) {
            return 'c:' . spl_object_id($callback);
        }
        if (is_object($callback)) {
            return 'o:' . $callback::class;
        }

        return 'x:' . md5(serialize($callback));
    }

    private static function buildAnnotationCacheKey(ReflectionClass|ReflectionMethod|ReflectionProperty $reflector, ?string $annotationName): string
    {
        $annotationKey = $annotationName ?? '*';

        if ($reflector instanceof ReflectionClass) {
            return 'class:' . $reflector->getName() . '|' . $annotationKey;
        }
        if ($reflector instanceof ReflectionMethod) {
            return 'method:' . $reflector->getDeclaringClass()->getName() . '::' . $reflector->getName() . '|' . $annotationKey;
        }

        return 'property:' . $reflector->getDeclaringClass()->getName() . '::$' . $reflector->getName() . '|' . $annotationKey;
    }

    /**
     * Get a ReflectionClass
     * 
     * @param object|string $objectOrClass
     * 
     * @return ReflectionClass
     */
    public static function getClass(null|object|string $objectOrClass = null): ReflectionClass
    {
        $reflection = new ReflectionClass($objectOrClass);

        return $reflection;
    }

    /**
     * Get a any object from callable object
     * 
     * @param ?callable $callable
     * 
     * @return ReflectionFunction|ReflectionMethod
     */
    public static function fromCallable(?callable $callable = null): ReflectionFunction|ReflectionMethod
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable) && function_exists($callable)) {
            return new ReflectionFunction($callable);
        }

        if (is_string($callable) && strpos($callable, '::') !== false) {
            return self::createFromMethodName($callable);
        }

        if (is_object($callable) && method_exists($callable, '__invoke')) {
            return self::createFromMethodName($callable, '__invoke');
        }

        if (is_array($callable)) {
            return self::createFromMethodName($callable[0], $callable[1]);
        }

        throw new Exception('Callable is not found');
    }

    /**
     * Get the reflection of the specified function.
     *
     * @param Closure|string|null $function The function to reflect.
     * 
     * @return ReflectionFunction The reflection of the function.
     */
    public static function getFunction(null|Closure|string $function = null): ReflectionFunction
    {
        $reflection = new ReflectionFunction($function);

        return $reflection;
    }

    /**
     * Get the ReflectionMethod object for the specified class and method.
     *
     * @param object|string|null $objectOrClass The class name or object to reflect on.
     * @param string|null $method The method name to reflect on.
     * 
     * @return ReflectionMethod The ReflectionMethod object for the specified class and method.
     */
    public static function getMethod(object|string|null $objectOrClass, string|null $method = null): ReflectionMethod
    {
        $reflection = self::createFromMethodName($objectOrClass, $method);

        return $reflection;
    }

    /**
     * Get the parameters of a ReflectionClass or ReflectionFunction.
     *
     * @param ReflectionClass|ReflectionFunction|null $reflection
     * 
     * @return array<ReflectionParameter>
     */
    public static function getParameters(null|ReflectionClass|ReflectionFunction $reflection = null): array
    {
        $parameters = [];

        if ($reflection instanceof ReflectionClass) {
            $parameters = $reflection->getConstructor() ? $reflection->getConstructor()->getParameters() : [];
        } else if ($reflection instanceof ReflectionFunction) {
            $parameters = $reflection->getParameters();
        }

        return $parameters;
    }

    /**
     * Calls a non-public method on an object or class.
     * 
     * @param object|string|null $objectOrClass The object instance or class name to call the method on.
     * @param string $method The name of the method to call.
     * @param array $args An array of arguments to pass to the method.
     * 
     * @return mixed The result of the method call.
     */
    public static function callNonPublicMethod(object|string|null $objectOrClass, string $method, array $args = []): mixed
    {
        $ref = self::createFromMethodName($objectOrClass, $method);
        if (method_exists($ref, 'setAccessible')) {
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $ref->setAccessible(true);
            }
        }

        return $ref->invokeArgs($objectOrClass, $args);
    }

    /**
     * Get method parameters names
     * 
     * @param object|string|null $objectOrClass
     * @param string|null $method
     * 
     * @return string[]
     */
    public static function getMethodParameters(object|string|null $objectOrClass, $method): array
    {
        $reflection = self::createFromMethodName($objectOrClass, $method);
        return array_map(fn($p) => $p->getName(), $reflection->getParameters());
    }

    /**
     * Converts an entity object to an associative array.
     * 
     * @param object|string $entity The entity object to convert.
     * 
     * @return array An associative array representing the entity's properties and their values.
     */
    public static function entityToArray(object|string $entity): array
    {
        $ref = new ReflectionClass($entity);
        $data = [];

        foreach ($ref->getProperties() as $prop) {
            if (method_exists($prop, 'setAccessible')) {
                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    $prop->setAccessible(true);
                }
            }

            $data[$prop->getName()] = $prop->getValue($entity);
        }

        return $data;
    }

    /**
     * Lazy loads a property value using a loader function if the property is null.
     * 
     * @param object|string $objectOrClass The object containing the property.
     * @param string $prop The name of the property to lazy load.
     * @param callable $loader A callable that returns the value to set if the property is null.
     * 
     * @return mixed The value of the property after lazy loading.
     */
    public static function lazyPropertyLoader(object|string $objectOrClass, string $prop, callable $loader): mixed
    {
        $ref = new ReflectionProperty($objectOrClass, $prop);
        if (method_exists($ref, 'setAccessible')) {
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $ref->setAccessible(true);
            }
        }

        if ($ref->getValue($objectOrClass) === null) {
            $value = $loader();
            $ref->setValue($objectOrClass, $value);
        }

        return $ref->getValue($objectOrClass);
    }

    /**
     * Invokes a callable with named arguments.
     * 
     * @param callable|string $func
     * @param array $args
     * 
     * @return mixed
     * 
     * @throws InvalidArgumentException
     */
    public static function invokeWithNamedArguments(callable|string $func, array $args = []): mixed
    {
        $ref = new ReflectionFunction($func);
        $invokeArgs = [];

        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $args)) {
                $invokeArgs[] = $args[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $invokeArgs[] = $param->getDefaultValue();
            } else {
                throw new InvalidArgumentException("Missing argument: $name");
            }
        }

        return $ref->invokeArgs($invokeArgs);
    }

    /**
     * Autowires a callable by resolving its parameters.
     * 
     * @param callable|string $func
     * 
     * @return mixed
     * 
     * @throws RuntimeException
     */
    public static function autowire(callable|string $func): mixed
    {
        $ref = new ReflectionFunction($func);

        $invokeArgs = [];
        foreach ($ref->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && $type->isBuiltin()) {
                $className = $type->getName();
                $invokeArgs[] = new $className();
            } elseif ($parameter->isDefaultValueAvailable()) {
                $invokeArgs[] = $parameter->getDefaultValue();
            } else {
                throw new RuntimeException("Cannot autowire parameter: " . $parameter->getName());
            }
        }

        return $ref->invokeArgs($invokeArgs);
    }

    /**
     * Get method parameter names
     * 
     * @param object|string $objectOrClass
     * @param string $method
     * @param bool $requiredType
     * 
     * @return array<ReflectionParameter|string>
     */
    public static function getMethodParameterNames(object|string $objectOrClass, string $method, bool $requiredType = true): array
    {
        $reflector = new ReflectionClass($objectOrClass);
        $method = $reflector->getMethod($method);

        if (!$requiredType) {
            return $method->getParameters();
        }

        $names = [];
        foreach ($method->getParameters() as $parameter) {

            $type = $parameter->getType();
            if (!$type) {
                continue;
            }

            if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
                if (!($type instanceof ReflectionNamedType)) {
                    continue;
                }

                $isBuiltin = $type->isBuiltin();
                if (!$isBuiltin) {
                    continue;
                }
            }

            $names[] = $parameter->getName();
        }

        return $names;
    }

    /**
     * Get the parameters of a given ReflectionFunction or ReflectionMethod.
     *
     * @param ReflectionFunction|ReflectionMethod|null $reflection The reflection object to get parameters from.
     * 
     * @return array<ReflectionParameter> An array of ReflectionParameter objects representing the parameters of the function or method.
     */
    public static function getReflectionParameters(null|ReflectionFunction|ReflectionMethod $reflection = null): array
    {
        $parameters = $reflection->getParameters();

        return $parameters;
    }

    /**
     * Check if the given parameter is an instance of ReflectionNamedType.
     *
     * @param mixed $parameter The parameter to check.
     * 
     * @return bool Returns true if the parameter is an instance of ReflectionNamedType, false otherwise.
     */
    public static function isNamedType(mixed $parameter = null): bool
    {
        return $parameter instanceof ReflectionNamedType;
    }

    /**
     * Get the reflection of the specified class and instantiate it with its dependencies.
     *
     * @param null|object|string $objectOrClass
     * 
     * @return object
     */
    public static function getClassReflection(null|object|string $objectOrClass = null): object
    {
        $dependencies = [];

        $reflection = self::getClass($objectOrClass);
        $parameters = self::getParameters($reflection);

        foreach ($parameters as $parameter) {
            /** @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type */
            $type = $parameter->getType();

            if (!$type || !self::isNamedType($type)) {
                continue;
            }

            if ($parameter->isOptional()) {
                continue;
            }

            // Smaller Or Equal than 8.1.0
            if (OperationSystem::comparePHPVersion('8.1.0', '<=')) {
                $name = $type->getName();
                $instance = new ReflectionClass($name);

                // Greater than 8.0.0
            } else if (OperationSystem::comparePHPVersion('8.0.0', '>')) {
                // @phpstan-ignore-next-line
                $instance = $parameter->getClass()->newInstance();
            }

            $dependencies[] = $instance;
        }

        return $reflection->newInstance(...$dependencies);
    }


    /**
     * Get the declaring class from the given Reflector object.
     *
     * @param Reflector $reflector
     * 
     * @return ReflectionClass|null
     */
    public function getDeclaringClassFromReflector(Reflector $reflector): ?ReflectionClass
    {
        if ($reflector instanceof ReflectionClass) {
            return $reflector;
        }

        if ($reflector instanceof ReflectionClassConstant) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionProperty) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionMethod) {
            return $reflector->getDeclaringClass();
        }

        if ($reflector instanceof ReflectionParameter) {
            return $reflector->getDeclaringClass();
        }

        return null;
    }

    /**
     * Get the parent class of the given object or class name.
     *
     * @param null|object|string $objectOrClass The object or class name to get the parent class from.
     * 
     * @return bool|string Returns the parent class name if found, false if no parent class, or null if invalid input.
     */
    public static function getParentClassName(null|object|string $objectOrClass = null): bool|string
    {
        if (function_exists('get_parent_class')) {
            return get_parent_class($objectOrClass);
        }

        $reflectedClass = new ReflectionClass($objectOrClass);
        $parentClass = $reflectedClass->getParentClass();

        return $parentClass ? $parentClass->getName() : null;
    }

    /**
     * Get the parent ReflectionClass of the given class, or false if none.
     *
     * @param object|string $objectOrClass
     *
     * @return ReflectionClass|false
     */
    public static function getParentClass(object|string $objectOrClass): ReflectionClass|false
    {
        return (new ReflectionClass($objectOrClass))->getParentClass();
    }

    /**
     * Calls the specified method with the given arguments.
     *
     * @param callable|null $method
     * @param array ...$arguments
     * 
     * @return mixed
     */
    public static function callMethod(?callable $method = null, array ...$arguments): mixed
    {
        return call_user_func($method, $arguments);
    }

    /**
     * Calls a method with an array of arguments.
     *
     * @param callable|null $method
     * @param ArrayObject|array $arguments
     * 
     * @return mixed
     */
    public static function callMethodArray(?callable $method = null, ArrayObject|array $arguments = []): mixed
    {
        return call_user_func_array($method, $arguments);
    }

    /**
     * Check if the given object has a class prototype.
     *
     * @param object $object The object to check for class prototype.
     * 
     * @return bool Returns true if the object has a class prototype, false otherwise.
     */
    public static function hasClassPrototype(object $object): bool
    {
        $reflectionObject = new ReflectionObject($object);

        $parents = [];
        $parent = $reflectionObject->getParentClass();
        if ($parent instanceof ReflectionClass) {
            $parents[] = $parent;
        }

        $parents = array_merge($parents, $reflectionObject->getInterfaces());
        foreach ($parents as $knownParent) {
            if ($knownParent->hasMethod($reflectionObject->getName())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calls a method of a class or object with the provided arguments.
     *
     * @param object|string|null $objectOrClass The class or object to call the method on
     * @param string|null $method The name of the method to call
     * @param array ...$arguments The arguments to pass to the method
     * 
     * @return mixed The result of the method call
     */
    public static function callMethodOfClass(object|string|null $objectOrClass = null, ?string $method = null, array ...$arguments): mixed
    {
        $reflection = self::getMethod($objectOrClass, $method);

        if ($reflection->isStatic()) {
            return forward_static_call($method, $arguments);
        }

        return self::callMethod($method, $arguments);
    }

    /**
     * Calls a method of a class or object with an array of arguments.
     *
     * @param object|string|null $controller The class or object to call the method on.
     * @param string|null $method The method to call.
     * @param array $arguments The arguments to pass to the method.
     * 
     * @return mixed The result of the method call.
     */
    public static function callArrayMethodOfClass(object|string|null $controller = null, ?string $method = null, array $arguments = []): mixed
    {
        $reflection = self::getMethod($controller, $method);

        if ($reflection->isStatic()) {
            return forward_static_call_array($method, $arguments);
        }

        return self::callMethodArray($method, $arguments);
    }

    /**
     * Get the constants of a class or interface.
     *
     * @param object|string $objectOrClass The class name or an object of the class.
     * @param int $filter A bitmask of the desired filter (default is public constants).
     * 
     * @return array An associative array of constant names and their values.
     */
    public static function getConstants(object|string $objectOrClass, int $filter = ReflectionProperty::IS_PUBLIC): array
    {
        $objectOrClass = new ReflectionClass($objectOrClass);

        return $objectOrClass->getConstants($filter);
    }

    /**
     * Get a ReflectionFunction or ReflectionMethod instance for the given callable or string function name.
     *
     * @param callable|string $function
     * 
     * @return ReflectionFunction|ReflectionMethod
     */
    public static function getReflectionFunction(callable|string $function): ReflectionFunction|ReflectionMethod
    {
        if (is_array($function)) {
            return self::createFromMethodName($function[0], $function[1]);
        }

        if ($function instanceof Closure) {
            return new ReflectionFunction($function);
        }

        if (is_object($function)) {
            return self::createFromMethodName($function, '__invoke');
        }

        return new ReflectionFunction($function);
    }

    /**
     * Get the visibility of a property in an object.
     *
     * @param object $object The object containing the property
     * @param string $propertyName The name of the property
     * 
     * @return ?string The visibility of the property ('public', 'protected', or 'private')
     */
    public static function getVisibility(object $object, string $propertyName): ?string
    {
        $reflectionProperty = self::getReflectionProperty($object, $propertyName);

        if ($reflectionProperty->isPrivate()) {
            return 'private';
        }

        if ($reflectionProperty->isPublic()) {
            return 'public';
        }

        if ($reflectionProperty->isProtected()) {
            return 'protected';
        }

        if ($reflectionProperty->isAbstract()) {
            return 'abstract';
        }

        return null;
    }

    /**
     * Check if the given object has a specific property.
     *
     * @param object $object The object to check for the property
     * @param string $propertyName The name of the property to check for
     * 
     * @return bool Returns true if the object has the specified property, false otherwise
     */
    public static function hasProperty(object $object, string $propertyName): bool
    {
        try {
            self::getReflectionProperty($object, $propertyName);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }

    /**
     * Retrieves the parameters of the given function.
     *
     * @param ReflectionFunction $function The reflection of the function to inspect.
     * 
     * @return array<ReflectionParameter> An array of ReflectionParameter objects representing the function's parameters.
     */
    public function getFunctionParameters(ReflectionFunction $function): array
    {
        return $function->getParameters();
    }

    /**
     * Get the ReflectionProperty object for the specified property of the given object.
     *
     * @param object $object The object to reflect on
     * @param string $propertyName The name of the property to retrieve
     * 
     * @return ReflectionProperty The ReflectionProperty object for the specified property
     * 
     * @throws Exception If the property does not exist in the object
     */
    private static function getReflectionProperty(object $object, string $propertyName): ReflectionProperty
    {
        $reflectionObject = new ReflectionObject($object);

        $reflectionProperty = null;

        if ($reflectionObject->hasProperty($propertyName)) {
            $reflectionProperty = $reflectionObject->getProperty($propertyName);
        } else {
            $parent = $reflectionObject->getParentClass();

            while ($reflectionProperty === null && $parent !== false) {
                if ($parent->hasProperty($propertyName)) {
                    $reflectionProperty = $parent->getProperty($propertyName);
                }

                $parent = $parent->getParentClass();
            }
        }

        if (!$reflectionProperty) {
            throw new Exception("Property $propertyName does not exist in " . get_class($object));
        }

        return $reflectionProperty;
    }

    /**
     * Export the type of a ReflectionType as a string.
     *
     * @param ReflectionType $type The ReflectionType to export.
     * @param bool $inUnion Whether the type is part of a union type.
     * 
     * @return string The exported type as a string.
     */
    public static function exportType(ReflectionType $type, bool $inUnion = false): string
    {
        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(function (ReflectionType $type) {
                return $this->exportType($type, true);
            }, $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            $result = implode('&', array_map(function (ReflectionType $type) {
                return $this->exportType($type);
            }, $type->getTypes()));

            return $inUnion ? "($result)" : $result;
        }

        if (!$type instanceof ReflectionNamedType) {
            throw new Exception('Unsupported ReflectionType class: ' . $type::class);
        }

        $result = '';

        if ($type->allowsNull() && $type->getName() !== 'mixed' && $type->getName() !== 'null') {
            $result .= '?';
        }

        if (!$type->isBuiltin() && $type->getName() !== 'self' && $type->getName() !== 'static') {
            $result .= '\\';
        }

        $result .= $type->getName();

        return $result;
    }

    /**
     * Get the class hierarchy for a given ReflectionClass.
     *
     * This method retrieves all parent classes of the given class recursively.
     *
     * @param ReflectionClass $class The ReflectionClass for which to retrieve the hierarchy.
     * 
     * @return array<ReflectionClass|bool> An array containing the class hierarchy in reverse order, starting from the given class.
     */
    public static function getClassHierarchy(ReflectionClass $class): array
    {
        $classes = [];

        while ($class) {
            $classes[] = $class;
            $class = $class->getParentClass();
        }

        return array_reverse($classes);
    }

    /**
     * Checks if the given value is a callable function or method.
     *
     * @param mixed $value The value to check if it is callable.
     * @param bool $syntax_only If set to true, only checks if the value is a valid callable syntax.
     * @param null|string &$callable_name A variable to store the name of the callable if found.
     * 
     * @return bool Returns true if the value is callable, false otherwise.
     */
    public static function isCallable(mixed $value = null, bool $syntax_only = false, null|string &$callable_name = null): bool
    {
        return is_callable($value, $syntax_only, $callable_name);
    }

    /**
     * Get the method names of a class or object.
     *
     * @param null|object|string $objectOrClass The object or class to get method names from.
     * 
     * @return array<string> An array of method names.
     */
    public static function getClassMethodNames(null|object|string $objectOrClass = null): array
    {
        return get_class_methods($objectOrClass);
    }

    /**
     * Parse an array of traces into an array of TraceObject instances.
     *
     * @param array $traces An array of traces to parse
     * 
     * @return array<TraceObject> An array of TraceObject instances
     */
    public static function parseTrace(array $traces = []): array
    {
        $traceObjects = [];

		foreach ($traces as $trace) {
			if (empty($trace)) {
				continue;
			}

			$traceObjects[] = new TraceObject($trace);
		}

        return $traceObjects;
    }

    /**
     * Get a new instance of the specified class with the provided arguments.
     *
     * @param string|null $class
     * @param array $arguments
     * @param array $allowClass
     * 
     * @return object|bool Returns a new instance of the class if successful, otherwise false.
     */
    public static function getNewInstance(?string $class = null, array $arguments = [], array $allowClass = []): object|bool
    {
        $requiredArguments = [];

        if (!self::isClassExists($class)) {
            return false;
        }

        if (!self::isInstantiable($class)) {
            return false;
        }

        if (self::hasConstructor($class)) {
            $requiredArguments = $arguments;
        }

        if ($allowClass != [] && !in_array($class, $allowClass)) {
            return false;
        }

        return new $class(...$requiredArguments);
    }

    /**
     * Check if a class exists in the current PHP environment.
     *
     * @param string $class The class name to check for existence.
     * @param bool $autoload Whether to autoload the class if not already loaded.
     * 
     * @return bool Returns true if the class exists, false otherwise.
     */
    public static function isClassExists(string $class, bool $autoload = true): bool
    {
        return class_exists($class, $autoload);
    }

    /**
     * Check if the class has a constructor method.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function hasConstructor(object|string $objectOrClass): bool
    {

        $reflectionObject = new ReflectionClass($objectOrClass);
        $constructor = $reflectionObject->getConstructor();

        return $constructor !== null && $constructor->isConstructor() && !$constructor->isDeprecated();
    }

    /**
     * Check if the given class is instantiable.
     *
     * @param string $class The class name to check
     * 
     * @return bool Returns true if the class is instantiable, false otherwise
     */
    public static function isInstantiable(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $reflectionObject = new ReflectionClass($class);

        return $reflectionObject->isInstantiable();
    }

    /**
     * Check if a given ReflectionParameter is an optional parameter.
     *
     * @param ReflectionParameter $parameter The parameter to check
     * 
     * @return bool True if the parameter is optional, false otherwise
     */
    public static function isOptionalParameter(ReflectionParameter $parameter): bool
    {
        return $parameter->isVariadic() || $parameter->isDefaultValueAvailable();
    }

    /**
     * Get the value of a specified property from an object or class using reflection.
     *
     * @param object|string|null $objectOrClass The object or class to retrieve the property value from.
     * @param string $propertyName The name of the property to retrieve the value from.
     * 
     * @return mixed The value of the property, or null if the property does not exist or cannot be accessed.
     */
    public static function getPropertyValue(object|string|null $objectOrClass, string $propertyName): mixed
    {
        try {
            $property = self::getProperty($objectOrClass, $propertyName);
            if (method_exists($property, 'setAccessible')) {
                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $property->setAccessible(true);
                }
            }

            return $property->getValue(is_object($objectOrClass) ? $objectOrClass : null);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * Set the value of a property for a given object or class.
     *
     * @param object|string|null $objectOrClass The object or class to set the property value for.
     * @param string $propertyName The name of the property to set.
     * @param mixed $value The value to set for the property.
     * 
     * @return bool Returns true if the property value was successfully set, false otherwise.
     */
    public static function setPropertyValue(object|string|null $objectOrClass, string $propertyName, $value): bool
    {
        try {
            $property = self::getProperty($objectOrClass, $propertyName);
            if (method_exists($property, 'setAccessible')) {
                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $property->setAccessible(true);
                }
            }

            $property->setValue(is_object($objectOrClass) ? $objectOrClass : null, $value);
        } catch (ReflectionException $e) {
            return false;
        }

        return true;
    }

    /**
     * Throws an error if any required parameter is empty.
     *
     * @param null|object|string $objectOrClass
     * @param string|null $method
     * @param array $defineValues
     * 
     * @return bool
     * 
     * @throws ArgumentEmptyException
     */
    public static function throwEmptyParameterError(null|object|string $objectOrClass = null, ?string $method = null, array $defineValues = []): bool
    {
        $class = self::getClass($objectOrClass);

        $method = $class->getMethod($method);

        $parameters = $method->getParameters();
        $countOfRequiredParameters = $method->getNumberOfRequiredParameters();
        if ($countOfRequiredParameters === 0) {
            return false;
        }

        $requiredParameters = [];
        foreach ($parameters as $parameter) {
            $isDefaultValueAvailable = $parameter->isDefaultValueAvailable();

            if ($isDefaultValueAvailable) {
                continue;
            }

            if ($parameter->allowsNull()) {
                continue;
            }

            if ($parameter->isOptional()) {
                continue;
            }

            $requiredParameters[] = $parameter;
        }

        $messages = [];
        foreach ($requiredParameters as $parameter) {
            $position = $parameter->getPosition() + 1;
            $name = $parameter->getName();
            $className = $class->getName();
            $methodName = $method->getName();

            $methodAccessModifier = self::getAccessModifierType($method);

            $arrow = "->";
            if ($method->isStatic()) {
                $arrow = "::";
            }

            if (!in_array($name, array_keys($defineValues))) {
                continue;
            }

            $messages[] = "[{$methodAccessModifier} {$className}{$arrow}{$methodName}] Argument #{$position} (\${$name}) cannot be empty";
        }

        throw new ArgumentEmptyException(join("\r\n", $messages));
    }

    /**
     * Returns the access modifier type of a class member.
     *
     * @param ReflectionMethod|ReflectionProperty $method
     * 
     * @return string
     */
    public static function getAccessModifierType(ReflectionMethod|ReflectionProperty $method): string
    {
        $type = "default";

        if ($method->isPublic()) {
            $type = "public";
        } else if ($method->isProtected()) {
            $type = "protected";
        } else if ($method->isPrivate()) {
            $type = "private";
        } else if ($method->isReadOnly()) {
            $type = "readonly";
        }

        return $type;
    }

    /**
     * Get the sensitive parameter attributes.
     *
     * @param ReflectionParameter $param
     * @param string|null $name = null
     * @param int $flags = 0
     * 
     * @return array<ReflectionAttribute>
     */
    public static function getSensitiveParameterAttributes(ReflectionParameter $param, string|null $name = null, int $flags = 0): array
    {
        return $param->getAttributes($name, $flags);
    }

    /**
     * Create a new instance of the class without calling the constructor.
     *
     * @param ReflectionClass $reflectionClass
     * 
     * @return object
     */
    public static function newInstanceWithoutConstructor(ReflectionClass $reflectionClass): object
    {
        return $reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * Returns the starting line number of the function declaration.
     *
     * @param ReflectionFunction $reflectionFunction
     * 
     * @return int
     */
    public static function getFunctionStartLine(ReflectionFunction $reflectionFunction): int
    {
        return $reflectionFunction->getStartLine();
    }

    /**
     * Get the return type of the function.
     *
     * @param ReflectionMethod $reflectionMethod
     * 
     * @return null|ReflectionType
     */
    public static function getReturnType(ReflectionMethod $reflectionMethod): null|ReflectionType
    {
        return $reflectionMethod->getReturnType();
    }

    /**
     * Get the class modifier string representation.
     *
     * @param ?ReflectionClass $class
     * 
     * @return string
     */
    public static function getClassModifierString(?ReflectionClass $class = null): string
    {
        $modifier = $class->getModifiers();

        return self::getMethodModifierStringFromConstaints($modifier);
    }

    /**
     * Get the default properties of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array
     */
    public static function getClassDefaultProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getDefaultProperties();
    }

    /**
     * Get the scope class name of a closure.
     *
     * @param Closure $closure
     * 
     * @return string|null
     */
    public static function getClosureScope(Closure $closure): ?string
    {
        $ref = new ReflectionFunction($closure);
        $cls = $ref->getClosureScopeClass();
        return $cls ? $cls->getName() : null;
    }

    /**
     * Get the source file of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return string
     */
    public static function getClassSourceFile(object|string $objectOrClass): string
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getFileName();
    }

    /**
     * Check if a parameter allows null values.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * @param string $paramName
     * 
     * @return bool
     */
    public static function isParameterNullable(object|string $objectOrClass, string $methodName, string $paramName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        // Greater than 8.0.0
        if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            foreach ($ref->getParameters() as $param) {
                if ($param->getName() === $paramName) {
                    return $param->allowsNull();
                }
            }

            throw new InvalidArgumentException("Parameter '{$paramName}' not found in {$objectOrClass}::{$methodName}()");
        }

        // @phpstan-ignore-next-line
        $p = $ref->getParameter($paramName);
        return $p->allowsNull();
    }

    /**
     * Get the method modifiers for a given class and method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return int
     */
    public static function getClassMethodModifiers(object|string $objectOrClass, string $methodName): int
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getModifiers();
    }

    /**
     * Get the method modifier string based on the visibility and static flag.
     *
     * @param ?ReflectionMethod $method = null
     * 
     * @return string
     */
    public static function getMethodModifierString(?ReflectionMethod $method = null): string
    {
        $modifier = $method->getModifiers();

        return self::getMethodModifierStringFromConstaints($modifier);
    }

    /**
     * Get the enum value by its name.
     *
     * @param string $name
     * 
     * @return mixed
     */
    public static function getEnumByName(string $enum, string $name): mixed
    {
        foreach ($enum::cases() as $case) {
            if ($case->name === $name) {
                return $case;
            }
        }

        return false;
    }

    /**
     * Filters the constraints by visibility.
     *
     * @param ReflectionClass $reflect
     * @param array $constants
     * @param string $visibility The visibility to filter by
     * 
     * @throws Exception
     * 
     * @return array The filtered constraints
     */
    public static function filterConstrainsByVisibility(ReflectionClass $reflect, array $constants, ?string $visibility = null): array
    {
        if (!$visibility) {
            throw new Exception('You must fill the visibility option parameter');
        }

        switch ($visibility) {
            case 'private':
                return array_filter($constants, function (string $name) use ($reflect): bool {
                    return $reflect->getReflectionConstant($name)->isPrivate();
                }, ARRAY_FILTER_USE_KEY);
            case 'protected':
                return array_filter($constants, function (string $name) use ($reflect): bool {
                    return $reflect->getReflectionConstant($name)->isProtected();
                }, ARRAY_FILTER_USE_KEY);
            case 'public':
                return array_filter($constants, function (string $name) use ($reflect): bool {
                    return $reflect->getReflectionConstant($name)->isPublic();
                }, ARRAY_FILTER_USE_KEY);
            case 'deprecated':
                return array_filter($constants, function (string $name) use ($reflect): bool {
                    return $reflect->getReflectionConstant($name)->isDeprecated();
                }, ARRAY_FILTER_USE_KEY);
            case 'enumcase':
                return array_filter($constants, function (string $name) use ($reflect): bool {
                    return $reflect->getReflectionConstant($name)->isEnumCase();
                }, ARRAY_FILTER_USE_KEY);
            default:
                throw new Exception('Unknown visibility option');
        }
    }

    /**
     * Get the string representation of the modifier flags for a ReflectionMethod.
     *
     * @param int $modifier The modifier flags for the ReflectionMethod.
     * 
     * @return string The string representation of the modifier flags.
     */
    public static function getMethodModifierStringFromConstaints(int $modifier): string
    {
        $modifierString = "";

        if ($modifier & ReflectionMethod::IS_FINAL) {
            $modifierString .= "final ";
        } else if ($modifier & ReflectionMethod::IS_PRIVATE) {
            $modifierString .= "private ";
        } else if ($modifier & ReflectionMethod::IS_PUBLIC) {
            $modifierString .= "public ";
        } else if ($modifier & ReflectionMethod::IS_ABSTRACT) {
            $modifierString .= "abstract ";
        } else if ($modifier & ReflectionMethod::IS_PROTECTED) {
            $modifierString .= "protected ";
        }

        if ($modifier & ReflectionMethod::IS_STATIC) {
            $modifierString .= "static";
        }

        return trim($modifierString);
    }

    /**
     * Get the function modifier string based on the modifier flags.
     *
     * @param int $modifier
     * 
     * @return string
     */
    public static function getFunctionModifierStringFromConstaints(int $modifier): string
    {
        $modifierString = "";

        if ($modifier & ReflectionFunction::IS_DEPRECATED) {
            $modifierString .= "deprecated";
        }

        return $modifierString;
    }

    /**
     * Get the object modifier string based on the modifier flags.
     *
     * @param int $modifier
     * 
     * @return string
     */
    public static function getEnumModifierStringFromConstaints(int $modifier): string
    {
        $modifierString = "";

        if ($modifier & ReflectionEnum::IS_FINAL) {
            $modifierString .= "final";
        } else if ($modifier & ReflectionEnum::IS_EXPLICIT_ABSTRACT) {
            $modifierString .= "explicit_abstract";
        } else if ($modifier & ReflectionEnum::IS_IMPLICIT_ABSTRACT) {
            $modifierString .= "implicit_abstract";
        } else if ($modifier & ReflectionEnum::IS_READONLY) {
            $modifierString .= "readonly";
        }

        return $modifierString;
    }

    /**
     * Get the object modifier string based on the modifier flags.
     *
     * @param int $modifier
     * 
     * @return string
     */
    public static function getObjectModifierStringFromConstaints(int $modifier): string
    {
        $modifierString = "";

        if ($modifier & ReflectionObject::IS_FINAL) {
            $modifierString .= "final";
        } else if ($modifier & ReflectionObject::IS_EXPLICIT_ABSTRACT) {
            $modifierString .= "explicit_abstract";
        } else if ($modifier & ReflectionObject::IS_IMPLICIT_ABSTRACT) {
            $modifierString .= "implicit_abstract";
        } else if ($modifier & ReflectionObject::IS_READONLY) {
            $modifierString .= "readonly";
        }

        return $modifierString;
    }

    /**
     * Get the class modifier string based on the modifier flags.
     *
     * @param int $modifier
     * 
     * @return string
     */
    public static function getClassModifierStringFromConstaints(int $modifier): string
    {
        $modifierString = "";

        if ($modifier & ReflectionClass::IS_FINAL) {
            $modifierString .= "final";
        } else if ($modifier & ReflectionClass::IS_EXPLICIT_ABSTRACT) {
            $modifierString .= "explicit_abstract";
        } else if ($modifier & ReflectionClass::IS_IMPLICIT_ABSTRACT) {
            $modifierString .= "implicit_abstract";
        } else if ($modifier & ReflectionClass::IS_READONLY) {
            $modifierString .= "readonly";
        }

        return $modifierString;
    }

    /**
     * Get the static properties of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array
     */
    public static function getClassStaticProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getStaticProperties();
    }

    /**
     * Get the attributes of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<ReflectionAttribute>
     */
    public static function getClassAttributes(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getAttributes();
    }

    /**
     * Get the return type of a method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return string|null
     */
    public static function getMethodReturnType(object|string $objectOrClass, string $methodName): ?string
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        // Greater than 8.0.0
        if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            $returnType = $ref->getReturnType();
            if ($returnType === null) {
                return null;
            }

            if ($returnType instanceof ReflectionNamedType) {
                return $ref->hasReturnType() ? $returnType?->getName() : null;
            }
        } else {
            // @phpstan-ignore-next-line
            return $ref->hasReturnType() ? $ref->getReturnType()?->getName() : null;
        }

        return null;
    }

    /**
     * Get the prototype of a method if it exists. (the method it overrides in a parent or interface)
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return ReflectionMethod|null
     */
    public static function getMethodPrototype(object|string $objectOrClass, string $methodName): ?ReflectionMethod
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        if (!$ref->hasPrototype()) {
            return null;
        }

        try {
            return $ref->getPrototype();
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * Check if a class is instantiable.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isClassInstantiable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isInstantiable();
    }

    /**
     * Get the doc comment of a closure function.
     *
     * @param closure $closure
     * 
     * @return string|null
     */
    public static function getFunctionDocComment(Closure $closure): ?string
    {
        $ref = new ReflectionFunction($closure);
        return $ref->getDocComment() ?: null;
    }

    /**
     * Check if a class is declared as an interface.
     * 
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isClassInterface(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isInterface();
    }

    /**
     * Check if a class is declared as abstract.
     * 
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isClassAbstract(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isAbstract();
    }

    /**
     * Get non-public default properties of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array
     */
    public static function getNonPublicDefaultProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $defaults = $ref->getDefaultProperties();
        $res = [];

        foreach ($ref->getProperties() as $p) {
            if (!$p->isPublic()) {
                $res[$p->getName()] = $defaults[$p->getName()] ?? null;
            }
        }

        return $res;
    }

    /**
     * Check if a method is declared as final.
     *
     * @param object|string $objectOrClass The object or class name to check.
     * @param string $methodName The name of the method to check.
     * 
     * @return bool Returns true if the method is final, false otherwise.
     */
    public static function isMethodFinal(object|string $objectOrClass, string $methodName): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        if ($ref->hasMethod($methodName)) {
            return $ref->getMethod($methodName)->isFinal();
        }

        return false;
    }

    /**
     * Get the version of a PHP extension.
     *
     * @param string $extensionName
     * 
     * @return string|null
     */
    public static function getExtensionVersion(string $extensionName): ?string
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getVersion();
    }

    /**
     * Get the ending line number of a class declaration
     * 
     * @param object|string $objectOrClass
     * 
     * @return int
     */
    public static function getClassEndLine(object|string $objectOrClass): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getEndLine();
    }

    /**
     * Get the starting line number of a class declaration
     * 
     * @param object|string $objectOrClass
     * 
     * @return int
     */
    public static function getClassStartLine(object|string $objectOrClass): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getStartLine();
    }

    /**
     * Check if a method returns a reference.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodReturnsReference(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->returnsReference();
    }

    /**
     * Get class constants.
     *
     * @param object|string $objectOrClass
     * @param string|null $name
     * 
     * @return mixed
     */
    public static function getClassConstants(object|string $objectOrClass, ?string $name = null): mixed
    {
        $ref = new ReflectionClass($objectOrClass);

        if ($name !== null && $ref->hasConstant($name)) {
            return $ref->getConstant($name);
        }

        return $ref->getConstants();
    }

    /**
     * Get the names of interfaces implemented by a class.
     * 
     * @param object|string $objectOrClass
     * 
     * @return array
     */
    public static function getImplementedInterfaceNames(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getInterfaceNames();
    }

    /**
     * Get the object bound to a closure.
     * 
     * @param closure $closure
     * 
     * @return object|null
     */
    public static function getClosureBoundObject(Closure $closure): ?object
    {
        $ref = new ReflectionFunction($closure);
        return $ref->getClosureThis();
    }

    /**
     * Get the extension name of the class.
     * 
     * @param object|string $objectOrClass
     * 
     * @return string|null
     */
    public static function getClassExtension(object|string $objectOrClass): string|null
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getExtension()?->getName();
    }

    /**
     * Get the class name of the parameter type.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return string|null
     */
    public static function getParameterClassName(ReflectionParameter $parameter): mixed
    {
        // Greater than 8.0.0
        if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            $instance = $parameter->getType();
        } else {
            try {
                // @phpstan-ignore-next-line
                $instance = $parameter->getClass();
            } catch (Exception $e) {
                return NULL;
            }
        }

        $name = self::isParameterClassType($parameter) ? $instance->getName() : NULL;

        if ($name !== NULL) {
            $lowerCaseName = strtolower($name);

            if (!is_null($class = $parameter->getDeclaringClass())) {
                switch ($lowerCaseName) {
                    case 'self':
                        return $class->getName();
                    case 'parent':
                        return ($parent = $class->getParentClass()) ? $parent->name : NULL;
                }
            }
        }

        return $name;
    }

    /**
     * Get the dependencies of a given class by analyzing its constructor parameters.
     *
     * @param object|string $objectOrClass The class name to analyze
     * 
     * @return array<string, Instance|mixed> An array containing the dependencies of the class
     */
    public static function getDependencies(object|string $objectOrClass): array
    {
        $reflection = new ReflectionClass($objectOrClass);

        $constructor = $reflection->getConstructor();
        if ($constructor === NULL) {
            return [];
        }

        $dependencies = [];
        foreach ($constructor->getParameters() as $parameter) {
            if (OperationSystem::comparePHPVersion('5.6.0', '>=') && $parameter->isVariadic()) {
                break;
            }

            $className = self::getParameterClassName($parameter);
            if ($className !== NULL) {
                $isNullable = self::isNullableParameter($parameter);
                $dependencies[$parameter->getName()] = new Instance($className, $isNullable);
            } else {
                $dependencies[$parameter->getName()] = $parameter->isDefaultValueAvailable() ? $parameter->getDefaultValue() : NULL;
            }
        }

        return $dependencies;
    }

    /**
     * Check if the parameter is a class type.
     *
     * This method determines if the given parameter is a class type based on the PHP version.
     *
     * @param ReflectionParameter $parameter The parameter to check
     * 
     * @return bool Returns true if the parameter is a class type, false otherwise
     */
    public static function isParameterClassType(ReflectionParameter $parameter): bool
    {
        $isClass = false;

        // Greater or Equal than 8.0.0
        if (OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            $type = $parameter->getType();
            if ($type instanceof ReflectionNamedType) {
                $isClass = !$type->isBuiltin();
            }
        } else {
            try {
                // @phpstan-ignore-next-line
                $class = $parameter->getClass();
            } catch (ReflectionException $e) {
                if (!self::isNullableParameter($parameter)) {
                    $name = NULL;

                    if (OperationSystem::comparePHPVersion('7.0.0', '>=')) {
                        $type = $parameter->getType();
                        if ($type instanceof ReflectionNamedType) {
                            $name = $type->getName();
                        }
                    }
                } else {
                    $class = NULL;
                }
            }

            $isClass = $class !== NULL;
        }

        return $isClass;
    }

    /**
     * Check if the given parameter is nullable.
     *
     * @param ReflectionParameter $param
     * 
     * @return bool
     */
    public static function isNullableParameter(ReflectionParameter $param): bool
    {
        return $param->isOptional() || (OperationSystem::comparePHPVersion('7.1.0', '>=') && $param->getType()->allowsNull());
    }

    /**
     * Get the inheritance chain of a given object or class.
     * 
     * @param object|string $objectOrClass
     * 
     * @return array<bool|string>
     */
    public static function getInheritanceChain(object|string $objectOrClass): array
    {
        $chain = [];
        $current = $objectOrClass;
        while ($parent = get_parent_class($current)) {
            $chain[] = $parent;
            $current = $parent;
        }

        return $chain;
    }

    /**
     * Get the required arguments based on the reflection and arguments provided.
     *
     * @param null|ReflectionFunction|ReflectionMethod $reflection
     * @param null|Container|array $arguments
     * 
     * @return array<int,object|Container>
     */
    public static function getRequiredArguments(null|ReflectionFunction|ReflectionMethod $reflection = null, null|Container|array $arguments = null): array
    {
        $dependencies = [];

        /** @var ReflectionParameter[] $parameters */
        $parameters = self::getReflectionParameters($reflection);

        foreach ($parameters as $parameter) {
            /** @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type */
            $type = $parameter->getType();

            if (!$type || !self::isNamedType($type)) {
                continue;
            }

            $name = $type->getName();

            if (!self::isClassExists($name) && !interface_exists($name)) {
                continue;
            }

            if ($arguments instanceof Container && $dependency = $arguments->getByType($name)) {
                $dependencies[] = $dependency;
                continue;
            }

            if (OperationSystem::comparePHPVersion('8.1.0', '<=')) {
                $reflectionClass = new ReflectionClass($name);
            } else if (OperationSystem::comparePHPVersion('8.0.0', '>') && method_exists($parameter, 'getClass')) {
                // @phpstan-ignore-next-line
                $reflectionClass = $parameter->getClass();
            }

            $instance = $reflectionClass->newInstance();
            if (!$reflectionClass->isInstance($instance)) {
                continue;
            }

            $dependencies[] = $instance;
        }

        return $dependencies;
    }

    /**
     * Create a ReflectionMethod instance from a method name.
     * 
     * @param object|string $objectOrClass
     * @param string $methodName
     * @param string $methodBodyPhp
     * 
     * @return string
     */
    public static function createOverridingSubclass(object|string $objectOrClass, string $methodName, string $methodBodyPhp): string
    {
        $reflectionClass = new ReflectionClass($objectOrClass);
        $short = str_replace('\\', '_', $objectOrClass) . '_Proxy_' . uniqid();
        $params = [];
        $paramNames = [];
        $methodRef = $reflectionClass->getMethod($methodName);

        foreach ($methodRef->getParameters() as $p) {
            $decl = '';

            if ($p->hasType()) {
                if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                    if ($p->hasType()) {
                        if ($type instanceof ReflectionNamedType) {
                            $decl .= ($p->getType()->allowsNull() ? '?' : '') . $type->getName() . ' ';
                        } elseif ($type instanceof ReflectionUnionType) {
                            $decl .= ($p->getType()->allowsNull() ? '?' : '') . implode('|', array_map(fn($t) => $t->getName(), $type->getTypes())) . ' ';
                        } elseif ($type instanceof ReflectionIntersectionType) {
                            $decl .= ($p->getType()->allowsNull() ? '?' : '') . implode('&', array_map(fn($t) => $t->getName(), $type->getTypes())) . ' ';
                        }
                    }
                } else {
                    // @phpstan-ignore-next-line
                    $decl .= ($p->getType()->allowsNull() ? '?' : '') . $p->getType()->getName() . ' ';
                }
            }

            if ($p->isPassedByReference()) {
                $decl .= '&';
            }

            $decl .= '$' . $p->getName();
            if ($p->isDefaultValueAvailable()) {
                $decl .= ' = ' . var_export($p->getDefaultValue(), true);
            }

            $params[] = $decl;
            $paramNames[] = '$' . $p->getName();
        }

        $paramsStr = implode(', ', $params);
        $paramNamesStr = implode(', ', $paramNames);

        $code = sprintf('
        class %s extends %s {
            public function %s(%s) {
                %s
            }
        }
    ', $short, '\\' . ltrim($objectOrClass, '\\'), $methodName, $paramsStr, $methodBodyPhp);

        eval ($code);
        return $short;
    }

    /**
     * Replace a function's implementation using the runkit extension.
     * 
     * @param string $functionName
     * @param string $newCode
     * 
     * @throws RuntimeException
     * @return bool
     */
    public static function replaceFunctionWithRunkit(string $functionName, string $newCode): bool
    {
        if (!extension_loaded('runkit')) {
            throw new RuntimeException("runkit extension required");
        }

        if (!function_exists($functionName)) {
            throw new RuntimeException("Function $functionName does not exist for replacement");
        }

        $ref = new ReflectionFunction($functionName);
        $params = [];
        foreach ($ref->getParameters() as $p) {
            $type = $p->getType();

            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                if ($p->hasType()) {
                    if ($type instanceof ReflectionNamedType) {
                        $params[] = $type->getName() . ' $' . $p->getName();
                    } elseif ($type instanceof ReflectionUnionType) {
                        $params[] = implode('|', array_map(fn($t) => $t->getName(), $type->getTypes())) . ' $' . $p->getName();
                    } elseif ($type instanceof ReflectionIntersectionType) {
                        $params[] = implode('&', array_map(fn($t) => $t->getName(), $type->getTypes())) . ' $' . $p->getName();
                    }
                } else {
                    $params[] = '$' . $p->getName();
                }
            } else {
                // @phpstan-ignore-next-line
                $params[] = ($p->hasType() ? $p->getType()->getName() . ' ' : '') . '$' . $p->getName();
            }
        }
        $argsSignature = implode(', ', $params);

        if (function_exists('runkit_function_redefine')) {
            runkit_function_redefine($functionName, $argsSignature, $newCode);
        }

        return true;
    }

    /**
     * Invoke a method on an object with before and after interceptors.
     * 
     * @param object|string $objectOrClass
     * @param string $method
     * @param array $args
     * @param callable $before
     * @param callable $after
     * 
     * @return mixed
     */
    public static function invokeWithInterceptor(object|string $objectOrClass, string $method, array $args, callable $before, callable $after): mixed
    {
        $reflection = self::createFromMethodName($objectOrClass, $method);
        if (method_exists($reflection, 'setAccessible')) {
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $reflection->setAccessible(true);
            }
        }

        if (is_callable($before)) {
            $before($method, $args);
        }

        $result = $reflection->invokeArgs($objectOrClass, $args);

        if (is_callable($after)) {
            $after($method, $result);
        }

        return $result;
    }

    /**
     * Create a proxy object for the specified class with method interception.
     * 
     * @param object|string $objectOrClass
     * @param callable $interceptor
     * 
     * @return object
     */
    public static function createProxy(object|string $objectOrClass, callable $interceptor): object
    {
        $reflectionClass = new ReflectionClass($objectOrClass);

        return new class ($reflectionClass, $interceptor) {
            private ReflectionClass $ref;
            private $target;
            private $interceptor;

            public function __construct(ReflectionClass $reflectionClass, $interceptor)
            {
                $this->ref = $reflectionClass;
                $this->target = $reflectionClass->newInstanceWithoutConstructor();
                $this->interceptor = $interceptor;
            }

            public function __call(string $method, array $args): mixed
            {
                $reflectionMethod = $this->ref->getMethod($method);
                if (method_exists($reflectionMethod, 'setAccessible')) {
                    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                        // @phpstan-ignore-next-line
                        $reflectionMethod->setAccessible(true);
                    }
                }

                if (method_exists($this->interceptor, $method)) {
                    ($this->interceptor)($method, $args);
                }
                return $reflectionMethod->invokeArgs($this->target, $args);
            }
        };
    }

    /**
     * Create a lazy loading proxy for the specified class.
     * 
     * @param object|string $objectOrClass
     * @param closure $closure
     * @param int $options
     * 
     * @throws Exception
     * 
     * @return object
     */
    public static function createLazyProxy(object|string $objectOrClass, Closure $closure, int $options = 0): object
    {
        if (PHP_VERSION_ID < 80400) {
            throw new Exception('Lazy loading proxies require PHP 8.4 or higher.');
        }

        $reflector = new ReflectionClass($objectOrClass);

        return $reflector->newLazyProxy($closure, $options);
    }

    /**
     * Get the inner variables of a class (non-static properties) as an associative array.
     *
     * @param string|object $objectOrClass The class name or object to retrieve inner variables from.
     * 
     * @return array<string,ReflectionProperty> An associative array where keys are the inner variable names and values are ReflectionProperty objects.
     */
    public static function getClassInnerVariables(string|object $objectOrClass): array
    {
        try {
            $reflection = new ReflectionClass($objectOrClass);
        } catch (ReflectionException $e) {
            return [];
        }

        $properties = $reflection->getProperties();
        $innerVariables = [];

        /** @var ReflectionProperty $property */
        foreach ($properties as $property) {
            if (!$property->isStatic()) {
                $innerVariables[$property->getName()] = $property;
            }
        }

        return $innerVariables;
    }

    /**
     * Invoke a method on an object or a static method on a class with the given parameters.
     *
     * @param  object|string|null    $objectOrClass
     * @param  null|callable|string  $method
     * @param  ArrayObject|array     $passParameters
     * @param  null|Container|array  $arguments
     * 
     * @return mixed
     */
    public static function invoke(object|string|null $objectOrClass = null, null|callable|string $method = null, ArrayObject|array $passParameters = [], null|Container|array $arguments = []): mixed
    {
        $reflection = $method == null ? self::getFunction($objectOrClass) : self::getMethod($objectOrClass, $method);
        $dependencies = self::getRequiredArguments($reflection, $arguments);
        $newInstance = $objectOrClass;

        if (!is_object($objectOrClass) && is_string($objectOrClass)) {
            $newInstance = self::getClassReflection($objectOrClass);
        }

        if (isset($arguments) && !empty($arguments)) {
            $objectOrClass = new ReflectionClass($newInstance ?? $objectOrClass);
            $newInstance = $objectOrClass->newInstance($arguments);

            if (!$objectOrClass->isInstance($newInstance)) {
                return false;
            }

            $dependencies = [...$dependencies, ...array_filter(($passParameters instanceof ArrayObject) ? $passParameters->getRawData() : $passParameters)];
        }

        if ($newInstance === null) {
            throw new RuntimeException('Instance object is not initialized');
        }

        Injector::inject($newInstance, $newInstance, true);

        return $reflection->invoke($newInstance, ...$dependencies);
    }

    /**
     * Get metadata information for the properties of a given class.
     *
     * @param object|string $objectOrClass The name of the class to retrieve property metadata from.
     * @param int|null $filter
     * 
     * @throws ReflectionException
     * 
     * @return ArrayObject<int, array<string, mixed>> An ArrayObject containing metadata information for each property of the class.
     * 
     * Each entry contains:
     * 
     *          - name       : string
     *          - type       : string
     *          - nullable   : bool|null
     *          - visibility : string
     *          - default    : string|null
     */
    public static function getClassPropertyMetadata(object|string $objectOrClass, int|null $filter = null): ArrayObject
    {
        $result = [];

        $reflection = new ReflectionClass($objectOrClass);
        $properties = $reflection->getProperties($filter);

        /**
         * @var ReflectionProperty[] $properties
         */
        foreach ($properties as $property) {
            /**
             * @var ReflectionNamedType|null $type
             */
            $type = $property->getType();

            $result[] = [
                'name' => $property->getName(),
                'type' => $type ? $type->getName() : null,
                'nullable' => $type ? $type->allowsNull() : null,
                'visibility' => $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private'),
                'default' => $property->isDefault() ? $property->getDeclaringClass()->getDefaultProperties()[$property->getName()] ?? null : null,
            ];
        }

        return new ArrayObject($result);
    }

    /**
     * Create a namespaced name for a class or interface.
     *
     * @param string $name
     * 
     * @return string
     */
    public function createNamespacedName(string $name): string
    {
        $isExists = class_exists($name) || interface_exists($name);
        if ($isExists && !str_starts_with($name, '\\')) {
            return sprintf("\\%s", $name);
        }

        return $name;
    }

    /**
     * Get the path definition of a class or object.
     * 
     * @param object $classOrObject
     * @return string|null
     */
    protected function getPathDefinition(object $classOrObject): string|null
    {
        $reflector = new ReflectionClass($classOrObject);
        $filename = $reflector->getFileName();
        if (!$filename) {
            return null;
        }

        return dirname($filename);
    }

    /**
     * Get the error type message based on the error type constant.
     *
     * @param int $type The error type constant.
     * 
     * @return string The corresponding error type message.
     */
    public static function getErrorTypeMessage(int $type): string
    {
        return match ($type) {
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_DEPRECATED => 'Deprecated',
            E_NOTICE => 'Notice',
            E_PARSE => 'Compile-time parse error',
            E_RECOVERABLE_ERROR => 'Recoverable Error',
            E_USER_DEPRECATED => 'User-generated deprecated',
            E_USER_ERROR => 'User-generated error',
            E_USER_NOTICE => 'User-generated notice',
            E_USER_WARNING => 'User-generated warning',
            E_WARNING => 'Runtime Warning',
            E_ERROR => 'Fatal runtime error',
        };
    }

    /**
     * Get all traits used by a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<string, ReflectionClass>
     */
    public static function getClassTraits(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getTraits();
    }

    /**
     * Get the names of all traits used by a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<string>
     */
    public static function getClassTraitNames(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getTraitNames();
    }

    /**
     * Get the trait aliases defined in a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<string, string>
     */
    public static function getClassTraitAliases(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getTraitAliases();
    }

    /**
     * Check if a class is an enum type.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isEnum(object|string $objectOrClass): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);
        return $ref->isEnum();
    }

    /**
     * Get all cases of an enum.
     *
     * @param string $enumClass
     * 
     * @return array<ReflectionEnumUnitCase> Enum cases
     * 
     * @throws Exception
     */
    public static function getEnumCases(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        return $ref->getCases();
    }

    /**
     * Get the backing type of a backed enum.
     *
     * @param string $enumClass
     * 
     * @return ReflectionNamedType|null
     * 
     * @throws Exception
     */
    public static function getEnumBackingType(string $enumClass): ?ReflectionNamedType
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);

        if (!$ref->isBacked()) {
            return null;
        }

        return $ref->getBackingType();
    }

    /**
     * Check if a class is declared as readonly (PHP 8.2+).
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isReadonlyClass(object|string $objectOrClass): bool
    {
        if (!OperationSystem::comparePHPVersion('8.2.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);
        return $ref->isReadOnly();
    }

    /**
     * Check if a property is declared as readonly.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return bool
     */
    public static function isReadonlyProperty(object|string $objectOrClass, string $propertyName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isReadOnly();
    }

    /**
     * Get the starting line number of a method declaration.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return int|false
     */
    public static function getMethodStartLine(object|string $objectOrClass, string $methodName): int|false
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getStartLine();
    }

    /**
     * Get the ending line number of a method declaration.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return int|false
     */
    public static function getMethodEndLine(object|string $objectOrClass, string $methodName): int|false
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getEndLine();
    }

    /**
     * Get the file name where a method is declared.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return string|false
     */
    public static function getMethodFileName(object|string $objectOrClass, string $methodName): string|false
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getFileName();
    }

    /**
     * Check if a method is declared as abstract.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodAbstract(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isAbstract();
    }

    /**
     * Check if a method is declared as static.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodStatic(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isStatic();
    }

    /**
     * Check if a method is declared as public.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodPublic(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isPublic();
    }

    /**
     * Check if a method is declared as private.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodPrivate(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isPrivate();
    }

    /**
     * Check if a method is declared as protected.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodProtected(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isProtected();
    }

    /**
     * Check if a class is defined internally by a PHP extension.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isInternalClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isInternal();
    }

    /**
     * Check if a class is user-defined (not built-in).
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isUserDefinedClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isUserDefined();
    }

    /**
     * Check if a class is declared as final.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isFinalClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isFinal();
    }

    /**
     * Check if a class is an anonymous class.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isAnonymousClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isAnonymous();
    }

    /**
     * Check if a class has a specific method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function hasMethod(object|string $objectOrClass, string $methodName): bool
    {
        return (new ReflectionClass($objectOrClass))->hasMethod($methodName);
    }

    /**
     * Check if a class has a specific constant.
     *
     * @param object|string $objectOrClass
     * @param string $constantName
     * 
     * @return bool
     */
    public static function hasConstant(object|string $objectOrClass, string $constantName): bool
    {
        return (new ReflectionClass($objectOrClass))->hasConstant($constantName);
    }

    /**
     * Get the constructor parameters of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<ReflectionParameter>
     */
    public static function getConstructorParameters(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return [];
        }

        return $constructor->getParameters();
    }

    /**
     * Check if a property is declared as public.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return bool
     */
    public static function isPropertyPublic(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isPublic();
    }

    /**
     * Check if a property is declared as private.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return bool
     */
    public static function isPropertyPrivate(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isPrivate();
    }

    /**
     * Check if a property is declared as protected.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return bool
     */
    public static function isPropertyProtected(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isProtected();
    }

    /**
     * Get the type name of a property.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return string|null
     */
    public static function getPropertyTypeName(object|string $objectOrClass, string $propertyName): ?string
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return null;
        }

        $type = $ref->getProperty($propertyName)->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    /**
     * Get the default value of a property.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return mixed
     */
    public static function getPropertyDefaultValue(object|string $objectOrClass, string $propertyName): mixed
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return null;
        }

        $property = $ref->getProperty($propertyName);

        if (!$property->hasDefaultValue()) {
            return null;
        }

        return $property->getDefaultValue();
    }

    /**
     * Get the doc comment of a method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return string|false
     */
    public static function getMethodDocComment(object|string $objectOrClass, string $methodName): string|false
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getDocComment();
    }

    /**
     * Get the doc comment of a property.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return string|false
     */
    public static function getPropertyDocComment(object|string $objectOrClass, string $propertyName): string|false
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->getDocComment();
    }

    /**
     * Check if a method is a generator function.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodGenerator(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isGenerator();
    }

    /**
     * Check if a method has variadic parameters.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodVariadic(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isVariadic();
    }

    /**
     * Get a closure from a method bound to a specific object.
     *
     * @param object $object
     * @param string $methodName
     * 
     * @return Closure
     */
    public static function getMethodClosure(object $object, string $methodName): Closure
    {
        $ref = self::createFromMethodName($object, $methodName);
        if (method_exists($ref, 'setAccessible')) {
            if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $ref->setAccessible(true);
            }
        }

        return $ref->getClosure($object);
    }

    /**
     * Invoke a static method with the given arguments.
     *
     * @param string $className
     * @param string $methodName
     * @param array $args
     * 
     * @return mixed
     */
    public static function invokeStaticMethod(string $className, string $methodName, array $args = []): mixed
    {
        $ref = self::createFromMethodName($className, $methodName);

        if (!$ref->isStatic()) {
            throw new Exception("Method {$className}::{$methodName}() is not static");
        }

        return $ref->invokeArgs(null, $args);
    }

    /**
     * Get all parent class names in the inheritance chain.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<string>
     */
    public static function getClassParentNames(object|string $objectOrClass): array
    {
        $parents = class_parents($objectOrClass);
        return $parents !== false ? array_values(array_map(fn($c) => $c, array_keys($parents))) : [];
    }

    /**
     * Get all method names declared in an interface.
     *
     * @param string $interfaceName
     * 
     * @return array<string>
     * 
     * @throws Exception
     */
    public static function getInterfaceMethodNames(string $interfaceName): array
    {
        if (!interface_exists($interfaceName)) {
            throw new Exception("Interface {$interfaceName} does not exist");
        }

        $ref = new ReflectionClass($interfaceName);
        return array_map(fn(ReflectionMethod $m) => $m->getName(), $ref->getMethods());
    }

    /**
     * Check if a parameter is passed by reference.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return bool
     */
    public static function isParameterPassedByReference(ReflectionParameter $parameter): bool
    {
        return $parameter->isPassedByReference();
    }

    /**
     * Get the default value of a parameter.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return mixed
     */
    public static function getParameterDefaultValue(ReflectionParameter $parameter): mixed
    {
        if (!$parameter->isDefaultValueAvailable()) {
            return null;
        }

        return $parameter->getDefaultValue();
    }

    /**
     * Get the type name of a parameter.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return string|null
     */
    public static function getParameterTypeName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    /**
     * Get the position index of a parameter.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return int
     */
    public static function getParameterPosition(ReflectionParameter $parameter): int
    {
        return $parameter->getPosition();
    }

    /**
     * Get a specific constant value from a class.
     *
     * @param object|string $objectOrClass
     * @param string $constantName
     * 
     * @return mixed
     */
    public static function getClassConstantValue(object|string $objectOrClass, string $constantName): mixed
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasConstant($constantName)) {
            return null;
        }

        return $ref->getConstant($constantName);
    }

    /**
     * Get a ReflectionClassConstant for a specific constant in a class.
     *
     * @param object|string $objectOrClass
     * @param string $constantName
     * 
     * @return ReflectionClassConstant|false
     */
    public static function getReflectionConstant(object|string $objectOrClass, string $constantName): ReflectionClassConstant|false
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->getReflectionConstant($constantName);
    }

    /**
     * Check if a class constant is declared as public.
     *
     * @param object|string $objectOrClass
     * @param string $constantName
     * 
     * @return bool
     */
    public static function isConstantPublic(object|string $objectOrClass, string $constantName): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false) {
            return false;
        }

        return $constant->isPublic();
    }

    /**
     * Get functions defined by a PHP extension.
     *
     * @param string $extensionName
     * 
     * @return array<string, ReflectionFunction>
     */
    public static function getExtensionFunctions(string $extensionName): array
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getFunctions();
    }

    /**
     * Get class names defined by a PHP extension.
     *
     * @param string $extensionName
     * 
     * @return array<string>
     */
    public static function getExtensionClassNames(string $extensionName): array
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getClassNames();
    }

    /**
     * Check if a closure is declared as static.
     *
     * @param Closure $closure
     * 
     * @return bool
     */
    public static function isClosureStatic(Closure $closure): bool
    {
        $ref = new ReflectionFunction($closure);
        return $ref->isStatic();
    }

    /**
     * Get parameters of a closure.
     *
     * @param Closure $closure
     * 
     * @return array<ReflectionParameter>
     */
    public static function getClosureParameters(Closure $closure): array
    {
        $ref = new ReflectionFunction($closure);
        return $ref->getParameters();
    }

    /**
     * Get the return type name of a function or closure.
     *
     * @param Closure|string $function
     * 
     * @return string|null
     */
    public static function getFunctionReturnTypeName(Closure|string $function): ?string
    {
        $ref = new ReflectionFunction($function);
        $type = $ref->getReturnType();

        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    /**
     * Check if a method is the constructor.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodConstructor(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isConstructor();
    }

    /**
     * Check if a method is the destructor.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function isMethodDestructor(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isDestructor();
    }

    /**
     * Get the unique object identifier (hash).
     *
     * @param object $object
     * 
     * @return string
     */
    public static function getObjectHash(object $object): string
    {
        return spl_object_hash($object);
    }

    /**
     * Get the integer object ID.
     *
     * @param object $object
     * 
     * @return int
     */
    public static function getObjectId(object $object): int
    {
        return spl_object_id($object);
    }

    /**
     * Get the fully qualified class name of an object.
     *
     * @param object $object
     * 
     * @return string
     */
    public static function getFullyQualifiedClassName(object $object): string
    {
        return $object::class;
    }

    /**
     * Get the INI entries defined by a PHP extension.
     *
     * @param string $extensionName
     * 
     * @return array
     */
    public static function getExtensionIniEntries(string $extensionName): array
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getINIEntries();
    }

    /**
     * Get the dependencies of a PHP extension.
     *
     * @param string $extensionName
     * 
     * @return array
     */
    public static function getExtensionDependencies(string $extensionName): array
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getDependencies();
    }

    /**
     * Get the executing line number of a generator.
     *
     * @param Generator $generator
     * 
     * @return int
     */
    public static function getGeneratorExecutingLine(Generator $generator): int
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getExecutingLine();
    }

    /**
     * Get the function reflection of a generator.
     *
     * @param Generator $generator
     * 
     * @return ReflectionFunctionAbstract
     */
    public static function getGeneratorFunction(Generator $generator): ReflectionFunctionAbstract
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getFunction();
    }

    /**
     * Get the $this object of a generator if bound.
     *
     * @param Generator $generator
     * 
     * @return object|null
     */
    public static function getGeneratorThis(Generator $generator): ?object
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getThis();
    }

    /**
     * Check if a method has a specific attribute.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * @param string $attributeName
     * 
     * @return bool
     */
    public static function hasMethodAttribute(object|string $objectOrClass, string $methodName, string $attributeName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $attributes = $ref->getAttributes($attributeName);
        return count($attributes) > 0;
    }

    /**
     * Get all attributes of a method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * @param string|null $attributeName
     * 
     * @return array<ReflectionAttribute>
     */
    public static function getMethodAttributes(object|string $objectOrClass, string $methodName, ?string $attributeName = null): array
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getAttributes($attributeName);
    }

    /**
     * Check if a property has a specific attribute.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * @param string $attributeName
     * 
     * @return bool
     */
    public static function hasPropertyAttribute(object|string $objectOrClass, string $propertyName, string $attributeName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $attributes = $ref->getProperty($propertyName)->getAttributes($attributeName);
        return count($attributes) > 0;
    }

    /**
     * Get all attributes of a property.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * @param string|null $attributeName
     * 
     * @return array<ReflectionAttribute>
     */
    public static function getPropertyAttributes(object|string $objectOrClass, string $propertyName, ?string $attributeName = null): array
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return [];
        }

        return $ref->getProperty($propertyName)->getAttributes($attributeName);
    }

    /**
     * Get all abstract methods of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<ReflectionMethod>
     */
    public static function getAbstractMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $method) => $method->isAbstract()
        );
    }

    /**
     * Get all static methods of a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return array<ReflectionMethod>
     */
    public static function getStaticMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $method) => $method->isStatic()
        );
    }

    /**
     * Check if a class is a trait.
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isTrait(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isTrait();
    }

    /**
     * Check if a class is iterable (implements Traversable).
     *
     * @param object|string $objectOrClass
     * 
     * @return bool
     */
    public static function isIterableClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isIterable();
    }

    /**
     * Get the number of public methods in a class.
     *
     * @param object|string $objectOrClass
     * 
     * @return int
     */
    public static function getPublicMethodCount(object|string $objectOrClass): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return count($ref->getMethods(ReflectionMethod::IS_PUBLIC));
    }

    /**
     * Get the number of properties in a class with optional filter.
     *
     * @param object|string $objectOrClass
     * @param int|null $filter
     * 
     * @return int
     */
    public static function getPropertyCount(object|string $objectOrClass, ?int $filter = null): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return count($filter !== null ? $ref->getProperties($filter) : $ref->getProperties());
    }

    /**
     * Get all ReflectionClassConstant objects of a class.
     *
     * @param object|string $objectOrClass
     * @param int|null $filter
     * 
     * @return array<ReflectionClassConstant>
     */
    public static function getReflectionConstants(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);

        if ($filter !== null && OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return $ref->getReflectionConstants($filter);
        }

        return $ref->getReflectionConstants();
    }

    /**
     * Get the static variable values of a closure or function.
     *
     * @param Closure|string $function
     * 
     * @return array
     */
    public static function getFunctionStaticVariables(Closure|string $function): array
    {
        $ref = new ReflectionFunction($function);
        return $ref->getStaticVariables();
    }

    /**
     * Get the static variable values of a method.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return array
     */
    public static function getMethodStaticVariables(object|string $objectOrClass, string $methodName): array
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->getStaticVariables();
    }

    /**
     * Get the closed-over variables (use clause) of a closure.
     *
     * @param Closure $closure
     * 
     * @return array
     */
    public static function getClosureUsedVariables(Closure $closure): array
    {
        $ref = new ReflectionFunction($closure);
        return $ref->getClosureUsedVariables();
    }

    /**
     * Check if a method has a return type declaration.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return bool
     */
    public static function hasMethodReturnType(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->hasReturnType();
    }

    /**
     * Check if a property has a type declaration.
     *
     * @param object|string $objectOrClass
     * @param string $propertyName
     * 
     * @return bool
     */
    public static function hasPropertyType(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->hasType();
    }

    /**
     * Create a new instance of a class with the given arguments.
     *
     * @param object|string $objectOrClass
     * @param mixed ...$args
     * 
     * @return object
     */
    public static function newInstanceArgs(object|string $objectOrClass, array $args = []): object
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->newInstanceArgs($args);
    }

    /**
     * Get the type of a ReflectionParameter as a full string representation including union/intersection types.
     *
     * @param ReflectionParameter $parameter
     * 
     * @return string|null
     */
    public static function getParameterTypeString(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        if ($type instanceof ReflectionType) {
            return ($type->allowsNull() ? 'null' : '') . $type->__tostring();
        }

        return null;
    }

    /**
     * Get the return type as a full string representation including union/intersection types.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return string|null
     */
    public static function getMethodReturnTypeString(object|string $objectOrClass, string $methodName): ?string
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $type = $ref->getReturnType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        return null;
    }

    /**
     * Get a method signature string representation.
     *
     * @param object|string $objectOrClass
     * @param string $methodName
     * 
     * @return string
     */
    public static function getMethodSignature(object|string $objectOrClass, string $methodName): string
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $modifiers = implode(' ', Reflection::getModifierNames($ref->getModifiers()));
        $returnType = $ref->hasReturnType() ? ': ' . $ref->getReturnType() : '';

        $params = [];
        foreach ($ref->getParameters() as $param) {
            $paramStr = '';

            if ($param->hasType()) {
                $paramStr .= $param->getType() . ' ';
            }

            if ($param->isVariadic()) {
                $paramStr .= '...';
            }

            $paramStr .= '$' . $param->getName();

            if ($param->isOptional() && $param->isDefaultValueAvailable()) {
                $paramStr .= ' = ' . var_export($param->getDefaultValue(), true);
            }

            $params[] = $paramStr;
        }

        return sprintf('%s function %s(%s)%s', $modifiers, $ref->getName(), implode(', ', $params), $returnType);
    }

    /**
     * Check if a class uses a specific trait.
     *
     * @param object|string $objectOrClass
     * @param string $traitName
     * 
     * @return bool
     */
    public static function classUsesTrait(object|string $objectOrClass, string $traitName): bool
    {
        $traits = class_uses($objectOrClass);
        return $traits !== false && in_array($traitName, $traits);
    }

    /**
     * Check if a class implements a specific interface by name.
     *
     * @param object|string $objectOrClass
     * @param string $interfaceName
     * 
     * @return bool
     */
    public static function classImplementsInterface(object|string $objectOrClass, string $interfaceName): bool
    {
        return (new ReflectionClass($objectOrClass))->implementsInterface($interfaceName);
    }

    // ========================================================================
    // Fiber Reflection (PHP 8.1+)
    // ========================================================================

    /**
     * Get the ReflectionFiber instance for a running Fiber.
     *
     * @param \Fiber $fiber The Fiber instance to reflect.
     * 
     * @return ReflectionFiber The reflection of the given Fiber.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberReflection(\Fiber $fiber): ReflectionFiber
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        return new ReflectionFiber($fiber);
    }

    /**
     * Get the callable that was used to create the Fiber.
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     * 
     * @return callable The Fiber's callable.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberCallable(\Fiber $fiber): callable
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        $rf = new ReflectionFiber($fiber);
        return $rf->getCallable();
    }

    /**
     * Get the file where a Fiber is currently executing.
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     * 
     * @return string The file path of the executing Fiber.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberExecutingFile(\Fiber $fiber): string
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        $rf = new ReflectionFiber($fiber);
        return $rf->getExecutingFile();
    }

    /**
     * Get the line number where a Fiber is currently executing.
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     * 
     * @return int The executing line number.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberExecutingLine(\Fiber $fiber): int
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        $rf = new ReflectionFiber($fiber);
        return $rf->getExecutingLine();
    }

    /**
     * Get the backtrace of a suspended or running Fiber.
     *
     * @param \Fiber $fiber   The Fiber instance to inspect.
     * @param int    $options debug_backtrace options (default DEBUG_BACKTRACE_PROVIDE_OBJECT).
     * 
     * @return array The Fiber's backtrace.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberTrace(\Fiber $fiber, int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        $rf = new ReflectionFiber($fiber);
        return $rf->getTrace($options);
    }

    /**
     * Check if an enum is a backed enum (has string or int backing).
     *
     * @param string $enumClass The fully qualified enum class name.
     * 
     * @return bool True if the enum is backed, false otherwise.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function isBackedEnum(string $enumClass): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        return $ref->isBacked();
    }

    /**
     * Get all case names of an enum as a flat string array.
     *
     * @param string $enumClass The fully qualified enum class name.
     * 
     * @return array<string> Array of case name strings.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumCaseNames(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        return array_map(
            fn(ReflectionEnumUnitCase $case) => $case->getName(),
            $ref->getCases()
        );
    }

    /**
     * Get an associative array of enum case name => value for backed enums.
     *
     * @param string $enumClass The fully qualified backed enum class name.
     * 
     * @return array<string, int|string> Case name => backing value.
     * 
     * @throws Exception If PHP version is below 8.1 or enum is not backed.
     */
    public static function getEnumValues(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        if (!$ref->isBacked()) {
            throw new Exception("Enum {$enumClass} is not a backed enum.");
        }

        $values = [];
        foreach ($ref->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase) {
                $values[$case->getName()] = $case->getBackingValue();
            }
        }

        return $values;
    }

    /**
     * Check if a specific case name exists in an enum.
     *
     * @param string $enumClass The fully qualified enum class name.
     * @param string $caseName  The case name to search for.
     * 
     * @return bool True if the case exists.
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function hasEnumCase(string $enumClass, string $caseName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        return $ref->hasCase($caseName);
    }

    /**
     * Get a specific enum case reflection by name.
     *
     * @param string $enumClass The fully qualified enum class name.
     * @param string $caseName  The case name to retrieve.
     * 
     * @return ReflectionEnumUnitCase|ReflectionEnumBackedCase
     * 
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumCase(string $enumClass, string $caseName): ReflectionEnumUnitCase|ReflectionEnumBackedCase
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);
        return $ref->getCase($caseName);
    }

    /**
     * Get the full type string of a property, including union/intersection types.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The property name.
     * 
     * @return string|null The type string or null if no type is declared.
     */
    public static function getPropertyTypeString(object|string $objectOrClass, string $propertyName): ?string
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return null;
        }

        $type = $ref->getProperty($propertyName)->getType();

        if ($type === null) {
            return null;
        }

        if ($type instanceof ReflectionNamedType) {
            return ($type->allowsNull() && $type->getName() !== 'mixed' ? '?' : '') . $type->getName();
        }

        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(fn(ReflectionNamedType $t) => $t->getName(), $type->getTypes()));
        }

        return null;
    }

    /**
     * Check if a property is promoted (declared in a constructor parameter).
     * Requires PHP 8.0+.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The property name.
     * 
     * @return bool True if the property is promoted.
     */
    public static function isPropertyPromoted(object|string $objectOrClass, string $propertyName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isPromoted();
    }

    /**
     * Check if a property is declared as static on a given class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The property name.
     * 
     * @return bool True if the property is static.
     */
    public static function isPropertyStaticByName(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->isStatic();
    }

    /**
     * Get the names of all properties filtered by visibility.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param int|null      $filter        A ReflectionProperty filter bitmask, or null for all.
     * 
     * @return array<string> Array of property name strings.
     */
    public static function getPropertyNames(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $properties = $filter !== null ? $ref->getProperties($filter) : $ref->getProperties();

        return array_map(fn(ReflectionProperty $p) => $p->getName(), $properties);
    }

    /**
     * Check if a given ReflectionParameter is variadic.
     *
     * @param ReflectionParameter $parameter The parameter to check.
     * 
     * @return bool True if the parameter is variadic.
     */
    public static function isParameterVariadic(ReflectionParameter $parameter): bool
    {
        return $parameter->isVariadic();
    }

    /**
     * Check if a given ReflectionParameter is promoted (PHP 8.0+).
     *
     * @param ReflectionParameter $parameter The parameter to check.
     * 
     * @return bool True if the parameter is a constructor promoted property.
     */
    public static function isParameterPromoted(ReflectionParameter $parameter): bool
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return false;
        }

        return $parameter->isPromoted();
    }

    /**
     * Get all attributes of a specific parameter.
     *
     * @param ReflectionParameter $parameter     The parameter to inspect.
     * @param string|null         $attributeName Optional attribute class name to filter.
     * 
     * @return array<ReflectionAttribute>
     */
    public static function getParameterAttributes(ReflectionParameter $parameter, ?string $attributeName = null): array
    {
        return $parameter->getAttributes($attributeName);
    }

    /**
     * Check if a parameter has a specific attribute.
     *
     * @param ReflectionParameter $parameter     The parameter to inspect.
     * @param string              $attributeName The attribute class name to check.
     * 
     * @return bool True if the attribute is present.
     */
    public static function hasParameterAttribute(ReflectionParameter $parameter, string $attributeName): bool
    {
        return count($parameter->getAttributes($attributeName)) > 0;
    }

    /**
     * Get the number of parameters in a function or closure.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return int The total number of parameters.
     */
    public static function getFunctionParameterCount(Closure|string $function): int
    {
        $ref = new ReflectionFunction($function);
        return $ref->getNumberOfParameters();
    }

    /**
     * Get the number of required parameters in a function or closure.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return int The number of required parameters.
     */
    public static function getFunctionRequiredParameterCount(Closure|string $function): int
    {
        $ref = new ReflectionFunction($function);
        return $ref->getNumberOfRequiredParameters();
    }

    /**
     * Check if a function or closure is deprecated.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return bool True if the function is deprecated.
     */
    public static function isFunctionDeprecated(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);
        return $ref->isDeprecated();
    }

    /**
     * Get the file name where a function or closure is defined.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return string|false The file path, or false for internal functions.
     */
    public static function getFunctionFileName(Closure|string $function): string|false
    {
        $ref = new ReflectionFunction($function);
        return $ref->getFileName();
    }

    /**
     * Get the ending line number of a function or closure definition.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return int|false The ending line, or false for internal functions.
     */
    public static function getFunctionEndLine(Closure|string $function): int|false
    {
        $ref = new ReflectionFunction($function);
        return $ref->getEndLine();
    }

    /**
     * Check if a function or closure is defined internally by a PHP extension.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return bool True if the function is internal.
     */
    public static function isFunctionInternal(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);
        return $ref->isInternal();
    }

    /**
     * Check if a function or closure is user-defined.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return bool True if the function is user-defined.
     */
    public static function isFunctionUserDefined(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);
        return $ref->isUserDefined();
    }

    /**
     * Check if a function or closure is a generator.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return bool True if the function is a generator.
     */
    public static function isFunctionGenerator(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);
        return $ref->isGenerator();
    }

    /**
     * Check if a function or closure is variadic.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return bool True if the function has variadic parameters.
     */
    public static function isFunctionVariadic(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);
        return $ref->isVariadic();
    }

    /**
     * Get the parameter names of a function or closure as a flat string array.
     *
     * @param Closure|string $function The function or closure to inspect.
     * 
     * @return array<string> Array of parameter name strings.
     */
    public static function getFunctionParameterNames(Closure|string $function): array
    {
        $ref = new ReflectionFunction($function);
        return array_map(fn(ReflectionParameter $p) => $p->getName(), $ref->getParameters());
    }

    /**
     * Check if a method is deprecated (internal methods only).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $methodName    The method name.
     * 
     * @return bool True if the method is deprecated.
     */
    public static function isMethodDeprecated(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isDeprecated();
    }

    /**
     * Check if a method is internal (defined by a PHP extension, not user code).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $methodName    The method name.
     * 
     * @return bool True if the method is internal.
     */
    public static function isMethodInternal(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isInternal();
    }

    /**
     * Check if a method is user-defined (not built into PHP).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $methodName    The method name.
     * 
     * @return bool True if the method is user-defined.
     */
    public static function isMethodUserDefined(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return $ref->isUserDefined();
    }

    /**
     * Get the total number of methods in a class with an optional filter.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param int|null      $filter        A ReflectionMethod filter bitmask, or null for all.
     * 
     * @return int The count of matching methods.
     */
    public static function getMethodCount(object|string $objectOrClass, ?int $filter = null): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return count($filter !== null ? $ref->getMethods($filter) : $ref->getMethods());
    }

    /**
     * Get the count of static methods in a class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return int The count of static methods.
     */
    public static function getStaticMethodCount(object|string $objectOrClass): int
    {
        $ref = new ReflectionClass($objectOrClass);
        return count(array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $m) => $m->isStatic()
        ));
    }

    /**
     * Get the names of all methods in a class with an optional filter.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param int|null      $filter        A ReflectionMethod filter bitmask, or null for all.
     * 
     * @return array<string> Array of method name strings.
     */
    public static function getMethodNames(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $methods = $filter !== null ? $ref->getMethods($filter) : $ref->getMethods();

        return array_map(fn(ReflectionMethod $m) => $m->getName(), $methods);
    }

    /**
     * Get all methods declared directly in the class (excludes inherited methods).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return array<ReflectionMethod> Methods declared in the class itself.
     */
    public static function getOwnMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $className = $ref->getName();

        return array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $m) => $m->getDeclaringClass()->getName() === $className
        );
    }

    /**
     * Get all properties declared directly in the class (excludes inherited properties).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return array<ReflectionProperty> Properties declared in the class itself.
     */
    public static function getOwnProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $className = $ref->getName();

        return array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => $p->getDeclaringClass()->getName() === $className
        );
    }

    /**
     * Get the doc comment of a class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return string|false The doc comment, or false if none.
     */
    public static function getClassDocComment(object|string $objectOrClass): string|false
    {
        return (new ReflectionClass($objectOrClass))->getDocComment();
    }

    /**
     * Get the fully qualified class name from a ReflectionClass or object.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return string The fully qualified class name.
     */
    public static function getClassName(object|string $objectOrClass): string
    {
        return (new ReflectionClass($objectOrClass))->getName();
    }

    /**
     * Get all constants declared directly in the class (excludes inherited constants).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return array<string, mixed> Constant name => value pairs.
     */
    public static function getOwnConstants(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $className = $ref->getName();
        $result = [];

        foreach ($ref->getReflectionConstants() as $constant) {
            if ($constant->getDeclaringClass()->getName() === $className) {
                $result[$constant->getName()] = $constant->getValue();
            }
        }

        return $result;
    }

    /**
     * Get the number of constants in a class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return int The count of class constants.
     */
    public static function getConstantCount(object|string $objectOrClass): int
    {
        return count((new ReflectionClass($objectOrClass))->getConstants());
    }

    /**
     * Get the number of interfaces implemented by a class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return int The count of implemented interfaces.
     */
    public static function getInterfaceCount(object|string $objectOrClass): int
    {
        return count((new ReflectionClass($objectOrClass))->getInterfaces());
    }

    /**
     * Get the number of traits used by a class.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * 
     * @return int The count of traits.
     */
    public static function getTraitCount(object|string $objectOrClass): int
    {
        return count((new ReflectionClass($objectOrClass))->getTraits());
    }

    /**
     * Check if an object is an instance of a given class or interface name.
     *
     * @param object        $object    The object instance to check.
     * @param object|string $className The class name or object to compare against.
     * 
     * @return bool True if $object is an instance of $className.
     */
    public static function isInstanceOf(object $object, object|string $className): bool
    {
        return (new ReflectionClass($className))->isInstance($object);
    }

    /**
     * Deep-clone an object by using reflection to copy all property values.
     * Handles private and protected properties across the inheritance chain.
     *
     * @param object $object The object to clone.
     * 
     * @return object A deep copy of the object.
     */
    public static function deepClone(object $object): object
    {
        $ref = new ReflectionClass($object);
        $clone = $ref->newInstanceWithoutConstructor();

        $current = $ref;
        do {
            foreach ($current->getProperties() as $property) {
                if ($property->isStatic()) {
                    continue;
                }

                if (method_exists($property, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $property->setAccessible(true);
                }

                $value = $property->getValue($object);
                $property->setValue($clone, is_object($value) ? clone $value : $value);
            }
            $current = $current->getParentClass();
        } while ($current);

        return $clone;
    }

    /**
     * Check if a PHP extension is loaded.
     *
     * @param string $extensionName The extension name to check.
     * 
     * @return bool True if the extension is loaded.
     */
    public static function isExtensionLoaded(string $extensionName): bool
    {
        return extension_loaded($extensionName);
    }

    /**
     * Get all constants defined by a PHP extension.
     *
     * @param string $extensionName The extension name.
     * 
     * @return array<string, mixed> Constant name => value pairs.
     */
    public static function getExtensionConstants(string $extensionName): array
    {
        $ref = new ReflectionExtension($extensionName);
        return $ref->getConstants();
    }

    /**
     * Get a human-readable summary string for a PHP extension.
     *
     * @param string $extensionName The extension name.
     * 
     * @return string Summary including name, version, function count, and class count.
     */
    public static function getExtensionSummary(string $extensionName): string
    {
        $ref = new ReflectionExtension($extensionName);
        $functions = $ref->getFunctions();
        $classes = $ref->getClassNames();

        return sprintf(
            'Extension: %s v%s — %d function(s), %d class(es)',
            $ref->getName(),
            $ref->getVersion() ?? 'unknown',
            count($functions),
            count($classes)
        );
    }

    /**
     * Create a WeakReference to the given object.
     *
     * @param object $object The object to create a weak reference to.
     * 
     * @return \WeakReference The weak reference.
     */
    public static function createWeakReference(object $object): \WeakReference
    {
        return \WeakReference::create($object);
    }

    /**
     * Get a comprehensive metadata summary of a class.
     *
     * Returns an associative array with class name, namespace, parent, interfaces,
     * traits, method/property/constant counts, and key boolean flags.
     *
     * @param object|string $objectOrClass The class or object to summarize.
     * 
     * @return array<string, mixed> The metadata summary.
     */
    public static function getClassSummary(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);

        return [
            'name' => $ref->getName(),
            'shortName' => $ref->getShortName(),
            'namespace' => $ref->getNamespaceName(),
            'parent' => $ref->getParentClass() ? $ref->getParentClass()->getName() : null,
            'interfaces' => $ref->getInterfaceNames(),
            'traits' => $ref->getTraitNames(),
            'fileName' => $ref->getFileName(),
            'startLine' => $ref->getStartLine(),
            'endLine' => $ref->getEndLine(),
            'methodCount' => count($ref->getMethods()),
            'propertyCount' => count($ref->getProperties()),
            'constantCount' => count($ref->getConstants()),
            'isAbstract' => $ref->isAbstract(),
            'isFinal' => $ref->isFinal(),
            'isInterface' => $ref->isInterface(),
            'isTrait' => $ref->isTrait(),
            'isEnum' => method_exists($ref, 'isEnum') ? $ref->isEnum() : false,
            'isInternal' => $ref->isInternal(),
            'isInstantiable' => $ref->isInstantiable(),
            'isAnonymous' => $ref->isAnonymous(),
        ];
    }

    /**
     * Get a metadata summary of a method.
     *
     * Returns an associative array with method name, visibility, line range,
     * parameter count, return type, and key boolean flags.
     *
     * @param object|string $objectOrClass The class or object containing the method.
     * @param string        $methodName    The method name.
     * 
     * @return array<string, mixed> The method metadata.
     */
    public static function getMethodSummary(object|string $objectOrClass, string $methodName): array
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        return [
            'name' => $ref->getName(),
            'class' => $ref->getDeclaringClass()->getName(),
            'visibility' => $ref->isPublic() ? 'public' : ($ref->isProtected() ? 'protected' : 'private'),
            'isStatic' => $ref->isStatic(),
            'isAbstract' => $ref->isAbstract(),
            'isFinal' => $ref->isFinal(),
            'isConstructor' => $ref->isConstructor(),
            'isDestructor' => $ref->isDestructor(),
            'isGenerator' => $ref->isGenerator(),
            'isVariadic' => $ref->isVariadic(),
            'parameterCount' => $ref->getNumberOfParameters(),
            'requiredParameterCount' => $ref->getNumberOfRequiredParameters(),
            'returnType' => $ref->hasReturnType() ? (string) $ref->getReturnType() : null,
            'startLine' => $ref->getStartLine(),
            'endLine' => $ref->getEndLine(),
            'fileName' => $ref->getFileName(),
        ];
    }

    /**
     * Check if a string is a valid object attribute name (non-control characters).
     *
     * @param string $attributeName The attribute name to validate.
     * 
     * @return bool True if the name is valid, false otherwise.
     */
    public static function isValidObjectAttributeName(string $attributeName): bool
    {
        return (bool) preg_match('/[^\x00-\x1f\x7f-\x9f]+/', $attributeName);
    }

    /**
     * Check if a string is a valid class attribute name according to PHP naming rules.
     *
     * @param string $attributeName The attribute name to validate.
     * 
     * @return bool True if the name is valid, false otherwise.
     */
    public static function isValidClassAttributeName(string $attributeName): bool
    {
        return (bool) preg_match('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', $attributeName);
    }

    /**
     * Get the value of an attribute from an object, regardless of its visibility.
     * Traverses the class hierarchy to find the attribute if it's not declared in the immediate class.
     *
     * @param object $object        The object instance to inspect.
     * @param string $attributeName The name of the attribute to retrieve.
     * 
     * @return mixed The value of the attribute, or null if not found.
     */
    public static function getObjectAttribute(object $object, string $attributeName)
    {
        $reflector = new ReflectionObject($object);
        do {
            try {
                $attribute = $reflector->getProperty($attributeName);
                if (!$attribute || $attribute->isPublic()) {
                    return $object->{$attributeName};
                }

                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $attribute->setAccessible(true);
                }
                $value = $attribute->getValue($object);
                if (version_compare(PHP_VERSION, '8.1.0', '<')) {
                    // @phpstan-ignore-next-line
                    $attribute->setAccessible(false);
                }

                return $value;
            } catch (ReflectionException $e) {
            }
        } while ($reflector = $reflector->getParentClass());
    }

    /**
     * Get the type of a reflected member (class, method, property, constant, or parameter).
     * Determines the member type based on the class of the reflection object.
     * @param ReflectionClass|ReflectionClassConstant|ReflectionMethod|ReflectionParameter|ReflectionProperty $member
     * @return null|string
     */
    public static function getMemberType(object $member): ?string
    {
        if ($member instanceof ReflectionClassConstant) {
            return 'constant';
        } elseif ($member instanceof ReflectionProperty) {
            return 'property';
        } elseif ($member instanceof ReflectionClass) {
            return 'class';
        } elseif ($member instanceof ReflectionParameter) {
            return 'parameter';
        } elseif ($member instanceof ReflectionMethod) {
            return 'method';
        }

        return null;
    }

    /**
     * Check if a class is declared inside a namespace.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     *
     * @return bool True if the class is in a namespace.
     */
    public static function classInNamespace(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->inNamespace();
    }

    /**
     * Get the value of a static property from a class.
     * If the property does not exist and no default is provided, a ReflectionException is thrown.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The static property name.
     * @param mixed         $default       Optional default returned when the property does not exist.
     *
     * @return mixed The value of the static property, or $default if provided.
     */
    public static function getClassStaticPropertyValue(object|string $objectOrClass, string $propertyName, mixed $default = null): mixed
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return $default;
        }

        return $ref->getStaticPropertyValue($propertyName, $default);
    }

    /**
     * Set the value of a static property on a class.
     *
     * @param object|string $objectOrClass The class or object to modify.
     * @param string        $propertyName  The static property name.
     * @param mixed         $value         The new value.
     *
     * @return void
     */
    public static function setClassStaticPropertyValue(object|string $objectOrClass, string $propertyName, mixed $value): void
    {
        $ref = new ReflectionClass($objectOrClass);
        $ref->setStaticPropertyValue($propertyName, $value);
    }

    /**
     * Invoke a method on a concrete object instance with individual arguments.
     * Automatically makes non-public methods accessible before invocation.
     *
     * @param object|string $objectOrClass The class or object that owns the method.
     * @param string        $methodName    The method name to invoke.
     * @param object|null   $instance      The object instance to invoke on (null for static methods).
     * @param mixed         ...$args       Arguments to pass to the method.
     *
     * @return mixed The return value of the invoked method.
     */
    public static function invokeMethod(object|string $objectOrClass, string $methodName, ?object $instance = null, mixed ...$args): mixed
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        if (method_exists($ref, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $ref->setAccessible(true);
        }

        return $ref->invoke($instance, ...$args);
    }

    /**
     * Get the tentative return type of a method (PHP 8.1+).
     * Tentative return types are used in internal PHP functions marked with #[\ReturnTypeWillChange].
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $methodName    The method name.
     *
     * @return ReflectionType|null The tentative return type, or null if not declared.
     */
    public static function getMethodTentativeReturnType(object|string $objectOrClass, string $methodName): ?ReflectionType
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        if (!method_exists($ref, 'getTentativeReturnType')) {
            return null;
        }

        return $ref->getTentativeReturnType();
    }

    /**
     * Check if a method has a tentative return type declaration (PHP 8.1+).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $methodName    The method name.
     *
     * @return bool True if a tentative return type is declared.
     */
    public static function hasMethodTentativeReturnType(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);

        if (!method_exists($ref, 'hasTentativeReturnType')) {
            return false;
        }

        return $ref->hasTentativeReturnType();
    }

    /**
     * Get the raw ReflectionType of a property.
     * Unlike getPropertyTypeName / getPropertyTypeString, this returns the
     * ReflectionType object directly (may be Named, Union, or Intersection).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The property name.
     *
     * @return ReflectionType|null The ReflectionType, or null if no type is declared.
     */
    public static function getPropertyType(object|string $objectOrClass, string $propertyName): ?ReflectionType
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return null;
        }

        return $ref->getProperty($propertyName)->getType();
    }

    /**
     * Check whether a property is lazy (PHP 8.4+).
     * Lazy properties are those whose initialization is deferred
     * via ReflectionClass::newLazyGhost() or ReflectionClass::newLazyProxy().
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $propertyName  The property name.
     * @param object|null   $instance      The object instance to check against (required for lazy state).
     *
     * @return bool True if the property is currently in a lazy (uninitialized) state.
     */
    public static function isPropertyLazy(object|string $objectOrClass, string $propertyName, ?object $instance = null): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'isLazy')) {
            return false;
        }

        return $property->isLazy($instance);
    }

    /**
     * Check if the default value of a parameter is a PHP constant (or class constant).
     *
     * @param ReflectionParameter $parameter The parameter to inspect.
     *
     * @return bool True if the default value is defined via a constant expression.
     */
    public static function isParameterDefaultValueConstant(ReflectionParameter $parameter): bool
    {
        if (!$parameter->isDefaultValueAvailable()) {
            return false;
        }

        return $parameter->isDefaultValueConstant();
    }

    /**
     * Get the constant name that provides the default value of a parameter.
     * Returns null when the default is not a constant or none is available.
     *
     * @param ReflectionParameter $parameter The parameter to inspect.
     *
     * @return string|null The constant name string, or null.
     */
    public static function getParameterDefaultValueConstantName(ReflectionParameter $parameter): ?string
    {
        if (!$parameter->isDefaultValueAvailable() || !$parameter->isDefaultValueConstant()) {
            return null;
        }

        return $parameter->getDefaultValueConstantName();
    }

    /**
     * Check whether a function or closure is an anonymous function (PHP 8.2+).
     *
     * @param Closure|string $function The function or closure to inspect.
     *
     * @return bool True if the function is anonymous.
     */
    public static function isFunctionAnonymous(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);

        if (!method_exists($ref, 'isAnonymous')) {
            // Closures are always anonymous; named functions are not.
            return $function instanceof Closure;
        }

        return $ref->isAnonymous();
    }

    /**
     * Get the namespace name of a function.
     * Returns an empty string for functions defined in the global namespace.
     *
     * @param Closure|string $function The function or closure to inspect.
     *
     * @return string The namespace name, or an empty string.
     */
    public static function getFunctionNamespaceName(Closure|string $function): string
    {
        return (new ReflectionFunction($function))->getNamespaceName();
    }

    /**
     * Check if a function is defined inside a namespace (not in the global scope).
     *
     * @param Closure|string $function The function or closure to inspect.
     *
     * @return bool True if the function belongs to a namespace.
     */
    public static function isFunctionInNamespace(Closure|string $function): bool
    {
        return (new ReflectionFunction($function))->inNamespace();
    }

    /**
     * Invoke a function or closure with individual arguments.
     *
     * @param Closure|string $function The function or closure to invoke.
     * @param mixed          ...$args  Arguments to pass to the function.
     *
     * @return mixed The return value of the invoked function.
     */
    public static function invokeFunction(Closure|string $function, mixed ...$args): mixed
    {
        return (new ReflectionFunction($function))->invoke(...$args);
    }

    /**
     * Get the tentative return type of a function (PHP 8.1+).
     *
     * @param Closure|string $function The function or closure to inspect.
     *
     * @return ReflectionType|null The tentative return type, or null if not declared.
     */
    public static function getFunctionTentativeReturnType(Closure|string $function): ?ReflectionType
    {
        $ref = new ReflectionFunction($function);

        if (!method_exists($ref, 'getTentativeReturnType')) {
            return null;
        }

        return $ref->getTentativeReturnType();
    }

    /**
     * Check if a function has a tentative return type declaration (PHP 8.1+).
     *
     * @param Closure|string $function The function or closure to inspect.
     *
     * @return bool True if a tentative return type is declared.
     */
    public static function hasFunctionTentativeReturnType(Closure|string $function): bool
    {
        $ref = new ReflectionFunction($function);

        if (!method_exists($ref, 'hasTentativeReturnType')) {
            return false;
        }

        return $ref->hasTentativeReturnType();
    }

    /**
     * Check if a class constant is declared as protected.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return bool True if the constant is protected.
     */
    public static function isConstantProtected(object|string $objectOrClass, string $constantName): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'isProtected')) {
            return false;
        }

        return $constant->isProtected();
    }

    /**
     * Check if a class constant is declared as private.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return bool True if the constant is private.
     */
    public static function isConstantPrivate(object|string $objectOrClass, string $constantName): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'isPrivate')) {
            return false;
        }

        return $constant->isPrivate();
    }

    /**
     * Check if a class constant is declared as final (PHP 8.3+).
     * Final constants cannot be overridden in child classes.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return bool True if the constant is final.
     */
    public static function isConstantFinal(object|string $objectOrClass, string $constantName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.3.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'isFinal')) {
            return false;
        }

        return $constant->isFinal();
    }

    /**
     * Check if a class constant is an enum case (PHP 8.1+).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return bool True if the constant represents an enum case.
     */
    public static function isConstantEnumCase(object|string $objectOrClass, string $constantName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'isEnumCase')) {
            return false;
        }

        return $constant->isEnumCase();
    }

    /**
     * Get all attributes attached to a class constant.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     * @param string|null   $attributeName Optional attribute class name to filter.
     *
     * @return array<ReflectionAttribute> Array of ReflectionAttribute objects.
     */
    public static function getConstantAttributes(object|string $objectOrClass, string $constantName, ?string $attributeName = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false) {
            return [];
        }

        return $constant->getAttributes($attributeName);
    }

    /**
     * Check if a class constant has a type declaration (PHP 8.3+).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return bool True if the constant has an explicit type.
     */
    public static function hasConstantType(object|string $objectOrClass, string $constantName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.3.0', '>=')) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'hasType')) {
            return false;
        }

        return $constant->hasType();
    }

    /**
     * Get the type of a class constant (PHP 8.3+).
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return ReflectionType|null The constant's declared type, or null.
     */
    public static function getConstantType(object|string $objectOrClass, string $constantName): ?ReflectionType
    {
        if (!OperationSystem::comparePHPVersion('8.3.0', '>=')) {
            return null;
        }

        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false || !method_exists($constant, 'getType')) {
            return null;
        }

        return $constant->getType();
    }

    /**
     * Get the doc comment of a class constant.
     *
     * @param object|string $objectOrClass The class or object to inspect.
     * @param string        $constantName  The constant name.
     *
     * @return string|false The doc comment, or false if none exists.
     */
    public static function getConstantDocComment(object|string $objectOrClass, string $constantName): string|false
    {
        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false) {
            return false;
        }

        return $constant->getDocComment();
    }

    /**
     * Get the execution trace of a suspended Generator (PHP 8.0+).
     *
     * @param Generator $generator The generator instance to inspect.
     * @param int       $options   debug_backtrace option flags (default DEBUG_BACKTRACE_PROVIDE_OBJECT).
     *
     * @return array The generator's current execution trace.
     */
    public static function getGeneratorTrace(Generator $generator, int $options = DEBUG_BACKTRACE_PROVIDE_OBJECT): array
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getTrace($options);
    }

    /**
     * Get the author of a Zend extension.
     *
     * @param string $name The Zend extension name.
     *
     * @return string The author string reported by the extension.
     */
    public static function getZendExtensionAuthor(string $name): string
    {
        return (new ReflectionZendExtension($name))->getAuthor();
    }

    /**
     * Get the URL of a Zend extension.
     *
     * @param string $name The Zend extension name.
     *
     * @return string The URL string reported by the extension.
     */
    public static function getZendExtensionUrl(string $name): string
    {
        return (new ReflectionZendExtension($name))->getURL();
    }

    /**
     * Get the copyright notice of a Zend extension.
     *
     * @param string $name The Zend extension name.
     *
     * @return string The copyright string reported by the extension.
     */
    public static function getZendExtensionCopyright(string $name): string
    {
        return (new ReflectionZendExtension($name))->getCopyright();
    }

    /**
     * Check if a PHP extension is a persistent extension (loaded into the process permanently).
     *
     * @param string $extensionName The extension name.
     *
     * @return bool True if the extension is persistent.
     */
    public static function isExtensionPersistent(string $extensionName): bool
    {
        return (new ReflectionExtension($extensionName))->isPersistent();
    }

    /**
     * Check if a PHP extension is temporary (loaded at runtime and not persistent).
     *
     * @param string $extensionName The extension name.
     *
     * @return bool True if the extension is temporary.
     */
    public static function isExtensionTemporary(string $extensionName): bool
    {
        return (new ReflectionExtension($extensionName))->isTemporary();
    }

    /**
     * Get ReflectionClass objects for all classes defined by a PHP extension.
     * Unlike getExtensionClassNames(), this returns fully-hydrated ReflectionClass instances.
     *
     * @param string $extensionName The extension name.
     *
     * @return array<string, ReflectionClass> Class name => ReflectionClass pairs.
     */
    public static function getExtensionClasses(string $extensionName): array
    {
        return (new ReflectionExtension($extensionName))->getClasses();
    }

    /**
     * Convert a bitmask of modifier flags into an array of human-readable modifier name strings.
     * Wraps Reflection::getModifierNames() as a public static API.
     *
     * @param int $modifiers The bitmask value returned by getModifiers() on a method, property, or class.
     *
     * @return array<string> List of modifier name strings (e.g. ['public', 'static', 'final']).
     */
    public static function getModifierNames(int $modifiers): array
    {
        return Reflection::getModifierNames($modifiers);
    }

    /**
     * Check if a Fiber has been started (PHP 8.1+).
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     *
     * @return bool True if the Fiber has been started.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function isFiberStarted(\Fiber $fiber): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        // @phpstan-ignore-next-line
        return (new ReflectionFiber($fiber))->isStarted();
    }

    /**
     * Check if a Fiber is currently running (PHP 8.1+).
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     *
     * @return bool True if the Fiber is actively executing.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function isFiberRunning(\Fiber $fiber): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        // @phpstan-ignore-next-line
        return (new ReflectionFiber($fiber))->getFiber()->isRunning();
    }

    /**
     * Check if a Fiber is suspended (PHP 8.1+).
     * A Fiber is suspended when it has called Fiber::suspend() and is waiting to be resumed.
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     *
     * @return bool True if the Fiber is suspended.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function isFiberSuspended(\Fiber $fiber): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        // @phpstan-ignore-next-line
        return (new ReflectionFiber($fiber))->getFiber()->isSuspended();
    }

    /**
     * Check if a Fiber has terminated (PHP 8.1+).
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     *
     * @return bool True if the Fiber has finished executing.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function isFiberTerminated(\Fiber $fiber): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        // @phpstan-ignore-next-line
        return (new ReflectionFiber($fiber))->getFiber()->isTerminated();
    }

    /**
     * Get the return value of a terminated Fiber (PHP 8.1+).
     * Throws a FiberError if the Fiber has not terminated.
     *
     * @param \Fiber $fiber The Fiber instance to inspect.
     *
     * @return mixed The value returned by the Fiber's callable.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getFiberReturn(\Fiber $fiber): mixed
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Fibers require PHP 8.1 or higher.');
        }

        // @phpstan-ignore-next-line
        return (new ReflectionFiber($fiber))->getFiber()->getReturn();
    }

    /**
     * Get the interface names implemented by a class.
     *
     * @param object|string $objectOrClass
     *
     * @return array<string>
     */
    public static function getClassInterfaceNames(object|string $objectOrClass): array
    {
        return (new ReflectionClass($objectOrClass))->getInterfaceNames();
    }

    /**
     * Check if a class is cloneable.
     *
     * @param object|string $objectOrClass
     *
     * @return bool
     */
    public static function isCloneableObjectClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isCloneable();
    }

    /**
     * Check if the given class is an interface.
     *
     * @param object|string $objectOrClass
     *
     * @return bool
     */
    public static function isInterfaceClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isInterface();
    }

    /**
     * Check if a class is abstract using its name or object instance.
     *
     * @param object|string $objectOrClass
     *
     * @return bool
     */
    public static function isAbstractClass(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->isAbstract();
    }

    /**
     * Get all constants of a class with an optional visibility filter.
     *
     * @param object|string $objectOrClass
     * @param int|null      $filter        ReflectionClassConstant filter bitmask, or null for all.
     *
     * @return array<string, mixed> Constant name => value pairs.
     */
    public static function getStrictClassConstants(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return $filter !== null && OperationSystem::comparePHPVersion('8.0.0', '>=')
            ? $ref->getConstants($filter)
            : $ref->getConstants();
    }

    /**
     * Get all static property values of a class.
     *
     * @param object|string $objectOrClass
     *
     * @return array<string, mixed> Property name => value pairs.
     */
    public static function getClassStaticPropertyValues(object|string $objectOrClass): array
    {
        return (new ReflectionClass($objectOrClass))->getStaticProperties();
    }

    /**
     * Get all static ReflectionProperty objects of a class.
     *
     * @param object|string $objectOrClass
     *
     * @return array<ReflectionProperty>
     */
    public static function getStrictClassStaticProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return array_values(array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => $p->isStatic()
        ));
    }

    /**
     * Create a lazy ghost instance of the given class (PHP 8.4+).
     * The initializer is called the first time a non-skipped property is accessed.
     *
     * @param object|string $objectOrClass
     * @param Closure       $initializer
     * @param int           $options
     *
     * @return object
     *
     * @throws Exception If PHP version is below 8.4.
     */
    public static function createLazyGhost(object|string $objectOrClass, Closure $initializer, int $options = 0): object
    {
        if (PHP_VERSION_ID < 80400) {
            throw new Exception('Lazy ghosts require PHP 8.4 or higher.');
        }

        return (new ReflectionClass($objectOrClass))->newLazyGhost($initializer, $options);
    }

    /**
     * Get the visibility string of a method ('public', 'protected', or 'private').
     *
     * @param object|string $objectOrClass
     * @param string        $methodName
     *
     * @return string
     */
    public static function getMethodVisibility(object|string $objectOrClass, string $methodName): string
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        if ($ref->isPublic()) {
            return 'public';
        }
        if ($ref->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Get the ReflectionParameter array for all parameters of a method.
     *
     * @param object|string $objectOrClass
     * @param string        $methodName
     *
     * @return array<ReflectionParameter>
     */
    public static function getMethodParameterReflections(object|string $objectOrClass, string $methodName): array
    {
        return self::createFromMethodName($objectOrClass, $methodName)->getParameters();
    }

    /**
     * Get the modifier bitmask of a named property.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return int
     */
    public static function getPropertyModifiers(object|string $objectOrClass, string $propertyName): int
    {
        return (new ReflectionClass($objectOrClass))->getProperty($propertyName)->getModifiers();
    }

    /**
     * Get the modifier names of a named property as a string array.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return array<string>
     */
    public static function getPropertyModifierNames(object|string $objectOrClass, string $propertyName): array
    {
        return Reflection::getModifierNames(self::getPropertyModifiers($objectOrClass, $propertyName));
    }

    /**
     * Get the visibility string of a property ('public', 'protected', or 'private').
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return string
     */
    public static function getPropertyVisibility(object|string $objectOrClass, string $propertyName): string
    {
        $ref = (new ReflectionClass($objectOrClass))->getProperty($propertyName);

        if ($ref->isPublic()) {
            return 'public';
        }
        if ($ref->isProtected()) {
            return 'protected';
        }
        return 'private';
    }

    /**
     * Check if a named property is initialized on a given object instance.
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     * @param object|null   $instance
     *
     * @return bool
     */
    public static function isPropertyInitializedByName(object|string $objectOrClass, string $propertyName, ?object $instance = null): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        if (!$ref->hasProperty($propertyName)) {
            return false;
        }
        return $ref->getProperty($propertyName)->isInitialized($instance);
    }

    /**
     * Check if a property is declared directly in the specified class (not inherited).
     *
     * @param object|string $objectOrClass
     * @param string        $propertyName
     *
     * @return bool
     */
    public static function isPropertyDeclaredInClass(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);
        if (!$ref->hasProperty($propertyName)) {
            return false;
        }
        return $ref->getProperty($propertyName)->getDeclaringClass()->getName() === $ref->getName();
    }

    /**
     * Check if a ReflectionParameter has a type declaration.
     *
     * @param ReflectionParameter $parameter
     *
     * @return bool
     */
    public static function hasParameterType(ReflectionParameter $parameter): bool
    {
        return $parameter->hasType();
    }

    /**
     * Get the name of the declaring function/method for a parameter.
     *
     * @param ReflectionParameter $parameter
     *
     * @return string
     */
    public static function getParameterDeclaringFunctionName(ReflectionParameter $parameter): string
    {
        return $parameter->getDeclaringFunction()->getName();
    }

    /**
     * Get the declaring class name of the function that owns the parameter.
     * Returns null for standalone functions.
     *
     * @param ReflectionParameter $parameter
     *
     * @return string|null
     */
    public static function getParameterDeclaringClassName(ReflectionParameter $parameter): ?string
    {
        $class = $parameter->getDeclaringClass();
        return $class ?? $class->getName();
    }

    /**
     * Get the short name of a named function (without namespace prefix).
     *
     * @param Closure|string $function
     *
     * @return string
     */
    public static function getFunctionShortName(Closure|string $function): string
    {
        return (new ReflectionFunction($function))->getShortName();
    }

    /**
     * Get the $this object bound to a closure, or null if none.
     *
     * @param Closure|string $closure
     *
     * @return object|null
     */
    public static function getClosureThis(Closure|string $closure): ?object
    {
        return (new ReflectionFunction($closure))->getClosureThis();
    }

    /**
     * Check if a function returns a reference.
     *
     * @param Closure|string $function
     *
     * @return bool
     */
    public static function isFunctionReturnsReference(Closure|string $function): bool
    {
        return (new ReflectionFunction($function))->returnsReference();
    }

    /**
     * Get the name of a PHP extension.
     *
     * @param string $extensionName
     *
     * @return string
     */
    public static function getExtensionName(string $extensionName): string
    {
        return (new ReflectionExtension($extensionName))->getName();
    }

    /**
     * Get the name of a Zend extension.
     *
     * @param string $name
     *
     * @return string
     */
    public static function getZendExtensionName(string $name): string
    {
        return (new ReflectionZendExtension($name))->getName();
    }

    /**
     * Get a ReflectionObject for the given runtime object instance.
     *
     * @param object $object
     *
     * @return ReflectionObject
     */
    public static function getReflectionObject(object $object): ReflectionObject
    {
        return new ReflectionObject($object);
    }

    /**
     * Get all property values of a runtime object, including non-public ones.
     *
     * @param object $object
     *
     * @return array<string, mixed> Property name => value pairs.
     */
    public static function getObjectPropertyValues(object $object): array
    {
        $ref = new ReflectionObject($object);
        $result = [];

        foreach ($ref->getProperties() as $property) {
            if (method_exists($property, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }
            $result[$property->getName()] = $property->getValue($object);
        }

        return $result;
    }

    /**
     * Get all property names of a runtime object, including dynamic properties.
     *
     * @param object $object
     *
     * @return array<string>
     */
    public static function getObjectPropertyNames(object $object): array
    {
        return array_map(
            fn(ReflectionProperty $p) => $p->getName(),
            (new ReflectionObject($object))->getProperties()
        );
    }

    /**
     * Get all ReflectionMethod objects for a runtime object.
     *
     * @param object   $object
     * @param int|null $filter Optional ReflectionMethod filter bitmask.
     *
     * @return array<ReflectionMethod>
     */
    public static function getObjectMethods(object $object, ?int $filter = null): array
    {
        $ref = new ReflectionObject($object);
        return $filter !== null ? $ref->getMethods($filter) : $ref->getMethods();
    }

    /**
     * Check if a runtime object has a named property (including dynamic ones).
     *
     * @param object $object
     * @param string $propertyName
     *
     * @return bool
     */
    public static function objectHasProperty(object $object, string $propertyName): bool
    {
        return (new ReflectionObject($object))->hasProperty($propertyName);
    }

    /**
     * Check if a runtime object has a named method.
     *
     * @param object $object
     * @param string $methodName
     *
     * @return bool
     */
    public static function objectHasMethod(object $object, string $methodName): bool
    {
        return (new ReflectionObject($object))->hasMethod($methodName);
    }

    /**
     * Check if a runtime object instance is a lazy object (PHP 8.4+).
     *
     * @param object $object
     *
     * @return bool
     */
    public static function isObjectLazy(object $object): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionObject($object);
        return method_exists($ref, 'isUninitializedLazyObject') && $ref->isUninitializedLazyObject($object);
    }

    // ========================================================================
    // ReflectionEnum — additional coverage
    // ========================================================================

    /**
     * Get the interface names implemented by an enum (PHP 8.1+).
     *
     * @param string $enumClass
     *
     * @return array<string>
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumInterfaceNames(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return (new ReflectionEnum($enumClass))->getInterfaceNames();
    }

    /**
     * Get the trait names used by an enum (PHP 8.1+).
     *
     * @param string $enumClass
     *
     * @return array<string>
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumTraitNames(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return (new ReflectionEnum($enumClass))->getTraitNames();
    }

    /**
     * Get the method names of an enum (PHP 8.1+).
     *
     * @param string $enumClass
     *
     * @return array<string>
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumMethodNames(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return array_map(
            fn(ReflectionMethod $m) => $m->getName(),
            (new ReflectionEnum($enumClass))->getMethods()
        );
    }

    /**
     * Get the declaring class of a class constant.
     *
     * @param object|string $objectOrClass
     * @param string        $constantName
     *
     * @return ReflectionClass|null
     */
    public static function getConstantDeclaringClass(object|string $objectOrClass, string $constantName): ?ReflectionClass
    {
        $constant = (new ReflectionClass($objectOrClass))->getReflectionConstant($constantName);
        return $constant !== false ? $constant->getDeclaringClass() : null;
    }

    /**
     * Get the modifier bitmask of a class constant.
     *
     * @param object|string $objectOrClass
     * @param string        $constantName
     *
     * @return int
     */
    public static function getConstantModifiers(object|string $objectOrClass, string $constantName): int
    {
        $constant = (new ReflectionClass($objectOrClass))->getReflectionConstant($constantName);
        return $constant !== false ? $constant->getModifiers() : 0;
    }

    /**
     * Get the modifier names of a class constant as a string array.
     *
     * @param object|string $objectOrClass Class or object
     * @param string        $constantName  Constant name
     *
     * @return array<string> Modifier names
     */
    public static function getConstantModifierNames(object|string $objectOrClass, string $constantName): array
    {
        return Reflection::getModifierNames(self::getConstantModifiers($objectOrClass, $constantName));
    }

    public static function isSensitiveFunction($name): bool
    {
        static $commandExecutionFunctions = [
        'exec',
        'passthru',
        'system',
        'shell_exec',
        'popen',
        'proc_open',
        'pcntl_exec',
        ];

        static $codeExecutionFunctions = [
        'assert',
        'preg_replace',
        'create_function',
        'include',
        'include_once',
        'require',
        'require_once'
        ];

        static $callbackFunctions = [
        'ob_start' => 0,
        'array_diff_uassoc' => -1,
        'array_diff_ukey' => -1,
        'array_filter' => 1,
        'array_intersect_uassoc' => -1,
        'array_intersect_ukey' => -1,
        'array_map' => 0,
        'array_reduce' => 1,
        'array_udiff_assoc' => -1,
        'array_udiff_uassoc' => [-1, -2],
        'array_udiff' => -1,
        'array_uintersect_assoc' => -1,
        'array_uintersect_uassoc' => [-1, -2],
        'array_uintersect' => -1,
        'array_walk_recursive' => 1,
        'array_walk' => 1,
        'assert_options' => 1,
        'uasort' => 1,
        'uksort' => 1,
        'usort' => 1,
        'preg_replace_callback' => 1,
        'spl_autoload_register' => 0,
        'iterator_apply' => 1,
        'call_user_func' => 0,
        'call_user_func_array' => 0,
        'register_shutdown_function' => 0,
        'register_tick_function' => 0,
        'set_error_handler' => 0,
        'set_exception_handler' => 0,
        'session_set_save_handler' => [0, 1, 2, 3, 4, 5],
        'sqlite_create_aggregate' => [2, 3],
        'sqlite_create_function' => 2,
        ];

        static $informationDiscosureFunctions = [
        'phpinfo',
        'posix_mkfifo',
        'posix_getlogin',
        'posix_ttyname',
        'getenv',
        'get_current_user',
        'proc_get_status',
        'get_cfg_var',
        'disk_free_space',
        'disk_total_space',
        'diskfreespace',
        'getcwd',
        'getlastmo',
        'getmygid',
        'getmyinode',
        'getmypid',
        'getmyuid'
        ];

        static $otherFunctions = [
        'extract',
        'parse_str',
        'putenv',
        'ini_set',
        'mail',
        'header',
        'proc_nice',
        'proc_terminate',
        'proc_close',
        'pfsockopen',
        'fsockopen',
        'apache_child_terminate',
        'posix_kill',
        'posix_mkfifo',
        'posix_setpgid',
        'posix_setsid',
        'posix_setuid',
        'unserialize',
        'ini_alter',
        'simplexml_load_file',
        'simplexml_load_string',
        'forward_static_call',
        'forward_static_call_array',
        ];

        if (is_string($name)) {
            $name = strtolower($name);
        }

        if ($name instanceof Closure) {
            return false;
        }

        if (is_array($name) || strpos($name, ":") !== false) {
            return true;
        }

        if (strpos($name, "\\") !== false) {
            return true;
        }

        if (in_array($name, $commandExecutionFunctions)) {
            return true;
        }

        if (in_array($name, $codeExecutionFunctions)) {
            return true;
        }

        if (isset($callbackFunctions[$name])) {
            return true;
        }

        if (in_array($name, $informationDiscosureFunctions)) {
            return true;
        }

        if (in_array($name, $otherFunctions)) {
            return true;
        }

        return static::isFilesystemFunction($name);
    }

    public static function isFilesystemFunction(string $name): bool
    {
        static $fileWriteFunctions = [
        'bzopen',
        'chgrp',
        'chmod',
        'chown',
        'copy',
        'file_put_contents',
        'fopen',
        'ftp_get',
        'ftp_nb_get',
        'gzopen',
        'image2wbmp',
        'imagegd',
        'imagegd2',
        'imagegif',
        'imagejpeg',
        'imagepng',
        'imagewbmp',
        'imagexbm',
        'iptcembed',
        'lchgrp',
        'lchown',
        'link',
        'mkdir',
        'move_uploaded_file',
        'rename',
        'rmdir',
        'symlink',
        'tempnam',
        'tmpfile',
        'touch',
        'unlink',
        ];

        static $fileContentFunctions = [
        'file_get_contents',
        'file',
        'filegroup',
        'fileinode',
        'fileowner',
        'fileperms',
        'ftp_nb_put',
        'ftp_put',
        'glob',
        'gzfile',
        'hash_update_file',
        'highlight_file',
        'imagecreatefromgif',
        'imagecreatefromjpeg',
        'imagecreatefrompng',
        'imagecreatefromwbmp',
        'imagecreatefromxbm',
        'imagecreatefromxpm',
        'is_executable',
        'is_uploaded_file',
        'parse_ini_file',
        'php_strip_whitespace',
        'readfile',
        'readgzfile',
        'readlink',
        'realpath',
        'show_source',
        'stat',
        ];

        static $filesystemFunctions = [
        'exif_imagetype',
        'exif_read_data',
        'exif_thumbnail',
        'file_exists',
        'fileatime',
        'filectime',
        'filemtime',
        'filesize',
        'filetype',
        'get_meta_tags',
        'getimagesize',
        'hash_file',
        'hash_hmac_file',
        'is_dir',
        'is_file',
        'is_link',
        'is_readable',
        'is_writable',
        'is_writeable',
        'linkinfo',
        'lstat',
        'md5_file',
        'pathinfo',
        'read_exif_data',
        'sha1_file',
        ];

        if (in_array($name, $fileWriteFunctions)) {
            return true;
        }

        if (in_array($name, $fileContentFunctions)) {
            return true;
        }

        if (in_array($name, $filesystemFunctions)) {
            return true;
        }

        return false;
    }

    /**
     * Get ReflectionReference for an array element if it is a reference.
     *
     * @param array $array The array containing the element.
     * @param int|string $key The key of the element.
     *
     * @return ReflectionReference|null The reference, or null if not a reference.
     */
    public static function getArrayElementReference(array &$array, int|string $key): ?ReflectionReference
    {
        if (!class_exists(ReflectionReference::class)) {
            return null;
        }

        return ReflectionReference::fromArrayElement($array, $key);
    }

    /**
     * Get the unique reference ID string for an array element.
     *
     * @param array $array The array containing the element.
     * @param int|string $key The key of the element.
     *
     * @return string|null The reference ID, or null if not a reference.
     */
    public static function getReferenceId(array &$array, int|string $key): ?string
    {
        $ref = self::getArrayElementReference($array, $key);
        return $ref?->getId();
    }

    /**
     * Check if two array elements refer to the same reference.
     *
     * @param array $a First array.
     * @param int|string $keyA Key in first array.
     * @param array $b Second array.
     * @param int|string $keyB Key in second array.
     *
     * @return bool True if both elements share the same reference.
     */
    public static function isSameReference(array &$a, int|string $keyA, array &$b, int|string $keyB): bool
    {
        $refA = self::getArrayElementReference($a, $keyA);
        $refB = self::getArrayElementReference($b, $keyB);

        if ($refA === null || $refB === null) {
            return false;
        }

        return $refA->getId() === $refB->getId();
    }

    /**
     * Extract all type name strings from any ReflectionType (Named, Union, Intersection).
     *
     * @param ReflectionType|null $type The type to extract names from.
     *
     * @return array<string> Flat array of type name strings.
     */
    public static function getAllTypeNames(?ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof ReflectionNamedType) {
            return [$type->getName()];
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $names = [];
            foreach ($type->getTypes() as $subType) {
                $names = array_merge($names, self::getAllTypeNames($subType));
            }
            return $names;
        }

        return [];
    }

    /**
     * Check if a property type allows null.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool True if the property type is nullable.
     */
    public static function isNullablePropertyType(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $type = $ref->getProperty($propertyName)->getType();

        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    /**
     * Check if a property type is a builtin type.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool True if the property type is builtin.
     */
    public static function isBuiltinPropertyType(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $type = $ref->getProperty($propertyName)->getType();

        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin();
        }

        return false;
    }

    /**
     * Get all type names from a method's return type (handles union/intersection).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return array<string> Array of return type name strings.
     */
    public static function getReturnTypeNames(object|string $objectOrClass, string $methodName): array
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return self::getAllTypeNames($ref->getReturnType());
    }

    /**
     * Check if a method return type is void.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isReturnTypeVoid(object|string $objectOrClass, string $methodName): bool
    {
        return self::getReturnTypeNames($objectOrClass, $methodName) === ['void'];
    }

    /**
     * Check if a method return type is never.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isReturnTypeNever(object|string $objectOrClass, string $methodName): bool
    {
        return self::getReturnTypeNames($objectOrClass, $methodName) === ['never'];
    }

    /**
     * Check if a method return type is mixed.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isReturnTypeMixed(object|string $objectOrClass, string $methodName): bool
    {
        return self::getReturnTypeNames($objectOrClass, $methodName) === ['mixed'];
    }

    /**
     * Check if a method return type allows null.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isReturnTypeNullable(object|string $objectOrClass, string $methodName): bool
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $type = $ref->getReturnType();

        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    /**
     * Check if a ReflectionType is a union type.
     *
     * @param ReflectionType|null $type The type to check.
     *
     * @return bool
     */
    public static function isUnionType(?ReflectionType $type): bool
    {
        return $type instanceof ReflectionUnionType;
    }

    /**
     * Check if a ReflectionType is an intersection type.
     *
     * @param ReflectionType|null $type The type to check.
     *
     * @return bool
     */
    public static function isIntersectionType(?ReflectionType $type): bool
    {
        return $type instanceof ReflectionIntersectionType;
    }

    /**
     * Get all type names from a parameter (handles union/intersection/named).
     *
     * @param ReflectionParameter $parameter The parameter.
     *
     * @return array<string> Array of type name strings.
     */
    public static function getParameterTypeNames(ReflectionParameter $parameter): array
    {
        return self::getAllTypeNames($parameter->getType());
    }

    /**
     * Resolve a ReflectionType to an array of class names for dependency injection.
     * Filters out builtin types and returns only class/interface names.
     *
     * @param ReflectionType|null $type The type to resolve.
     *
     * @return array<string> Array of fully qualified class names.
     */
    public static function resolveTypeToClassNames(?ReflectionType $type): array
    {
        if ($type === null) {
            return [];
        }

        if ($type instanceof ReflectionNamedType) {
            return $type->isBuiltin() ? [] : [$type->getName()];
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            $classes = [];
            foreach ($type->getTypes() as $subType) {
                if ($subType instanceof ReflectionNamedType && !$subType->isBuiltin()) {
                    $classes[] = $subType->getName();
                }
            }
            return $classes;
        }

        return [];
    }

    /**
     * Get all constructor-promoted properties (PHP 8.0+).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<ReflectionProperty> Array of promoted ReflectionProperty objects.
     */
    public static function getPromotedProperties(object|string $objectOrClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        return array_values(array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => $p->isPromoted()
        ));
    }

    /**
     * Get all readonly properties of a class (PHP 8.1+).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<ReflectionProperty> Array of readonly ReflectionProperty objects.
     */
    public static function getReadonlyPropertiesList(object|string $objectOrClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        return array_values(array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => $p->isReadOnly()
        ));
    }

    /**
     * Get properties that are not yet initialized on an object instance.
     *
     * @param object $object The object instance.
     * @param int|null $filter Optional ReflectionProperty filter bitmask.
     *
     * @return array<ReflectionProperty> Array of uninitialized ReflectionProperty objects.
     */
    public static function getUninitializedProperties(object $object, ?int $filter = null): array
    {
        $ref = new ReflectionClass($object);
        $properties = $filter !== null ? $ref->getProperties($filter) : $ref->getProperties();
        $result = [];

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!$property->isInitialized($object)) {
                $result[] = $property;
            }
        }

        return $result;
    }

    /**
     * Get methods that override a parent class method.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<ReflectionMethod> Array of overriding ReflectionMethod objects.
     */
    public static function getOverriddenMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $parent = $ref->getParentClass();

        if ($parent === false) {
            return [];
        }

        $result = [];
        foreach ($ref->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }

            if ($parent->hasMethod($method->getName())) {
                $result[] = $method;
            }
        }

        return $result;
    }

    /**
     * Get methods inherited from parent classes (not declared in the class itself).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<ReflectionMethod> Array of inherited ReflectionMethod objects.
     */
    public static function getInheritedMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $className = $ref->getName();

        return array_values(array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $m) => $m->getDeclaringClass()->getName() !== $className
        ));
    }

    /**
     * Get properties inherited from parent classes (not declared in the class itself).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<ReflectionProperty> Array of inherited ReflectionProperty objects.
     */
    public static function getInheritedProperties(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $className = $ref->getName();

        return array_values(array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => $p->getDeclaringClass()->getName() !== $className
        ));
    }

    /**
     * Map each method name to the trait it originates from.
     * Only includes methods that come from traits.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string, string> Method name => trait name pairs.
     */
    public static function getTraitMethodOrigins(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $origins = [];

        foreach ($ref->getTraits() as $trait) {
            foreach ($trait->getMethods() as $method) {
                $origins[$method->getName()] = $trait->getName();
            }
        }

        return $origins;
    }

    /**
     * Get the names of abstract methods in a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of abstract method name strings.
     */
    public static function getAbstractMethodNames(object|string $objectOrClass): array
    {
        return array_map(
            fn(ReflectionMethod $m) => $m->getName(),
            self::getAbstractMethods($objectOrClass)
        );
    }

    /**
     * Get the names of static methods in a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of static method name strings.
     */
    public static function getStaticMethodNames(object|string $objectOrClass): array
    {
        return array_map(
            fn(ReflectionMethod $m) => $m->getName(),
            self::getStaticMethods($objectOrClass)
        );
    }

    /**
     * Get the names of all static properties in a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of static property name strings.
     */
    public static function getStaticPropertyNames(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        return array_map(
            fn(ReflectionProperty $p) => $p->getName(),
            array_filter($ref->getProperties(), fn(ReflectionProperty $p) => $p->isStatic())
        );
    }

    /**
     * Diff methods between two classes. Returns methods present in classA but not in classB.
     *
     * @param object|string $classA The first class.
     * @param object|string $classB The second class.
     *
     * @return array<string> Array of method names in classA but not classB.
     */
    public static function diffClassMethods(object|string $classA, object|string $classB): array
    {
        $methodsA = self::getMethodNames($classA);
        $methodsB = self::getMethodNames($classB);

        return array_values(array_diff($methodsA, $methodsB));
    }

    /**
     * Diff properties between two classes. Returns properties present in classA but not in classB.
     *
     * @param object|string $classA The first class.
     * @param object|string $classB The second class.
     *
     * @return array<string> Array of property names in classA but not classB.
     */
    public static function diffClassProperties(object|string $classA, object|string $classB): array
    {
        $propsA = self::getPropertyNames($classA);
        $propsB = self::getPropertyNames($classB);

        return array_values(array_diff($propsA, $propsB));
    }

    /**
     * Get the depth of the inheritance chain (number of parent classes).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return int The depth (0 for a class with no parent).
     */
    public static function getClassDepth(object|string $objectOrClass): int
    {
        $depth = 0;
        $ref = new ReflectionClass($objectOrClass);

        while ($ref = $ref->getParentClass()) {
            $depth++;
        }

        return $depth;
    }

    /**
     * Get the number of source code lines in a class definition.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return int Line count, or 0 if unavailable.
     */
    public static function getClassLineCount(object|string $objectOrClass): int
    {
        $ref = new ReflectionClass($objectOrClass);
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        if ($start === false || $end === false) {
            return 0;
        }

        return $end - $start + 1;
    }

    /**
     * Get all traits used by a class and all its ancestors recursively.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Unique array of trait names.
     */
    public static function getAllAncestorTraits(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $traits = [];

        do {
            foreach ($ref->getTraitNames() as $traitName) {
                $traits[] = $traitName;
                $traitRef = new ReflectionClass($traitName);
                foreach ($traitRef->getTraitNames() as $subTrait) {
                    $traits[] = $subTrait;
                }
            }
        } while ($ref = $ref->getParentClass());

        return array_values(array_unique($traits));
    }

    /**
     * Get a summary of the public API (public methods and properties) of a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array{methods: array<string>, properties: array<string>, constants: array<string>}
     */
    public static function getPublicApi(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);

        return [
            'methods' => array_map(
                fn(ReflectionMethod $m) => $m->getName(),
                $ref->getMethods(ReflectionMethod::IS_PUBLIC)
            ),
            'properties' => array_map(
                fn(ReflectionProperty $p) => $p->getName(),
                $ref->getProperties(ReflectionProperty::IS_PUBLIC)
            ),
            'constants' => array_keys(
                OperationSystem::comparePHPVersion('8.0.0', '>=')
                    ? $ref->getConstants(ReflectionClassConstant::IS_PUBLIC)
                    : $ref->getConstants()
            ),
        ];
    }

    /**
     * Check if a class has any parent class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool True if the class has a parent.
     */
    public static function classHasParent(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->getParentClass() !== false;
    }

    /**
     * Check if a class extends a specific parent class or implements a specific interface.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $target The target class or interface name.
     *
     * @return bool
     */
    public static function classExtendsOrImplements(object|string $objectOrClass, string $target): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if ($ref->isSubclassOf($target)) {
            return true;
        }

        if (interface_exists($target) && $ref->implementsInterface($target)) {
            return true;
        }

        return false;
    }

    /**
     * Get interface methods that are not yet implemented by a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of unimplemented method names.
     */
    public static function getUnimplementedInterfaceMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $ownMethods = array_map(fn(ReflectionMethod $m) => $m->getName(), $ref->getMethods());
        $unimplemented = [];

        foreach ($ref->getInterfaces() as $interface) {
            foreach ($interface->getMethods() as $method) {
                if (!in_array($method->getName(), $ownMethods, true)) {
                    $unimplemented[] = $method->getName();
                }
            }
        }

        return array_values(array_unique($unimplemented));
    }

    /**
     * Get the modifier bitmask of a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return int The modifier bitmask.
     */
    public static function getClassModifiers(object|string $objectOrClass): int
    {
        return (new ReflectionClass($objectOrClass))->getModifiers();
    }

    /**
     * Populate an object's properties from an associative array.
     * Handles private/protected properties via reflection.
     *
     * @param object $object The object to hydrate.
     * @param array<string, mixed> $data Property name => value pairs.
     *
     * @return void
     */
    public static function hydrateObject(object $object, array $data): void
    {
        $ref = new ReflectionClass($object);

        foreach ($data as $name => $value) {
            if (!$ref->hasProperty($name)) {
                continue;
            }

            $property = $ref->getProperty($name);

            if (method_exists($property, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }

            $property->setValue($object, $value);
        }
    }

    /**
     * Copy property values from source object to target object.
     * Only copies properties that exist in both objects.
     *
     * @param object $source The source object.
     * @param object $target The target object.
     * @param int|null $filter Optional ReflectionProperty filter bitmask.
     *
     * @return void
     */
    public static function copyProperties(object $source, object $target, ?int $filter = null): void
    {
        $sourceRef = new ReflectionClass($source);
        $targetRef = new ReflectionClass($target);
        $properties = $filter !== null ? $sourceRef->getProperties($filter) : $sourceRef->getProperties();

        foreach ($properties as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (!$targetRef->hasProperty($property->getName())) {
                continue;
            }

            if (method_exists($property, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }

            $targetProp = $targetRef->getProperty($property->getName());

            if (method_exists($targetProp, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $targetProp->setAccessible(true);
            }

            if ($property->isInitialized($source)) {
                $targetProp->setValue($target, $property->getValue($source));
            }
        }
    }

    /**
     * Compare two objects by their property values.
     *
     * @param object $a First object.
     * @param object $b Second object.
     *
     * @return bool True if all shared property values are equal.
     */
    public static function objectEquals(object $a, object $b): bool
    {
        if ($a::class !== $b::class) {
            return false;
        }

        $ref = new ReflectionClass($a);

        foreach ($ref->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if (method_exists($property, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
                // @phpstan-ignore-next-line
                $property->setAccessible(true);
            }

            if ($property->getValue($a) !== $property->getValue($b)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get uninitialized property names on an object.
     *
     * @param object $object The object instance.
     *
     * @return array<string> Array of uninitialized property name strings.
     */
    public static function getUninitializedPropertyNames(object $object): array
    {
        return array_map(
            fn(ReflectionProperty $p) => $p->getName(),
            self::getUninitializedProperties($object)
        );
    }

    /**
     * Get dynamic (undeclared) properties on an object.
     *
     * @param object $object The object instance.
     *
     * @return array<string, mixed> Property name => value pairs for dynamic properties.
     */
    public static function getObjectDynamicProperties(object $object): array
    {
        $ref = new ReflectionObject($object);
        $declared = array_map(fn(ReflectionProperty $p) => $p->getName(), $ref->getProperties());
        $all = (array) $object;
        $dynamic = [];

        foreach ($all as $key => $value) {
            $cleanKey = preg_replace('/[\x00].*[\x00]/', '', $key);
            if (!in_array($cleanKey, $declared, true)) {
                $dynamic[$cleanKey] = $value;
            }
        }

        return $dynamic;
    }

    /**
     * Get instantiated attribute objects from a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string|null $attributeName Optional attribute class name to filter.
     *
     * @return array<object> Array of attribute instances.
     */
    public static function getClassAttributeInstances(object|string $objectOrClass, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            $ref->getAttributes($attributeName)
        );
    }

    /**
     * Get instantiated attribute objects from a method.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     * @param string|null $attributeName Optional attribute class name to filter.
     *
     * @return array<object> Array of attribute instances.
     */
    public static function getMethodAttributeInstances(object|string $objectOrClass, string $methodName, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = self::createFromMethodName($objectOrClass, $methodName);
        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            $ref->getAttributes($attributeName)
        );
    }

    /**
     * Get instantiated attribute objects from a property.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     * @param string|null $attributeName Optional attribute class name to filter.
     *
     * @return array<object> Array of attribute instances.
     */
    public static function getPropertyAttributeInstances(object|string $objectOrClass, string $propertyName, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return [];
        }

        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            $ref->getProperty($propertyName)->getAttributes($attributeName)
        );
    }

    /**
     * Get instantiated attribute objects from a parameter.
     *
     * @param ReflectionParameter $parameter The parameter.
     * @param string|null $attributeName Optional attribute class name to filter.
     *
     * @return array<object> Array of attribute instances.
     */
    public static function getParameterAttributeInstances(ReflectionParameter $parameter, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            $parameter->getAttributes($attributeName)
        );
    }

    /**
     * Get instantiated attribute objects from a class constant.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $constantName The constant name.
     * @param string|null $attributeName Optional attribute class name to filter.
     *
     * @return array<object> Array of attribute instances.
     */
    public static function getConstantAttributeInstances(object|string $objectOrClass, string $constantName, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        $constant = $ref->getReflectionConstant($constantName);

        if ($constant === false) {
            return [];
        }

        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            $constant->getAttributes($attributeName)
        );
    }

    /**
     * Get methods that have a specific return type name.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $typeName The return type name to filter by.
     *
     * @return array<ReflectionMethod> Array of matching ReflectionMethod objects.
     */
    public static function getMethodsByReturnType(object|string $objectOrClass, string $typeName): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $result = [];

        foreach ($ref->getMethods() as $method) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === $typeName) {
                $result[] = $method;
            }
        }

        return $result;
    }

    /**
     * Get methods that have a specific attribute.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $attributeName The attribute class name.
     *
     * @return array<ReflectionMethod> Array of matching ReflectionMethod objects.
     */
    public static function getMethodsByAttribute(object|string $objectOrClass, string $attributeName): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        return array_values(array_filter(
            $ref->getMethods(),
            fn(ReflectionMethod $m) => count($m->getAttributes($attributeName)) > 0
        ));
    }

    /**
     * Get properties that have a specific attribute.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $attributeName The attribute class name.
     *
     * @return array<ReflectionProperty> Array of matching ReflectionProperty objects.
     */
    public static function getPropertiesByAttribute(object|string $objectOrClass, string $attributeName): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        return array_values(array_filter(
            $ref->getProperties(),
            fn(ReflectionProperty $p) => count($p->getAttributes($attributeName)) > 0
        ));
    }

    /**
     * Get methods filtered by visibility string ('public', 'protected', 'private').
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $visibility The visibility string.
     *
     * @return array<ReflectionMethod> Array of matching ReflectionMethod objects.
     */
    public static function getMethodsByVisibility(object|string $objectOrClass, string $visibility): array
    {
        $filter = match ($visibility) {
            'public' => ReflectionMethod::IS_PUBLIC,
            'protected' => ReflectionMethod::IS_PROTECTED,
            'private' => ReflectionMethod::IS_PRIVATE,
            default => throw new InvalidArgumentException("Invalid visibility: $visibility"),
        };

        return (new ReflectionClass($objectOrClass))->getMethods($filter);
    }

    /**
     * Check if a method is declared directly in the class (not inherited).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isMethodDeclaredInClass(object|string $objectOrClass, string $methodName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasMethod($methodName)) {
            return false;
        }

        return $ref->getMethod($methodName)->getDeclaringClass()->getName() === $ref->getName();
    }

    /**
     * Check if a method overrides a parent class method.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return bool
     */
    public static function isMethodOverriding(object|string $objectOrClass, string $methodName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasMethod($methodName)) {
            return false;
        }

        if ($ref->getMethod($methodName)->getDeclaringClass()->getName() !== $ref->getName()) {
            return false;
        }

        $parent = $ref->getParentClass();
        return $parent !== false && $parent->hasMethod($methodName);
    }

    /**
     * Get the parent method that a method overrides.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return ReflectionMethod|null The parent method, or null if not overriding.
     */
    public static function getOverriddenParentMethod(object|string $objectOrClass, string $methodName): ?ReflectionMethod
    {
        if (!self::isMethodOverriding($objectOrClass, $methodName)) {
            return null;
        }

        $parent = (new ReflectionClass($objectOrClass))->getParentClass();
        return $parent !== false && $parent->hasMethod($methodName) ? $parent->getMethod($methodName) : null;
    }

    /**
     * Get the declaring class name of a method.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return string The declaring class name.
     */
    public static function getMethodDeclaringClassName(object|string $objectOrClass, string $methodName): string
    {
        return self::createFromMethodName($objectOrClass, $methodName)->getDeclaringClass()->getName();
    }

    /**
     * Check if a closure is bound to an object.
     *
     * @param Closure $closure The closure.
     *
     * @return bool
     */
    public static function isClosureBound(Closure $closure): bool
    {
        $ref = new ReflectionFunction($closure);
        return $ref->getClosureThis() !== null;
    }

    /**
     * Get the return type of a closure as a ReflectionType.
     *
     * @param Closure $closure The closure.
     *
     * @return ReflectionType|null
     */
    public static function getClosureReturnType(Closure $closure): ?ReflectionType
    {
        return (new ReflectionFunction($closure))->getReturnType();
    }

    /**
     * Get the parameter count of a closure.
     *
     * @param Closure $closure The closure.
     *
     * @return int
     */
    public static function getClosureParameterCount(Closure $closure): int
    {
        return (new ReflectionFunction($closure))->getNumberOfParameters();
    }

    /**
     * Rebind a closure to a new object and/or scope class.
     *
     * @param Closure $closure The closure to rebind.
     * @param object|null $newThis The new $this for the closure.
     * @param object|string|null $newScope The new scope class.
     *
     * @return Closure|null The rebound closure, or null on failure.
     */
    public static function rebindClosure(Closure $closure, ?object $newThis, object|string|null $newScope = null): ?Closure
    {
        if ($newScope === null && $newThis !== null) {
            $newScope = $newThis::class;
        }

        return Closure::bind($closure, $newThis, $newScope);
    }

    /**
     * Get the number of required parameters of a closure.
     *
     * @param Closure $closure The closure.
     *
     * @return int
     */
    public static function getClosureRequiredParameterCount(Closure $closure): int
    {
        return (new ReflectionFunction($closure))->getNumberOfRequiredParameters();
    }

    /**
     * Check if a closure has a return type declaration.
     *
     * @param Closure $closure The closure.
     *
     * @return bool
     */
    public static function closureHasReturnType(Closure $closure): bool
    {
        return (new ReflectionFunction($closure))->hasReturnType();
    }

    /**
     * Read the source code of a class from its file.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return string|null The source code, or null if file not found.
     */
    public static function getClassSourceCode(object|string $objectOrClass): ?string
    {
        $ref = new ReflectionClass($objectOrClass);
        $file = $ref->getFileName();
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        if ($file === false || $start === false || $end === false || !file_exists($file)) {
            return null;
        }

        $lines = file($file);
        return implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }

    /**
     * Extract the source code of a method from its file.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return string|null The method source code, or null if unavailable.
     */
    public static function getMethodSourceCode(object|string $objectOrClass, string $methodName): ?string
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $file = $ref->getFileName();
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        if ($file === false || $start === false || $end === false || !file_exists($file)) {
            return null;
        }

        $lines = file($file);
        return implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }

    /**
     * Extract the source code of a function or closure from its file.
     *
     * @param Closure|string $function The function or closure.
     *
     * @return string|null The function source code, or null if unavailable.
     */
    public static function getFunctionSourceCode(Closure|string $function): ?string
    {
        $ref = new ReflectionFunction($function);
        $file = $ref->getFileName();
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        if ($file === false || $start === false || $end === false || !file_exists($file)) {
            return null;
        }

        $lines = file($file);
        return implode('', array_slice($lines, $start - 1, $end - $start + 1));
    }

    /**
     * Get the number of source code lines in a method.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return int Line count, or 0 if unavailable.
     */
    public static function getMethodLineCount(object|string $objectOrClass, string $methodName): int
    {
        $ref = self::createFromMethodName($objectOrClass, $methodName);
        $start = $ref->getStartLine();
        $end = $ref->getEndLine();

        if ($start === false || $end === false) {
            return 0;
        }

        return $end - $start + 1;
    }

    /**
     * Invoke a method with named (associative) arguments.
     *
     * @param object $object The object instance.
     * @param string $methodName The method name.
     * @param array<string, mixed> $namedArgs Named arguments.
     *
     * @return mixed
     *
     * @throws InvalidArgumentException If a required argument is missing.
     */
    public static function invokeMethodWithNamedArgs(object $object, string $methodName, array $namedArgs = []): mixed
    {
        $ref = self::createFromMethodName($object, $methodName);

        if (method_exists($ref, 'setAccessible') && version_compare(PHP_VERSION, '8.1.0', '<')) {
            // @phpstan-ignore-next-line
            $ref->setAccessible(true);
        }

        $args = [];
        foreach ($ref->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $namedArgs)) {
                $args[] = $namedArgs[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->isVariadic()) {
                break;
            } else {
                throw new InvalidArgumentException("Missing required argument: $name");
            }
        }

        return $ref->invokeArgs($object, $args);
    }

    /**
     * Create an instance of a class using named constructor arguments.
     *
     * @param string $className The class name.
     * @param array<string, mixed> $namedArgs Named constructor arguments.
     *
     * @return object
     *
     * @throws InvalidArgumentException If a required argument is missing.
     */
    public static function createInstanceFromMap(string $className, array $namedArgs = []): object
    {
        $ref = new ReflectionClass($className);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return $ref->newInstance();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            if (array_key_exists($name, $namedArgs)) {
                $args[] = $namedArgs[$name];
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif ($param->isVariadic()) {
                break;
            } else {
                throw new InvalidArgumentException("Missing required constructor argument: $name");
            }
        }

        return $ref->newInstanceArgs($args);
    }

    /**
     * Invoke a closure with an array of arguments.
     *
     * @param Closure $closure The closure to invoke.
     * @param array $args Arguments array.
     *
     * @return mixed
     */
    public static function invokeClosureWithArgs(Closure $closure, array $args = []): mixed
    {
        return (new ReflectionFunction($closure))->invokeArgs($args);
    }

    /**
     * Find a backed enum case by its backing value.
     *
     * @param string $enumClass The fully qualified enum class name.
     * @param int|string $value The backing value to search for.
     *
     * @return object|null The enum case, or null if not found.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumCaseByValue(string $enumClass, int|string $value): ?object
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);

        if (!$ref->isBacked()) {
            return null;
        }

        foreach ($ref->getCases() as $case) {
            if ($case instanceof ReflectionEnumBackedCase && $case->getBackingValue() === $value) {
                return $case->getValue();
            }
        }

        return null;
    }

    /**
     * Check if an enum has a specific method.
     *
     * @param string $enumClass The fully qualified enum class name.
     * @param string $methodName The method name.
     *
     * @return bool
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function enumHasMethod(string $enumClass, string $methodName): bool
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return (new ReflectionEnum($enumClass))->hasMethod($methodName);
    }

    /**
     * Get attributes of a specific enum case.
     *
     * @param string $enumClass The fully qualified enum class name.
     * @param string $caseName The case name.
     * @param string|null $attributeName Optional attribute class to filter.
     *
     * @return array<ReflectionAttribute> Array of ReflectionAttribute objects.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumCaseAttributes(string $enumClass, string $caseName, ?string $attributeName = null): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        $ref = new ReflectionEnum($enumClass);

        if (!$ref->hasCase($caseName)) {
            return [];
        }

        return $ref->getCase($caseName)->getAttributes($attributeName);
    }

    /**
     * Get all constant values of an enum (both regular constants and cases).
     *
     * @param string $enumClass The fully qualified enum class name.
     *
     * @return array<string, mixed> Constant name => value pairs.
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumConstants(string $enumClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return (new ReflectionEnum($enumClass))->getConstants();
    }

    /**
     * Get the number of cases in an enum.
     *
     * @param string $enumClass The fully qualified enum class name.
     *
     * @return int
     *
     * @throws Exception If PHP version is below 8.1.
     */
    public static function getEnumCaseCount(string $enumClass): int
    {
        if (!OperationSystem::comparePHPVersion('8.1.0', '>=')) {
            throw new Exception('Enums require PHP 8.1 or higher.');
        }

        return count((new ReflectionEnum($enumClass))->getCases());
    }

    /**
     * Check if a property has hooks defined (PHP 8.4+).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool True if the property has any hooks.
     */
    public static function isPropertyHooked(object|string $objectOrClass, string $propertyName): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'hasHooks')) {
            return false;
        }

        return $property->hasHooks();
    }

    /**
     * Get the hooks defined on a property (PHP 8.4+).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return array Hook reflections, or empty array.
     */
    public static function getPropertyHooks(object|string $objectOrClass, string $propertyName): array
    {
        if (PHP_VERSION_ID < 80400) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return [];
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'getHooks')) {
            return [];
        }

        return $property->getHooks();
    }

    /**
     * Check if a property is virtual (has hooks but no backing store, PHP 8.4+).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool
     */
    public static function isVirtualProperty(object|string $objectOrClass, string $propertyName): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'isVirtual')) {
            return false;
        }

        return $property->isVirtual();
    }

    /**
     * Check if a property is declared as abstract (PHP 8.4+).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool
     */
    public static function isAbstractProperty(object|string $objectOrClass, string $propertyName): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'isAbstract')) {
            return false;
        }

        return $property->isAbstract();
    }

    /**
     * Check if a property is declared as final (PHP 8.4+).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool
     */
    public static function isFinalProperty(object|string $objectOrClass, string $propertyName): bool
    {
        if (PHP_VERSION_ID < 80400) {
            return false;
        }

        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $property = $ref->getProperty($propertyName);

        if (!method_exists($property, 'isFinal')) {
            return false;
        }

        return $property->isFinal();
    }

    /**
     * Check if two method signatures are compatible (same parameter types and return type).
     *
     * @param ReflectionMethod $a First method.
     * @param ReflectionMethod $b Second method.
     *
     * @return bool True if signatures match.
     */
    public static function methodSignaturesMatch(ReflectionMethod $a, ReflectionMethod $b): bool
    {
        if ($a->getNumberOfParameters() !== $b->getNumberOfParameters()) {
            return false;
        }

        $paramsA = $a->getParameters();
        $paramsB = $b->getParameters();

        for ($i = 0, $c = count($paramsA); $i < $c; $i++) {
            $typeA = $paramsA[$i]->getType();
            $typeB = $paramsB[$i]->getType();

            if (!self::typesEqual($typeA, $typeB)) {
                return false;
            }
        }

        return self::typesEqual($a->getReturnType(), $b->getReturnType());
    }

    /**
     * Compare two ReflectionType objects for equality.
     *
     * @param ReflectionType|null $a First type.
     * @param ReflectionType|null $b Second type.
     *
     * @return bool True if both types represent the same type.
     */
    public static function typesEqual(?ReflectionType $a, ?ReflectionType $b): bool
    {
        if ($a === null && $b === null) {
            return true;
        }

        if ($a === null || $b === null) {
            return false;
        }

        return (string) $a === (string) $b;
    }

    /**
     * Find the trait that declares a specific method on a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return string|null The trait name, or null if not from a trait.
     */
    public static function getDeclaringTraitForMethod(object|string $objectOrClass, string $methodName): ?string
    {
        $ref = new ReflectionClass($objectOrClass);

        foreach ($ref->getTraits() as $trait) {
            if ($trait->hasMethod($methodName)) {
                return $trait->getName();
            }
        }

        return null;
    }

    /**
     * Find the trait that declares a specific property on a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return string|null The trait name, or null if not from a trait.
     */
    public static function getDeclaringTraitForProperty(object|string $objectOrClass, string $propertyName): ?string
    {
        $ref = new ReflectionClass($objectOrClass);

        foreach ($ref->getTraits() as $trait) {
            if ($trait->hasProperty($propertyName)) {
                return $trait->getName();
            }
        }

        return null;
    }

    /**
     * Check if a class has a destructor method.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function hasDestructor(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->hasMethod('__destruct');
    }

    /**
     * Get the destructor ReflectionMethod if it exists.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return ReflectionMethod|null
     */
    public static function getDestructor(object|string $objectOrClass): ?ReflectionMethod
    {
        $ref = new ReflectionClass($objectOrClass);
        return $ref->hasMethod('__destruct') ? $ref->getMethod('__destruct') : null;
    }

    /**
     * Get the names of constructor-promoted parameters.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of promoted parameter name strings.
     */
    public static function getConstructorPromotedParameterNames(object|string $objectOrClass): array
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        $constructor = $ref->getConstructor();

        if ($constructor === null) {
            return [];
        }

        return array_map(
            fn(ReflectionParameter $p) => $p->getName(),
            array_filter(
                $constructor->getParameters(),
                fn(ReflectionParameter $p) => $p->isPromoted()
            )
        );
    }

    /**
     * Get the constructor ReflectionMethod if it exists.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return ReflectionMethod|null
     */
    public static function getConstructor(object|string $objectOrClass): ?ReflectionMethod
    {
        return (new ReflectionClass($objectOrClass))->getConstructor();
    }

    /**
     * Get the names of all currently loaded PHP extensions.
     *
     * @param bool $zendExtensions If true, return Zend extensions instead.
     *
     * @return array<string>
     */
    public static function getLoadedExtensionNames(bool $zendExtensions = false): array
    {
        return get_loaded_extensions($zendExtensions);
    }

    /**
     * Get a printable information dump string for a PHP extension.
     *
     * @param string $extensionName The extension name.
     *
     * @return string The extension info string.
     */
    public static function getExtensionInfoString(string $extensionName): string
    {
        $ref = new ReflectionExtension($extensionName);
        ob_start();
        $ref->info();
        return ob_get_clean() ?: '';
    }

    /**
     * Get the constants of a specific interface.
     *
     * @param string $interfaceName The interface name.
     *
     * @return array<string, mixed> Constant name => value pairs.
     *
     * @throws Exception If interface does not exist.
     */
    public static function getInterfaceConstants(string $interfaceName): array
    {
        if (!interface_exists($interfaceName)) {
            throw new Exception("Interface $interfaceName does not exist");
        }

        return (new ReflectionClass($interfaceName))->getConstants();
    }

    /**
     * Create a new WeakMap instance.
     *
     * @return WeakMap
     *
     * @throws Exception If PHP version is below 8.0.
     */
    public static function createWeakMap(): WeakMap
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            throw new Exception('WeakMap requires PHP 8.0 or higher.');
        }

        return new WeakMap();
    }

    /**
     * Check if a property allows null values (by class and property name).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool
     */
    public static function isPropertyNullable(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        $type = $ref->getProperty($propertyName)->getType();

        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    /**
     * Check if a property has a default value (by class and property name).
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $propertyName The property name.
     *
     * @return bool
     */
    public static function hasPropertyDefaultValue(object|string $objectOrClass, string $propertyName): bool
    {
        $ref = new ReflectionClass($objectOrClass);

        if (!$ref->hasProperty($propertyName)) {
            return false;
        }

        return $ref->getProperty($propertyName)->hasDefaultValue();
    }

    /**
     * Get the raw ReflectionType of a method's return type.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return ReflectionType|null
     */
    public static function getMethodReturnTypeObject(object|string $objectOrClass, string $methodName): ?ReflectionType
    {
        return self::createFromMethodName($objectOrClass, $methodName)->getReturnType();
    }

    /**
     * Get the class name of a runtime object instance.
     *
     * @param object $object The object instance.
     *
     * @return string The class name.
     */
    public static function getObjectClassName(object $object): string
    {
        return (new ReflectionObject($object))->getName();
    }

    /**
     * Get the short name (without namespace) of a runtime object.
     *
     * @param object $object The object instance.
     *
     * @return string The short class name.
     */
    public static function getObjectShortName(object $object): string
    {
        return (new ReflectionObject($object))->getShortName();
    }

    /**
     * Get the namespace of a runtime object.
     *
     * @param object $object The object instance.
     *
     * @return string The namespace name.
     */
    public static function getObjectNamespace(object $object): string
    {
        return (new ReflectionObject($object))->getNamespaceName();
    }

    /**
     * Get the file where a runtime object's class is defined.
     *
     * @param object $object The object instance.
     *
     * @return string|false The file path, or false for internal classes.
     */
    public static function getObjectFileName(object $object): string|false
    {
        return (new ReflectionObject($object))->getFileName();
    }

    /**
     * Check if a runtime object's class is anonymous.
     *
     * @param object $object The object instance.
     *
     * @return bool
     */
    public static function isObjectAnonymous(object $object): bool
    {
        return (new ReflectionObject($object))->isAnonymous();
    }

    /**
     * Get the parent class name of a runtime object, or null.
     *
     * @param object $object The object instance.
     *
     * @return string|null
     */
    public static function getObjectParentClassName(object $object): ?string
    {
        $parent = (new ReflectionObject($object))->getParentClass();
        return $parent !== false ? $parent->getName() : null;
    }

    /**
     * Get all interface names implemented by a runtime object.
     *
     * @param object $object The object instance.
     *
     * @return array<string>
     */
    public static function getObjectInterfaceNames(object $object): array
    {
        return (new ReflectionObject($object))->getInterfaceNames();
    }

    /**
     * Get all trait names used by a runtime object's class.
     *
     * @param object $object The object instance.
     *
     * @return array<string>
     */
    public static function getObjectTraitNames(object $object): array
    {
        return (new ReflectionObject($object))->getTraitNames();
    }

    /**
     * Get all traits used by a class recursively (including sub-traits of traits).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Unique array of all trait names.
     */
    public static function getRecursiveTraits(object|string $objectOrClass): array
    {
        $collected = [];
        $queue = class_uses($objectOrClass) ?: [];

        while (!empty($queue)) {
            $trait = array_shift($queue);
            if (isset($collected[$trait])) {
                continue;
            }
            $collected[$trait] = true;
            $subTraits = class_uses($trait) ?: [];
            foreach ($subTraits as $sub) {
                $queue[] = $sub;
            }
        }

        return array_keys($collected);
    }

    /**
     * Get a map of method name => attribute instances for all methods in a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string|null $attributeName Optional attribute class to filter.
     *
     * @return array<string, object[]> Method name => array of attribute instances.
     */
    public static function getMethodAnnotationMap(object|string $objectOrClass, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        $map = [];

        foreach ($ref->getMethods() as $method) {
            $attrs = $method->getAttributes($attributeName);
            if (!empty($attrs)) {
                $map[$method->getName()] = array_map(
                    fn(ReflectionAttribute $a) => $a->newInstance(),
                    $attrs
                );
            }
        }

        return $map;
    }

    /**
     * Get a map of property name => attribute instances for all properties in a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string|null $attributeName Optional attribute class to filter.
     *
     * @return array<string, object[]> Property name => array of attribute instances.
     */
    public static function getPropertyAnnotationMap(object|string $objectOrClass, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        $ref = new ReflectionClass($objectOrClass);
        $map = [];

        foreach ($ref->getProperties() as $property) {
            $attrs = $property->getAttributes($attributeName);
            if (!empty($attrs)) {
                $map[$property->getName()] = array_map(
                    fn(ReflectionAttribute $a) => $a->newInstance(),
                    $attrs
                );
            }
        }

        return $map;
    }

    /**
     * Check if a ReflectionNamedType is a builtin type.
     *
     * @param ReflectionType|null $type The type to check.
     *
     * @return bool
     */
    public static function isTypeBuiltin(?ReflectionType $type): bool
    {
        return $type instanceof ReflectionNamedType && $type->isBuiltin();
    }

    /**
     * Get the name of a ReflectionNamedType, or null for non-named types.
     *
     * @param ReflectionType|null $type The type.
     *
     * @return string|null
     */
    public static function getTypeName(?ReflectionType $type): ?string
    {
        return $type instanceof ReflectionNamedType ? $type->getName() : null;
    }

    /**
     * Check if a type allows null.
     *
     * @param ReflectionType|null $type The type.
     *
     * @return bool
     */
    public static function isTypeNullable(?ReflectionType $type): bool
    {
        if ($type === null) {
            return true;
        }

        return $type->allowsNull();
    }

    /**
     * Get attributes of a function or closure.
     *
     * @param Closure|string $function The function.
     * @param string|null $attributeName Optional attribute class to filter.
     *
     * @return array<ReflectionAttribute>
     */
    public static function getFunctionAttributes(Closure|string $function, ?string $attributeName = null): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            return [];
        }

        return (new ReflectionFunction($function))->getAttributes($attributeName);
    }

    /**
     * Get instantiated attribute objects from a function or closure.
     *
     * @param Closure|string $function The function.
     * @param string|null $attributeName Optional attribute class to filter.
     *
     * @return array<object>
     */
    public static function getFunctionAttributeInstances(Closure|string $function, ?string $attributeName = null): array
    {
        return array_map(
            fn(ReflectionAttribute $a) => $a->newInstance(),
            self::getFunctionAttributes($function, $attributeName)
        );
    }

    /**
     * Check if a function has a specific attribute.
     *
     * @param Closure|string $function The function.
     * @param string $attributeName The attribute class name.
     *
     * @return bool
     */
    public static function hasFunctionAttribute(Closure|string $function, string $attributeName): bool
    {
        return count(self::getFunctionAttributes($function, $attributeName)) > 0;
    }

    /**
     * Extract @param tags from a method's doc comment.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return array<string, string> Parameter name => type/description pairs.
     */
    public static function parseMethodParamTags(object|string $objectOrClass, string $methodName): array
    {
        $doc = self::getMethodDocComment($objectOrClass, $methodName);

        if ($doc === false) {
            return [];
        }

        $params = [];
        if (preg_match_all('/@param\s+(\S+)\s+\$(\w+)/', $doc, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $params[$match[2]] = $match[1];
            }
        }

        return $params;
    }

    /**
     * Extract @return tag from a method's doc comment.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return string|null The return type string from doc comment, or null.
     */
    public static function parseMethodReturnTag(object|string $objectOrClass, string $methodName): ?string
    {
        $doc = self::getMethodDocComment($objectOrClass, $methodName);

        if ($doc === false) {
            return null;
        }

        if (preg_match('/@return\s+(\S+)/', $doc, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * Extract @throws tags from a method's doc comment.
     *
     * @param object|string $objectOrClass The class or object.
     * @param string $methodName The method name.
     *
     * @return array<string> Array of exception class name strings.
     */
    public static function parseMethodThrowsTags(object|string $objectOrClass, string $methodName): array
    {
        $doc = self::getMethodDocComment($objectOrClass, $methodName);

        if ($doc === false) {
            return [];
        }

        $throws = [];
        if (preg_match_all('/@throws\s+(\S+)/', $doc, $matches)) {
            $throws = $matches[1];
        }

        return $throws;
    }

    /**
     * Get metadata for all methods in a class as an array of summaries.
     *
     * @param object|string $objectOrClass The class or object.
     * @param int|null $filter Optional ReflectionMethod filter bitmask.
     *
     * @return array<array<string, mixed>> Array of method metadata arrays.
     */
    public static function getAllMethodSummaries(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $methods = $filter !== null ? $ref->getMethods($filter) : $ref->getMethods();

        return array_map(
            fn(ReflectionMethod $m) => self::getMethodSummary($objectOrClass, $m->getName()),
            $methods
        );
    }

    /**
     * Get metadata for all properties in a class.
     *
     * @param object|string $objectOrClass The class or object.
     * @param int|null $filter Optional ReflectionProperty filter bitmask.
     *
     * @return array<array<string, mixed>> Array of property metadata arrays.
     */
    public static function getAllPropertyMetadata(object|string $objectOrClass, ?int $filter = null): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $properties = $filter !== null ? $ref->getProperties($filter) : $ref->getProperties();
        $result = [];

        foreach ($properties as $property) {
            $type = $property->getType();
            $result[] = [
                'name' => $property->getName(),
                'visibility' => $property->isPublic() ? 'public' : ($property->isProtected() ? 'protected' : 'private'),
                'isStatic' => $property->isStatic(),
                'isReadonly' => method_exists($property, 'isReadOnly') ? $property->isReadOnly() : false,
                'isPromoted' => method_exists($property, 'isPromoted') ? $property->isPromoted() : false,
                'type' => $type instanceof ReflectionNamedType ? $type->getName() : ($type !== null ? (string) $type : null),
                'hasDefault' => $property->hasDefaultValue(),
                'default' => $property->hasDefaultValue() ? $property->getDefaultValue() : null,
                'declaringClass' => $property->getDeclaringClass()->getName(),
            ];
        }

        return $result;
    }

    /**
     * Get a list of all magic methods implemented by a class.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return array<string> Array of implemented magic method names.
     */
    public static function getImplementedMagicMethods(object|string $objectOrClass): array
    {
        $ref = new ReflectionClass($objectOrClass);
        $magic = [];

        foreach (self::$magicMethods as $method) {
            $name = '__' . $method;
            if ($ref->hasMethod($name)) {
                $magic[] = $name;
            }
        }

        return $magic;
    }

    /**
     * Check if a class implements the __toString magic method.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isStringable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->hasMethod('__toString');
    }

    /**
     * Check if a class implements the __invoke magic method.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isInvokable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->hasMethod('__invoke');
    }

    /**
     * Check if a class implements the Countable interface.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isCountable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->implementsInterface(\Countable::class);
    }

    /**
     * Check if a class implements the Serializable interface.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isSerializable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->implementsInterface(\Serializable::class);
    }

    /**
     * Check if a class implements the JsonSerializable interface.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isJsonSerializable(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->implementsInterface(\JsonSerializable::class);
    }

    /**
     * Check if a class implements the ArrayAccess interface.
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function isArrayAccessible(object|string $objectOrClass): bool
    {
        return (new ReflectionClass($objectOrClass))->implementsInterface(\ArrayAccess::class);
    }

    /**
     * Check if a class implements the Stringable interface (PHP 8.0+).
     *
     * @param object|string $objectOrClass The class or object.
     *
     * @return bool
     */
    public static function implementsStringable(object|string $objectOrClass): bool
    {
        if (!OperationSystem::comparePHPVersion('8.0.0', '>=')) {
            return false;
        }

        return (new ReflectionClass($objectOrClass))->implementsInterface(\Stringable::class);
    }

    /**
     * Check if a generator is closed (cannot be resumed).
     *
     * @param Generator $generator The generator.
     *
     * @return bool
     */
    public static function isGeneratorClosed(Generator $generator): bool
    {
        try {
            $rg = new ReflectionGenerator($generator);
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Get the executing function name of a generator.
     *
     * @param Generator $generator The generator.
     *
     * @return string The function name.
     */
    public static function getGeneratorFunctionName(Generator $generator): string
    {
        $rg = new ReflectionGenerator($generator);
        return $rg->getFunction()->getName();
    }

    /**
     * Clear the internal annotation cache.
     *
     * @return void
     */
    public static function clearAnnotationCache(): void
    {
        self::$annotationCache = [];
    }

    /**
     * Clear the internal call method cache.
     *
     * @return void
     */
    public static function clearCallMethodCache(): void
    {
        self::$callMethodCache = [];
    }

    /**
     * Clear all internal reflection caches.
     *
     * @return void
     */
    public static function clearAllCaches(): void
    {
        self::$annotationCache = [];
        self::$callMethodCache = [];
    }

    /**
     * Get the current size of the annotation cache.
     *
     * @return int Number of cached entries.
     */
    public static function getAnnotationCacheSize(): int
    {
        return count(self::$annotationCache);
    }

    /**
     * Get the current size of the call method cache.
     *
     * @return int Number of cached entries.
     */
    public static function getCallMethodCacheSize(): int
    {
        return count(self::$callMethodCache);
    }
}
