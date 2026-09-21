<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\CLI\Component;

use Clover\Classes\CLI\TerminalUI;

/**
 * Input Field Component
 */
class InputField
{
    /** @var TerminalUI Terminal UI instance */
    private TerminalUI $ui;

    /**
     * Constructor
     *
     * @param TerminalUI $ui
     */
    public function __construct(TerminalUI $ui)
    {
        $this->ui = $ui;
    }

    /**
     * Prompt for input
     * 
     * @param string $label Label for the input field
     * 
     * @return string
     */
    public function prompt(string $label): string
    {
        $this->ui->clearScreen();
        echo $label . ": ";
        system('stty -icanon -echo');
        $input = fgets(STDIN);
        system('stty sane');
        return trim($input);
    }
}
