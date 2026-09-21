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
 * Class Column
 *
 * Annotation to define a database column for an entity property.
 */
#[Attribute]
class Column
{
    /**
     * @var string The name of the column.
     */
    public string $name;

    /**
     * @var string The data type of the column.
     */
    public string $type;

    /**
     * @var bool Whether the column can be null. Default is true.
     */
    public bool $nullable = true;

    /**
     * Column constructor.
     *
     * @param string $name The name of the column.
     * @param string $type The data type of the column.
     * @param bool $nullable Whether the column can be null. Default is true.
     */
    public function __construct(string $name, string $type, bool $nullable = true)
    {
        $this->name = $name;
        $this->type = $type;
        $this->nullable = $nullable ?? $this->nullable;
    }
}