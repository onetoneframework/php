<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\DataStructor;

use Clover\Classes\DataStructor\DoubleyLinkedList;

use SplStack;
use SplDoublyLinkedList;

/**
 * Stack Data Structure
 */
class Stack extends DoubleyLinkedList
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::$stock = new SplStack();
        parent::$stock->setIteratorMode(SplDoublyLinkedList::IT_MODE_LIFO | SplDoublyLinkedList::IT_MODE_KEEP);
    }
}
