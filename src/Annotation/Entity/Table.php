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
 * Defines the database table mapped to an entity class.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Table
{
	/** Creates a table mapping attribute. */
	public function __construct(public string $name)
	{
	}
}
