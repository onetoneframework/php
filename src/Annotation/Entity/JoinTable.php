<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Annotation\Entity;

use Attribute;

/**
 * Class JoinTable
 *
 * Annotation to define a join table for an entity property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JoinTable
{
    /**
     * @var string|null The name of the join table.
     */
    public $name;

    /**
     * @var array The join columns configuration.
     */
    public $joinColumns = [];

    /**
     * @var array The inverse join columns configuration.
     */
    public $inverseJoinColumns = [];

    /**
     * JoinTable constructor.
     *
     * @param array $options An array of options including 'name', 'joinColumns', and 'inverseJoinColumns'.
     */
    public function __construct(array $options)
    {
        $this->name = $options['name'] ?? null;
        $this->joinColumns = $options['joinColumns'] ?? [];
        $this->inverseJoinColumns = $options['inverseJoinColumns'] ?? [];
    }
}