<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem\Linux;

/**
 * Class Base
 *
 * @package Clover\Classes\OperationSystem\Linux
 */
class Base
{
    /**
     * Check if a specific Linux extension is installed.
     *
     * @param string $path
     * @return bool
     */
    public static function isLinuxExtensionInstalled(string $path): bool
    {
        if (PHP_OS !== 'Linux') {
            return false;
        }

        return file_exists($path);
    }
}