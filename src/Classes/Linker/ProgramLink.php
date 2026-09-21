<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Linker;

use Clover\Enumeration\ProgramLink as Link;

/**
 * Class ProgramLink
 *
 * @package Clover\Classes\Linker
 */
class ProgramLink
{

    private ?Link $ide = null;

    /**
     * ProgramLink constructor.
     *
     * @param Link|null $ide
     */
    public function __construct(?Link $ide = null)
    {
        $this->ide = $ide ?? Link::VLC;
    }
}