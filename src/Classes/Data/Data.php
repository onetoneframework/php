<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use Clover\Classes\Data\{StringObject, IntegerObject, BooleanObject, DoubleObject, ArrayObject, ResourceObject, NullObject};
use function gettype;

class Data
{
	protected static $data;

	public function __construct($data)
	{
		self::$data = $data;
	}

	public function toObject(): array|ArrayObject|bool|BooleanObject|DoubleObject|IntegerObject|NullObject|ResourceObject|StringObject
	{
		$type = gettype(self::$data);

		if ($type === 'string') {
			return new StringObject((string) self::$data);
		} else if ($type === 'integer') {
			return new IntegerObject(self::$data);
		} else if ($type === 'boolean') {
			return new BooleanObject(self::$data);
		} else if ($type === 'double') {
			return new DoubleObject(self::$data);
		} else if ($type === 'array') {
			return new ArrayObject(self::$data);
		} else if ($type === 'resource') {
			return new ResourceObject(self::$data);
		} else if ($type === 'NULL') {
			return new NullObject(self::$data);
		}

		return self::$data;
	}
}
