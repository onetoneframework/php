<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class AWSACLFlag
{
    public const ACL_AUTHENTICATED_READ = 'authenticated-read';
    public const ACL_PRIVATE = 'private';
    public const ACL_PUBLIC_READ = 'public-read';
    public const ACL_PUBLIC_READ_WRITE = 'public-read-write';
}
