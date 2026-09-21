<?php

declare(strict_types=1);

namespace App\Modules\Board;

use App\Modules\Board\Contract\PostRepositoryInterface;
use Clover\Framework\Module\AbstractModule;

final class BoardModule extends AbstractModule
{
	private const LEGACY_POST_SERVICE_IDENTIFIER = 'module.board.posts';

	public function getName(): string
	{
		return 'board';
	}

	/**
	 * @return string[]
	 */
	public function getExportedServiceIdentifiers(): array
	{
		return [PostRepositoryInterface::class, self::LEGACY_POST_SERVICE_IDENTIFIER];
	}
}
