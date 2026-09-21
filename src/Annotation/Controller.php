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
 * stereotype for an MVC/controller class.
 *
 * Optional path prefix ($value) applies to routes declared on handler methods
 * class-level RequestMapping on a Controller class. You may instead use an empty $value and put
 * the base path on {@see RequestMapping} at class scope; {@see RouteAnnotationReader} merges that
 * path into each handler route.
 * Use {@see RestController} for API-style controllers when you want that naming convention.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class Controller
{
	/**
	 * @var string $value Class-level path prefix prepended to each method route pattern (empty string = no prefix).
	 */
	public string $value;

	/**
	 * Controller constructor.
	 *
	 * @param string $value Optional class-level path prefix for routes.
	 */
	public function __construct(string $value = '')
	{
		$this->value = $value;
	}
}
