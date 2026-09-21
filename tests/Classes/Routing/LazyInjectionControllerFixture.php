<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Routing;

use Clover\Annotation\Autowiring;
use Clover\Annotation\Route;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Framework\Component\BaseController;

final class LazyInjectionControllerFixture extends BaseController
{
	#[Autowiring]
	private static PHPDataObject $database;

	#[Route(method: 'GET', pattern: '/lazy-injection')]
	public function handle(): string
	{
		return self::$database instanceof PHPDataObject ? 'injected' : 'missing';
	}
}
