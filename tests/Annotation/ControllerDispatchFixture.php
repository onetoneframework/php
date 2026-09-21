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
use Clover\Framework\Component\BaseController;

/**
 * Loaded by {@see ControllerAnnotationRouteTest} for Router::fromFile + handle() integration.
 */
#[Controller('/router-itest')]
final class ControllerDispatchFixture extends BaseController
{
	#[GetMapping('/hit')]
	public function hit(): string
	{
		return 'controller-dispatch-ok';
	}
}
