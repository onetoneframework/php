<?php

declare(strict_types=1);

namespace App\Modules\Board\Service;

use App\Modules\Board\Contract\PostRepositoryInterface;
use App\Modules\Board\Exception\PostNotFoundException;

/**
 * In-memory post store for the board module.
 */
final class PostService implements PostRepositoryInterface
{
	private const FIRST_POST_IDENTIFIER = 1;

	/** @var array<int, array{id: int, title: string, body: string}> */
	private array $posts = [];

	private int $nextIdentifier = self::FIRST_POST_IDENTIFIER;

	/**
	 * @return array{id: int, title: string, body: string}
	 */
	public function create(string $title, string $body): array
	{
		$post = [
			'id' => $this->nextIdentifier,
			'title' => $title,
			'body' => $body,
		];

		$this->posts[$this->nextIdentifier] = $post;
		++$this->nextIdentifier;

		return $post;
	}

	/**
	 * @return array{id: int, title: string, body: string}
	 */
	public function getById(int $postIdentifier): array
	{
		if (!array_key_exists($postIdentifier, $this->posts)) {
			throw new PostNotFoundException($postIdentifier);
		}

		return $this->posts[$postIdentifier];
	}

	/**
	 * @return array<int, array{id: int, title: string, body: string}>
	 */
	public function listAll(): array
	{
		return array_values($this->posts);
	}

}
