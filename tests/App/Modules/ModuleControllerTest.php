<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules;

use App\Modules\Board\BoardModule;
use App\Modules\Board\Contract\PostRepositoryInterface;
use App\Modules\Board\Controller\BoardController;
use App\Modules\Board\Service\PostService;
use App\Modules\Comment\Controller\CommentController;
use App\Modules\Comment\Service\CommentService;
use Clover\Classes\DependencyInjection\Container;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Framework\Module\ModuleManager;
use Clover\Framework\Module\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleControllerTest extends TestCase
{
	private PostService $postService;
	private CommentService $commentService;

	protected function setUp(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_POST = [];
		$this->postService = new PostService();
		$container = new Container();
		$this->assertTrue($container->set(PostService::class, $this->postService));
		$container->bind(PostRepositoryInterface::class, PostService::class);
		$moduleRegistry = new ModuleRegistry();
		$moduleRegistry->register(new BoardModule(''));
		$this->commentService = new CommentService(new ModuleManager($moduleRegistry, $container));
		BoardController::$postService = $this->postService;
		CommentController::$commentService = $this->commentService;
	}

	protected function tearDown(): void
	{
		$_POST = [];
		unset($_SERVER['REQUEST_METHOD']);
	}

	public function testBoardControllerListsPosts(): void
	{
		$this->postService->create('First post', 'Board content');

		$response = (new BoardController())->listPosts();

		$this->assertSame('{"posts":[{"id":1,"title":"First post","body":"Board content"}]}', $response->getBody());
		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
	}

	public function testBoardControllerReturnsRequestedPost(): void
	{
		$post = $this->postService->create('First post', 'Board content');

		$response = (new BoardController())->showPost($post['id']);

		$this->assertSame('{"post":{"id":1,"title":"First post","body":"Board content"}}', $response->getBody());
		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
	}

	public function testBoardControllerReturnsNotFoundForMissingPost(): void
	{
		$response = (new BoardController())->showPost(99);

		$this->assertSame(HTTPStatusCode::NOT_FOUND, $response->getStatusCode());
	}

	public function testBoardControllerCreatesValidPost(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['title' => ' Created post ', 'body' => ' Posted content '];

		$response = (new BoardController())->createPost();

		$this->assertSame(HTTPStatusCode::CREATED, $response->getStatusCode());
		$this->assertSame('Created post', $this->postService->getById(1)['title']);
	}

	public function testBoardControllerRejectsInvalidPost(): void
	{
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['title' => ' ', 'body' => 'Posted content'];

		$response = (new BoardController())->createPost();

		$this->assertSame(HTTPStatusCode::UNPROCESSABLE_ENTITY, $response->getStatusCode());
		$this->assertSame([], $this->postService->listAll());
	}

	public function testCommentControllerCreatesAndListsComments(): void
	{
		$post = $this->postService->create('First post', 'Board content');
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST = ['body' => ' First comment '];

		$createResponse = (new CommentController())->createComment($post['id']);
		$listResponse = (new CommentController())->listComments($post['id']);

		$this->assertSame(HTTPStatusCode::CREATED, $createResponse->getStatusCode());
		$this->assertSame(
			'{"post_id":1,"comments":[{"id":1,"post_id":1,"body":"First comment"}]}',
			$listResponse->getBody()
		);
	}

	public function testCommentControllerReturnsNotFoundForMissingPost(): void
	{
		$response = (new CommentController())->listComments(99);

		$this->assertSame(HTTPStatusCode::NOT_FOUND, $response->getStatusCode());
	}
}
