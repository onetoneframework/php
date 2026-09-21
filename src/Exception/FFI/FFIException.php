<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Exception\FFI;

use RuntimeException;

/**
 * Base class for every failure raised while preparing a native FFI binding.
 *
 * The FFI wrappers used to call `exit()` / `die()` on these two failures, which
 * terminated the whole process — including a web request that merely tried to
 * construct the object behind a feature check. They throw now, so a caller can
 * fall back to a non-native path.
 */
class FFIException extends RuntimeException
{
}
