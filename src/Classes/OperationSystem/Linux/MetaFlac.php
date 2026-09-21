<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem\Linux;

use Clover\Classes\OperationSystem;
use Clover\Classes\OperationSystem\Linux\Binary;
use RuntimeException;
use function sprintf;

/**
 * Class MetaFlac
 *
 * @package Clover\Classes\OperationSystem\Linux
 */
class MetaFlac extends Binary
{
    /**
     * Get the MD5 sum of the specified FLAC files.
     *
     * @param array $files An array of file paths to FLAC files for which to calculate the MD5 sum.
     * 
     * @return bool|string|null
     * 
     * @throws RuntimeException
     */
    public static function getMD5Sum(array $files = []): bool|string|null
    {
        if (!self::isMetaFlacInstalled()) {
            throw new RuntimeException('Metaflac is not installed');
        }

        return OperationSystem::executeShell(sprintf("/usr/bin/metaflac --show-md5sum %s 2>&1", join(' ', $files)));
    }
}
