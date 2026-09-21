<?php

declare(strict_types=1);

namespace App\Modules\Comment\Service;

use App\Modules\Board\Contract\PostRepositoryInterface;
use Clover\Framework\Module\ModuleManager;

/**
 * Comment store that validates posts through the board module.
 */
final class CommentService
{
	private const FIRST_COMMENT_IDENTIFIER = 1;
	private const IDENTIFIER_INCREMENT = 1;

	/** @var array<int, array<int, array{id: int, post_id: int, body: string}>> */
	private array $commentsByPost = [];

	/** @var array<int, int> */
	private array $nextIdentifierByPost = [];

	public function __construct(private readonly ModuleManager $moduleManager)
	{
	}

	/**
	 * @return array{id: int, post_id: int, body: string}
	 */
	public function add(int $postId, string $body): array
	{
		/** @var PostRepositoryInterface $postRepository */
		$postRepository = $this->moduleManager->service('board', PostRepositoryInterface::class);
		$postRepository->getById($postId);

		$commentIdentifier = $this->nextIdentifierByPost[$postId] ?? self::FIRST_COMMENT_IDENTIFIER;
		$comment = [
			'id' => $commentIdentifier,
			'post_id' => $postId,
			'body' => $body,
		];

		$this->commentsByPost[$postId][$commentIdentifier] = $comment;
		$this->nextIdentifierByPost[$postId] = $commentIdentifier + self::IDENTIFIER_INCREMENT;

		return $comment;
	}

	/**
	 * @return array<int, array{id: int, post_id: int, body: string}>
	 */
	public function listByPostId(int $postId): array
	{
		/** @var PostRepositoryInterface $postRepository */
		$postRepository = $this->moduleManager->service('board', PostRepositoryInterface::class);
		$postRepository->getById($postId);

		return array_values($this->commentsByPost[$postId] ?? []);
	}
}
