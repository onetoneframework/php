<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Reflection;
/**
 * Class Instance
 *
 * @package Clover\Classes\Reflection
 */
class Instance
{
    // The name of the class for which the instance is being created
    private string $className;

    // Indicates whether the instance can be null
    private bool $nullable;

    /**
     * Instance constructor.
     *
     * @param string $className The name of the class for which the instance is being created
     * @param bool   $nullable Indicates whether the instance can be null
     * 
     * @return void
     */
    public function __construct(string $className, bool $nullable)
    {
        $this->className = $className;
        $this->nullable = $nullable;
    }
}