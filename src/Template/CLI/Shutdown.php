<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Framework\Component\Translator;

$colors = [
    'red'      => "\033[31m",
    'green'    => "\033[32m",
    'yellow'   => "\033[33m",
    'blue'     => "\033[34m",
    'magenta'  => "\033[35m",
    'cyan'     => "\033[36m",
    'gray'     => "\033[90m",
    'reset'    => "\033[0m",
    'bold'     => "\033[1m",
];

$file = $error['file'];
$message = $error['message'];
$line = $error['line'];
$type = ReflectionHandler::getErrorTypeMessage($error['type']);

echo $colors['red'] . $colors['bold'] . Translator::trans('template_messages.cli.shutdown_banner', [], "=== [ Shutdown ] ===") . $colors['reset'] . PHP_EOL;
echo $colors['yellow'] . Translator::trans('template_messages.cli.file', [], 'File') . ": {$file}:{$line}" . $colors['reset'] . PHP_EOL;
echo $colors['magenta'] . Translator::trans('template_messages.cli.message', [], 'Message') . ": {$message}" . $colors['reset'] . PHP_EOL;
echo $colors['cyan'] . Translator::trans('template_messages.cli.type', [], 'Type') . ": {$type}" . $colors['reset'] . PHP_EOL;
echo $colors['gray'] . str_repeat('=', 60) . $colors['reset'] . PHP_EOL;
