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
use function count;

/**
 * Checkbox Form Component
 */
class CheckboxForm
{
    /** @var array<int, array{checked: bool}> Options for the checkbox form */
    private array $options;

    /** @var TerminalUI Terminal UI instance */
    private TerminalUI $ui;

    /** @var int Currently selected option index */
    private int $selected = 0;

    /**
     * Constructor
     *
     * @param array<string> $labels Labels for the checkbox options
     * @param TerminalUI $ui Terminal UI instance
     */
    public function __construct(array $labels, TerminalUI $ui)
    {
        $this->options = array_map(fn($label) => ['label' => $label, 'checked' => false], $labels);
        $this->ui = $ui;
    }

    /**
     * Show the checkbox form
     *
     * @return array Selected options
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

            if ($key === " ") {
                $this->options[$this->selected]['checked'] = !$this->options[$this->selected]['checked'];
            }

            if ($key === "\n" || $key === "\r") {
                break;
            }

            $this->draw();
        }

        $this->ui->showCursor();
        return array_filter($this->options, fn($opt) => $opt['checked']);
    }

    /**
     * Draw the checkbox form
     * 
     * @return void
     */
    private function draw(): void
    {
        echo "\033[H";

        foreach ($this->options as $i => $opt) {
            $mark = $opt['checked'] ? '[x]' : '[ ]';
            $line = "$mark " . $opt['label'];
            echo $i === $this->selected ? $this->ui->color(" > $line", 'green') . "\n" : "   $line\n";
        }
    }
}
