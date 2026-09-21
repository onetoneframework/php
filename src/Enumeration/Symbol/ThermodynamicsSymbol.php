<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class ThermodynamicsSymbol
{    
    public const ENTROPY = 'S';
    public const HEAT = 'Q';
    public const INTERNAL_ENERGY = 'U';
    public const PRESSURE = 'P';
    public const TEMPERATURE = 'T';
    public const VOLUME = 'V';
}