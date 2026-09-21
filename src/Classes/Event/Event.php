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
 * Base Event class
 */
abstract class Event
{
    /** 
     * Indicates whether the propagation of the event has been stopped.
     * When set to true, no further listeners will be called for this event.
     */
    private bool $propagationStopped = false;

    /** 
     * Stops the propagation of the event to further listeners.
     * Once this method is called, no more listeners will be called for this event.
     */
    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }

    /** 
     * Checks if the propagation of the event has been stopped.
     *
     * @return bool True if propagation is stopped, false otherwise.
     */
    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }
}
