<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\QueryBuilder\Traits;

/**
 * Trait LimitTrait
 *
 * A trait for adding LIMIT functionality to query builders.
 */
trait LimitTrait
{
    protected $limit = null;
    protected $offset = null;

    /**
     * Set LIMIT value
     * 
     * @param int $limit
     * 
     * @return self
     */
    public function limit($limit): self
    {
        $this->limit = (int)$limit;
        return $this;
    }

    /**
     * Set OFFSET value
     * 
     * @param int $offset
     * 
     * @return self
     */
    public function offset($offset): self
    {
        $this->offset = (int)$offset;
        return $this;
    }

    /**
     * Alias for limit()
     * 
     * @param int $limit
     * 
     * @return self
     */
    public function take($limit): self
    {
        return $this->limit($limit);
    }

    /**
     * Alias for offset()
     * 
     * @param int $offset
     * 
     * @return self
     */
    public function skip($offset): self
    {
        return $this->offset($offset);
    }

    /**
     * Set limit and offset for pagination
     * 
     * @param int $page
     * @param int $perPage
     * 
     * @return self
     */
    public function forPage($page, $perPage = 15): self
    {
        return $this->offset(($page - 1) * $perPage)->limit($perPage);
    }

    /**
     * Remove LIMIT and OFFSET clauses
     * 
     * @return self
     */
    public function withoutLimit(): self
    {
        $this->limit = null;
        $this->offset = null;
        return $this;
    }
}
