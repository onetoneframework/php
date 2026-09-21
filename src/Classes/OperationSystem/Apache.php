<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem;

use function in_array;

/**
 * Class Apache
 *
 * A wrapper class for Apache-specific functions in PHP.
 *
 * @package Clover\Classes\OperationSystem
 */
class Apache
{

    /**
     * Check if the mod_rewrite module is enabled.
     *
     * @return bool
     */
    public static function isModRewriteEnabled(): bool
    {
        return in_array('mod_rewrite', apache_get_modules());
    }

    /**
     * Terminate the current Apache process.
     * 
     * @return void
     */
    public static function terminateProcess(): void
    {
        apache_child_terminate();
    }

    /**
     * Get a list of all loaded Apache modules.
     *
     * @return array
     */
    public static function getLoadedModulesList(): array
    {
        return apache_get_modules();
    }

    /**
     * Get the Apache version.
     *
     * @return bool|string
     */
    public static function getVersion(): bool|string
    {
        return apache_get_version();
    }

    /**
     * Set an Apache environment variable.
     *
     * @param string $variable
     * @param string $value
     * @param bool   $walkToTop
     * 
     * @return bool
     */
    public static function setEnvironmentVariable(string $variable, string $value, bool $walkToTop = false): bool
    {
        return apache_setenv($variable, $value, $walkToTop);
    }

    /**
     * Get an Apache environment variable.
     *
     * @param string $variable
     * @param bool   $walkToTop
     * 
     * @return bool|string
     */
    public static function getEnvironmentVariable(string $variable, bool $walkToTop = false): bool|string
    {
        return apache_getenv($variable, $walkToTop);
    }

    /**
     * Lookup a URI in Apache.
     *
     * @param string $filename
     * 
     * @return bool|object
     */
    public static function lookupUri(string $filename): bool|object
    {
        return apache_lookup_uri($filename);
    }

    /**
     * Set a request note in Apache.
     *
     * @param string      $note_name
     * @param string|null $note_value
     * 
     * @return string|bool
     */
    public static function setRequestNote(string $note_name, ?string $note_value = null): string|bool
    {
        return apache_note($note_name, $note_value);
    }

    /**
     * Get a request note from Apache.
     *
     * @param string $note_name
     * 
     * @return string|bool
     */
    public static function getRequestNote(string $note_name): bool|string
    {
        return apache_note($note_name);
    }

    /**
     * Get all request headers from Apache.
     *
     * @return array
     */
    public static function getAllRequestHeaders(): array
    {
        return apache_request_headers();
    }

    /**
     * Get all response headers from Apache.
     *
     * @return array
     */
    public static function getAllResponseHeaders(): array
    {
        return apache_response_headers();
    }

    /**
     * Get all headers from Apache.
     *
     * @return array
     */
    public static function getAllHeaders(): array
    {
        return getallheaders();
    }
}
