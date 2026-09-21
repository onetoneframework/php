<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Classes\DependencyInjection\Container as LegacyContainer;
use Clover\Contract\ContainerInterface;
use Clover\Implement\EventBusInterface;
use Clover\Implement\EventDispatcherInterface;
use Throwable;

/**
 * Event Manager class
 */
class EventManager
{
    /** @var Dispatcher|null $instance Singleton instance of the Event Dispatcher */
    private static ?Dispatcher $instance = null;
    /** @var EventBus|null $eventBus Optional singleton instance of the Event Bus for global access */
    private static ?EventBus $eventBus = null;
    /** @var ContainerInterface|LegacyContainer|null $container Optional container for dependency resolution (DI first, singleton fallback) */
    private static ContainerInterface|LegacyContainer|null $container = null;

    /**
     * Get the singleton instance of the Event Dispatcher
     * 
     * @return Dispatcher
     */
    public static function getInstance(): Dispatcher
    {
        if (self::$container !== null) {
            try {
                $dispatcher = self::resolveDispatcherFromContainer();
                if ($dispatcher instanceof Dispatcher) {
                    return $dispatcher;
                }
            } catch (Throwable $e) {
                // Fallback to singleton instance if the container cannot resolve dispatcher.
            }
        }

        if (self::$instance === null) {
            self::$instance = new Dispatcher();
        }

        return self::$instance;
    }

    /**
     * Get the singleton instance of the Event Bus
     * 
     * @return EventBus
     */
    public static function getEventBus(): EventBus
    {
        if (self::$container !== null) {
            try {
                $bus = self::resolveEventBusFromContainer();
                if ($bus instanceof EventBus) {
                    return $bus;
                }
            } catch (Throwable $e) {
                // Fallback to singleton bus if container is unavailable.
            }
        }

        if (self::$eventBus === null) {
            self::$eventBus = new EventBus(self::getInstance());
        }

        return self::$eventBus;
    }

    /**
     * Set the singleton instance of the Event Dispatcher
     * 
     * @param Dispatcher $dispatcher
     * 
     * @return void
     */
    public static function setInstance(Dispatcher $dispatcher): void
    {
        self::$instance = $dispatcher;
        self::$eventBus = new EventBus($dispatcher);
    }

    /**
     * Set container for dispatcher resolution (DI first, singleton fallback).
     *
     * @param ContainerInterface|LegacyContainer $container
     * @return void
     */
    public static function setContainer(ContainerInterface|LegacyContainer $container): void
    {
        self::$container = $container;
    }

    /**
     * Clear the singleton instance and containere reference
     * 
     * @return void
     */
    public static function clearInstance(): void
    {
        self::$instance = null;
        self::$eventBus = null;
        self::$container = null;
    }

    /**
     * Resolve the Event Dispatcher from the container if available
     * 
     * @return mixed The resolved Event Dispatcher instance or null if not resolvable
     */
    private static function resolveDispatcherFromContainer(): mixed
    {
        if (self::$container === null) {
            return null;
        }

        if (method_exists(self::$container, 'make')) {
            return self::$container->make(EventDispatcherInterface::class);
        }

        if (method_exists(self::$container, 'get')) {
            return self::$container->get(EventDispatcherInterface::class);
        }

        return null;
    }

    /**
     * Resolve EventBus from container if available
     * 
     * @return mixed The resolved EventBus instance or null if not resolvable
     */
    private static function resolveEventBusFromContainer(): mixed
    {
        if (self::$container === null) {
            return null;
        }

        if (method_exists(self::$container, 'make')) {
            return self::$container->make(EventBusInterface::class);
        }

        if (method_exists(self::$container, 'get')) {
            return self::$container->get(EventBusInterface::class);
        }

        return null;
    }
}
