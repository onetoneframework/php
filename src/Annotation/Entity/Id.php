<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation\Entity;

use Attribute;

/**
 * Class ID
 *
 * Annotation to define the primary key for an entity property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class ID
{
    /**
     * ID constructor.
     */
    public function __construct()
    {
    }
}
