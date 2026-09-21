<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\FFI\Windows\CustomWindow;
use Clover\Classes\FFI\Windows\WindowsAPI;
use Clover\Implement\CommandInterface;
use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputOption;

class WindowsStyleCommand implements CommandInterface
{
    public array $arguments = [];
    public array $options = [];

    public function getName(): string
    {
        return 'windows:style';
    }

    public function getDescription(): string
    {
        return 'Run Windows API style experiments and lock-screen tests';
    }

    public function configure(): void
    {
        $this->options[] = new InputOption('prompt', 'Prompt', 'How are you?');
    }

    public function run(Input $input): bool
    {
        $windowsAPI = new WindowsAPI();

        $window = new CustomWindow($windowsAPI);
        $window->setTitle("Hello World")->setSize(280, 380)->setPosition(200, 1580);
        $window->addImageBox(__DIR__."/qr.png", 0, 0, 192, 192);
        $window->run();

        return true;
    }
}
