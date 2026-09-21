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
use Throwable;
use function defined;
use function define;
use function extension_loaded;

/**
 * One WindowsAPI for the whole process, shared by every test that makes live
 * calls.
 *
 * Constructing it opens twenty-three libraries, and releasing an FFI handle
 * built from windows.h corrupts PHP's heap on this build. Two create-and-drop
 * cycles are enough, with no framework code involved at all:
 *
 *     $h = FFI::cdef($windowsHeader . $user32Header, 'User32.dll');
 *     unset($h);          // twice over, and the process dies with
 *                         // "zend_mm_heap corrupted"
 *
 * A test that builds its own instance walks straight into that, so the suite
 * died partway through whichever test happened to be running. The instance is
 * therefore built once and never released, which also saves twenty-three cdefs
 * per test.
 *
 * The holder is a class of its own rather than a static on the trait: a trait's
 * static is per-using-class, so four test classes would hold four instances and
 * the process would be back to releasing several at shutdown.
 */
final class SharedWindowsApi
{
    private static ?WindowsAPI $instance = null;

    private static ?string $reason = null;

    private static bool $attempted = false;

    /**
     * The shared instance, or the reason there is not one.
     *
     * @return array{0: WindowsAPI|null, 1: string|null}
     */
    public static function instanceOrReason(): array
    {
        if (self::$attempted) {
            return [self::$instance, self::$reason];
        }

        self::$attempted = true;

        if (!extension_loaded('ffi')) {
            self::$reason = 'FFI extension is not available.';

            return [null, self::$reason];
        }

        if (($_ENV['RUN_WINDOWS_FFI_TESTS'] ?? 'false') !== 'true') {
            self::$reason = 'Windows FFI integration tests are disabled by default.';

            return [null, self::$reason];
        }

        if (!defined('BASE_PATH')) {
            define('BASE_PATH', __DIR__ . '/../../../root');
        }

        try {
            self::$instance = new WindowsAPI();
        } catch (Throwable $throwable) {
            self::$reason = $throwable->getMessage();
        }

        return [self::$instance, self::$reason];
    }
}
