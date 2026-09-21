<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Data;

/**
 * Class ResourceObject
 *
 * Represents a resource and provides methods to handle it.
 */
#[\AllowDynamicProperties]
class ResourceObject
{
    /**
     * The resource data.
     *
     * @var mixed
     */
    protected $data;

    /**
     * ResourceObject constructor.
     *
     * @param mixed $data The resource data.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
    
    /**
     * Converts the resource to a string.
     *
     * @return string The string representation of the resource.
     */
    public function __toString(): string
    {
        return (string)$this->data;
    }
}
