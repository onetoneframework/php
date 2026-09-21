<?php

declare(strict_types=1);

use App\Modules\Board\Contract\PostRepositoryInterface;
use App\Modules\Board\Service\PostService;
use Clover\Classes\DependencyInjection\Container;

return static function (Container $container): void {
	$registered = $container->set(
		PostService::class,
		static function (): PostService {
			return new PostService();
		}
	);

	if (!$registered) {
		throw new RuntimeException('The board post service could not be registered.');
	}

	$container->bind('module.board.posts', PostService::class);
	$container->bind(PostRepositoryInterface::class, PostService::class);
};
