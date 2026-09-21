<?php

declare(strict_types=1);

use App\Modules\Comment\Service\CommentService;
use Clover\Classes\DependencyInjection\Container;
use Clover\Framework\Module\ModuleManager;

return static function (Container $container): void {
	$registered = $container->set(
		CommentService::class,
		static function (Container $container): CommentService {
			return new CommentService($container->get(ModuleManager::class));
		}
	);

	if (!$registered) {
		throw new RuntimeException('The comment service could not be registered.');
	}

	$container->bind('module.comment.comments', CommentService::class);
};
