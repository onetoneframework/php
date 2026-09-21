<?php

declare(strict_types=1);

namespace App\Modules\Board\Contract;

/**
 * Public post lookup contract exported by the board module.
 */
interface PostRepositoryInterface
{
	/**
	 * @return array{id: int, title: string, body: string}
	 */
	public function getById(int $postIdentifier): array;
}
