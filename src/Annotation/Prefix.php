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
 * Class Prefix
 *
 * Annotation to define a URL prefix for a controller method or class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Prefix
{
    /**
     * @var string The prefix value.
     */
    public $value;

    /**
     * Prefix constructor.
     *
     * @param string $value The prefix value.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
