<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\AI\Orchestration;

use Clover\Classes\AI\Orchestration\AgentResponse;
use Clover\Classes\AI\Orchestration\Attributes;
use PHPUnit\Framework\TestCase;

final class AgentResponseTest extends TestCase
{
	public function testResponseCreatesEmptyMetadataByDefault(): void
	{
		$response = new AgentResponse('normalized content');

		self::assertSame('normalized content', $response->content());
		self::assertSame(0, $response->metadata()->count());
	}

	public function testResponsePreservesNormalizedContentAndMetadata(): void
	{
		$metadata = new Attributes([
			'model' => 'internal-model',
			'tokens' => 42,
		]);
		$response = new AgentResponse('normalized content', $metadata);

		self::assertSame('normalized content', $response->content());
		self::assertSame($metadata, $response->metadata());
		self::assertSame('internal-model', $response->metadata()->get('model'));
		self::assertSame(42, $response->metadata()->get('tokens'));
	}
}
