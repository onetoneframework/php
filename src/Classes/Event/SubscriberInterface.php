<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

/**
 * Subscriber Interface
 */
interface SubscriberInterface
{
    /**
     * Get the events that the subscriber wants to listen to
     * 
     * @return array An array of event names and their corresponding handler methods
     */
    public function getSubscribedEvents();
}
