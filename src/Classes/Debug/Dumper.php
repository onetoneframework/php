<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    GPL 3.0
 */


namespace Clover\Classes\Debug;

use Exception;
use function var_representation;

class Dumper
{
    public static function representation(array|bool|float|int|null|string $value, int $flags = 0): string
    {
        if (!function_exists('var_representation')) {
            throw new Exception('Call to undefined function var_representation');
        }

        return var_representation($value, $flags);
    }

    public static function dump(mixed $value, mixed ...$values)
    {
        var_dump($value, $values);
    }

    public static function export(mixed $value, bool $return = false)
    {
        return var_export($value, $return);
    }

    public static function pretty(mixed $value)
    {
        if (is_array($value)) {
            if (is_countable($value)) {
                $has_multi_values = count(array_filter($value, function ($array) {
                    $factorial_filter = function ($array) {
                        return array_filter($array, function ($values) {
                            return count($values) > 1;
                        });
                    };

                    if (is_array($array) && is_array(array_pop($array))) {
                        return count($factorial_filter($array)) > 1;
                    }

                    return count(array_values($array)) > 1;
                })) > 0;

                var_dump($has_multi_values);

                //if (!$has_multi_values) {
                    $print_data = "";

                    foreach ($value as $row) {
                        $print_data .= $print_data === "" ? implode("", $row) : PHP_EOL.implode("", $row);
                    }

                    echo $print_data;
                //}
            }
        }
    }
}
