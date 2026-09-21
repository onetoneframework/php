<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\{ColorText, Input, InputOption};
use Clover\Classes\Data\StringObject;
use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Date\Date;
use Clover\Classes\LLM\Gemini;
use Clover\Classes\OperationSystem;
use Clover\Classes\System\Output;
use Clover\Enumeration\Gemini\Model;
use Clover\Framework\Component\Translator;
use Clover\Implement\CommandInterface;
use Exception;
use function sprintf;

/**
 * Wizard Command Class
 *
 * Interactive command-line wizard for managing framework server environment.
 * Provides menu-driven interface for composer, docker, PHP, frontend, and other operations.
 */
class WizardCommand implements CommandInterface
{
    /**
     * Translate a wizard string (keys live under cli_wizard.* in language files).
     *
     * @param array<string, string> $replacements
     */
    private static function wizardTrans(string $key, array $replacements = [], ?string $default = null): string
    {
        return Translator::trans('cli_wizard.' . $key, $replacements, $default);
    }

    /**
     * BCP 47 style tag for prompts (Gemini and logging), derived from the active translator locale.
     */
    private static function wizardLocaleTag(): string
    {
        return match (Translator::getInstance()->getLanguage()) {
            'ko' => 'ko-KR',
            'ja' => 'ja-JP',
            'zh_cn' => 'zh-CN',
            'zh_tw' => 'zh-TW',
            'ru' => 'ru-RU',
            default => 'en',
        };
    }

    /**
     * @var array Command arguments.
     */
    public array $arguments = [];

    /**
     * @var array Command options.
     */
    public array $options = [];

    /**
     * @var PHPDataObject Database connection object.
     */
    private PHPDataObject $db;

    /**
     * Get the command name.
     *
     * @return string The command name.
     */
    public function getName(): string
    {
        return 'framework:wizard';
    }

    /**
     * Get the command description.
     *
     * @return string The command description.
     */
    public function getDescription(): string
    {
        return self::wizardTrans(
            'command_description',
            [],
            'Interactive menu for local PHP framework development tasks.'
        );
    }

    /**
     * Configure the command options and arguments.
     *
     * @return void
     */
    public function configure(): void
    {
        $this->options[] = new InputOption(
            'prompt',
            'Prompt',
            self::wizardTrans('gemini_user_prompt', [], 'Enter your prompt for Gemini')
        );
    }

    /**
     * Handle composer-related operations.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handleComposer(Input $input): void
    {
        $choose = $input->choose(
            ['✨ ' . self::wizardTrans('composer_update')],
            ColorText::color(self::wizardTrans('composer_prompt'), 'green'),
            'yellow',
            true
        );

        if ($choose === 0) {
            Output::printLine(ColorText::color(' INFO ', 'white', 'blue') . ' ' . ColorText::color(self::wizardTrans('composer_updating'), 'white'));

            $composerCommand = "php ./bin/phar/composer.phar";
            $check = OperationSystem::executeShell('where composer 2>&1');
            if (strpos($check, '\composer') !== false) {
                $composerCommand = "composer";
            }

            OperationSystem::executeShellAndPrint(sprintf("%s update --working-dir=./res/Platform/PHP 2>&1", $composerCommand));
        }

    }

    /**
     * Handle docker-related operations.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handleDocker(Input $input): void
    {
        $choose = $input->choose([
            '⏩ ' . self::wizardTrans('docker_start'),
            '🚩 ' . self::wizardTrans('docker_stop_all'),
            '🚫 ' . self::wizardTrans('docker_remove_volumes'),
            '✨ ' . self::wizardTrans('docker_rebuild'),
            '🛰️  ' . self::wizardTrans('docker_tcp_reserved'),
            '♻️  ' . self::wizardTrans('docker_restart_php'),
            '♻️  ' . self::wizardTrans('docker_restart_nginx'),
            '📜 ' . self::wizardTrans('docker_mariadb_logs'),
        ], ColorText::color(self::wizardTrans('docker_prompt'), 'green'), 'yellow', true);

        if ($choose === 0) {
            $response = OperationSystem::executeShellAndPrint(sprintf("docker-compose up -d"));
        } else if ($choose === 1) {
            $response = OperationSystem::executeShellAndPrint(sprintf("docker stop $(docker ps -q)"));
        } else if ($choose === 2) {
            $response = OperationSystem::executeShellAndPrint(sprintf("docker volume rm $(docker volume ls -q)"));
        } else if ($choose === 3) {
            $response = OperationSystem::executeShellAndPrint(sprintf("docker-compose up -d --build"));
        } else if ($choose === 4) {
            OperationSystem::executeShellAndPrint(sprintf("netsh interface ipv4 show excludedportrange protocol=tcp"));
        } else if ($choose === 5) {
            $containerId = trim(OperationSystem::executeShell('docker ps -q --filter "name=php"'));
            if (!$containerId) {
                throw new Exception(self::wizardTrans('docker_container_not_found'));
            }

            $output = OperationSystem::executeShellAndPrint('docker restart php');
            var_dump($output);
        } else if ($choose === 6) {
            $containerId = trim(OperationSystem::executeShell('docker ps -q --filter "name=nginx"'));
            if (!$containerId) {
                throw new Exception(self::wizardTrans('docker_container_not_found'));
            }

            $output = OperationSystem::executeShellAndPrint("docker restart {$containerId}");
        } else if ($choose === 7) {
            OperationSystem::executeShellAndPrint("docker logs mariadb");
        }
    }

    /**
     * Handle frontend build operations.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handleFrontend(Input $input): void
    {
        $choose = $input->choose([
            '✨ ' . self::wizardTrans('frontend_webpack'),
            '✨ ' . self::wizardTrans('frontend_vite'),
            '✨ ' . self::wizardTrans('frontend_esbuild'),
        ], ColorText::color(self::wizardTrans('frontend_prompt'), 'green'), 'yellow', true);

        if ($choose === 0) {
            $currentDir = getcwd();

            $cmd = 'cd /d "' . $currentDir . '\res\frontend" && npm run build && cd /d "' . $currentDir . '"';
            OperationSystem::executeShellAndPrint("cmd /c \"$cmd\"");
        } else if ($choose === 1) {
            $currentDir = getcwd();

            $cmd = 'cd /d "' . $currentDir . '\res\frontend" && npm run build:vite && cd /d "' . $currentDir . '"';
            OperationSystem::executeShellAndPrint("cmd /c \"$cmd\"");
        } else if ($choose === 2) {
            $currentDir = getcwd();

            $cmd = 'cd /d "' . $currentDir . '\res\frontend" && npm run build:esbuild && cd /d "' . $currentDir . '"';
            OperationSystem::executeShellAndPrint("cmd /c \"$cmd\"");
        }
    }

    /**
     * Handle extension installation operations.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handleExtension(Input $input): void
    {
        $choose = $input->choose(
            ['📦 ' . self::wizardTrans('extension_chocolatey')],
            ColorText::color(self::wizardTrans('extension_prompt'), 'green'),
            'yellow',
            true
        );

        if ($choose === 0) {
            $currentDir = getcwd();

            OperationSystem::executeShellAndPrint("cd {$currentDir}/res/scripts && powershell -ExecutionPolicy Bypass -File {$currentDir}/res/scripts/install_chocolatey.ps1");
        }
    }

    /**
     * Handle PHP-related operations.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handlePHP(Input $input): void
    {
        $choose = $input->choose([
            '⏩ ' . self::wizardTrans('php_server'),
            '📃 ' . self::wizardTrans('php_phpdoc'),
            '🗂️  ' . self::wizardTrans('php_ffi_copy'),
            '📖 ' . self::wizardTrans('php_clear_devdeps'),
        ], ColorText::color(self::wizardTrans('php_prompt'), 'green'), 'yellow', true);

        $currentDirectory = getcwd();
        if ($choose === 0) {
            Output::printLine(ColorText::color(self::wizardTrans('php_server_tip'), 'blue'));

            OperationSystem::executeShellAndPrint("php -S localhost:8080 -t res/Platform/PHP/root");
        } else if ($choose === 1) {
            $response = OperationSystem::executeShellAndPrint("php ./bin/phar/phpDocumentor.phar run -d ./res/Platform/PHP/src -t docs/framework 2>&1");
        } else if ($choose === 2) {
            OperationSystem::executeShellAndPrint("cd {$currentDirectory}/res/scripts && powershell -ExecutionPolicy Bypass -File {$currentDirectory}/res/scripts/c_php_ffi_copy.ps1");
        } else if ($choose === 3) {
            OperationSystem::executeShellAndPrint("cd {$currentDirectory}/res/scripts && powershell -ExecutionPolicy Bypass -File {$currentDirectory}/res/scripts/clear-devdeps.ps1");
        }
    }

    /**
     * Handle unit test execution.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handleUnittest(Input $input): void
    {
        OperationSystem::executeShellAndPrint("php ./bin/phar/phpunit.phar --bootstrap ./res/Platform/PHP/vendor/autoload.php --colors=always ./res/Platform/PHP/tests --coverage-html ./php-test-coverage 2>&1");
    }

    /**
     * Handle php-stan test execution.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    private function handlePHPStan(Input $input): void
    {
        OperationSystem::executeShellAndPrint("php ./bin/phar/phpstan.phar --colors=always ./res/Platform/PHP/src --memory-limit 500M -c ./res/Platform/PHP/phpstan.neon.dist");
    }

    /**
     * Handle Google Gemini AI chat interactions.
     *
     * @param Input $input The input object.
     * 
     * @return void
     */
    public function handleGemini(Input $input): void
    {
        $localeTag = self::wizardLocaleTag();
        $systemPrompt = trim(self::wizardTrans('gemini_system_locale', ['locale' => $localeTag])) . "\n\n" . trim(self::wizardTrans('gemini_system_body'));

        $geminiClient = new Gemini\Client($_ENV['GEMINI_API_KEY'], Model::GEMINI_2_5_FLASH);
        $response = $geminiClient->requestPrompt($systemPrompt);

        if ($response->hasError()) {
            $error = $response->getError();
            $response = $error->getMessage();
        } else {
            $response = $response->getText();
        }

        Output::printLine(ColorText::color(' ' . self::wizardTrans('gemini_header') . ' ', 'white', 'blue') . ' ' . $response);

        echo PHP_EOL;

        $prompt = $input->getPrompt(self::wizardTrans('gemini_user_prompt') . ': ');
        $response = $geminiClient->requestPrompt($systemPrompt . ' | User message: ' . $prompt);
        
        if ($response->hasError()) {
            $error = $response->getError();
            $response = $error->getMessage();
        } else {
            $response = $response->getText();
        }

        echo PHP_EOL;

        Output::printLine(ColorText::color(' ' . self::wizardTrans('gemini_header') . ' ', 'white', 'blue') . ' ' . $response);
    }

    /**
     * Execute the wizard command.
     *
     * @param Input $input The input object containing options and arguments.
     * 
     * @return bool True on success, false on failure.
     */
    public function run(Input $input): bool
    {
        Translator::getInstance();

        $logoText = <<<EOD

    ******   **                                  ********                                                                 **    
   **////** /**                                 /**/////                                                                 /**    
  **    //  /**  ******  **    **  *****  ******/**       ******  ******   **********   *****  ***     **  ******  ******/**  **
 /**        /** **////**/**   /** **///**//**//*/******* //**//* //////** //**//**//** **///**//**  * /** **////**//**//*/** ** 
 /**        /**/**   /**//** /** /******* /** / /**////   /** /   *******  /** /** /**/******* /** ***/**/**   /** /** / /****  
 //**    ** /**/**   /** //****  /**////  /**   /**       /**    **////**  /** /** /**/**////  /****/****/**   /** /**   /**/** 
  //******  ***//******   //**   //******/***   /**      /***   //******** *** /** /**//****** ***/ ///**//****** /***   /**//**
   //////  ///  //////     //     ////// ///    //       ///     //////// ///  //  //  ////// ///    ///  //////  ///    //  // 

EOD;

        $logo = new StringObject($logoText);
        Output::printLine(ColorText::color('┌──────────────────────────────────────────────────────────────────────────────────────────────┐', 'blue'));
        Output::printLine(ColorText::color($logo, 'red'));
        Output::printLine(ColorText::color('└──────────────────────────────────────────────────────────────────────────────────────────────┘', 'blue'));

        Output::printLine(ColorText::color(' ' . self::wizardTrans('header_control_center') . ' ', 'white', 'blue') . ' ' . ColorText::color(self::wizardTrans('header_version'), 'yellow'));
        Output::printLine(ColorText::color(
            self::wizardTrans('session_label') . ': ' . date('Y-m-d H:i:s', time()) . ' ' . Date::getDayNameOfWeek(time()),
            'magenta'
        ));
        Output::printLine(ColorText::color(
            self::wizardTrans('environment_label') . ': ' . self::wizardTrans('environment_local'),
            'green'
        ));

        echo PHP_EOL;

        $choose = $input->choose([
            '✨ ' . self::wizardTrans('category_composer'),
            '🐳 ' . self::wizardTrans('category_docker'),
            '🔧 ' . self::wizardTrans('category_unittest'),
            '📝 ' . self::wizardTrans('category_php'),
            '📦 ' . self::wizardTrans('category_verdaccio'),
            '💻 ' . self::wizardTrans('category_frontend'),
            '🗃️  ' . self::wizardTrans('category_extension'),
            '📣 ' . self::wizardTrans('category_gemini'),
        ], ColorText::color(self::wizardTrans('main_prompt'), 'green'), 'yellow', true);
        
        $handlers = [
            0 => 'handleComposer',
            1 => 'handleDocker',
            2 => 'handleUnittest',
            3 => 'handlePHP',
            4 => 'handleFrontend',
            5 => 'handleExtension',
            6 => 'handleGemini',
        ];

        if (isset($handlers[$choose])) {
            $this->{$handlers[$choose]}($input);
        }

        return true;
    }
}
