<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes;
use ArrayIterator;

/**
 * Class ChainableArrayIterator
 *
 * An extension of PHP's ArrayIterator that supports method chaining.
 */
class ChainableArrayIterator extends ArrayIterator
{
    public function prev(): static
    {
        $pos = $this->key();
        if ($pos > 0) {
            $this->seek($pos - 1);
        }

        return $this;
    }

    public function currentValue(): mixed
    {
        return $this->current();
    }

    public function first(): static
    {
        $this->rewind();
        return $this;
    }

    public function last(): static
    {
        if ($this->count() > 0) {
            $this->seek($this->count() - 1);
        }

        return $this;
    }

    #[\ReturnTypeWillChange]
    public function next(): static
    {
        parent::next();
        return $this;
    }

    public function hasNext(): bool
    {
        return $this->key() < $this->count() - 1;
    }

    public function hasPrev(): bool
    {
        return $this->key() > 0;
    }

    #[\ReturnTypeWillChange]
    public function rewind(): static
    {
        parent::rewind();
        return $this;
    }
}