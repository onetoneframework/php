<?php

declare(strict_types=1);

namespace App\Modules\Board\Controller;

use App\Modules\Board\Exception\PostNotFoundException;
use App\Modules\Board\Service\PostService;
use Clover\Annotation\Autowiring;
use Clover\Annotation\Prefix;
use Clover\Annotation\Route;
use Clover\Classes\HTTP\Request;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Framework\Component\BaseController;
use Clover\Framework\Component\Response;

#[Prefix('/board')]
final class BoardController extends BaseController
{
	#[Autowiring]
	public static PostService $postService;

	#[Route('GET', '/posts')]
	public function listPosts(): Response
	{
		return $this->responseJson([
			'posts' => self::$postService->listAll(),
		]);
	}

	#[Route('GET', '/posts/{id}?:(\d+)')]
	public function showPost(int $id): Response
	{
		try {
			$post = self::$postService->getById($id);
		} catch (PostNotFoundException) {
			return $this->responseJson(['error' => 'Post not found'], [])->setStatusCode(HTTPStatusCode::NOT_FOUND);
		}

		return $this->responseJson(['post' => $post]);
	}

	#[Route('POST', '/posts')]
	public function createPost(): Response
	{
		$title = (string) Request::getPostParameter('title')->trim();
		$body = (string) Request::getPostParameter('body')->trim();

		if ($title === '' || $body === '') {
			return $this->responseJson(['error' => 'Title and body are required'], [])->setStatusCode(HTTPStatusCode::UNPROCESSABLE_ENTITY);
		}

		$post = self::$postService->create($title, $body);

		return $this->responseJson(['post' => $post], [])->setStatusCode(HTTPStatusCode::CREATED);
	}
}
