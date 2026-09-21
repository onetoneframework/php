<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

/**
 * Build a browser path (leading slash) from a location relative to {@see BASE_PATH}.
 *
 * Use this in string literals so the path matches the app root on disk
 * (e.g. App/Frontend/Layout/MenuShadow/about.css), which IDEs can complete when the
 * workspace includes res/Platform/PHP/root or you use a path-intel mapping to that folder.
 */
final class PublicPath
{
    public static function fromBase(string $relativeToBase): string
    {
        $normalized = str_replace('\\', '/', $relativeToBase);
        $normalized = ltrim($normalized, '/');

        return $normalized === '' ? '/' : '/' . $normalized;
    }
}
