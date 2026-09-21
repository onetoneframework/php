<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

/**
 * Subscriber Interface
 *
 * Defines the contract for event subscribers.
 * Classes implementing this interface can subscribe to specific events.
 */
interface SubscriberInterface
{
    /**
     * Get the list of events this subscriber is subscribed to.
     *
     * @return array Array of event names and their corresponding listener methods.
     */
    public static function getSubscribedEvents(): array;
}