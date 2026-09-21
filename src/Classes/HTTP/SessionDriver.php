<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\HTTP;

use Clover\Classes\BaseClass;

class SessionDriver extends BaseClass
{
    public function __construct()
    {

    }

    public function isStarted(): bool
    {
        return Session::isStarted();
    }

    public function start(): bool
    {
        return Session::start();
    }

    public function get(string $key): mixed
    {
        return Session::get($key);
    }

    public function set(string $key, mixed $value): bool
    {
        return Session::set($key, $value);
    }
}