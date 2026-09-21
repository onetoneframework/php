<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Protocol Enumeration
 */
abstract class Protocol
{
    public const ATOM = "atom://";
    public const EMACS = "emacs://";
    public const FILE = "file://";
    public const MAC_VIM = "mvim://";
    public const PHAR = "phar://";
    public const PHPSTORM = "phpstorm://";
    public const SUBLIME = "subl://";
    public const TEXTMATE = "txmt://";
    public const VISUAL_STUIO_CODE = "vscode://";
}
