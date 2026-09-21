<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Event;

use Clover\Implement\SubscriberInterface;

abstract class AbstractSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [];
    }
}
