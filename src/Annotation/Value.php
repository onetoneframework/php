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
 * Class Value
 *
 * Annotation to define a value for a property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Value
{
    /**
     * @var string $value The value to be assigned to the property. This can be used to specify a default value or a value from configuration for dependency injection.
     */
    public $value;

    /**
     * Value constructor.
     *
     * @param string $value The value to be assigned.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
