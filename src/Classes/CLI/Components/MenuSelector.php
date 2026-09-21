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
 * Menu Selector Component
 */
class MenuSelector
{
    /** @var array Menu items */
    private array $items;

    /** @var TerminalUI Terminal UI instance */
    private TerminalUI $ui;

    /** @var int Currently selected item index */
    private int $selected = 0;

    /**
     * Constructor
     *
     * @param array $items Menu items
     * @param TerminalUI $ui Terminal UI instance
     */
    public function __construct(array $items, TerminalUI $ui)
    {
        $this->items = $items;
        $this->ui = $ui;
    }

    /**
     * Show the menu selector
     *
     * @return string Selected item
     */
    public function show(): mixed
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
                $this->selected = min(count($this->items) - 1, $this->selected + 1);
            }

            if ($key === "\n" || $key === "\r") {
                break;
            }

            $this->draw();
        }

        $this->ui->showCursor();
        return $this->items[$this->selected];
    }

    /**
     * Draw the menu
     * 
     * @return void
     */
    private function draw(): void
    {
        echo "\033[H";
        foreach ($this->items as $i => $item) {
            if ($i === $this->selected) {
                echo $this->ui->color(" > $item", 'cyan') . "\n";
            } else {
                echo "   $item\n";
            }
        }
    }
}
