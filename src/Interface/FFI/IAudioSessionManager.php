<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Interface\FFI;

abstract class IAudioSessionManager
{
    /**
     * @var object{GetSessionEnumerator: callable, RegisterSessionNotification: callable, UnregisterSessionNotification: callable, RegisterDuckNotification: callable, UnregisterDuckNotification: callable} $lpVtbl
     */
    public $lpVtbl;
}
