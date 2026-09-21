<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class SetTheorySymbol
{
    public const ELEMENT_OF = '∈';
    public const EMPTY_SET = '∅';
    public const INTERSECTION = '∩';
    public const NOT_ELEMENT_OF = '∉';
    public const PROPER_SUBSET = '⊂';
    public const SUBSET = '⊆';
    public const UNION = '∪';
}