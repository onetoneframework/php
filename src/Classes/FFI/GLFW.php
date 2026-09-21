<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\FFI;

use Clover\Classes\FFI\FFIWrapper;
use FFI;

class GLFW extends FFIWrapper
{
    private FFI $glfw;

    public function __construct()
    {
        $glfwHeader = file_get_contents(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/glfw.h"));
        $this->glfw = FFI::cdef($glfwHeader, match (PHP_OS_FAMILY) { 'Windows' => 'glfw3.dll', 'Linux' => 'libglfw.so.3', 'Darwin' => 'glfw3.dylib'});
    }
}
