<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\FFI;

use Clover\Classes\FFI\Windows\WindowsAPI;

/**
 * Reach the process-wide WindowsAPI, or skip with the reason there is not one.
 *
 * See SharedWindowsApi for why exactly one instance exists and why it is never
 * released.
 */
trait SharesOneWindowsApi
{
    private function windowsApiOrSkip(): WindowsAPI
    {
        [$windowsApi, $reason] = SharedWindowsApi::instanceOrReason();

        if ($windowsApi === null) {
            $this->markTestSkipped((string) $reason);
        }

        return $windowsApi;
    }
}
