<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

use function is_object;
use function is_array;

/** 
 * Object Handler Class
 * 
 * @package Clover\Classes\Data
 */
class ObjectHandler
{
    /** 
     * Recursively convert an object to an array.
     * This function checks if the input is an object and converts it to an array using get_mangled_object_vars. 
     * If the input is already an array, it applies the conversion recursively to each element. 
     * If the input is neither an object nor an array, it returns the input as is.
     *
     * @param mixed $obj The input variable that may be an object or an array.
     * @return mixed The converted array if the input was an object or an array, or the original input if it was neither.
     */
    public static function objectToArray(mixed $obj): mixed
    {
        if (is_object($obj)) {
            $obj = get_mangled_object_vars($obj);
        }

        if (is_array($obj)) {
            return array_map(function ($obj) {
                return self::objectToArray($obj);
            }, $obj);
        } else {
            return $obj;
        }
    }

    /**
     * Convert an array with mangled property names back to an associative array with original property names.
     * This function iterates through the input array, checks for mangled property names (private and protected), and extracts the original property names using regular expressions. 
     * It then constructs a new array with the unmangled property names as keys and the corresponding values.
     * @param mixed $mangledProps The input array with mangled property names.
     * @return array The resulting array with unmangled property names.
     */
    public static function arrayToObject(mixed $mangledProps): array
    {
        $unmangled = [];
        foreach ($mangledProps as $key => $value) {
            // Private: \0ClassName\0property
            if (preg_match('/^\0.+\0(.+)$/', $key, $matches)) {
                $unmangled[$matches[1]] = $value;
            }
            // Protected: \0*\0property
            elseif (preg_match('/^\0\*\0(.+)$/', $key, $matches)) {
                $unmangled[$matches[1]] = $value;
            }
            // Public: unchanged
            else {
                $unmangled[$key] = $value;
            }
        }

        return $unmangled;
    }
}
