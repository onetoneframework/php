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
 * Marks a property for dependency injection.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Autowire
{
	/** Optional service identifier used by dependency injection integrations. */
	public ?string $value;

	/**
	 * Creates an autowiring marker.
	 *
	 * @param string|null $value Optional service identifier.
	 */
	public function __construct(?string $value = null)
	{
		$this->value = $value;
	}
}

/**
 * Preserves the legacy autowiring attribute name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[Deprecated('Use Autowire instead.')]
final class Autowiring extends Autowire
{
}
