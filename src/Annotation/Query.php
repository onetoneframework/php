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
 * Class Query
 *
 * Annotation to define a query parameter for a controller method or class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class Query
{
    /**
     * @var string The query parameter name.
     */
    public $value;

    /**
     * Query constructor.
     *
     * @param string $value The query parameter name.
     */
    public function __construct(string $value)
    {
        $this->value = $value;
    }
}
