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
 * Mouse Tracker Component
 */
class MouseTracker
{
    /** @var TerminalUI Terminal UI instance */
    private TerminalUI $ui;

    /**
     * Constructor
     *
     * @param TerminalUI $ui Terminal UI instance
     */
    public function __construct(TerminalUI $ui)
    {
        $this->ui = $ui;
    }

    /**
     * Track mouse clicks
     * 
     * @return void
     */
    public function track(): void
    {
        $this->ui->clearScreen();
        $this->ui->hideCursor();
        echo "\033[?1000h\033[?1006h";

        echo "Click anywhere (press 'q' to quit)\n";
        while (true) {
            $key = $this->ui->getKey();
            if ($key === 'q') {
                break;
            }

            if (preg_match('/\e\[<(\d+);(\d+);(\d+)([mM])/', $key, $m)) {
                echo "\nClicked at X={$m[2]}, Y={$m[3]}\n";
            }
        }

        echo "\033[?1000l\033[?1006l";
        $this->ui->showCursor();
    }
}
