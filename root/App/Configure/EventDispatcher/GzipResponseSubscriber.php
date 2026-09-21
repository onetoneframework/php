<?php

declare(strict_types=1);

namespace App\Configure\EventDispatcher;

use Clover\Classes\Data\YamlHandler;
use Clover\Framework\Event\BeforeResponseSend;
use Clover\Implement\SubscriberInterface;

final class GzipResponseSubscriber implements SubscriberInterface
{
	private const DEFAULT_CONFIG = [
		'enabled' => false,
		'level' => 6,
		'min_length' => 512,
		'types' => ['json', 'html', 'text', 'xml'],
		'excluded_paths' => [],
	];

	private static ?array $config = null;

	public static function getSubscribedEvents(): array
	{
		return [
			BeforeResponseSend::class => 'onBeforeResponseSend',
		];
	}

	public function onBeforeResponseSend(BeforeResponseSend $event): void
	{
		$config = $this->getConfig();
		if (($config['enabled'] ?? false) !== true) {
			return;
		}

		$response = $event->response;
		$type = $response->getType();
		$allowedTypes = $config['types'] ?? [];
		if (!in_array($type, $allowedTypes, true)) {
			return;
		}

		$requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
		foreach (($config['excluded_paths'] ?? []) as $excludedPath) {
			if (is_string($excludedPath) && $excludedPath !== '' && str_starts_with($requestUri, $excludedPath)) {
				return;
			}
		}

		$acceptEncoding = (string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '');
		if (!str_contains(strtolower($acceptEncoding), 'gzip')) {
			return;
		}

		if ($response->hasHeader('Content-Encoding')) {
			return;
		}

		$body = $response->getBody();
		if (!is_string($body) || $body === '') {
			return;
		}

		$minLength = (int) ($config['min_length'] ?? 512);
		if (strlen($body) < $minLength) {
			return;
		}

		$level = (int) ($config['level'] ?? 6);
		$level = max(1, min(9, $level));
		$compressed = gzencode($body, $level);
		if ($compressed === false) {
			return;
		}

		$response->setBody($compressed);
		$response->setHeader('Content-Encoding', 'gzip');
		$response->setHeader('Vary', 'Accept-Encoding');
		$response->setHeader('Content-Length', (string) strlen($compressed));
	}

	private function getConfig(): array
	{
		if (self::$config !== null) {
			return self::$config;
		}

		$configPath = BASE_PATH . '/App/Configure/eventbus.gzip.yml';
		$parsed = [];
		if (file_exists($configPath)) {
			$content = file_get_contents($configPath);
			if (is_string($content) && trim($content) !== '') {
				$handler = new YamlHandler(preg_split('/\r\n|\r|\n/', $content) ?: []);
				$parsed = $handler->getData();
			}
		}
		$gzipConfig = is_array($parsed['gzip'] ?? null) ? $parsed['gzip'] : $parsed;

		if (!is_array($gzipConfig)) {
			$gzipConfig = [];
		}

		self::$config = array_merge(self::DEFAULT_CONFIG, $gzipConfig);
		return self::$config;
	}
}
