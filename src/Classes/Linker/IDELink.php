<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Linker;

use Clover\Enumeration\{IDE, Protocol};
use function explode;
use function implode;
use function ltrim;
use function preg_match;
use function rawurlencode;
use function realpath;
use function sprintf;
use function str_replace;

/**
 * Class IDELink
 *
 * @package Clover\Classes\Linker
 */
class IDELink
{

    private ?string $ide = null;

    /**
     * Build a vscode:// URL that opens a local file at the given line and column.
     *
     * @param string $file   Absolute or resolvable filesystem path.
     * @param int    $line   One-based line number.
     * @param int    $column One-based column number.
     *
     * @return string URI such as vscode://file/C:/path/to/File.php:12:1
     */
    public static function buildVisualStudioCodeUri(string $file, int $line, int $column = 1): string
    {
        if ($line < 1) {
            $line = 1;
        }
        if ($column < 1) {
            $column = 1;
        }

        $resolved = $file;
        $realPath = realpath($file);
        if ($realPath !== false) {
            $resolved = $realPath;
        }

        $normalized = str_replace('\\', '/', $resolved);
        $pathWithoutLeadingSlash = ltrim($normalized, '/');
        $segments = explode('/', $pathWithoutLeadingSlash);
        $encodedSegments = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }
            if (preg_match('/^[A-Za-z]:$/', $segment) === 1) {
                $encodedSegments[] = $segment;
            } else {
                $encodedSegments[] = rawurlencode($segment);
            }
        }

        $pathAfterFile = implode('/', $encodedSegments);

        return sprintf('%sfile/%s:%d:%d', Protocol::VISUAL_STUIO_CODE, $pathAfterFile, $line, $column);
    }

    /**
     * IDELink constructor.
     *
     * @param string|null $ide
     */
    public function __construct(?string $ide = IDE::VISUAL_STUIO_CODE)
    {
        $this->ide = $ide;
    }

    /**
     * Generate a link to open a file in the specified IDE at a specific line.
     *
     * @param string $file
     * @param int    $line
     * 
     * @return string
     */
    public function generate(string $file, int $line): string
    {
        $protocol = Protocol::FILE;
        $format = "%s#L%s";

        switch ($this->ide) {
            case IDE::VISUAL_STUIO_CODE:
                return self::buildVisualStudioCodeUri($file, $line);
            case IDE::EMACS:
                $protocol = Protocol::EMACS;
                $format = "open?url=file://%s&line=%s";
                break;
            case IDE::SUBLIME:
                $protocol = Protocol::SUBLIME;
                $format = "open?url=file://%s&line=%s";
                break;
            case IDE::MAC_VIM:
                $protocol = Protocol::MAC_VIM;
                $format = "open?url=file://%s&line=%s";
                break;
            case IDE::TEXTMATE:
                $protocol = Protocol::TEXTMATE;
                $format = "open?url=file://%s&line=%s";
                break;
            case IDE::PHPSTORM:
                $protocol = Protocol::PHPSTORM;
                $format = "open?file=%s&line=%s";
                break;
            case IDE::ATOM:
                $protocol = Protocol::ATOM;
                $format = "open?file=%s&line=%s";
                break;
        }

        return sprintf("%s%s", $protocol, sprintf($format, trim($file, "/"), $line));
    }
}
