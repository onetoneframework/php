<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

use Clover\Classes\BaseClass;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\DependencyInjection\Injector;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use function is_array;
use function is_object;
use function is_string;
use function uniqid;

/**
 * Class RouteExecutor
 * 
 * Executes a route by invoking the associated callback with the provided arguments.
 */
class RouteExecutor extends BaseClass
{
    /** @var string|null */
    private ?string $class;

    /** @var string|null */
    private ?string $method;

    /** @var mixed */
    private mixed $callback;

    /** @var string[] */
    private $arguments;

    /** @var Container|null */
    private ?Container $container;
    private EventDispatcherAdapter $eventDispatcher;

    /**
     * Constructor
     * 
     * @param string|null $class The class name
     * @param string|null $method The method name
     * @param mixed $callback The callback to execute
     * @param array $arguments The arguments to pass to the callback
     * @param Container|null $container The dependency injection container
     */
    public function __construct(?string $class = null, ?string $method = null, mixed $callback = null, array $arguments = [], ?Container $container = null, ?EventDispatcherAdapter $eventDispatcher = null)
    {
        $this->class = $class;
        $this->method = $method;
        $this->callback = $callback;
        $this->arguments = $arguments;
        $this->container = $container;
        $this->eventDispatcher = $eventDispatcher ?? EventDispatcherAdapter::fromEventManager();
    }

    /**
     * Get the dependency injection container
     * 
     * @return Container|null
     */
    private function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * Get the callback
     * 
     * @return mixed
     */
    private function getCallback(): mixed
    {
        return $this->callback;
    }

    /**
     * Get the method name
     * 
     * @return string|null
     */
    private function getMethod(): ?string
    {
        return $this->method;
    }

    /**
     * Get the class name
     * 
     * @return string|null
     */
    private function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * Get the arguments
     * 
     * @return array
     */
    private function getArguments(): array
    {
        return $this->arguments;
    }
    
    /**
     * Invoke the route's callback with the provided arguments
     *
     * @param mixed $nextArguments Additional arguments to pass to the callback
     * @return mixed The result of the callback execution
     */
    public function __invoke(mixed $nextArguments = []): mixed
    {
        $class = $this->getClass();
        $method = $this->getMethod();
        $callback = $this->getCallback();
        $arguments = $this->getArguments() ?? [];
        $arguments = [...$arguments, $nextArguments];
        $container = $this->getContainer();

		$profilerEnabled = Profiler::isEnabled();
		$profilerToken = '';
		if ($profilerEnabled) {
			$location = 'RouteExecutor::__invoke';
			if (is_string($class) && $class !== '') {
				$location = (is_string($method) && $method !== '') ? $class . '::' . $method : $class;
			} elseif (is_array($callback) && isset($callback[0], $callback[1])) {
				$resolvedClass = is_string($callback[0]) ? $callback[0] : (is_object($callback[0]) ? $callback[0]::class : '');
				$resolvedMethod = is_string($callback[1]) ? $callback[1] : '';
				if ($resolvedClass !== '') {
					$location = ($resolvedMethod !== '') ? $resolvedClass . '::' . $resolvedMethod : $resolvedClass;
				}
			} elseif (is_string($callback) && $callback !== '') {
				$location = $callback;
			}

			$profilerToken = uniqid('pf_', true);
			$this->eventDispatcher->dispatch(new KernelSpanStarted(
				$profilerToken,
				'Kernel::RouteExecutor',
				$location
			));
		}

        try {
            if (isset($class) && !empty($class) && class_exists($class)) {
				// Resolve static controller dependencies only for the selected route.
				Injector::inject($class);
                $callback = new $class;
            }

            if (!isset($method) && empty($method) && is_callable($callback)) {
                return ReflectionHandler::callMethodArray($callback, $arguments);
            }

            if (is_object($callback)) {
                return ReflectionHandler::invoke($callback, $method, $arguments, $container);
            }

            return false;
		} finally {
			if ($profilerEnabled) {
				$this->eventDispatcher->dispatch(new KernelSpanFinished($profilerToken));
			}
		}
    }
}
