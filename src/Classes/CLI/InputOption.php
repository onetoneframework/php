<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI;

use Clover\Classes\BaseClass;

/**
 * InputOption class for defining command-line input options
 */
class InputOption extends BaseClass
{
    public string $name;
    public string $description;
    public mixed $default;

    /**
     * Constructor for InputOption
     *
     * @param string $name        The name of the option
     * @param string $description A brief description of the option
     * @param mixed  $default     The default value for the option
     * 
     * @return void
     */
    public function __construct(string $name, string $description = '', mixed $default = null)
    {
        $this->name = $name;
        $this->description = $description;
        $this->default = $default;
    }
}
