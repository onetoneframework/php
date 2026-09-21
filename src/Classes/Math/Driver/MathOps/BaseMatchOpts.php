<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Math\MathOps\Driver;

class BaseMatchOpts
{
    protected $_config = null;

    protected function __construct(array $config)
    {
        $this->_config = $config;
    }

    public static function getInstance(array $config)
    {
        return new static($config);
    }
}