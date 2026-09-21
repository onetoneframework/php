<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Annotation;

use Clover\Annotation\Controller;
use Clover\Annotation\GetMapping;
use Clover\Annotation\RequestMapping;

/**
 * stereotype on class + class-level path via RequestMapping only.
 */
#[Controller]
#[RequestMapping('/spring-class-rm')]
final class ClassRequestMappingFixture
{
	#[GetMapping('/item')]
	public function item(): void
	{
	}
}
