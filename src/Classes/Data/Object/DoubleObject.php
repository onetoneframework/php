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
class DoubleObject extends BaseObject
{

    protected $rawData;

    public function __construct($data)
    {
        $this->rawData = $data;
    }

    public function __toString(): string
    {
        return (string)$this->rawData;
    }
}
