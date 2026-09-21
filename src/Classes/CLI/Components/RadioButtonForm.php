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
 * Radio Button Form Component
 */
class RadioButtonForm
{
    /** @var array<int, array{label: string, checked: bool}> Options for the radio button form */
    private array $options;

    /** @var TerminalUI Terminal UI instance */
    private TerminalUI $ui;

    /** @var int Currently selected option index */
    private int $selected = 0;

    /**
     * Constructor
     *
     * @param array      $labels Labels for the radio button options
     * @param TerminalUI $ui Terminal UI instance
     */
    public function __construct(array $labels, TerminalUI $ui)
    {
        $this->options = array_map(fn($label) => ['label' => $label, 'checked' => false], $labels);
        $this->ui = $ui;
    }

    /**
     * Show the radio button form
     *
     * @return array Selected option
     */
    public function show(): array
    {
        $this->ui->clearScreen();
        $this->ui->hideCursor();
        $this->draw();

        while (true) {
            $key = $this->ui->getKey();
            if ($key === "\033[A") {
                $this->selected = max(0, $this->selected - 1);
            }

            if ($key === "\033[B") {
                $this->selected = min(count($this->options) - 1, $this->selected + 1);
            }

            if ($key === "\n" || $key === "\r") {
                break;
            }

            $this->draw();
        }

        $this->ui->showCursor();
        return $this->options[$this->selected];
    }

    /**
     * Draw the radio button form
     * 
     * @return void
     */
    private function draw(): void
    {
        echo "\033[H";
        foreach ($this->options as $i => $opt) {
            $mark = $i === $this->selected ? '[x]' : '[ ]';
            $line = "$mark " . $opt['label'];
            echo $i === $this->selected
                ? $this->ui->color(" > $line", 'blue') . "\n"
                : "   $line\n";
        }
    }
}
