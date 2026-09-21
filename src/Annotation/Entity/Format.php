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
 * Defines a serialization format for an entity property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Format
{
	/** Creates a property format attribute. */
	public function __construct(public string $value)
	{
	}
}
