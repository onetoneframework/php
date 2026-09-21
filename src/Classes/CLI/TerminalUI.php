<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI;

use Clover\Classes\BaseClass;

/**
 * TerminalUI class for handling terminal UI operations
 */
class TerminalUI extends BaseClass
{
    /**
     * Detects whether the output stream supports ANSI color sequences.
     *
     * @param resource $resource Output stream (defaults to {@see STDOUT}).
     *
     * @return bool True when color escape codes are expected to work.
     */
    public function isColorConsoleCapabilities(mixed $resource = STDOUT): bool
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI') || 'xterm' === getenv('TERM');
        }

        return function_exists('posix_isatty') && posix_isatty($resource);
    }

    /**
     * Clears the terminal screen
     */
    public function clearScreen(): void
    {
        echo "\033[2J\033[H";
    }

    /**
     * Hides the terminal cursor
     */
    public function hideCursor(): void
    {
        echo "\033[?25l";
    }

    /**
     * Shows the terminal cursor
     */
    public function showCursor(): void
    {
        echo "\033[?25h";
    }

    /**
     * Moves the cursor to the specified position
     *
     * @param int|float $x The x-coordinate (column)
     * @param int|float $y The y-coordinate (row)
     */
    public function moveCursor(int|float $x, int|float $y): void
    {
        echo "\033[" . \intval($y) . ";" . \intval($x) . "H";
    }

    /**
     * Clears the current line
     */
    public function clearLine(): void
    {
        echo "\033[2K\r";
    }

    /**
     * Colors the given text with the specified color
     *
     * @param string $text  The text to color
     * @param string $color The color name (red, green, yellow, blue, magenta, cyan)
     * @return string The colored text
     */
    public function color(string $text, string $color): string
    {
        $colors = [
            'red' => '31',
            'green' => '32',
            'yellow' => '33',
            'blue' => '34',
            'magenta' => '35',
            'cyan' => '36',
        ];

        return "\033[" . ($colors[$color] ?? '0') . "m$text\033[0m";
    }

    /**
     * Reads a single key press from the terminal
     *
     * @return bool|string The key pressed
     */
    public function getKey(): bool|string
    {
        system('stty -icanon -echo');
        $key = '';
        $first = fread(STDIN, 1);
        if ($first === "\033") {
            $key .= $first;
            stream_set_blocking(STDIN, false);
            usleep(10000);
            while (($c = fread(STDIN, 1)) !== false) {
                $key .= $c;
                if (!stream_get_meta_data(STDIN)['unread_bytes']) {
                    break;
                }
            }

            stream_set_blocking(STDIN, true);
        } else {
            $key = $first;
        }

        system('stty sane');
        return $key;
    }
}
