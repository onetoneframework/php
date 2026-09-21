<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Database;

use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\Database\RelationLoader;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Entity for the relation-loader tests. Named so it cannot collide with the
 * fixtures the sibling ActiveRecord test classes declare, which share one
 * process.
 */
class RelationLoaderSubject extends ActiveRecord
{
	protected ?string $table = 'relation_loader_subjects';

	/**
	 * @Id
	 * @Column(name="id", type="integer", nullable=false)
	 */
	protected int $id;

	/**
	 * @Column(name="title", type="string", nullable=true)
	 */
	protected ?string $title;
}

/**
 * Characterisation tests for the relation loading moved out of ActiveRecord.
 *
 * The loading itself needs a database, and this suite has none - the sibling
 * tests that do are skipped for want of pdo_sqlite. What is checked here is
 * everything reachable without one: that the four seams the move opened answer
 * correctly, that the guard clauses still guard, and that the entry points
 * application code binds against still resolve.
 */
final class RelationLoaderTest extends TestCase
{
	private mixed $originalConnection = null;

	protected function setUp(): void
	{
		parent::setUp();
		$this->originalConnection = $this->connectionProperty()->getValue();
	}

	protected function tearDown(): void
	{
		$this->connectionProperty()->setValue(null, $this->originalConnection);
		parent::tearDown();
	}

	/**
	 * The connection is process-wide static state that earlier test classes
	 * leave behind, so the tests about its absence clear it themselves.
	 */
	private function connectionProperty(): \ReflectionProperty
	{
		$property = new \ReflectionProperty(ActiveRecord::class, 'db');
		$property->setAccessible(true);

		return $property;
	}

	private function withoutConnection(): void
	{
		$this->connectionProperty()->setValue(null, null);
	}

	public function testAnEntityWithoutAConnectionRefusesToLoadARelation(): void
	{
		$this->withoutConnection();
		$subject = new RelationLoaderSubject();

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('Database connection not set');

		(new RelationLoader($subject))->load('anything', ['type' => 'oneToMany']);
	}

	public function testEagerLoadingAnEmptyCollectionReturnsIt(): void
	{
		$this->assertSame([], RelationLoader::eagerLoadForCollection([], ['relation']));
	}

	public function testEagerLoadingWithNoRelationsReturnsTheCollection(): void
	{
		$entities = [new RelationLoaderSubject()];

		$this->assertSame($entities, RelationLoader::eagerLoadForCollection($entities, []));
	}

	public function testEagerLoadingWithoutAConnectionReturnsTheCollection(): void
	{
		$this->withoutConnection();
		$entities = [new RelationLoaderSubject()];

		$this->assertNull(ActiveRecord::getConnection());
		$this->assertSame($entities, RelationLoader::eagerLoadForCollection($entities, ['relation']));
	}

	/**
	 * Application code calls this as Model::eagerLoadForCollection(...), so it
	 * has to stay on ActiveRecord whatever owns the work.
	 */
	public function testActiveRecordStillAnswersEagerLoadForCollection(): void
	{
		$entities = [new RelationLoaderSubject()];

		$this->assertSame($entities, RelationLoaderSubject::eagerLoadForCollection($entities, []));
	}

	#region the seams the move opened

	public function testMetadataForReadsTheCacheByClassName(): void
	{
		new RelationLoaderSubject();

		$metadata = ActiveRecord::metadataFor(RelationLoaderSubject::class);

		$this->assertIsArray($metadata);
		$this->assertSame('relation_loader_subjects', $metadata['table']);
		$this->assertArrayHasKey('id', $metadata['columns']);
	}

	public function testMetadataForAnUnknownClassIsNull(): void
	{
		$this->assertNull(ActiveRecord::metadataFor('No\\Such\\Entity'));
	}

	public function testMetadataForAgreesWithTheInstanceMethod(): void
	{
		$subject = new RelationLoaderSubject();

		$this->assertSame($subject->getMetadata(), ActiveRecord::metadataFor(RelationLoaderSubject::class));
	}

	/**
	 * @dataProvider seamProvider
	 */
	public function testTheSeamsRelationLoaderNeedsArePublic(string $method): void
	{
		$reflection = new ReflectionClass(ActiveRecord::class);

		$this->assertTrue(
			$reflection->getMethod($method)->isPublic(),
			$method . '() was widened for RelationLoader; narrowing it again breaks relation loading.'
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function seamProvider(): array
	{
		return [
			'getMetadata' => ['getMetadata'],
			'mapRowToEntity' => ['mapRowToEntity'],
			'castValueToType' => ['castValueToType'],
			'metadataFor' => ['metadataFor'],
			'getConnection' => ['getConnection'],
		];
	}

	#endregion

	public function testTheLoaderCoversEveryRelationshipKind(): void
	{
		$reflection = new ReflectionClass(RelationLoader::class);

		foreach (['OneToMany', 'ManyToOne', 'OneToOne', 'ManyToMany'] as $kind) {
			$this->assertTrue(
				$reflection->hasMethod('load' . $kind . 'Relationship'),
				'Lazy loading for ' . $kind . ' must exist.'
			);
			$this->assertTrue(
				$reflection->hasMethod('eagerLoad' . $kind),
				'Eager loading for ' . $kind . ' must exist.'
			);
			$this->assertTrue(
				$reflection->hasMethod('eagerLoad' . $kind . 'ForCollection'),
				'Collection eager loading for ' . $kind . ' must exist.'
			);
		}
	}
}
