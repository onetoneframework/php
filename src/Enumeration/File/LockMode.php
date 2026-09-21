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
 * Lock Mode Enumeration
 */
abstract class LockMode
{
    /**
     * Acquire an exclusive lock
     * @var string
     */
    public const ACQUIRE_EXCLUSIVE_LOCK = 'r';
    /**
     * Acquire a shared lock
     * @var string
     */
    public const ACQUIRE_SHARED_LOCK = 'r';
    /**
     * Non-blocking operation while locking
     * @var string
     */
    public const RELEASE_LOCK = 'r';
}
