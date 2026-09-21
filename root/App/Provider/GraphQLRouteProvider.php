<?php

declare(strict_types=1);

namespace App\Provider;

use App\Controller\GraphQLController;
use Clover\Classes\Routing\Router;

final class GraphQLRouteProvider
{
	public static function register(Router $router): void
	{
		$router->get('/graphql', GraphQLController::class . '::handleGet');
		$router->post('/graphql', GraphQLController::class . '::handlePost');
	}
}
