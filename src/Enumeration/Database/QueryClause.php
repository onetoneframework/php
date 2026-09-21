<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class QueryClause
{
    public const GROUP = 'GROUP';
    public const HAVING = 'HAVING';
    public const JOIN = 'JOIN';
    public const LIMIT = 'LIMIT';
    public const ORDER = 'ORDER';
    public const VALUES = 'VALUES';
}