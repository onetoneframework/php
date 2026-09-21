<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation;

use Clover\Enumeration\ContentType as ContentTypeEnum;
use Attribute;

/**
 * Class ContentType
 *
 * Annotation to define the content type for a controller method or class.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class ContentType
{
    /**
     * @var string|ContentTypeEnum The content type enumeration value.
     */
    public $value;

    /**
     * ContentType constructor.
     *
     * @param string|ContentTypeEnum $value The content type enumeration value.
     */
    public function __construct(string|ContentTypeEnum $value)
    {
        $this->value = $value;
    }
}
