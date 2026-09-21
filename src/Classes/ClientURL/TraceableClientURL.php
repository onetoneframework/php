<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes;

#region use

use Clover\Classes\Debug\ProfilerHttpUrlPreview;
use Clover\Classes\Debug\Profiler;
use Clover\Framework\Component\TraceContext;
use Clover\Framework\Event\HttpClientLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Implement\ClientURLInterface;
use Clover\Classes\Data\StringObject;
use Exception;
use Throwable;
use function is_array;
use function is_object;
use function is_string;
use function parse_url;
use function strtoupper;
use function uniqid;

#endregion

/**
 * Decorator that adds request lifecycle tracing around a ClientURLInterface.
 */
class TraceableClientURL implements ClientURLInterface
{
	private ClientURLInterface $innerClient;

	/** @var callable(object): void|null */
	private $eventDispatcher;

	/**
	 * @param callable(object): void|null $eventDispatcher
	 */
	public function __construct(ClientURLInterface $innerClient, callable|null $eventDispatcher = null)
	{
		$this->innerClient = $innerClient;
		$this->eventDispatcher = $eventDispatcher;
	}

	public function execute(): mixed
	{
		$this->propagateTraceHeader();
		$requestUrl = $this->resolveRequestUrl();
		$method = $this->resolveHttpMethod();
		$host = (string) (parse_url($requestUrl, PHP_URL_HOST) ?: 'unknown-host');

		$this->dispatchEvent(new HttpClientLifecycleEvent(HttpClientLifecycleEvent::REQUEST_STARTED, TraceContext::appendTrace([
			'method' => $method, 
			'url' => $requestUrl, 
			'host' => $host
		])));

		$profilerEnabled = Profiler::isEnabled();
		$span = '';
		if ($profilerEnabled) {
			$span = uniqid('pf_', true);
			$this->dispatchEvent(new KernelSpanStarted(
				$span,
				'Kernel::HttpClientRequest',
				$requestUrl !== '' ? ProfilerHttpUrlPreview::forTimeline($requestUrl) : 'TraceableClientURL::execute'
			));
		}

		try {
			$response = $this->innerClient->execute();
			$statusCode = (int) ($this->information()?->getStatusCode() ?? 0);
		} catch (Exception $exception) {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($span));
			}
			$this->dispatchEvent(new HttpClientLifecycleEvent(HttpClientLifecycleEvent::REQUEST_FAILED, TraceContext::appendTrace([
				'method' => $method,
				'url' => $requestUrl,
				'error' => $this->innerClient->getLastErrorMessage(),
			])));
			throw $exception;
		} catch (Throwable $throwable) {
			if ($profilerEnabled) {
				$this->dispatchEvent(new KernelSpanFinished($span));
			}

			throw $throwable;
		}

		if ($profilerEnabled) {
			$this->dispatchEvent(new KernelSpanFinished($span));
		}
		$this->dispatchEvent(new HttpClientLifecycleEvent(HttpClientLifecycleEvent::REQUEST_FINISHED, TraceContext::appendTrace([
			'method' => $method,
			'url' => $requestUrl,
			'status' => $statusCode,
		])));

		return $response;
	}

	public function close(): void
	{
		$this->innerClient->close();
	}

	public function getLastErrorMessage(): string
	{
		return $this->innerClient->getLastErrorMessage();
	}

	public function getLastErrorNumber(): int
	{
		return $this->innerClient->getLastErrorNumber();
	}

	public function getSession(): mixed
	{
		return $this->innerClient->getSession();
	}

	public function information(): mixed
	{
		return $this->innerClient->information();
	}

	public function initialize(string|StringObject|null $instance = null): mixed
	{
		return $this->innerClient->initialize($instance);
	}

	public function option(): mixed
	{
		return $this->innerClient->option();
	}

	public function reset(): void
	{
		$this->innerClient->reset();
	}

	public function setOption(int $option, $value): mixed
	{
		return $this->innerClient->setOption($option, $value);
	}

	public function __call(string $name, array $arguments): mixed
	{
		return $this->innerClient->{$name}(...$arguments);
	}

	/**
	 * @param callable(object): void|null $eventDispatcher
	 */
	public function setEventDispatcher(callable|null $eventDispatcher): static
	{
		$this->eventDispatcher = $eventDispatcher;
		return $this;
	}

	private function dispatchEvent(object $event): void
	{
		if ($this->eventDispatcher === null) {
			return;
		}

		($this->eventDispatcher)($event);
	}

	private function resolveRequestUrl(): string
	{
		$option = $this->option();
		if (is_object($option) && property_exists($option, 'curlOptions')) {
			$url = (string) ($option::$curlOptions[CURLOPT_URL] ?? '');
			if ($url !== '') {
				return $url;
			}
		}

		$effectiveUrl = (string) ($this->information()?->getEffectiveURL() ?? '');
		return $effectiveUrl;
	}

	private function resolveHttpMethod(): string
	{
		$option = $this->option();
		if (!is_object($option) || !property_exists($option, 'curlOptions')) {
			return 'GET';
		}

		$customRequest = $option::$curlOptions[CURLOPT_CUSTOMREQUEST] ?? null;
		if (is_string($customRequest) && $customRequest !== '') {
			return strtoupper($customRequest);
		}

		$isPostMethod = (bool) ($option::$curlOptions[CURLOPT_POST] ?? false);
		return $isPostMethod ? 'POST' : 'GET';
	}

	private function propagateTraceHeader(): void
	{
		$option = $this->option();
		if (!is_object($option) || !property_exists($option, 'curlOptions')) {
			return;
		}

		$headers = $option::$curlOptions[CURLOPT_HTTPHEADER] ?? [];
		if (!is_array($headers)) {
			$headers = [];
		}

		$headers[] = TraceContext::getResponseHeaderName() . ': ' . TraceContext::getTraceId();
		$option::$curlOptions[CURLOPT_HTTPHEADER] = $headers;
	}
}
