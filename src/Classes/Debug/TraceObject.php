<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    GPL 3.0
 */


namespace Clover\Classes\Debug;

use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\Linker\IDELink;
use Clover\Classes\OperationSystem;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Enumeration\IDE;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use function array_slice;
use function count;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Class TraceObject
 * 
 * @package Clover\Classes\Debug0
 */
class TraceObject
{
    /** @var ReflectionClass|null $declaring_class */
    protected ?ReflectionClass $declaring_class = null;

    /** @var string|null $function */
    protected ?string $function = null;

    /** @var string|null $class */
    protected ?string $class = null;

    /** @var string|null $file */
    protected ?string $file = null;

    /** @var string|null $short_name */
    protected ?string $short_name = null;

    /** @var string|null $type */
    protected ?string $type = null;

    /** @var int|null $line */
    protected ?int $line = null;

    /** @var mixed[] $args */
    private ?array $args = null;

    /** @var string[] $comments */
    protected ?array $comments = null;

    /** @var string[] $interfaces */
    protected ?array $interfaces = null;

    /** @var string[] $traits */
    protected ?array $traits = null;

    /** @var string|null $text */
    protected ?string $text = null;

    /** @var string|null $code */
    protected ?string $code = null;

    /** @var TraceArgumentObject[] $arguments */
    protected array $arguments = [];

    /** @var string|null $annotation */
    protected ?string $annotation = null;

    /** @var string|null $ideLink */
    protected ?string $ideLink = null;

    /** @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $return_type */
    protected ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $return_type = null;

    /**
     * Constructor
     * 
     * @param array{function: string|null, class: string|null, file: string|null, type: string|null, line: int|null, args: array|null} $trace
     */
    public function __construct(array $trace)
    {
        $this->setFunction($trace['function'] ?? null);
        $this->class = $trace['class'] ?? null;
        $this->file = $trace['file'] ?? null;
        $this->type = $trace['type'] ?? null;
        $this->line = $trace['line'] ?? null;
        $this->args = $trace['args'] ?? null;

        if ($this->hasFile()) {
            $this->ideLink = (new IDELink(IDE::VISUAL_STUIO_CODE))->generate($this->file, $this->line);
        }

		if ($this->hasFunction() && $this->hasClass()) {
			$this->parseClass();
		} elseif ($this->hasFunction()) {
			$this->setText(sprintf('%s()', $this->getFunction()));
			$this->parseCode(!OperationSystem::isCommandLineInterface());
		}
    }

    /**
     * Get IDE link
     * 
     * @return string|null
     */
    public function getIDELink(): string|null
    {
        return $this->ideLink;
    }

    /**
     * Check that if arguments exist
     * 
     * @return bool
     */
    public function hasArguments(): bool
    {
        return isset($this->arguments) && count($this->arguments) > 0;
    }

    /**
     * Get arguments
     * 
     * @return TraceArgumentObject[]|null
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * Check that if return type exists
     * 
     * @return bool
     */
    public function hasReturnType(): bool
    {
        return isset($this->return_type) && !empty($this->return_type);
    }


    /**
     * Set return type
     * 
     * @param ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $return_type
     * 
     * @return void
     */
    public function setReturnType(ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $return_type): void
    {
        $this->return_type = $return_type;
    }

    /**
     * Get return type
     * 
     * @return ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
     */
    public function getReturnType(): ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null
    {
        return $this->return_type;
    }

    /**
     * Check that if file exists
     * 
     * @return bool
     */
    public function hasFile(): bool
    {
        return isset($this->file) && !empty($this->file);
    }

    /**
     * Get file
     * 
     * @return string
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Check that if short name exists
     * 
     * @return bool
     */
    public function hasShortName(): bool
    {
        return isset($this->short_name) && !empty($this->short_name);
    }

    /**
     * Set short name
     * 
     * @param string $short_name
     * 
     * @return void
     */
    public function setShortName(string $short_name): void
    {
        $this->short_name = $short_name;
    }

    /**
     * Get short name
     * 
     * @return string
     */
    public function getShortName(): ?string
    {
        return $this->short_name;
    }

    /**
     * Check that if type exists
     * 
     * @return bool
     */
    public function hasType(): bool
    {
        return isset($this->type);
    }

    /**
     * Get type
     * 
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Check that if line exists
     * 
     * @return bool
     */
    public function hasLine(): bool
    {
        return isset($this->line) && !empty($this->line);
    }

    /**
     * Get line
     * 
     * @return null|int
     */
    public function getLine(): ?int
    {
        return $this->line;
    }

    /**
     * Check that if text exists
     * 
     * @return bool
     */
    public function hasText(): bool
    {
        return isset($this->text);
    }

    /**
     * Set text
     * 
     * @param string $text
     * 
     * @return void
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * Get text
     * 
     * @return string
     */
    public function getText(): ?string
    {
        return $this->text;
    }

    /**
     * Check that if class exists
     * 
     * @return bool
     */
    public function hasClass(): bool
    {
        return isset($this->class);
    }

    /**
     * Get class
     * 
     * @return null|string
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Check that if code exists
     * 
     * @return bool
     */
    public function hasCode(): bool
    {
        return isset($this->code);
    }

    /**
     * Get code
     * 
     * @return string|null The rendered code block, or null when the frame has no file.
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * Check that if comment exists
     * 
     * @return bool
     */
    public function hasComment(): bool
    {
        return isset($this->comments);
    }

    /**
     * Get parsed comment list
     * 
     * @return string[]
     */
    public function getComment(): ?array
    {
        return $this->comments;
    }

    /**
     * Get comment of document
     * 
     * @return string
     */
    public function getCommentTag(): string
    {
        if ($this->comments === null) {
            return "";
        }

        return join("\r\n", $this->comments);
    }

    /**
     * Check that if parsed annotation is exists
     *
     * @return bool
     */
    public function hasAnnotation(): bool
    {
        return isset($this->annotation) && !empty($this->annotation);
    }

    /**
     * Get annotation
     * 
     * @return string
     */
    public function getAnnotation(): ?string
    {
        return $this->annotation;
    }

    /**
     * Set function argument into this class object
     * 
     * @param string|null $function
     * 
     * @return void
     */
    public function setFunction(string|null $function): void
    {
        $this->function = $function;
    }

    /**
     * Check that function is exists
     * 
     * @return bool
     */
    public function hasFunction(): bool
    {
        return isset($this->function) && !empty($this->function);
    }

    /**
     * Get function name
     * 
     * @return null|string
     */
    public function getFunction(): ?string
    {
        return $this->function;
    }

    /**
     * Check that if declaring class exists
     * 
     * @return bool
     */
    public function hasDeclaringClass(): bool
    {
        return isset($this->declaring_class);
    }

    /**
     * Set the declaring class for the reflected method
     * 
     * @param ReflectionClass $declaring_class
     * 
     * @return void
     */
    public function setDeclaringClass(ReflectionClass $declaring_class): void
    {
        $this->declaring_class = $declaring_class;
    }

    /**
     * Gets the declaring class for the reflected method
     * 
     * @return string
     */
    public function getDeclaringClass(): ?string
    {
        return $this->declaring_class?->getName();
    }

    /**
     * Parse class of reflection
     * 
     * @return void
     */
    private function parseClass(): void
    {
        $reflectionClass = new ReflectionClass($this->class);
        $shortName = $reflectionClass->getShortName();

        $this->setShortName($shortName);
        $defaultText = sprintf("%s%s%s", $this->getShortName() ?? "", $this->getType(), $this->getFunction());
        $this->setText(sprintf("%s()", $defaultText));

        // Trait
        $traits = $reflectionClass->getTraits();
        foreach ($traits as $trait) {
            if (!$trait->isTrait()) {
                continue;
            }

            $traitName = $trait->getName();

            if ($this->traits == null) {
                $this->traits = [];
            }

            $this->traits[] = $traitName;
        }

        // Interface
        $interfaces = $reflectionClass->getInterfaces();
        foreach ($interfaces as $interface) {
            $interfaceName = $interface->getName();

            if ($this->interfaces == null) {
                $this->interfaces = [];
            }

            $this->interfaces[] = $interfaceName;
        }

        // Methods
        $methods = $reflectionClass->getMethods();
        if (!$this->isMethodExist($methods)) {
            return;
        }

        $objectArguments = [];
        $method = $reflectionClass->getMethod($this->getFunction());
        if ($method->getNumberOfParameters() >= 0) {
            $objectArguments = $this->parseMethodArguments($method);
        }

        $this->parseCode(!OperationSystem::isCommandLineInterface());

        // Return Type
        if ($method->hasReturnType()) {
            $returnType = $method->getReturnType();
            $this->setReturnType($returnType);
        }

        // Arguments
        $arguments = [];
        foreach ($objectArguments as $argument) {
            $this->arguments[] = $argument;

            if (!$argument->hasArguments()) {
                continue;
            }

            $arguments[] = $argument->getArguments();
        }

        $arguments = $this->argumentToString($arguments) ?? "";
        $modifier = ReflectionHandler::getMethodModifierString($method);
        $returnType = $this->getReturnType() ?? "";
        $returnTypeFormat = ($this->hasReturnType() ? ": %s" : "");

        $this->setText(sprintf(("%s %s(%s)" . $returnTypeFormat), $modifier, $defaultText, $arguments, $returnType));
        $this->parseMethodComment($method);

        // Annotations
        $annotations = [];
        $this->setDeclaringClass($method->getDeclaringClass());
        /** @var ReflectionAttribute[] $attributes */
        $attributes = $method->getAttributes();
        foreach ($attributes as $attribute) {
            $arguments = $attribute->getArguments();
            $name = $attribute->getName();
            $target = $attribute->getTarget();

            $annotations[] = sprintf("#[%s[%s]]:%s", $name, join(", ", array_map(function ($data) {
                return isset($data) ? sprintf("'%s'", $data) : null;
            }, $arguments)), $target);
        }

        $this->annotation = join("\r\n", $annotations);
    }

    /**
     * Parse method comment
     * 
     * @param ReflectionMethod $method
     * 
     * @return void
     */
    private function parseMethodComment(ReflectionMethod $method): void
    {
        $comment = $method->getDocComment();
        if (!$comment) {
            return;
        }

        $comments = self::parseComments($comment);
        $this->comments = $comments;
    }

    /**
     * Check that if method exist
     * 
     * @param ReflectionMethod[] $methods
     * 
     * @return bool
     */
    private function isMethodExist(array $methods): bool
    {
        $existMethods = array_filter($methods, function ($method) {
            return $method->name == $this->getFunction();
        });

        return !empty($existMethods);
    }

    public static function renderCodeBlock(string $filePath, ?int $highlightLine = null, bool $highlight = true): ?string
    {
        if (!file_exists($filePath)) {
            return null;
        }

        $rawContent = $highlight ? show_source($filePath, true) : FileFunctions::read($filePath)->__toString();
        $lines = preg_split('/(\r\n|<br\s*\/?>)/ui', $rawContent);

        if (empty($lines)) {
            return null;
        }

        $output = '';
        foreach ($lines as $index => $lineContent) {
            $lineNumber = $index + 1;
            $isHighlighted = ($highlightLine !== null && $lineNumber === $highlightLine);
            $highlightClass = $isHighlighted ? ' class="code-line-highlight"' : '';
            $dataAttr = $isHighlighted ? ' data-highlight="true"' : '';
            $output .= sprintf('<div%s%s><span class="code-line-number">%d</span><span class="code-line-content">%s</span></div>', $highlightClass, $dataAttr, $lineNumber, $lineContent);
        }

        return $output;
    }

    /**
     * Parse code from file
     * 
     * @param bool $highlight
     * 
     * @return void
     */
    private function parseCode(bool $highlight = true): void
    {
        if (!$this->hasFile()) {
            return;
        }

        $rendered = self::renderCodeBlock($this->file, $this->line, $highlight);
        if ($rendered !== null) {
            $this->code = $rendered;
        }
    }

    /**
     * Parse arguments of method
     * 
     * @param ReflectionMethod $method
     * 
     * @return TraceArgumentObject[]
     */
    private function parseMethodArguments(ReflectionMethod $method): array
    {
        $returnArguments = [];
        $parameters = $method->getParameters();

        /** @var ReflectionParameter|ReflectionType|null[] $parameters */
        foreach ($parameters as $key => &$parameter) {
            $values = [];
            $isArgumentExist = false;

            /** @var ReflectionParameter $parameter */
            if ($parameter === null) {
                continue;
            }

            $traceArgumentObject = new TraceArgumentObject();
            if ($parameter->hasType()) {
                /** @var ReflectionNamedType|ReflectionUnionType|ReflectionIntersectionType|null $type */
                $type = $parameter->getType();

                if ($type instanceof ReflectionIntersectionType && OperationSystem::comparePHPVersion('8.1.0', '>')) {
                    if (method_exists($type, '__toString')) {
                        $traceArgumentObject->setType($type->__toString());
                        $values[] = $type->__toString();
                    } else {
                        $traceArgumentObject->setType($type);
                        $values[] = $type;
                    }

                } else if ($type instanceof ReflectionType) {
                    if (method_exists($type, '__toString')) {
                        $traceArgumentObject->setType($type->__toString());
                        $values[] = $type->__toString();
                    } else {
                        $traceArgumentObject->setType($type);
                        $values[] = $type;
                    }
                }
            }

            $name = $parameter->getName();
            $traceArgumentObject->setName($name);
            $traceArgumentObject->setPassedByReference($parameter->isPassedByReference());
            $values[] = $name;

            $sensitiveAttributes = $parameter->getAttributes(\SensitiveParameter::class);
            if ($sensitiveAttributes) {
                $traceArgumentObject->setSensitiveAttributes($sensitiveAttributes);
            }

            $isVariadic = $parameter->isVariadic();
            if ($isVariadic) {
                $traceArgumentObject->setVariadic($isVariadic);
            }

            // When default value is available and arguments is empty
            if ($parameter->isDefaultValueAvailable() && !isset($this->args)) {
                $isArgumentExist = true;

                $defaultValue = $parameter->getDefaultValue();
                $traceArgumentObject->setDefaultValue($defaultValue);
                $values[] = $defaultValue;
                // When arguments is defined
            } else if (isset($this->args)) {
                $arguments = $this->args[$key] ?? null;

                if (is_array($arguments) || is_object($arguments) || is_object($arguments)) {
                    $isArgumentExist = true;
                    $parsedArguments = $this->parseArgument($arguments);
                    $joinArgument = join(', ', $parsedArguments);
                    $values[] = $joinArgument;
                }
            }

            $typeFormat = (!$parameter->hasType() ? !!"" : "%s ");
            $methodFormat = ($isArgumentExist ? "%s = %s" : "$%s");

            $format = sprintf("%s%s", $typeFormat, $methodFormat);

            $values = array_map(function ($array) {
                if (is_array($array)) {
                    return implode($array);
                }

                return $array;
            }, $values);

            $arguments = vsprintf($format, $values);
            $traceArgumentObject->setArguments($arguments);

            $returnArguments[] = $traceArgumentObject;
        }

        return $returnArguments;
    }

    /**
     * Parse arguments
     * 
     * @param array|object|string|null $arguments
     * 
     * @return array
     */
    private function parseArgument(array|object|string|null $arguments): array
    {
        $parsedArguments = [];

        $map = is_array($arguments) ? $arguments : [$arguments];

        foreach ($map as $argument) {
            if (is_string($argument)) {
                $parsedArguments[] = empty($argument) ? "null" : "'{$argument}'";
                continue;
            }

            if (is_object($argument)) {
                $parsedArguments[] = get_class($argument) ?? "::";
                continue;
            }

            if (is_array($argument)) {
                if (empty($argument) == 0 || !is_countable($argument)) {
                    $parsedArguments[] = "[]";
                    continue;
                }

                $parsedArguments[] = $this->parseArgument($argument);
                continue;
            }

            $parsedArguments[] = "null";
        }

        $parsedArguments = array_map(function ($value) {
            return is_array($value) ? "[" . join(", ", $value) . "]" : $value;
        }, $parsedArguments);

        return $parsedArguments;
    }

    /**
     * Parse part of comments
     * 
     * @param string $comment
     * 
     * @return string[]
     */
    public static function parseComments(string $comment): array
    {
        $comments = array_slice(explode("\n", $comment), 1);
        foreach ($comments as $key => &$comment) {
            $comment = self::trimComment($comment);

            if (str_starts_with($comment, "@")) {
                unset($comments[$key]);
            } else if (empty($comment)) {
                unset($comments[$key]);
            }
        }

        return $comments;
    }

    /**
     * Trim comment
     * 
     * @param string $comment
     * 
     * @return string
     */
    public static function trimComment(string $comment): string
    {
        $comment = trim($comment);
        $comment = rtrim($comment, "/");
        $comment = ltrim($comment, "*");
        $comment = trim($comment);

        return $comment;
    }

    /**
     * Convert arguments to string
     * 
     * @param array $arguments
     * 
     * @return string
     */
    public function argumentToString(array $arguments): string
    {
        $arguments = array_map(function ($value) {
            return is_array($value) ? join(", ", $value) : (empty($value) ? "null" : $value);
        }, $arguments);
        $arguments = join(", ", $arguments);

        return $arguments;
    }
}
