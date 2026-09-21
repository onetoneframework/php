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

/**
 * Fixture for {@see ControllerAnnotationRouteTest}: Controller prefix + method mapping.
 */
#[Controller('/spring-fixture')]
final class ControllerRouteFixture
{
	#[GetMapping('/hello')]
	public function hello(): void
	{
	}
}
