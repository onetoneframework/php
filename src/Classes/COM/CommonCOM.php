<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\COM;
use Exception;
use COM;

class CommonCOM
{
    // @phpstan-ignore-next-line
    /** @var COM|object{ConnectServer: callable} */
    // @phpstan-ignore-next-line
    protected COM $com;

    public function __construct()
    {
        if (!class_exists('COM')) {
            throw new Exception('php extension `COM` is not enabled');
        }
    }
}
