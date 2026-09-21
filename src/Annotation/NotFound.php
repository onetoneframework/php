<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation;

use Attribute;

/**
 * Class NotFound
 *
 * Annotation to define a not found handler for a controller class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class NotFound
{
    /**
     * @var string $value The not found handler class name.
     */
    public string $value;

    /**
     * NotFound constructor.
     *
     * @param string $value The not found handler class name.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
