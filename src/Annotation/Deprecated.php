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
 * Marks a framework class or callable as deprecated.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_FUNCTION | Attribute::TARGET_METHOD)]
final class Deprecated
{
	public string $message;

	/** Creates a deprecation marker with an optional message. */
	public function __construct(string $message = '')
	{
		$this->message = $message;
	}
}
