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
 * Enumeration class for Integrated Development Environments (IDEs).
 */
abstract class IDE
{
    public const ATOM = "atom";
    public const EMACS = "emacs";
    public const MAC_VIM = "mac_vim";
    public const PHPSTORM = "phpstorm";
    public const SUBLIME = "sublime";
    public const TEXTMATE = "textmate";
    public const VISUAL_STUIO_CODE = "vscode";
}
