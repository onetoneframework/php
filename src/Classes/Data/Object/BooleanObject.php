<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use Clover\Classes\Data\BaseObject as BaseObject;

#[\AllowDynamicProperties]
class BooleanObject extends BaseObject
{
    protected $rawData;

    public function __construct(bool $data)
    {
        $this->rawData = $data;
    }

    public function _toString()
    {
        return 'test';
    }

    public function __call($name, $args): mixed
    {
        return 'test';
    }

    public function __isset($property)
    {
        return 'test2';
    }

    public function __get($name)
    {
        return 'test';
    }

    public function __invoke()
    {
        return $this->rawData;
    }
}
