<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem;

class ServerAPI
{
    /**
     * CTRL+C handler — runs on a Windows signal thread.
     * MUST NOT call any FFI function here (PostThreadMessageW, UnhookWinEvent, etc.) 
     * because PHP FFI is not thread-safe and doing so deadlocks the process.  
     * We only flip a plain PHP boolean; the main-thread pump checks it on every iteration.
     * 
     * @param callable|null $handler
     * @param bool $add
     * @return bool
     */
    public static function setControlHandler(callable|null $handler, bool $add = true): bool
    {
        return sapi_windows_set_ctrl_handler($handler, $add);
    }
}
