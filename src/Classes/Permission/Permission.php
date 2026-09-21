<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;

/**
 * Class Permission
 *
 * @package Clover\Classes
 */
class Permission
{
    private int $instanceMode;

    private static int $sharedMode = 0;

    /**
     * Permission constructor.
     *
     * @param int $mode
     */
    public function __construct(int $mode)
    {
        $this->instanceMode = $mode;
        self::$sharedMode = $mode;
    }

    /**
     * Get the permission mode.
     *
     * @return int
     */
    public function getMode(): int
    {
        return $this->instanceMode;
    }

    public static function getSharedMode(): int
    {
        return self::$sharedMode;
    }

    /**
     * Check if the file is a first-in-first-out pipe.
     *
     * @return bool
     */
    public function isFirstInFirstOutPipe(): bool
    {
        return ($this->instanceMode & 0x0100) === 0x0100;
    }

    /**
     * Check if the file is a character special device.
     *
     * @return bool
     */
    public function isSpecialCharacters(): bool
    {
        return ($this->instanceMode & 0x0020) === 0x0020;
    }

    /**
     * Check if the file is a directory.
     *
     * @return bool
     */
    public function isDirectory(): bool
    {
        return ($this->instanceMode & 0x0040) === 0x0040;
    }

    /**
     * Check if the file is a block special device.
     *
     * @return bool
     */
    public function isBlockSpecial(): bool
    {
        return ($this->instanceMode & 0x6000) === 0x6000;
    }

    /**
     * Check if the file is a regular file.
     *
     * @return bool
     */
    public function isRegular(): bool
    {
        return ($this->instanceMode & 0x0800) === 0x0800;
    }

    /**
     * Check if the file is a symbolic link.
     *
     * @return bool
     */
    public function isSymbolicLink(): bool
    {
        return ($this->instanceMode & 0xA000) === 0xA000;
    }

    /**
     * Check if the file is a socket.
     *
     * @return bool
     */
    public function isSocket(): bool
    {
        return ($this->instanceMode & 0xC000) === 0xC000;
    }
}
