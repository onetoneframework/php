<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\OperationSystem;

class WindowsCommand
{
    public function getInterpreterPath()
    {
        $command = 'powershell -command "(Get-Process -Id ' . getmypid() . ').Path"';
        return trim(shell_exec($command));
    }
}
