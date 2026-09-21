<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use AllowDynamicProperties;
use Closure;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Exception;

/**
 * Base Object Class
 */
#[AllowDynamicProperties]
class BaseObject
{
	/** @var mixed Raw data stored in the object */
	protected $rawData;

	/** @var array<string, Closure> Dynamically added methods */
	private $methods;

	/**
	 * Constructor
	 * 
	 * @param mixed $data
	 */
	public function __construct(mixed $data)
	{
		$this->rawData = $data;
	}

	/**
	 * Add method to object
	 * 
	 * @param string  $name
	 * @param Closure $func
	 * 
	 * @return void
	 */
	public function addMethod(string $name, Closure $func): void
	{
		$this->methods[$name] = $func->bindTo($this, self::class);
	}

	/**
	 * Call method
	 * 
	 * @param string $name
	 * @param array  $args
	 * 
	 * @throws Exception
	 * @return mixed
	 */
	public function __call(string $name, array $args): mixed
	{
		if (isset($this->methods[$name])) {
			return ReflectionHandler::callMethodArray($this->methods[$name], $args);
		}

		if (method_exists($this, $name)) {
			$reflection = ReflectionHandler::createFromMethodName($this, $name);
			return $reflection->invoke($this, ...$args);
		}

		throw new Exception("Method $name not defined");
	}

	/**
	 * Set raw data
	 * 
	 * @param mixed $data
	 * 
	 * @return void
	 */
	protected function setRawData(mixed $data): void
	{
		$this->rawData = $data;
	}

	/**
	 * Get raw data
	 * 
	 * @return mixed
	 */
	public function getRawData(): mixed
	{
		return $this->rawData;
	}

	/**
	 * Clone raw data
	 * 
	 * @return mixed
	 */
	public function &cloneRawData(): mixed
	{
		return $this->rawData;
	}

	/**
	 * Convert to string
	 * 
	 * @return string
	 */
	public function __toString(): string
	{
		return implode(" ", $this->rawData);
	}

	/**
	 * Type of string
	 * 
	 * @return bool
	 */
	protected function typeOfString(): bool
	{
		return $this instanceof StringObject;
	}

	/**
	 * Type casting
	 * 
	 * @param string $type
	 * 
	 * @return bool
	 */
	protected function typeCasting(string $type): bool
	{
		$data = $this->rawData;

		switch ($type) {
			case "string":
				$data = (string) $data;
				break;
			case "integer":
				$data = (int) $data;
				break;
			case "float":
				$data = (float) $data;
				break;
			case "boolean":
				$data = (bool) $data;
				break;
			case "array":
				$data = (array) $data;
				break;
			default:
				return false;
		}

		$this->setRawData($data);

		return true;
	}

	/**
	 * Get as object
	 * 
	 * @return mixed
	 */
	public function toObject(): mixed
	{
		$data = $this->getRawData();

		$type = gettype($data);

		if ($type === 'string') {
			return new StringObject($data);
		}

		if ($type === 'integer') {
			return new IntegerObject($data);
		}

		if ($type === 'array') {
			return new ArrayObject($data);
		}

		if ($type === 'double') {
			return new DoubleObject($data);
		}

		if ($type === 'array') {
			return new ArrayObject($data);
		}

		if ($type === 'resource') {
			return new ResourceObject($data);
		}

		if ($type === 'NULL') {
			return new NullObject($data);
		}

		return $data;
	}

	/**
	 * Get length
	 * 
	 * @return int
	 */
	public function length(): int
	{
		return 0;
	}
}
