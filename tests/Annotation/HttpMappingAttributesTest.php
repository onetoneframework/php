<?php

declare(strict_types=1);

namespace Clover\Tests\Annotation;

use Clover\Annotation\DeleteMapping;
use Clover\Annotation\GetMapping;
use Clover\Annotation\PatchMapping;
use Clover\Annotation\PostMapping;
use Clover\Annotation\PutMapping;
use Clover\Annotation\RequestMapping;
use PHPUnit\Framework\TestCase;

final class HttpMappingAttributesTest extends TestCase
{
	public function testGetMappingDefaults(): void
	{
		$mapping = new GetMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testGetMappingAcceptsRouteConstraints(): void
	{
		$mapping = new GetMapping('/users/{id}', 'api.example.test', 20, 'path', ['format' => 'json']);

		$this->assertSame('/users/{id}', $mapping->value);
		$this->assertSame('api.example.test', $mapping->host);
		$this->assertSame(20, $mapping->priority);
		$this->assertSame('path', $mapping->pathQueryKey);
		$this->assertSame(['format' => 'json'], $mapping->query);
	}

	public function testPostMappingDefaults(): void
	{
		$mapping = new PostMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testPostMappingAcceptsRouteConstraints(): void
	{
		$mapping = new PostMapping('/users', 'write.example.test', 15, 'route', ['mode' => 'create']);

		$this->assertSame('/users', $mapping->value);
		$this->assertSame('write.example.test', $mapping->host);
		$this->assertSame(15, $mapping->priority);
		$this->assertSame('route', $mapping->pathQueryKey);
		$this->assertSame(['mode' => 'create'], $mapping->query);
	}

	public function testPutMappingDefaults(): void
	{
		$mapping = new PutMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testPutMappingAcceptsRouteConstraints(): void
	{
		$mapping = new PutMapping('/users/{id}', 'write.example.test', 10, 'virtual', ['mode' => 'replace']);

		$this->assertSame('/users/{id}', $mapping->value);
		$this->assertSame('write.example.test', $mapping->host);
		$this->assertSame(10, $mapping->priority);
		$this->assertSame('virtual', $mapping->pathQueryKey);
		$this->assertSame(['mode' => 'replace'], $mapping->query);
	}

	public function testPatchMappingDefaults(): void
	{
		$mapping = new PatchMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testPatchMappingAcceptsRouteConstraints(): void
	{
		$mapping = new PatchMapping('/users/{id}', 'write.example.test', 5, 'path', ['mode' => 'patch']);

		$this->assertSame('/users/{id}', $mapping->value);
		$this->assertSame('write.example.test', $mapping->host);
		$this->assertSame(5, $mapping->priority);
		$this->assertSame('path', $mapping->pathQueryKey);
		$this->assertSame(['mode' => 'patch'], $mapping->query);
	}

	public function testDeleteMappingDefaults(): void
	{
		$mapping = new DeleteMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testDeleteMappingAcceptsRouteConstraints(): void
	{
		$mapping = new DeleteMapping('/users/{id}', 'write.example.test', 30, 'path', ['confirm' => 'true']);

		$this->assertSame('/users/{id}', $mapping->value);
		$this->assertSame('write.example.test', $mapping->host);
		$this->assertSame(30, $mapping->priority);
		$this->assertSame('path', $mapping->pathQueryKey);
		$this->assertSame(['confirm' => 'true'], $mapping->query);
	}

	public function testRequestMappingDefaults(): void
	{
		$mapping = new RequestMapping();

		$this->assertSame('*', $mapping->value);
		$this->assertSame('*', $mapping->method);
		$this->assertSame('*', $mapping->host);
		$this->assertSame(0, $mapping->priority);
		$this->assertSame('', $mapping->pathQueryKey);
		$this->assertSame([], $mapping->query);
	}

	public function testRequestMappingAcceptsMethodAndRouteConstraints(): void
	{
		$mapping = new RequestMapping('/search', 'OPTIONS', 'api.example.test', 40, 'uri', ['version' => '2']);

		$this->assertSame('/search', $mapping->value);
		$this->assertSame('OPTIONS', $mapping->method);
		$this->assertSame('api.example.test', $mapping->host);
		$this->assertSame(40, $mapping->priority);
		$this->assertSame('uri', $mapping->pathQueryKey);
		$this->assertSame(['version' => '2'], $mapping->query);
	}
}
