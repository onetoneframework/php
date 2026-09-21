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
 * File Mode Enumeration
 */
abstract class FileMode
{
    /**
     * Reading from beginning of binary file
     */
    public const READ_ONLY = 'r';

    /**
     * Reading and Writing from beginning of file
     */
    public const READ_OVERWRITE = 'r+';

    /**
     * Writing file with Truncate
     */
    public const WRITE_ONLY = 'w';

    /**
     * Reading and Writing file with Truncate
     */
    public const READ_WRITE_TRUNCATE = 'w+';

    /**
     * Append to the end of the file
     */
    public const APPEND_WRITE_ONLY = 'a';

    /**
     * Reading or Append to the end of the file
     */
    public const APPEND_READ_WRITE = 'a+';

    /**
     * Append to the end of the binary file
     */
    public const APPEND_BINARY_WRITE_ONLY = 'ab';

    /**
     * Reading or Append to the end of the binary file
     */
    public const APPEND_BINARY_READ_WRITE_ONLY = 'ab+';

    /**
     * Reading from beginning of binary file
     */
    public const READ_BINARY_ONLY = 'rb';

    /**
     * Reading and Writing from beginning of binary file
     */
    public const READ_BINARY_TRUNCATE = 'rb+';

    /**
     * Writing binary file with Truncate
     */
    public const WRITE_BINARY_ONLY = 'wb';

    /**
     * Reading and Writing binary file with Truncate
     */
    public const WRITE_BINARY_TRUNCATE = 'wb+';

    public const NEW_WRITE_ONLY = 'x';

    public const NEW_READ_WRITE_ONLY = 'x+';
}
