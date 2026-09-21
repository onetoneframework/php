<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\Routing;

use Clover\Classes\Event\EventManager;
use Clover\Classes\Routing\Router;
use Clover\Framework\Component\Response;
use PHPUnit\Framework\TestCase;

/**
 * Characterisation tests for the request path Router used to own directly:
 * handleInternal(), getCandidateRoutes(), getAllowedMethods() and
 * handleMethodNotAllowed().
 *
 * Those four were restructured, not copied, when they moved into
 * RouteDispatcher and RouteCollection — so a line-for-line comparison cannot
 * prove nothing was dropped. Every branch of the originals is exercised here
 * instead: each test fails if the branch it names disappears.
 *
 * Written against the public API only, so it holds whichever object ends up
 * owning the logic.
 */
final class RouterDispatchBranchTest extends TestCase
{
	protected function setUp(): void
	{
		parent::setUp();
		$_SERVER = [
			'HTTP_HOST' => 'localhost',
			'REQUEST_METHOD' => 'GET',
			'CONTENT_TYPE' => 'text/plain',
			'REQUEST_URI' => '/',
			'REMOTE_ADDR' => '10.0.0.1',
		];
	}

	protected function tearDown(): void
	{
		$_SERVER = [];
		EventManager::clearInstance();
		parent::tearDown();
	}

	private function requestFor(string $uri, string $method = 'GET'): Router
	{
		$_SERVER['REQUEST_URI'] = $uri;
		$_SERVER['REQUEST_METHOD'] = $method;

		return new Router();
	}

	#region getCandidateRoutes: bucket selection

	public function testAMethodWithNoBucketFallsThroughToTheAllowedMethodCheck(): void
	{
		$router = $this->requestFor('/anything', 'PUT');
		$router->get('/anything', static fn (): string => 'never');

		$result = $router->handle();

		$this->assertIsArray($result, 'PUT has no bucket, so the 405 branch runs rather than the matcher.');
		$this->assertSame(405, $result['code']);
	}

	public function testAMethodWithNoBucketAndNoOtherMatchIsFalse(): void
	{
		$router = $this->requestFor('/anything', 'PUT');
		$router->get('/elsewhere', static fn (): string => 'never');

		$this->assertFalse($router->handle());
	}

	public function testTheFirstSegmentBucketIsSearched(): void
	{
		$router = $this->requestFor('/users/7');
		$router->get('/users/{id}', static fn (): string => 'users');
		$router->get('/posts/{id}', static fn (): string => 'posts');

		$this->assertSame('users', $router->handle());
	}

	/**
	 * A pattern whose first segment is a parameter is filed under the '*' bucket,
	 * and it now matches.
	 *
	 * Route::match() opened with a cheap "pre-return" that compared the request's
	 * first segment against the route's first segment as literal text. For
	 * '/{slug}' that compared 'anything' with '{slug}', so every route whose
	 * first segment is a parameter was rejected before the real walk ever ran —
	 * which made the wildcard bucket dead weight and catchAll(), registered as
	 * '/{path}', unable to fire at all. The pre-return now only applies to a
	 * literal segment.
	 *
	 * @dataProvider parameterFirstSegmentProvider
	 */
	public function testARouteWhoseFirstSegmentIsAParameterMatches(string $pattern, string $uri): void
	{
		$router = $this->requestFor($uri);
		$router->get($pattern, static fn (): string => 'wildcard');

		$this->assertSame('wildcard', $router->handle());
	}

	public function testCatchAllReceivesAnUnroutedRequest(): void
	{
		$router = $this->requestFor('/no/such/place');
		$router->catchAll(static fn (): string => 'caught');

		$this->assertSame('caught', $router->handle());
	}

	public function testALiteralFirstSegmentStillRejectsAMismatch(): void
	{
		$router = $this->requestFor('/other/edit');
		$router->get('/users/edit', static fn (): string => 'never');

		$this->assertFalse($router->handle(), 'The literal pre-return still rejects early.');
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function parameterFirstSegmentProvider(): array
	{
		return [
			'one segment' => ['/{slug}', '/anything'],
			'two segments' => ['/{slug}/edit', '/anything/edit'],
			'catch-all' => ['/{path}', '/whatever'],
		];
	}

	public function testAParameterInALaterSegmentDoesMatch(): void
	{
		$router = $this->requestFor('/x/5');
		$router->get('/x/{id}', static fn (): string => 'hit');

		$this->assertSame('hit', $router->handle(), 'Only the first segment is affected.');
	}

	public function testTheRootBucketIsSearched(): void
	{
		$router = $this->requestFor('/');
		$router->get('/', static fn (): string => 'root');

		$this->assertSame('root', $router->handle());
	}

	public function testAnExactFirstSegmentWinsOverTheWildcardBucket(): void
	{
		$router = $this->requestFor('/users');
		$router->get('/users', static fn (): string => 'exact');
		$router->get('/{slug}', static fn (): string => 'wildcard');

		$this->assertSame(
			'exact',
			$router->handle(),
			'Candidates are concatenated exact-first, so the literal segment is tried before the wildcard.'
		);
	}

	public function testTheCompiledTableIsRebuiltAfterARouteIsAdded(): void
	{
		$router = $this->requestFor('/late');
		$router->get('/early', static fn (): string => 'early');

		// Force a compile with the first registration only.
		$this->assertFalse($router->handle());

		$router->get('/late', static fn (): string => 'late');

		$this->assertSame('late', $router->handle(), 'Adding a route must invalidate the compiled table.');
	}

	public function testTheCompiledTableIsRebuiltAfterARouteIsRemoved(): void
	{
		$router = $this->requestFor('/doomed');
		$router->get('/doomed', static fn (): string => 'alive');
		$router->get('/other', static fn (): string => 'other');

		$router->removeRoute('GET', '/doomed');

		$this->assertSame(1, $router->count());
		$this->assertFalse($router->handle(), 'Removing a route must invalidate the compiled table.');
	}

	public function testTheCompiledTableIsRebuiltAfterRemovalByPattern(): void
	{
		$router = $this->requestFor('/doomed');
		$router->get('/doomed', static fn (): string => 'alive');

		$router->removeRouteByPattern('/doomed');

		$this->assertSame(0, $router->count());
		$this->assertFalse($router->handle());
	}

	/**
	 * Matching must not edit the route it matched.
	 *
	 * Route::match() used to call StringObject::trim('/') on the pattern itself,
	 * and that rewrites in place — so from the first request onwards the stored
	 * pattern was 'doomed' rather than '/doomed', and removeRoute(), hasRoute(),
	 * listRoutes() and toArray() (which is what the route cache writes) all
	 * stopped recognising the pattern the route was registered under.
	 */
	public function testDispatchingLeavesTheStoredPatternAlone(): void
	{
		$router = $this->requestFor('/doomed');
		$router->get('/doomed', static fn (): string => 'alive');

		$this->assertSame(['GET' => ['/doomed']], $router->listRoutes());
		$this->assertSame('alive', $router->handle());
		$this->assertSame(['GET' => ['/doomed']], $router->listRoutes(), 'The pattern survives matching.');

		$this->assertTrue($router->hasRoute('GET', '/doomed'));
		$this->assertSame(['GET' => [['pattern' => '/doomed'] + $router->toArray()['GET'][0]]], $router->toArray());

		$router->removeRoute('GET', '/doomed');

		$this->assertSame(0, $router->count(), 'Removal by the registered pattern finds it.');
		$this->assertFalse($router->handle());
	}

	#endregion

	#region handleInternal: maintenance mode

	public function testMaintenanceWithoutAHandlerAnswersFiveZeroThree(): void
	{
		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'never reached');
		$router->enableMaintenance();

		$this->assertSame(['error' => 'Service Unavailable', 'code' => 503], $router->handle());
	}

	public function testMaintenanceWithAHandlerReturnsWhatTheHandlerReturns(): void
	{
		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'never reached');
		$router->enableMaintenance(static fn (): string => 'down for now');

		$this->assertSame('down for now', $router->handle());
	}

	public function testAListedClientPassesThroughMaintenance(): void
	{
		$_SERVER['REMOTE_ADDR'] = '10.0.0.1';
		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'served');
		$router->enableMaintenance(static fn (): string => 'down for now', ['10.0.0.1']);

		$this->assertSame('served', $router->handle(), 'A bypass IP must reach the route, not the maintenance handler.');
	}

	public function testAnUnlistedClientIsStillRefused(): void
	{
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'served');
		$router->enableMaintenance(static fn (): string => 'down for now', ['10.0.0.1']);

		$this->assertSame('down for now', $router->handle());
	}

	/**
	 * The bypass list is matched against REMOTE_ADDR, the peer the server is
	 * actually talking to.
	 *
	 * It used to be matched against HTTPRequest::getClientIP(), which reads only
	 * the HTTP_CLIENT_IP request header: absent on an ordinary request, so no
	 * bypass entry could ever match — and set by the client, so anyone who
	 * guessed a listed address walked straight through a closed site.
	 */
	public function testAForgedClientIpHeaderDoesNotBypassMaintenance(): void
	{
		$_SERVER['REMOTE_ADDR'] = '203.0.113.9';
		$_SERVER['HTTP_CLIENT_IP'] = '10.0.0.1';

		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'served');
		$router->enableMaintenance(static fn (): string => 'down for now', ['10.0.0.1']);

		$this->assertSame('down for now', $router->handle(), 'A client-set header must not open a closed site.');
	}

	public function testDisablingMaintenanceServesAgain(): void
	{
		$router = $this->requestFor('/anything');
		$router->get('/anything', static fn (): string => 'served');
		$router->enableMaintenance();
		$router->disableMaintenance();

		$this->assertSame('served', $router->handle());
		$this->assertFalse($router->isMaintenanceMode());
	}

	#endregion

	#region handleInternal: before and after hooks

	public function testABeforeHookReceivesTheMethodAndTheSegments(): void
	{
		$seen = [];
		$router = $this->requestFor('/users/7', 'POST');
		$router->before(static function ($method, $segments) use (&$seen): bool {
			$seen = ['method' => $method, 'segments' => is_object($segments) ? iterator_to_array($segments) : $segments];

			return true;
		});
		$router->post('/users/{id}', static fn (): string => 'ok');

		$router->handle();

		$this->assertSame('POST', $seen['method']);
		$this->assertSame(['users', '7'], array_values($seen['segments']));
	}

	public function testABeforeHookReturningFalseStopsBeforeTheRoute(): void
	{
		$reached = false;
		$router = $this->requestFor('/blocked');
		$router->before(static fn (): bool => false);
		$router->get('/blocked', static function () use (&$reached): string {
			$reached = true;

			return 'never';
		});

		$this->assertFalse($router->handle());
		$this->assertFalse($reached, 'The route must not run once a hook has aborted.');
	}

	public function testABeforeHookReturningAResponseAnswersTheRequest(): void
	{
		$router = $this->requestFor('/intercepted');
		$router->before(static fn (): Response => new Response('from the hook', [], 'text', 418));
		$router->get('/intercepted', static fn (): string => 'never');

		$response = $router->handle();

		$this->assertInstanceOf(Response::class, $response);
		$this->assertSame(418, $response->getStatusCode());
	}

	public function testEveryBeforeHookRunsInRegistrationOrderUntilOneStops(): void
	{
		$order = [];
		$router = $this->requestFor('/hooks');
		$router->before(static function () use (&$order): bool {
			$order[] = 'first';

			return true;
		});
		$router->before(static function () use (&$order): bool {
			$order[] = 'second';

			return false;
		});
		$router->before(static function () use (&$order): bool {
			$order[] = 'third';

			return true;
		});
		$router->get('/hooks', static fn (): string => 'never');

		$router->handle();

		$this->assertSame(['first', 'second'], $order, 'The third hook is past the abort and must not run.');
	}

	public function testAnAfterHookCanReplaceTheResult(): void
	{
		$router = $this->requestFor('/wrapped');
		$router->get('/wrapped', static fn (): string => 'original');
		$router->after(static fn ($method, $segments, $result): string => strtoupper((string) $result));

		$this->assertSame('ORIGINAL', $router->handle());
	}

	public function testAnAfterHookReturningNullLeavesTheResultAlone(): void
	{
		$router = $this->requestFor('/wrapped');
		$router->get('/wrapped', static fn (): string => 'original');
		$router->after(static fn (): ?string => null);

		$this->assertSame('original', $router->handle());
	}

	public function testAfterHooksChainInRegistrationOrder(): void
	{
		$router = $this->requestFor('/wrapped');
		$router->get('/wrapped', static fn (): string => 'a');
		$router->after(static fn ($method, $segments, $result): string => $result . 'b');
		$router->after(static fn ($method, $segments, $result): string => $result . 'c');

		$this->assertSame('abc', $router->handle());
	}

	public function testAfterHooksDoNotRunWhenNothingMatched(): void
	{
		$ran = false;
		$router = $this->requestFor('/absent');
		$router->get('/present', static fn (): string => 'x');
		$router->after(static function () use (&$ran): void {
			$ran = true;
		});

		$router->handle();

		$this->assertFalse($ran);
	}

	#endregion

	#region getAllowedMethods and handleMethodNotAllowed

	public function testAPathThatExistsUnderAnotherMethodIsFourZeroFive(): void
	{
		$router = $this->requestFor('/users', 'DELETE');
		$router->get('/users', static fn (): string => 'listed');
		$router->post('/users', static fn (): string => 'created');

		$result = $router->handle();

		$this->assertIsArray($result);
		$this->assertSame(405, $result['code']);
		$this->assertSame('Method Not Allowed', $result['error']);
	}

	public function testTheFourZeroFiveListsEveryMethodThatWouldHaveMatched(): void
	{
		$router = $this->requestFor('/users', 'DELETE');
		$router->get('/users', static fn (): string => 'listed');
		$router->post('/users', static fn (): string => 'created');
		$router->put('/other', static fn (): string => 'unrelated');

		$result = $router->handle();

		$this->assertIsArray($result);
		sort($result['allowed']);
		$this->assertSame(['GET', 'POST'], $result['allowed'], 'PUT has a route but not for this path.');
	}

	public function testEachAllowedMethodIsListedOnce(): void
	{
		$router = $this->requestFor('/users', 'DELETE');
		$router->get('/users', static fn (): string => 'first');
		$router->get('/users', static fn (): string => 'second');

		$result = $router->handle();

		$this->assertIsArray($result);
		$this->assertSame(['GET'], $result['allowed'], 'Two GET routes on one path still allow GET once.');
	}

	public function testAMethodNotAllowedHandlerReplacesTheDefaultAnswer(): void
	{
		$router = $this->requestFor('/users', 'DELETE');
		$router->get('/users', static fn (): string => 'listed');
		$router->setMethodNotAllowedHandler(static fn (array $allowed): string => 'try ' . implode(',', $allowed));

		$this->assertSame('try GET', $router->handle());
	}

	public function testAPathThatExistsNowhereIsNotAFourZeroFive(): void
	{
		$router = $this->requestFor('/absent', 'DELETE');
		$router->get('/present', static fn (): string => 'x');

		$this->assertFalse($router->handle(), 'No method matches this path, so it falls through rather than reporting 405.');
	}

	#endregion

	#region handleInternal: not found

	public function testAnUnmatchedPathWithoutAHandlerIsFalse(): void
	{
		$router = $this->requestFor('/absent');
		$router->get('/present', static fn (): string => 'x');

		$this->assertFalse($router->handle());
	}

	/**
	 * setNotFoundHandler() accepts `string|Closure|null`. The handler used to be
	 * resolved through ReflectionHandler::getCallMethodFromString(), which only
	 * understands a "Class::method" string, so the closure form the signature
	 * advertises threw "Target class is empty" instead of running.
	 */
	public function testAClosureNotFoundHandlerRuns(): void
	{
		$router = $this->requestFor('/absent');
		$router->get('/present', static fn (): string => 'x');
		$router->setNotFoundHandler(static fn (): string => 'nothing here');

		$this->assertSame('nothing here', $router->handle());
	}

	public function testDispatchPrefersTheNotFoundHandlerOverItsOwnFourZeroFour(): void
	{
		$router = $this->requestFor('/absent');
		$router->get('/present', static fn (): string => 'x');
		$router->setNotFoundHandler(static fn (): string => 'handled');

		$this->assertSame('handled', $router->dispatch());
	}

	public function testDispatchReportsFourZeroFourWithoutANotFoundHandler(): void
	{
		$router = $this->requestFor('/absent');
		$router->get('/present', static fn (): string => 'x');

		$this->assertSame(['error' => 'Not Found', 'code' => 404], $router->dispatch());
	}

	#endregion

	#region state that must survive clone and reset

	public function testACloneKeepsItsOwnRoutes(): void
	{
		$router = $this->requestFor('/original');
		$router->get('/original', static fn (): string => 'original');

		$copy = $router->cloneRouter();
		$copy->get('/added-to-copy', static fn (): string => 'copy');

		$this->assertSame(1, $router->count(), 'Writing to the clone must not reach the original.');
		$this->assertSame(2, $copy->count());
	}

	public function testACloneKeepsItsOwnHooks(): void
	{
		$router = $this->requestFor('/x');
		$copy = $router->cloneRouter();
		$copy->before(static fn (): bool => true);

		$this->assertSame(0, $router->stats()['before_hooks']);
		$this->assertSame(1, $copy->stats()['before_hooks']);
	}

	public function testResetClearsRoutesHooksAndMaintenance(): void
	{
		$router = $this->requestFor('/x');
		$router->get('/x', static fn (): string => 'x');
		$router->before(static fn (): bool => true);
		$router->after(static fn (): ?string => null);
		$router->name('named', 'GET', '/x', static fn (): string => 'x');
		$router->tag('/x', 'api');
		$router->rateLimit('/x', 10);
		$router->throttle('/x', 10);
		$router->pattern('id', '[0-9]+');
		$router->enableMaintenance();

		$router->reset();

		$stats = $router->stats();
		$this->assertSame(0, $stats['total']);
		$this->assertSame(0, $stats['before_hooks']);
		$this->assertSame(0, $stats['after_hooks']);
		$this->assertSame(0, $stats['named']);
		$this->assertSame(0, $stats['tags']);
		$this->assertSame(0, $stats['rate_limited']);
		$this->assertSame(0, $stats['throttled']);
		$this->assertFalse($stats['maintenance']);
		$this->assertSame([], $router->getGlobalPatterns());
		$this->assertNull($router->getRateLimit('/x'));
		$this->assertNull($router->getThrottle('/x'));
		$this->assertSame([], $router->getTags('/x'));
		$this->assertFalse($router->hasNamedRoute('named'));
	}

	public function testClearDropsRoutesAndTheNotFoundHandlerButKeepsNames(): void
	{
		$router = $this->requestFor('/x');
		$router->name('named', 'GET', '/x', static fn (): string => 'x');
		$router->setNotFoundHandler(static fn (): string => 'handled');

		$router->clear();

		$this->assertSame(0, $router->count());
		$this->assertTrue($router->hasNamedRoute('named'), 'clear() has never dropped names.');
		$this->assertSame(['error' => 'Not Found', 'code' => 404], $router->dispatch());
	}

	public function testMergeCopiesRoutesFromAnotherRouterUnderAPrefix(): void
	{
		$source = new Router();
		$source->get('/items', RouterMergeFixture::class . '::index');

		$router = $this->requestFor('/api/items');
		$router->merge($source, '/api');

		$this->assertSame(1, $router->count());
		$this->assertTrue($router->hasRoute('GET', '/api/items'));
		$this->assertSame(1, $source->count(), 'The source router is left alone.');
	}

	/**
	 * merge() used to rebuild each route from getClassAndMethod(), which a closure
	 * route cannot answer, so a router holding closures could not be merged at
	 * all. It copies the registered callback now.
	 */
	public function testMergeCopiesAClosureRoute(): void
	{
		$source = new Router();
		$source->get('/items', static fn (): string => 'items');

		$router = $this->requestFor('/api/items');
		$router->merge($source, '/api');

		$this->assertSame(1, $router->count());
		$this->assertSame('items', $router->handle(), 'The merged closure still runs.');
	}

	public function testToArrayExportsAClosureRouteWithoutThrowing(): void
	{
		$router = $this->requestFor('/items');
		$router->get('/items', static fn (): string => 'items');

		$export = $router->toArray();

		$this->assertSame('/items', $export['GET'][0]['pattern']);
		$this->assertSame('::', $export['GET'][0]['callback'], 'A closure has no class or method to name.');
	}

	public function testDumpDescribesAClosureRoute(): void
	{
		$router = $this->requestFor('/items');
		$router->get('/items', static fn (): string => 'items');

		$this->assertStringContainsString('{closure}', $router->dump());
		$this->assertSame(['GET /items -> {closure}'], $router->getRouteDescriptions());
	}

	#endregion
}

final class RouterMergeFixture
{
	public function index(): string
	{
		return 'items';
	}
}
