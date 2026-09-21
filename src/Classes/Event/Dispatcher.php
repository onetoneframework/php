<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Implement\EventDispatcherInterface;
use Clover\Classes\Event\Instance as EventInstance;
use Clover\Implement\SubscriberInterface;
use Throwable;
use function is_object;
use function get_class;
use function is_string;
use function is_array;
use function count;

/**
 * Event Dispatcher class
 */
class Dispatcher implements EventDispatcherInterface
{
	/** @var array<int, array<int, callable>> $listeners */
	private $listeners = [];

	/** @var array<int, callable[]> $sorted */
	private array $sorted = [];

	/** @var array<int, array{event: object, eventName: string, attempts: int}> */
	private array $queue = [];

	/** @var array<int, array{event: object, eventName: string, attempts: int, error: string}> */
	private array $deadLetterQueue = [];

	/** @var array<int, array{eventName: string, listener: string, error: string}> */
	private array $listenerFailures = [];
	/* @var int $maxRetryAttempts */
	private int $maxRetryAttempts = 3;
	/* @var string|null $queueStoragePath */
	private ?string $queueStoragePath = null;
	/* @var string|null $deadLetterLogPath */
	private ?string $deadLetterLogPath = null;

	/**
	 * Dispatch registered event with event name
	 * 
	 * @param object $event
	 * @param string $eventName
	 * 
	 * @return void
	 */
	public function dispatch(object $event, ?string $eventName = null): void
	{
		if (is_object($event)) {
			$eventName ??= get_class($event);
		}

		if (!$eventName) {
			$eventName = $event;
			$event = new EventInstance();
		}

		$listeners = $this->getListeners($eventName);

		if ($listeners) {
			$this->callListeners($listeners, $eventName, $event);
		}
	}

	/**
	 * Add subscriber
	 * 
	 * @param SubscriberInterface $subscriber
	 * 
	 * @return void
	 */
	public function addSubscriber(SubscriberInterface $subscriber): void
	{
		foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
			if (is_string($params)) {
				$this->addListener($eventName, [$subscriber, $params]);
			} elseif (isset($params[0]) && is_string($params[0])) {
				$this->addListener($eventName, [$subscriber, $params[0]], $params[1] ?? 0);
			} else {
				foreach ($params as $listener) {
					$this->addListener($eventName, [$subscriber, $listener[0]], $listener[1] ?? 0);
				}
			}
		}
	}

	/**
	 * Remove subscriber
	 * 
	 * @param SubscriberInterface $subscriber
	 * 
	 * @return void
	 */
	public function removeSubscriber(SubscriberInterface $subscriber): void
	{
		foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
			if (is_string($params)) {
				$this->removeListener($eventName, [$subscriber, $params]);
			} elseif (is_string($params[0])) {
				$this->removeListener($eventName, [$subscriber, $params[0]]);
			} else {
				foreach ($params as $listener) {
					$this->removeListener($eventName, [$subscriber, $listener[0]]);
				}
			}
		}
	}

	/**
	 * Call listeners
	 * 
	 * @param iterable $listeners
	 * @param string $eventName
	 * @param object $event
	 * 
	 * @return void
	 */
	protected function callListeners(iterable $listeners, string $eventName, object $event): void
	{
		foreach ($listeners as $listener) {
			if ($event instanceof EventInstance && $event->isPropagationStopped()) {
				break;
			}

			try {
				$listener($event, $eventName, $this);
			} catch (Throwable $e) {
				$this->listenerFailures[] = [
					'eventName' => $eventName,
					'listener' => $this->normalizeListenerName($listener),
					'error' => $e->getMessage(),
				];
			}
		}
	}

	/**
	 * Remove listener from matched by event name
	 * 
	 * @param string $eventName
	 * @param callable $listener
	 * 
	 * @return bool
	 */
	public function removeListener(string $eventName, callable $listener): bool
	{
		if (!$this->hasListener($eventName)) {
			return false;
		}

		unset($this->sorted[$eventName]);

		foreach ($this->listeners[$eventName] as $priority => $listeners) {
			$key = array_search($listener, $listeners, true);

			if ($key !== false) {
				unset($this->listeners[$eventName][$priority][$key]);

				if (empty($this->listeners[$eventName][$priority])) {
					unset($this->listeners[$eventName][$priority]);
				}

				if (empty($this->listeners[$eventName])) {
					unset($this->listeners[$eventName]);
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Get listeners
	 * 
	 * @param string $eventName
	 * 
	 * @return array
	 */
	public function getListeners(string $eventName = ''): array
	{
		if (empty($eventName)) {
			return $this->listeners;
		}

		if (!isset($this->listeners[$eventName])) {
			return [];
		}

		if (!isset($this->sorted[$eventName])) {
			$this->sortListeners($eventName);
		}

		return $this->sorted[$eventName];
	}

	/**
	 * Sort listeners by priority
	 * 
	 * @param string $eventName
	 * 
	 * @return void
	 */
	private function sortListeners(string $eventName): void
	{
		$this->sorted[$eventName] = [];

		if (isset($this->listeners[$eventName])) {
			krsort($this->listeners[$eventName]);

			foreach ($this->listeners[$eventName] as $listeners) {
				foreach ($listeners as $listener) {
					$this->sorted[$eventName][] = $listener;
				}
			}
		}
	}

	/**
	 * Get count of listeners
	 * 
	 * @param ?iterable $listeners
	 * 
	 * @return int
	 */
	public function getListenersCount(?iterable $listeners = null): int
	{
		if ($listeners === null) {
			$count = 0;
			foreach ($this->getListeners() as $eventListeners) {
				foreach ($eventListeners as $priorityListeners) {
					$count += count($priorityListeners);
				}
			}

			return $count;
		}

		return is_array($listeners) ? count($listeners) : iterator_count($listeners);
	}

	/**
	 * Check that listener is registered
	 * 
	 * @param ?string $eventName
	 * 
	 * @return void
	 */
	public function hasListener(?string $eventName = null): bool
	{
		if ($eventName !== null) {
			$listener = $this->getListeners($eventName);

			return !empty($listener);
		}

		if ($this->getListenersCount() <= 0) {
			return false;
		}

		foreach ($this->getListeners() as $listenerItem) {
			if ($listenerItem) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Add listener to event
	 * 
	 * @param string $eventName
	 * @param callable $listener
	 * @param int $priority
	 * 
	 * @return void
	 */
	public function addListener(string $eventName, callable $listener, int $priority = 0): void
	{
		unset($this->sorted[$eventName]);

		if (isset($this->listeners[$eventName][$priority])) {
			$this->listeners[$eventName][$priority][] = $listener;
		} else {
			$this->listeners[$eventName][$priority] = [$listener];
		}
	}

	/**
	 * Emit event
	 * 
	 * @param object $event
	 * 
	 * @return object|bool
	 */
	public function emit(object $event): object|bool
	{
		$eventName = get_class($event);
		$listeners = $this->getListeners($eventName);

		if (empty($listeners)) {
			return false;
		}

		foreach ($listeners as $listener) {
			if ($event instanceof EventInstance && $event->isPropagationStopped()) {
				break;
			}

			if (is_callable($listener)) {
				try {
					$listener($event);
				} catch (Throwable $e) {
					$this->listenerFailures[] = [
						'eventName' => $eventName,
						'listener' => $this->normalizeListenerName($listener),
						'error' => $e->getMessage(),
					];
				}
			}
		}

		return $event;
	}

	/**
	 * Remove all listeners
	 * 
	 * @param string|null $eventName
	 * 
	 * @return void
	 */
	public function removeAllListeners(?string $eventName = null): void
	{
		if ($eventName !== null) {
			unset($this->listeners[$eventName], $this->sorted[$eventName]);
		} else {
			$this->listeners = [];
			$this->sorted = [];
		}
	}

	/**
	 * Check if event exists
	 * 
	 * @param string $eventName
	 * 
	 * @return bool
	 */
	public function hasEvent(string $eventName): bool
	{
		return isset($this->listeners[$eventName]);
	}

	/**
	 * Get all event names
	 * 
	 * @return array
	 */
	public function getEventNames(): array
	{
		return array_keys($this->listeners);
	}

	/**
	 * Destructor
	 * 
	 * @return void
	 */
	public function __destruct()
	{
		$this->removeAllListeners();
	}

	/**
	 * Clone object
	 * 
	 * @return void
	 */
	public function __clone(): void
	{
		$this->listeners = [];
		$this->sorted = [];
	}

	/**
	 * String representation of the object
	 * 
	 * @return string
	 */
	public function __toString(): string
	{
		return 'Event Dispatcher';
	}

	/**
	 * Queue an event for asynchronous handling.
	 *
	 * @param object $event
	 * @param string|null $eventName
	 * @return void
	 */
	public function queue(object $event, ?string $eventName = null): void
	{
		$this->queue[] = [
			'event' => $event,
			'eventName' => $eventName ?? get_class($event),
			'attempts' => 0,
		];
		$this->persistQueueToStorage();
	}

	/**
	 * Process queued events with retry and DLQ semantics.
	 *
	 * @param int|null $maxMessages
	 * @return void
	 */
	public function processQueue(?int $maxMessages = null): void
	{
		$this->loadQueueFromStorage();
		$processed = 0;

		while (!empty($this->queue) && ($maxMessages === null || $processed < $maxMessages)) {
			$item = array_shift($this->queue);
			if ($item === null) {
				break;
			}

			$beforeFailureCount = count($this->listenerFailures);
			$this->dispatch($item['event'], $item['eventName']);
			$afterFailureCount = count($this->listenerFailures);
			$hasFailures = $afterFailureCount > $beforeFailureCount;

			if ($hasFailures) {
				$item['attempts']++;
				if ($item['attempts'] <= $this->maxRetryAttempts) {
					$this->queue[] = $item;
				} else {
					$lastFailure = $this->listenerFailures[$afterFailureCount - 1] ?? null;
					$deadLetter = [
						'event' => $item['event'],
						'eventName' => $item['eventName'],
						'attempts' => $item['attempts'],
						'error' => $lastFailure['error'] ?? 'Unknown listener failure',
					];
					$this->deadLetterQueue[] = $deadLetter;
					$this->appendDeadLetterLog($deadLetter);
				}
			}

			$processed++;
		}
		$this->persistQueueToStorage();
	}

	/**
	 * Configure the max retry attempts for async queue processing.
	 *
	 * @param int $maxRetryAttempts
	 * @return void
	 */
	public function setMaxRetryAttempts(int $maxRetryAttempts): void
	{
		$this->maxRetryAttempts = max(0, $maxRetryAttempts);
	}

	/**
	 * Persist async queue to a JSON file.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setQueueStoragePath(string $path): void
	{
		$this->queueStoragePath = $path;
		$this->loadQueueFromStorage();
	}

	/**
	 * Persist dead-letter records as JSONL.
	 *
	 * @param string $path
	 * @return void
	 */
	public function setDeadLetterLogPath(string $path): void
	{
		$this->deadLetterLogPath = $path;
	}

	/**
	 * Get current async event queue
	 * 
	 * @return array<int, array{event: object, eventName: string, attempts: int, error: string}>
	 */
	public function getDeadLetterQueue(): array
	{
		return $this->deadLetterQueue;
	}

	/**
	 * Get count of queued events
	 * 
	 * @return int
	 */
	public function getQueueCount(): int
	{
		return count($this->queue);
	}

	/**
	 * Get listener failures for the last dispatch call.
	 * 
	 * @return array<int, array{eventName: string, listener: string, error: string}>
	 */
	public function getListenerFailures(): array
	{
		return $this->listenerFailures;
	}

	/**
	 * Normalize a listener to a string representation for logging purposes.
	 * 
	 * @param callable $listener
	 * @return string
	 */
	private function normalizeListenerName(callable $listener): string
	{
		if (is_array($listener)) {
			$target = is_object($listener[0]) ? get_class($listener[0]) : (string) $listener[0];
			return $target . '::' . (string) $listener[1];
		}

		if (is_string($listener)) {
			return $listener;
		}

		return 'closure';
	}

	/**
	 * Persist the current queue to storage if a path is configured.
	 * 
	 * @return void
	 */
	private function persistQueueToStorage(): void
	{
		if ($this->queueStoragePath === null) {
			return;
		}

		$dir = dirname($this->queueStoragePath);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$payload = [];
		foreach ($this->queue as $item) {
			$payload[] = [
				'eventName' => $item['eventName'],
				'attempts' => $item['attempts'],
				'event' => base64_encode(serialize($item['event'])),
			];
		}

		file_put_contents($this->queueStoragePath, (string) json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
	}

	/**
	 * Load queue from storage if configured
	 * 
	 * @return void
	 */
	private function loadQueueFromStorage(): void
	{
		if ($this->queueStoragePath === null || !file_exists($this->queueStoragePath) || !empty($this->queue)) {
			return;
		}

		$content = file_get_contents($this->queueStoragePath);
		if ($content === false || $content === '') {
			return;
		}

		$data = json_decode($content, true);
		if (!is_array($data)) {
			return;
		}

		foreach ($data as $item) {
			if (!is_array($item) || !isset($item['event']) || !isset($item['eventName'])) {
				continue;
			}

			$rawEvent = base64_decode((string) $item['event'], true);
			if ($rawEvent === false) {
				continue;
			}

			$event = @unserialize($rawEvent, ['allowed_classes' => true]);
			if (!is_object($event)) {
				continue;
			}

			$this->queue[] = [
				'event' => $event,
				'eventName' => (string) $item['eventName'],
				'attempts' => isset($item['attempts']) ? (int) $item['attempts'] : 0,
			];
		}
	}

	/**
	 * Append a dead-letter record to the log file in JSONL format.
	 * 
	 * @param array{event: object, eventName: string, attempts: int, error: string} $deadLetter
	 * @return void
	 */
	private function appendDeadLetterLog(array $deadLetter): void
	{
		if ($this->deadLetterLogPath === null) {
			return;
		}

		$dir = dirname($this->deadLetterLogPath);
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}

		$line = [
			'timestamp' => date(DATE_ATOM),
			'eventName' => $deadLetter['eventName'],
			'attempts' => $deadLetter['attempts'],
			'error' => $deadLetter['error'],
		];

		file_put_contents(
			$this->deadLetterLogPath,
			(string) json_encode($line, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL,
			FILE_APPEND
		);
	}
}
