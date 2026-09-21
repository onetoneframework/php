<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\DataStructor;

use ReturnTypeWillChange;
use SplDoublyLinkedList;
use SplQueue;
use SplStack;

/**
 * Doubley Linked List Data Structure
 */
class DoubleyLinkedList extends SplDoublyLinkedList
{
    /** @var SplDoublyLinkedList|SplQueue|SplStack|null $stock The stock instance */
    protected static SplDoublyLinkedList|SplQueue|SplStack|null $stock = null;

    /**
     * Constructor
     */
    #[ReturnTypeWillChange]
    public function top(): mixed
    {
        return self::$stock->top();
    }

    /**
     * Push Item to Stock
     *
     * @param mixed $item
     * @return void
     */
    public function pushItem($item): void
    {
        self::$stock[] = $item;
    }
}
