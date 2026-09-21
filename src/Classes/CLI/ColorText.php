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
use function intval;

class ColorText extends BaseClass
{
    /**
     * Foreground ANSI color codes indexed by color name.
     *
     * @var array<string, string>
     */
    private static $colors = [
        'black' => '30',
        'red' => '31',
        'green' => '32',
        'yellow' => '33',
        'blue' => '34',
        'magenta' => '35',
        'cyan' => '36',
        'white' => '37',
    ];

    /**
     * Background ANSI color codes indexed by color name.
     *
     * @var array<string, string>
     */
    private static $backgroundColors = [
        'black' => '40',
        'red' => '41',
        'green' => '42',
        'yellow' => '43',
        'blue' => '44',
        'magenta' => '45',
        'cyan' => '46',
        'white' => '47',
    ];

    /**
     * Clears the terminal viewport and moves the cursor home.
     *
     * @return void
     */
    public static function clearScreen(): void
    {
        echo "\033[2J\033[H";
    }

    /**
     * Hides the text cursor until {@see showCursor()} is called.
     *
     * @return void
     */
    public static function hideCursor(): void
    {
        echo "\033[?25l";
    }

    /**
     * Restores the text cursor after {@see hideCursor()}.
     *
     * @return void
     */
    public static function showCursor(): void
    {
        echo "\033[?25h";
    }

    /**
     * Positions the cursor at the given column and row (1-based).
     *
     * @param int|float $x Column index.
     * @param int|float $y Row index.
     *
     * @return void
     */
    public static function moveCursor($x, $y): void
    {
        echo "\033[" . intval($y) . ";" . intval($x) . "H";
    }

    /**
     * Clears the current line from the cursor to the end and returns the carriage.
     *
     * @return void
     */
    public static function clearLine(): void
    {
        echo "\033[2K\r";
    }

    /**
     * Wraps text with ANSI foreground (and optional background) styling.
     *
     * @param string $text Raw text to colorize.
     * @param string $color Named foreground color.
     * @param string|null $background Optional named background color.
     *
     * @return string Text including ANSI escape sequences.
     */
    public static function color($text, $color, $background = null): string
    {
        $backgroundColor = "";
        $textColor = self::$colors[$color] ?? '0';
        if (isset(self::$backgroundColors[$background])) {
            $backgroundColor = ";".self::$backgroundColors[$background]."";
        }

        $colorSet = "\033[" . $textColor . "{$backgroundColor}m$text\033[0m";

        return $colorSet;
    }

}
