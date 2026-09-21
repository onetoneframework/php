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
 * Class Middleware
 *
 * Annotation to define middleware for a controller method or class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Middleware
{
    /**
     * @var string The middleware class name.
     */
    public $value;

    /**
     * Middleware constructor.
     *
     * @param string $value The middleware class name.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
