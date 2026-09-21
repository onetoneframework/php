<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Interpreter;

abstract class PHPServer
{
    public const NUMBER_OF_PROCESSORS = 'NUMBER_OF_PROCESSORS';
    public const OS = 'OS';
    public const PATH = 'Path';
    public const HOMEPATH = 'HOMEPATH';
    public const LOCAL_APPDATA = 'LOCALAPPDATA';
    public const PROCESSOR_ARCHITECTURE = 'PROCESSOR_ARCHITECTURE';
    public const PROCESSOR_IDENTIFIER = 'PROCESSOR_IDENTIFIER';
    public const PROCESSOR_LEVEL = 'PROCESSOR_LEVEL';
    public const PROCESSOR_REVISION = 'PROCESSOR_REVISION';
    public const VSCODE_PYTHON_AUTOACTIVATE_GUARD = 'VSCODE_PYTHON_AUTOACTIVATE_GUARD';
    public const VSCODE_INJECTION = 'VSCODE_INJECTION';
    public const VSCODE_GIT_IPC_HANDLE = 'VSCODE_GIT_IPC_HANDLE';
    public const VSCODE_GIT_ASKPASS_MAIN = 'VSCODE_GIT_ASKPASS_MAIN';
    public const VSCODE_GIT_ASKPASS_EXTRA_ARGS = 'VSCODE_GIT_ASKPASS_EXTRA_ARGS';
    public const VSCODE_GIT_ASKPASS_NODE = 'VSCODE_GIT_ASKPASS_NODE';
    public const GIT_ASKPASS = 'GIT_ASKPASS';
    public const COLOR_TERM = 'COLORTERM';
    public const LANG = 'LANG';
    public const TERM_PROGRAM_VERSION = 'TERM_PROGRAM_VERSION';
    public const TERM_PROGRAM = 'TERM_PROGRAM';
    public const WINDOWS_DIRECTORY = 'windir';
    public const USER_DOMAIN = 'USERDOMAIN';
    public const USER_DOMAIN_ROAMING_PROFILE = 'USERDOMAIN_ROAMINGPROFILE';
    public const PUBLIC = 'PUBLIC';
    public const COMPUTER_NAME = 'COMPUTERNAME';
    public const CHROME_CRASHPAD_PIPE_NAME = 'CHROME_CRASHPAD_PIPE_NAME';
    public const CHOCOLATEY_INSTALL = 'ChocolateyInstall';
    public const ALL_USERS_PROFILE = 'ALLUSERSPROFILE';
    public const CHOCOLATEY_LAST_PATH_UPDATE = 'ChocolateyLastPathUpdate';
}
