<?php

declare(strict_types=1);

namespace Clover\Tests\App\Modules\AudioAnalysis;

use App\Modules\AudioAnalysis\Controller\AudioAnalysisPageController;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Routing\RouteAnnotationReader;
use Clover\Enumeration\HTTPStatusCode;
use Clover\Framework\Component\Renderer;
use Clover\Framework\Component\Resource;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

use function define;
use function defined;
use function is_string;
use function str_contains;
use function substr_count;

final class AudioAnalysisPageControllerTest extends TestCase
{
	public function testDeclaresPublicPageRoute(): void
	{
		$routes = (new RouteAnnotationReader())->read(AudioAnalysisPageController::class);

		$this->assertCount(1, $routes);
		$this->assertSame('GET', $routes[0]->method);
		$this->assertSame('/audio-analysis', $routes[0]->pattern);
	}

	#[RunInSeparateProcess]
	#[PreserveGlobalState(false)]
	public function testRendersInteractivePageWithModuleAssets(): void
	{
		if (!defined('BASE_PATH')) {
			define('BASE_PATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'root');
		}

		$container = new Container();
		$container->set(Resource::class, new Resource('audio-analysis-test'));
		$container->set(Renderer::class, new Renderer());
		$response = (new AudioAnalysisPageController($container))->index();
		$body = (string) $response->getBody();
		$resources = $response->getResource();

		$this->assertIsArray($resources);
		$this->assertSame(HTTPStatusCode::OK, $response->getStatusCode());
		$this->assertStringContainsString('data-audio-analysis-page', $body);
		$this->assertStringContainsString('Voice Analysis Console', $body);
		$this->assertStringContainsString('studio-app-bar', $body);
		$this->assertStringContainsString('Request-only processing', $body);
		$this->assertStringNotContainsString('Processing engine online', $body);
		$this->assertStringContainsString('role="tablist"', $body);
		$this->assertStringContainsString('studio-session-layout', $body);
		$this->assertStringContainsString('/api/audio-analysis/analyses', $body);
		$this->assertStringContainsString('/api/audio-analysis/comparisons', $body);
		$this->assertStringContainsString('data-analysis-charts', $body);
		$this->assertStringContainsString('data-comparison-charts', $body);
		$this->assertStringContainsString('data-key-measurements', $body);
		$this->assertStringContainsString('studio-result-navigation', $body);
		$this->assertStringContainsString('Complete acoustic measurement change table', $body);
		$this->assertStringContainsString('id="comparison-baseline"', $body);
		$this->assertStringContainsString('role="rowgroup"', $body);
		$this->assertStringContainsString('dBFS is relative to digital full scale', $body);
		$this->assertStringContainsString('Complete measurement registry', $body);
		$this->assertStringContainsString('data-measurement-search', $body);
		$this->assertStringContainsString('data-measurement-filter="resonance"', $body);
		$this->assertSame(2, substr_count($body, 'data-button-spinner'));
		$this->assertSame(3, substr_count($body, 'data-audio-player'));
		$this->assertSame(3, substr_count($body, 'data-audio-preview'));
		$this->assertStringContainsString('data-audio-worklet-url="/App/Modules/AudioAnalysis/View/voice-transformer-worklet.js"', $body);
		$this->assertStringNotContainsString('<audio controls', $body);
		$this->assertStringContainsString('up to 1 GiB', $body);
		$this->assertSame('Voice Acoustic Workbench | Onetone', $resources['title']);
		$this->assertTrue($this->resourceContains($resources['cssMap'], 'audio-analysis.css'));
		$this->assertTrue($this->resourceContains($resources['scriptMap'], 'audio-analysis.js'));
	}

	/**
	 * @param array<int, array<string, mixed>> $resources
	 */
	private function resourceContains(array $resources, string $filename): bool
	{
		foreach ($resources as $resource) {
			foreach ($resource as $value) {
				if (is_string($value) && str_contains($value, $filename)) {
					return true;
				}
			}
		}

		return false;
	}
}
