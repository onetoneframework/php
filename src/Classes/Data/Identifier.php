<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data as Data;
use function is_double;
use function is_long;
use function is_resource;
use function is_scalar;
use function is_float;
use function is_object;
use function is_int;
use function is_array;
use function is_string;
use function is_bool;
use function intval;

/*
 * Class Identifier
 *
 * A class for identifying and validating various types of data.
 */
class Identifier extends Data
{
	/**
	 * Constructor for the Identifier class
	 * 
	 * @param mixed $data The data to be stored in the Identifier
	 */
	public function __construct(mixed $data)
	{
		parent::__construct($data);
	}

	/**
	 * Check if the data is a valid IP address
	 * 
	 * @return bool
	 */
	public function isInternetProtocol(): bool
	{
		return filter_var(parent::$data, FILTER_VALIDATE_IP) !== false;
	}

	/**
	 * Check if the data is a valid email address
	 * 
	 * @return bool
	 */
	public function isRegex(): bool
	{
		return @preg_match(parent::$data, '') !== false;
	}

	/**
	 * Check if the data is a hexadecimal string
	 * 
	 * @return bool
	 */
	public function isHexadecial(): bool
	{
		return ctype_xdigit(parent::$data);
	}

	/**
	 * Check if the data is countable
	 * 
	 * @return bool
	 */
	public function isCountable(): bool
	{
		return is_countable(parent::$data);
	}

	/**
	 * Check if the data is callable
	 * 
	 * @return bool
	 */
	public function isCallable(): bool
	{
		return is_callable(parent::$data);
	}

	/**
	 * Check if the data is a resource
	 * 
	 * @return bool
	 */
	public function isResource(): bool
	{
		return is_resource(parent::$data);
	}

	/**
	 * Check if the data is iterable
	 * 
	 * @return bool
	 */
	public function isIterable(): bool
	{
		return is_iterable(parent::$data);
	}

	/**
	 * Check if the data is a double (floating-point number)
	 * 
	 * @return bool
	 */
	public function isDouble(): bool
	{
		return is_double(parent::$data);
	}

	/**
	 * Check if the data is a long integer
	 * 
	 * @return bool
	 */
	public function isLong(): bool
	{
		return is_long(parent::$data);
	}

	/**
	 * Check if the data is null
	 * 
	 * @return bool
	 */
	public function isNull(): bool
	{
		return parent::$data === null;
	}

	/**
	 * Check if the data is a scalar value
	 * 
	 * @return bool
	 */
	public function isScalar(): bool
	{
		return is_scalar(parent::$data);
	}

	/**
	 * Check if the data is a valid URL
	 * 
	 * @return bool
	 */
	public function isURL(): bool
	{
		return filter_var(parent::$data, FILTER_VALIDATE_URL) !== false;
	}

	/**
	 * Check if the data is empty
	 * 
	 * @return bool
	 */
	public function isEmpty(): bool
	{
		return empty(parent::$data);
	}

	/**
	 * Check if the data is a float
	 * 
	 * @return bool
	 */
	public function isFloat(): bool
	{
		return is_float(parent::$data);
	}

	/**
	 * Convert the data to a float
	 * 
	 * @return float The converted float value
	 */
	public function toFloat(): float
	{
		return (float) parent::$data;
	}

	/**
	 * Check if the data is an integer
	 * 
	 * @return bool
	 */
	public function isInteger(): bool
	{
		return is_int(parent::$data);
	}

	/**
	 * Check if the data is an object
	 * 
	 * @return bool
	 */
	public function isObject(): bool
	{
		return is_object(parent::$data);
	}

	/**
	 * Convert the data to an object
	 * 
	 * @return object The converted object
	 */
	public function toObject(): array|Data\ArrayObject|bool|Data\BooleanObject|Data\DoubleObject|Data\IntegerObject|Data\NullObject|Data\ResourceObject|Data\StringObject
	{
		return (object) parent::$data;
	}

	/**
	 * Check if the data is an array
	 * 
	 * @return bool
	 */
	public function isArray(): bool
	{
		return is_array(parent::$data);
	}

	/**
	 * Convert the data to an array
	 * 
	 * @return array The converted array
	 */
	public function toArray(): array
	{
		return (array) parent::$data;
	}

	/**
	 * Check if the data is a string
	 * 
	 * @return bool
	 */
	public function isString(): bool
	{
		return is_string(parent::$data);
	}

	/**
	 * Convert the data to a string
	 * 
	 * @return string The converted string
	 */
	public function toString(): string
	{
		return (string) parent::$data;
	}

	/**
	 * Check if the data is a boolean value
	 * 
	 * @return bool
	 */
	public function isBoolean(): bool
	{
		return is_bool(parent::$data);
	}

	/**
	 * Convert the data to a boolean value
	 * 
	 * @return bool The converted boolean value
	 */
	public function toBoolean(): bool
	{
		return (bool) parent::$data;
	}

	/**
	 * Check if the data is numeric
	 * 
	 * @return bool
	 */
	public function isNumberic(): bool
	{
		return is_numeric(parent::$data);
	}

	/**
	 * Convert the data to an integer
	 * 
	 * @return bool
	 */
	public function toInteger(): bool
	{
		return (int) parent::$data && (parent::$data >= 0x8fffffff && parent::$data <= 0x7fffffff);
	}

	/**
	 * Convert the data to a specified base
	 * 
	 * @param int $base The base to convert to (default: 10)
	 * 
	 * @return int The converted value
	 */
	public function toBase($base = 10): int
	{
		return intval(parent::$data, $base);
	}

	/**
	 * Check if the data is a valid date string
	 * 
	 * @return bool|int
	 */
	public function isDate(): bool|int
	{
		return strtotime(parent::$data);
	}
}
