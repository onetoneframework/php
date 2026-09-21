<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules;

use App\Modules\Board\BoardModule;
use App\Modules\Board\Contract\PostRepositoryInterface;
use App\Modules\Board\Exception\PostNotFoundException;
use App\Modules\Board\Service\PostService;
use App\Modules\Comment\Service\CommentService;
use Clover\Classes\DependencyInjection\Container;
use Clover\Framework\Module\ModuleManager;
use Clover\Framework\Module\ModuleRegistry;
use PHPUnit\Framework\TestCase;

final class ModuleIntegrationTest extends TestCase
{
	public function testBoardServiceCreatesAndRetrievesPosts(): void
	{
		$postService = new PostService();

		$createdPost = $postService->create('Module system', 'The board module is active.');

		$this->assertSame(1, $createdPost['id']);
		$this->assertSame($createdPost, $postService->getById($createdPost['id']));
		$this->assertSame([$createdPost], $postService->listAll());
	}

	public function testBoardServiceRejectsMissingPosts(): void
	{
		$postService = new PostService();

		$this->expectException(PostNotFoundException::class);
		$this->expectExceptionMessage('Post "99" was not found.');

		$postService->getById(99);
	}

	public function testCommentModuleCallsExportedBoardService(): void
	{
		$container = new Container();
		$postService = new PostService();
		$container->set(PostService::class, $postService);
		$container->bind(PostRepositoryInterface::class, PostService::class);
		$moduleRegistry = new ModuleRegistry();
		$moduleRegistry->register(new BoardModule(''));
		$commentService = new CommentService(new ModuleManager($moduleRegistry, $container));
		$post = $postService->create('Cross-module contract', 'The comment module uses the board contract.');

		$comment = $commentService->add($post['id'], 'The contract works.');

		$this->assertSame($post['id'], $comment['post_id']);
		$this->assertSame([$comment], $commentService->listByPostId($post['id']));
	}
}
