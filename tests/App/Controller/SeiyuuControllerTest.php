<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\App\Controller;

use App\Controller\SeiyuuController;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Database\ActiveRecord;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Framework\Component\Renderer;
use Clover\Framework\Component\Resource;
use ErrorException;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use function define;
use function defined;
use function dirname;
use function json_decode;

/**
 * Drives the real SeiyuuController against a real database.
 *
 * SQLite in memory is used because it needs no server, no credentials and no teardown; the schema is
 * built from the #[Entity\Column] annotations on App\Entity\* and discarded with the connection. The
 * statements are the ones MySQL receives, so this covers query construction, binding, relation
 * loading and the JSON projection together.
 *
 * Every assertion runs through withoutDiagnostics(), which turns PHP warnings into failures. That is
 * the point of this suite rather than a detail of it: when the relations these endpoints depend on
 * are not loaded, PHP only warns, the response is still HTTP 200, and the payload silently carries
 * nulls where the portrait, gender and birthday should be. A test that checked the status code, or
 * merely that some rows came back, would pass against that.
 *
 * The suite skips when pdo_sqlite is absent. To run it:
 *
 *   php -d extension=php_pdo_sqlite.dll vendor/phpunit/phpunit/phpunit -c phpunit.xml.dist \
 *       --filter SeiyuuControllerTest
 */
final class SeiyuuControllerTest extends TestCase
{
	private SeiyuuController $controller;

	protected function setUp(): void
	{
		if (!extension_loaded('pdo_sqlite')) {
			$this->markTestSkipped('pdo_sqlite is not enabled in this PHP runtime.');
		}

		if (!defined('BASE_PATH')) {
			define('BASE_PATH', dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'root');
		}

		ActiveRecord::setDatabaseConnection($this->createSchema());

		$container = new Container();
		$container->set(Resource::class, new Resource('seiyuu-controller-test'));
		$container->set(Renderer::class, new Renderer());

		$this->controller = new SeiyuuController($container);
	}

	#region fixtures

	/**
	 * Build the seiyuu-domain schema and a deterministic fixture set.
	 *
	 * Table and column names mirror the live database exactly — voice_actor, voice_actor_detail and
	 * visual_novel_attachment — not the plural names ActiveRecord derives from the class names. The
	 * entities carry an explicit $table for that reason, and this schema has to track it: when the
	 * two disagree every query fails with "no such table".
	 */
	private function createSchema(): PDO
	{
		$connection = new PDO('sqlite::memory:');
		$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$connection->exec('CREATE TABLE `visual_novel_attachment` (`no` INTEGER PRIMARY KEY AUTOINCREMENT, `path` TEXT, `regdate` TEXT NULL, `comment` TEXT NULL)');
		$connection->exec(
			'CREATE TABLE `voice_actor` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`name` TEXT NOT NULL,
				`is_representative` INTEGER NOT NULL DEFAULT 0,
				`gender` INTEGER NULL,
				`relative_no` INTEGER NULL,
				`birthday` TEXT NULL,
				`country` TEXT NULL
			)'
		);
		$connection->exec(
			'CREATE TABLE `voice_actor_detail` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`attachment_no` INTEGER NULL,
				`voice_actor_no` INTEGER NOT NULL,
				`birthday` TEXT NULL,
				`gender` INTEGER NULL,
				`height` INTEGER NULL
			)'
		);

		// The detail view walks appearance -> {character, brand, seiyuu, game}, so those tables have
		// to exist for it to be exercised at all.
		$connection->exec(
			'CREATE TABLE `visual_novel` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`release` INTEGER NULL,
				`attachment_no` INTEGER NULL,
				`brand_no` INTEGER NULL,
				`type` TEXT NULL,
				`title` TEXT NULL
			)'
		);
		$connection->exec(
			'CREATE TABLE `visual_novel_brand` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`attachment_no` INTEGER NULL,
				`title` TEXT NULL
			)'
		);
		$connection->exec(
			'CREATE TABLE `visual_novel_character` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`hscene` INTEGER NULL,
				`title` TEXT NULL,
				`visual_novel_no` INTEGER NULL
			)'
		);
		$connection->exec(
			'CREATE TABLE `visual_novel_appearance` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`comment` TEXT NULL,
				`voice_actor_no` INTEGER NULL,
				`visual_novel_no` INTEGER NULL,
				`visual_novel_brand_no` INTEGER NULL,
				`character_no` INTEGER NULL
			)'
		);
		$connection->exec(
			'CREATE TABLE `visual_novel_related_attachment` (
				`no` INTEGER PRIMARY KEY AUTOINCREMENT,
				`filename` TEXT NULL,
				`type` TEXT NULL,
				`related_no` INTEGER NULL,
				`path` TEXT NULL
			)'
		);

		$connection->exec("INSERT INTO `visual_novel_brand` (`no`, `attachment_no`, `title`) VALUES (1, 1, 'Foglio')");
		$connection->exec("INSERT INTO `visual_novel` (`no`, `release`, `attachment_no`, `brand_no`, `type`, `title`) VALUES (1, 20060324, 1, 1, 'game', 'Mezzo Forte')");
		$connection->exec("INSERT INTO `visual_novel_character` (`no`, `hscene`, `title`, `visual_novel_no`) VALUES (1, 1, 'Mikura', 1)");
		// The second row deliberately points at a brand that does not exist, mirroring the sentinel
		// -1 foreign keys the live data carries, so the null-safe projection is covered.
		$connection->exec("INSERT INTO `visual_novel_appearance` (`comment`, `voice_actor_no`, `visual_novel_no`, `visual_novel_brand_no`, `character_no`) VALUES ('lead role', 1, 1, 1, 1)");
		$connection->exec("INSERT INTO `visual_novel_appearance` (`comment`, `voice_actor_no`, `visual_novel_no`, `visual_novel_brand_no`, `character_no`) VALUES (NULL, 1, 1, -1, 1)");

		$seiyuus = [
			['Aoi Yuuki', 2, '1992-03-27', '/App/Attachment/Seiyuu/aoi.webp'],
			['Ayane Sakura', 2, '1994-01-29', '/App/Attachment/Seiyuu/ayane.webp'],
			['Kana Hanazawa', 2, '1989-02-25', '/App/Attachment/Seiyuu/kana.webp'],
			['Mamoru Miyano', 1, '1983-06-08', '/App/Attachment/Seiyuu/mamoru.webp'],
			['Rie Takahashi', 2, '1994-02-27', '/App/Attachment/Seiyuu/rie.webp'],
		];

		$attachment = $connection->prepare('INSERT INTO `visual_novel_attachment` (`path`) VALUES (?)');
		$seiyuu = $connection->prepare('INSERT INTO `voice_actor` (`name`, `is_representative`, `gender`, `birthday`) VALUES (?, 0, ?, ?)');
		$detail = $connection->prepare('INSERT INTO `voice_actor_detail` (`attachment_no`, `voice_actor_no`, `birthday`, `gender`, `height`) VALUES (?, ?, ?, ?, 160)');

		foreach ($seiyuus as $index => [$name, $gender, $birthday, $path]) {
			$attachment->execute([$path]);
			$seiyuu->execute([$name, $gender, $birthday]);
			$detail->execute([$index + 1, $index + 1, $birthday, $gender]);
		}

		return $connection;
	}

	/**
	 * Run a call with PHP diagnostics promoted to exceptions.
	 *
	 * @template T
	 *
	 * @param callable(): T $call
	 *
	 * @return T
	 */
	private function withoutDiagnostics(callable $call): mixed
	{
		set_error_handler(
			static function (int $severity, string $message, string $file, int $line): bool {
				throw new ErrorException($message, 0, $severity, $file, $line);
			}
		);

		try {
			return $call();
		} finally {
			restore_error_handler();
		}
	}

	/**
	 * @return array<string, mixed>
	 */
	private function decode(object $response): array
	{
		return (array) json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function listPage(string $type = 'seiyuu', string $page = '1'): array
	{
		return $this->decode(
			$this->withoutDiagnostics(
				fn () => $this->controller->getList(new StringObject($type), new StringObject($page))
			)
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function findPage(string $keyword, string $type = 'seiyuu', string $page = '1'): array
	{
		return $this->decode(
			$this->withoutDiagnostics(
				fn () => $this->controller->findSeiyuu(new StringObject($type), new StringObject($keyword), new StringObject($page))
			)
		);
	}

	#endregion

	#region detail view

	/**
	 * @return array<string, mixed>
	 */
	private function viewPayload(string $type, string $id): array
	{
		return $this->decode(
			$this->withoutDiagnostics(
				fn () => $this->controller->getView(new StringObject($type), new StringObject($id))
			)
		);
	}

	/**
	 * The detail view used to load a fixed relation list — appearance, related_attachment, brand —
	 * on every type. Seiyuu declares none of the last two, and nothing loaded the relations the
	 * projection actually reads, so the portrait, gender and birthday were null on every request.
	 */
	public function testDetailViewResolvesTheSeiyuuDetailRelation(): void
	{
		$data = $this->viewPayload('seiyuu', '1')['data'];

		$this->assertSame('Aoi Yuuki', $data['name']);
		$this->assertSame('/App/Attachment/Seiyuu/aoi.webp', $data['path']);
		$this->assertNotNull($data['gender']);
		$this->assertNotNull($data['birthday']);
	}

	/**
	 * matchFromPairs() takes its alternatives as arguments, so all three projections were built on
	 * every request and each read properties the other two types do not have. Only the selected
	 * shape is built now, and a seiyuu payload must not carry the game/brand keys.
	 */
	public function testDetailViewBuildsOnlyTheSelectedShape(): void
	{
		$data = $this->viewPayload('seiyuu', '1')['data'];

		$this->assertSame(['no', 'name', 'path', 'gender', 'birthday'], array_keys($data));
		$this->assertArrayNotHasKey('release', $data);
		$this->assertArrayNotHasKey('brand', $data);
	}

	/**
	 * with() populates only the entity's own relations, so the appearance rows arrived with their
	 * character, brand, seiyuu and game unset — every field the appearance list renders except the
	 * comment. This is where most of the nulls came from.
	 */
	public function testDetailViewResolvesTheNestedAppearanceRelations(): void
	{
		$rows = $this->viewPayload('seiyuu', '1')['appearance'];

		$this->assertCount(2, $rows);
		$this->assertSame('Foglio', $rows[0]['brand']);
		$this->assertSame('Aoi Yuuki', $rows[0]['seiyuu']);
		$this->assertSame('Mezzo Forte', $rows[0]['game']);
		$this->assertSame('Mikura', $rows[0]['character']);
		$this->assertSame(1, $rows[0]['hscene']);
		$this->assertSame(20060324, $rows[0]['release']);
	}

	/**
	 * An appearance may reference a brand that does not exist — the live data uses -1 as a sentinel.
	 * The related name is then null, but the foreign key itself must survive, because it is read
	 * from the appearance rather than from the missing row.
	 */
	public function testDetailViewKeepsForeignKeysWhenTheRelatedRowIsMissing(): void
	{
		$rows = $this->viewPayload('seiyuu', '1')['appearance'];

		$this->assertNull($rows[1]['brand'], 'brand -1 has no row');
		$this->assertSame(-1, $rows[1]['brand_no'], 'the key itself must still be reported');
		$this->assertNull($rows[1]['comment']);
		$this->assertSame('Mikura', $rows[1]['character'], 'the other relations still resolve');
	}

	public function testDetailViewReportsNotFoundForAnUnknownIdentifier(): void
	{
		$response = $this->withoutDiagnostics(
			fn () => $this->controller->getView(new StringObject('seiyuu'), new StringObject('987654'))
		);

		$status = $response->getStatusCode();

		$this->assertSame(404, $status->value ?? $status);
	}

	#endregion

	#region schema mapping

	/**
	 * The entities must name the tables the database actually has.
	 *
	 * None of these classes matches ActiveRecord's derived plural — Seiyuu would become `seiyuus`
	 * and Seiyuu_Detail `seiyuu__details`, neither of which exists — so each carries an explicit
	 * $table. Dropping one turns every query into "no such table", and the listing renders empty.
	 *
	 * @return array<string, array{0: class-string, 1: string}>
	 */
	public static function entityTableProvider(): array
	{
		return [
			'seiyuu' => [\App\Entity\Seiyuu::class, 'voice_actor'],
			'seiyuu detail' => [\App\Entity\Seiyuu_Detail::class, 'voice_actor_detail'],
			'attachment' => [\App\Entity\Attachment::class, 'visual_novel_attachment'],
			'game' => [\App\Entity\Game::class, 'visual_novel'],
			'brand' => [\App\Entity\Brand::class, 'visual_novel_brand'],
			'character' => [\App\Entity\Character::class, 'visual_novel_character'],
			'appearance' => [\App\Entity\Appearance::class, 'visual_novel_appearance'],
			'related attachment' => [\App\Entity\Related_Attachment::class, 'visual_novel_related_attachment'],
		];
	}

	#[DataProvider('entityTableProvider')]
	public function testEntityMapsToTheExpectedTable(string $entity, string $table): void
	{
		$method = new \ReflectionMethod($entity, 'getTable');
		$method->setAccessible(true);

		$this->assertSame($table, $method->invoke(new $entity()));
	}

	/**
	 * `visual_novel_appearance` names its brand key `visual_novel_brand_no`, not `brand_no`. The
	 * entity mapped the shorter name, so the appearance-to-brand join resolved nothing and every
	 * consumer of it came back empty.
	 */
	public function testAppearanceMapsTheBrandKeyUnderItsRealColumnName(): void
	{
		$method = new \ReflectionMethod(\App\Entity\Appearance::class, 'getDatabaseColumnNames');
		$method->setAccessible(true);
		$columns = (array) $method->invoke(new \App\Entity\Appearance());

		$this->assertContains('visual_novel_brand_no', $columns);
		$this->assertNotContains('brand_no', $columns);
	}

	#endregion

	#region listing

	public function testListingReturnsEveryRow(): void
	{
		$payload = $this->listPage();

		$this->assertSame(5, $payload['count']);
		$this->assertCount(5, $payload['pages']);
		$this->assertSame(24, $payload['pagesPerList']);
	}

	public function testListingIsOrderedByName(): void
	{
		$names = array_column($this->listPage()['pages'], 'name');

		$this->assertSame(
			['Aoi Yuuki', 'Ayane Sakura', 'Kana Hanazawa', 'Mamoru Miyano', 'Rie Takahashi'],
			$names
		);
	}

	/**
	 * The regression this suite exists for.
	 *
	 * `attachment` is a relation of Seiyuu_Detail, not of Seiyuu. eagerLoadForCollection skips a
	 * relation name it cannot find on the entity without reporting it, so requesting it against
	 * Seiyuu left every portrait unresolved and the endpoint returned rows whose `path` was null.
	 */
	public function testListingResolvesThePortraitOnEveryRow(): void
	{
		foreach ($this->listPage()['pages'] as $row) {
			$this->assertNotNull($row['path'], sprintf('%s has no portrait path', $row['name']));
			$this->assertStringStartsWith('/App/Attachment/Seiyuu/', $row['path']);
		}
	}

	public function testListingResolvesTheDetailColumnsOnEveryRow(): void
	{
		foreach ($this->listPage()['pages'] as $row) {
			$this->assertNotNull($row['gender'], sprintf('%s has no gender', $row['name']));
			$this->assertNotNull($row['birthday'], sprintf('%s has no birthday', $row['name']));
			$this->assertNotNull($row['attachment_no']);
		}
	}

	public function testListingProjectsExactlyTheDocumentedColumns(): void
	{
		$row = $this->listPage()['pages'][0];

		$this->assertSame(
			['gender', 'birthday', 'attachment_no', 'path', 'no', 'name'],
			array_keys($row)
		);
	}

	#endregion

	#region searching

	public function testSearchFiltersByName(): void
	{
		$payload = $this->findPage('Aoi');

		$this->assertSame(1, $payload['count']);
		$this->assertSame('Aoi Yuuki', $payload['pages'][0]['name']);
		$this->assertSame('Aoi', $payload['keyword']);
	}

	public function testSearchMatchesPartialNames(): void
	{
		$names = array_column($this->findPage('a')['pages'], 'name');

		$this->assertCount(5, $names);
	}

	public function testSearchReturnsNothingForAnUnknownKeyword(): void
	{
		$payload = $this->findPage('Nonexistent');

		$this->assertSame(0, $payload['count']);
		$this->assertSame([], $payload['pages']);
	}

	/**
	 * The search path had no eager loading at all, so it returned rows whose gender, birthday,
	 * attachment_no and path were every one of them null.
	 */
	public function testSearchResolvesRelationsOnEveryRow(): void
	{
		foreach ($this->findPage('a')['pages'] as $row) {
			$this->assertNotNull($row['gender'], sprintf('%s has no gender', $row['name']));
			$this->assertNotNull($row['birthday'], sprintf('%s has no birthday', $row['name']));
			$this->assertNotNull($row['path'], sprintf('%s has no portrait path', $row['name']));
		}
	}

	/**
	 * The keyword is bound rather than interpolated, so a quote cannot terminate the statement.
	 */
	public function testHostileKeywordCannotBreakTheStatement(): void
	{
		$payload = $this->findPage("' OR 1=1 --");

		$this->assertSame(0, $payload['count']);
		$this->assertSame(5, $this->listPage()['count'], 'the table must still be intact');
	}

	#endregion

	#region paging

	public function testPagingReportsTheTotalPageCount(): void
	{
		$payload = $this->listPage();

		$this->assertSame(1.0, $payload['total_page']);
		$this->assertSame(0, $payload['offset']);
	}

	public function testSecondPageIsEmptyForASmallCatalogue(): void
	{
		$payload = $this->listPage('seiyuu', '2');

		$this->assertSame([], $payload['pages']);
		$this->assertSame(1, $payload['offset']);
	}

	#endregion

	#region routing

	/**
	 * The frontend's URLs and the controller's registered patterns have to agree.
	 *
	 * #[Prefix('/seiyuu')] is prepended to every pattern, and the patterns used to begin with
	 * `/seiyuu` themselves, so they registered as `/seiyuu/seiyuu/api/...`. Nothing failed loudly:
	 * the #[NotFound] handler answered the API calls with the SPA's HTML, JSON.parse threw inside
	 * PageRenderer's try/catch, and the listing rendered empty.
	 *
	 * The expected list is taken from the URLs Typescript/prev/frontend actually requests.
	 */
	public function testRegisteredRoutesMatchTheUrlsTheFrontendRequests(): void
	{
		$patterns = [];

		foreach ((new RouteAnnotationReader())->read(SeiyuuController::class) as $route) {
			$patterns[] = $route->pattern;
		}

		foreach (
			[
				'/seiyuu/api/paging/{type}?:([a-z]+)/{page}?:(\d+)?',
				'/seiyuu/api/find/{type}?:([a-z]+)/{seiyuu}?:(.*)?/{page}?:(\d+)?',
				'/seiyuu/api/view/{type}?:([a-z]+)/{id}?:(\d+)',
				'/seiyuu/api/upload/{type}?:([a-z]+)/{id}?:(\d+)',
				'/seiyuu/api/seiyuu/add',
				'/seiyuu/api/seiyuu/character/add',
				'/seiyuu/api/game/{type}?:([a-z]+)/add',
			] as $expected
		) {
			$this->assertContains($expected, $patterns);
		}
	}

	public function testNoRouteRepeatsThePrefix(): void
	{
		foreach ((new RouteAnnotationReader())->read(SeiyuuController::class) as $route) {
			$this->assertStringNotContainsString(
				'/seiyuu/seiyuu/',
				$route->pattern,
				'the #[Prefix] is being applied on top of a pattern that already carries it'
			);
		}
	}

	#endregion

	#region unknown type

	public function testUnknownTypeIsRejectedWithNoContent(): void
	{
		$response = $this->withoutDiagnostics(
			fn () => $this->controller->getList(new StringObject('bogus'), new StringObject('1'))
		);

		$this->assertSame(204, $response->getStatusCode()->value ?? $response->getStatusCode());
	}

	#endregion
}
