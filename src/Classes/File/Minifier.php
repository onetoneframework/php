<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

use function sprintf;

class Minifier
{
    /**
     * Minify CSS content.
     *
     * @param string $filename
     * @param string $cssBuff
     *
     * @return array|string|null
     */
    public static function minifyCssContent(string $filename, string $cssBuff): array|string|null
    {
        $filename = preg_replace('/(\?[a-z0-9]{1,500})/', '', $filename);

        $cssBuff = preg_replace_callback('/url\(([^\)]+)\)/i', function ($matches) use ($filename) {
            $url = trim($matches[1], '\'"');

            if (!preg_match("/^\//i", $url)) {
                return sprintf('url("%s/%s");', dirname($filename), $url);
            } else if (preg_match("/^data:/i", $url)) {
                return sprintf('url("%s");', $url);
            } else if (preg_match("/^\\//i", $url)) {
                return sprintf('url("%s");', $url);
            }
        }, $cssBuff);

        $cssBuff = preg_replace("@/\s*\*.*?\*/\s*|\s+@s", " ", $cssBuff);

        return $cssBuff;
    }
}
