<?php

declare(strict_types=1);

namespace App\Modules\Comment\Controller;

use App\Modules\Board\Exception\PostNotFoundException;
use App\Modules\Comment\Service\CommentService;
use Clover\Annotation\Autowiring;
use Clover\Annotation\Prefix;
use Clover\Annotation\Route;
use Clover\Classes\HTTP\Request;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Component\Response;

#[Prefix('/comment')]
final class CommentController extends BaseController
{
	#[Autowiring]
	public static CommentService $commentService;

	#[Route('GET', '/posts/{postId}?:(\d+)/comments')]
	public function listComments(int $postId): Response
	{
		try {
			$comments = self::$commentService->listByPostId($postId);
		} catch (PostNotFoundException) {
			return $this->responseJson(['error' => 'Post not found'], [])->setStatusCode(HTTPStatusCode::NOT_FOUND);
		}

		return $this->responseJson([
			'post_id' => $postId,
			'comments' => $comments,
		]);
	}

	#[Route('POST', '/posts/{postId}?:(\d+)/comments')]
	public function createComment(int $postId): Response
	{
		$body = (string) Request::getPostParameter('body')->trim();

		if ($body === '') {
			return $this->responseJson(['error' => 'Body is required'], [])->setStatusCode(HTTPStatusCode::UNPROCESSABLE_ENTITY);
		}

		try {
			$comment = self::$commentService->add($postId, $body);
		} catch (PostNotFoundException) {
			return $this->responseJson(['error' => 'Post not found'], [])->setStatusCode(HTTPStatusCode::NOT_FOUND);
		}

		return $this->responseJson(['comment' => $comment], [])->setStatusCode(HTTPStatusCode::CREATED);
	}
}
