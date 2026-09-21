<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    GPL 3.0
 */


namespace Clover\Classes\Debug;
use function is_array;

/**
 * Trace Argument Object Class
 * 
 * @package Clover\Classes\Debug
 */
class TraceArgumentObject
{
    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var bool
     */
    private bool $passedByReference = false;

    /**
     * @var mixed
     */
    private mixed $defaultValue;

    /**
     * @var bool
     */
    private bool $variadic = false;

    /**
     * @var array
     */
    private array $sensitiveAttributes = [];

    /**
     * @var array
     */
    private array $arguments = [];

    /**
     * Constructor
     */
    public function __construct()
    {
    }

    /**
     * Sets the variadic flag.
     * 
     * @param bool $variadic
     * 
     * @return void
     */
    public function setVariadic(bool $variadic): void
    {
        $this->variadic = $variadic;
    }

    /**
     * Sets the sensitive attributes.
     * 
     * @param array $sensitiveAttributes
     * 
     * @return void
     */
    public function setSensitiveAttributes(array $sensitiveAttributes): void
    {
        $this->sensitiveAttributes = $sensitiveAttributes;
    }

    /**
     * Sets the type of the argument.
     * 
     * @param string $type
     * 
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * Sets the name of the argument.
     * 
     * @param string $name
     * 
     * @return void
     */
    public function setName(string  $name): void
    {
        $this->name = $name;
    }

    /**
     * Sets whether the argument is passed by reference.
     * 
     * @param bool $passedByReference
     * 
     * @return void
     */
    public function setPassedByReference(bool $passedByReference): void
    {
        $this->passedByReference = $passedByReference;
    }

    /**
     * Sets the default value of the argument.
     * 
     * @param mixed $value
     * 
     * @return void
     */
    public function setDefaultValue($value): void
    {
        $this->defaultValue = $value;
    }

    /**
     * Adds an argument to the argument list.
     * 
     * @param mixed $arguments
     * 
     * @return void
     */
    public function setArguments(mixed $arguments): void
    {
        $this->arguments[] = $arguments;
    }

    /**
     * Gets the argument text representation.
     * 
     * @return array
     */
    public function getArgumentText(): array
    {
        $arguments = $this->getArguments();

        return array_map(function ($value) {
            return is_array($value) ? join(", ", $value) : $value;
        }, $arguments);
    }

    /**
     * Gets the arguments.
     * 
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Checks if there are any arguments.
     * 
     * @return bool
     */
    public function hasArguments(): bool
    {
        return isset($this->arguments) && !empty($this->arguments);
    }
}
