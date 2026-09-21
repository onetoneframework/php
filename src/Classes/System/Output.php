<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\System;

use Clover\Classes\Data\StringObject;
use function printf;

/**
 * Class System
 * 
 * Provides system-level output functionalities.
 */
class Output
{
    /**
     * Print content without a newline
     *
     * @param StringObject|string $content The content to print
     * 
     * @return void
     */
    public static function print(StringObject|string $content): void
    {
        echo $content;
    }

    /**
     * Print content with a newline
     *
     * @param string $content The content to print
     * 
     * @return void
     */
    public static function printLine(string $content): void
    {
        echo $content . PHP_EOL;
    }

    /**
     * Print formatted content
     *
     * @param string $format The format string
     * @param mixed ...$values The values to format
     * 
     * @return void
     */
    public static function printFormat(string $format, mixed ...$values): void
    {
        printf($format, ...$values);
    }
}
