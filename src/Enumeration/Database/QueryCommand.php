<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class QueryCommand
{
    public const ALTER = 'ALTER';
    public const BEGIN = 'BEGIN';
    public const COMMIT = 'COMMIT';
    public const CREATE = 'CREATE';
    public const DELETE = 'DELETE';
    public const DROP = 'DROP';
    public const GRANT = 'GRANT';
    public const INSERT = 'INSERT';
    public const JOIN = 'JOIN';
    public const REVOKE = 'REVOKE';
    public const ROLLBACK = 'ROLLBACK';
    public const SELECT = 'SELECT';
    public const TRUNCATE = 'TRUNCATE';
    public const UPDATE = 'UPDATE';
}