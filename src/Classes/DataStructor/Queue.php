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

use SplQueue;

/**
 * Queue Data Structure
 */
class Queue extends DoubleyLinkedList
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::$stock = new SplQueue();
    }

    /**
     * Enqueue an item
     *
     * @param mixed $value
     * @return void
     */
    public function enqueue($value)
    {
        return parent::$stock->enqueue($value);
    }

    /**
     * Dequeue an item
     *
     * @return mixed
     */
    public function dequeue()
    {
        return parent::$stock->dequeue();
    }
}
