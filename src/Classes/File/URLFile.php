<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\File;

/**
 * URL File
 */
class URLFile
{
    /**
     * Check if a URL file exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function isExists(string $file): bool
    {
        $stream = stream_context_create(['http' => ['method' => 'HEAD']]);
        if ($content = @fopen($file, 'r', true, $stream)) {
            $headers = stream_get_meta_data($content);
            fclose($content);
            $status = substr($headers['wrapper_data'][0], 9, 3);

            return $status >= 200 && $status < 400;
        }

        return false;
    }
}