<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class QueryOprator
{
    public const AND = 'AND';
    public const BETWEEN = 'BETWEEN';
    public const DISTINCT = 'DISTINCT';
    public const EQUAL = '=';
    public const EXISTS = 'EXISTS';
    public const GREATER_THAN = '>';
    public const GREATER_THAN_OR_EQUAL = '>=';
    public const IN = 'IN';
    public const IS_NOT_NULL = 'IS NOT NULL';
    public const IS_NULL = 'IS NULL';
    public const LESS_THAN = '<';
    public const LESS_THAN_OR_EQUAL = '<=';
    public const LIKE = 'LIKE';
    public const NOT_EQUAL = '<>';
    public const NOT_IN = 'NOT IN';
    public const OR = 'OR';
}