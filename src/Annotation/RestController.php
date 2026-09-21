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
 * Class RestController
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class RestController
{
    /**
     * @var string Route prefix for all methods in this controller.
     */
    public string $value;

    /**
     * RestController constructor.
     *
     * @param string $value Route prefix.
     */
    public function __construct(string $value = '')
    {
        $this->value = $value;
    }
}
