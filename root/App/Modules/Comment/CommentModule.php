<?php

declare(strict_types=1);

namespace App\Modules\Comment;

use App\Modules\Comment\Service\CommentService;
use Clover\Framework\Module\AbstractModule;

final class CommentModule extends AbstractModule
{
	private const LEGACY_COMMENT_SERVICE_IDENTIFIER = 'module.comment.comments';

	public function getName(): string
	{
		return 'comment';
	}

	/**
	 * @return string[]
	 */
	public function getDependencies(): array
	{
		return ['board'];
	}

	/**
	 * @return string[]
	 */
	public function getExportedServiceIdentifiers(): array
	{
		return [CommentService::class, self::LEGACY_COMMENT_SERVICE_IDENTIFIER];
	}
}
