<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


use function sem_get;

class Semaphore
{

    /**
     * Get a semaphore ID.
     *
     * @param int  $key
     * @param int  $max_acquire
     * @param int  $permissions
     * @param bool $auto_release
     * @return resource|false
     */
    public static function getId(int $key, int $max_acquire = 1, int $permissions = 0666, bool $auto_release = true): mixed
    {
        if (function_exists("sem_get")) {
            return sem_get($key, $max_acquire, $permissions, $auto_release);
        }

        return false;
    }
}
