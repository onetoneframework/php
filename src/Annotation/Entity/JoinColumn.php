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
 * Class JoinColumn
 *
 * Annotation to define a join column for an entity property.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class JoinColumn
{
    /**
     * @var string The name of the join column.
     */
    public $name;

    /**
     * @var string The name of the referenced column in the target entity.
     */
    public $referencedColumnName;

    /**
     * JoinColumn constructor.
     *
     * @param string $name The name of the join column.
     * @param string $referencedColumnName The name of the referenced column in the target entity.
     */
    public function __construct(string $name, string $referencedColumnName)
    {
        $this->name = $name;
        $this->referencedColumnName = $referencedColumnName;
    }
}
