<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class LogicSymbol
{
    public const AND = '∧';
    public const EQUIVALENT = '⇔';
    public const EXISTS = '∃';
    public const FOR_ALL = '∀';
    public const IMPLIES = '⇒';
    public const NOT = '¬';
    public const OR = '∨';
}