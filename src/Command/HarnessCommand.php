<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Command;

use Clover\Classes\CLI\Input;
use Clover\Classes\CLI\InputArgument;
use Clover\Classes\CLI\InputOption;
use Clover\Classes\System\Output;
use Clover\Implement\CommandInterface;

final class HarnessCommand implements CommandInterface
{
    public array $arguments = [];
    public array $options = [];

    public function getName(): string
    {
        return 'harness';
    }

    public function getDescription(): string
    {
        return 'Orchestrate common dev workflows (migrations, tests, static analysis, routes).';
    }

    public function configure(): void
    {
        $this->arguments[] = new InputArgument('task', 'Task name (list|doctor|migrate|routes|test|analyse|check)', 'list');
        $this->options[] = new InputOption('php', 'PHP executable (default: php)', 'php');
        $this->options[] = new InputOption('quiet', 'Reduce extra headings', false);
    }

    public function run(Input $input): bool
    {
        $task = (string) ($input->getArgument('task') ?? 'list');
        $php = (string) ($input->getOption('php') ?? 'php');
        $quiet = (bool) ($input->getOption('quiet') ?? false);

        $repoRoot = $this->getRepoRoot();
        if ($repoRoot === null) {
            Output::printLine('harness: unable to locate repository root from this command location.');
            return false;
        }

        return match ($task) {
            'list' => $this->taskList(),
            'doctor' => $this->taskDoctor($repoRoot, $php),
            'routes' => $this->taskRoutes($repoRoot, $php),
            'migrate' => $this->taskMigrate($repoRoot, $php, $quiet),
            'test' => $this->taskPhpUnit($repoRoot, $php, $quiet),
            'analyse' => $this->taskPhpStan($repoRoot, $php, $quiet),
            'check' => $this->taskCheck($repoRoot, $php, $quiet),
            default => $this->taskUnknown($task),
        };
    }

    private function taskList(): bool
    {
        Output::printLine('harness tasks:');
        Output::printLine('  - list       : show tasks');
        Output::printLine('  - doctor     : verify expected files/commands');
        Output::printLine('  - migrate    : run database:migrate');
        Output::printLine('  - routes     : run route:list');
        Output::printLine('  - test       : run PHPUnit');
        Output::printLine('  - analyse    : run PHPStan');
        Output::printLine('  - check      : run PHPUnit + PHPStan');
        Output::printLine('');
        Output::printLine('examples:');
        Output::printLine('  php ./php_console harness migrate');
        Output::printLine('  php ./php_console harness doctor');
        Output::printLine('  php ./php_console harness check');
        Output::printLine('  php ./php_console harness routes');
        return true;
    }

    private function taskUnknown(string $task): bool
    {
        Output::printLine("harness: unknown task '{$task}'");
        Output::printLine("run: php ./php_console harness list");
        return false;
    }

    private function taskDoctor(string $repoRoot, string $php): bool
    {
        $ok = true;

        Output::printLine('harness doctor:');
        $ok = $this->runProcess([$php, '-v'], $repoRoot) === 0 && $ok;

        $composerVendor = $repoRoot . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'Platform' . DIRECTORY_SEPARATOR . 'PHP' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (!is_file($composerVendor)) {
            $ok = false;
            Output::printLine("missing: {$composerVendor}");
            Output::printLine('fix: composer install --working-dir=./res/Platform/PHP');
        } else {
            Output::printLine('ok: PHP composer autoload present');
        }

        $phpUnit = $repoRoot . DIRECTORY_SEPARATOR . 'res' . DIRECTORY_SEPARATOR . 'Platform' . DIRECTORY_SEPARATOR . 'PHP' . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phpunit';
        if (!is_file($phpUnit) && !is_file($phpUnit . '.bat')) {
            $ok = false;
            Output::printLine('missing: PHPUnit binary under res/Platform/PHP/vendor/bin/');
        } else {
            Output::printLine('ok: PHPUnit binary present');
        }

        $phpStanPhar = $repoRoot . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'phar' . DIRECTORY_SEPARATOR . 'phpstan.phar';
        if (!is_file($phpStanPhar)) {
            $ok = false;
            Output::printLine("missing: {$phpStanPhar}");
        } else {
            Output::printLine('ok: PHPStan phar present');
        }

        return $ok;
    }

    private function taskRoutes(string $repoRoot, string $php): bool
    {
        return $this->runProcess([$php, './php_console', 'route:list'], $repoRoot) === 0;
    }

    private function taskMigrate(string $repoRoot, string $php, bool $quiet): bool
    {
        if (!$quiet) {
            Output::printLine('== Migrations ==');
        }

        return $this->runProcess([$php, './php_console', 'database:migrate'], $repoRoot) === 0;
    }

    private function taskCheck(string $repoRoot, string $php, bool $quiet): bool
    {
        $exit1 = $this->taskPhpUnit($repoRoot, $php, $quiet) ? 0 : 1;
        $exit2 = $this->taskPhpStan($repoRoot, $php, $quiet) ? 0 : 1;
        return $exit1 === 0 && $exit2 === 0;
    }

    private function taskPhpUnit(string $repoRoot, string $php, bool $quiet): bool
    {
        if (!$quiet) {
            Output::printLine('== PHPUnit ==');
        }
        return $this->runProcess(
            [
                $php,
                './res/Platform/PHP/vendor/bin/phpunit',
                '-c',
                './res/Platform/PHP/phpunit.xml.dist',
            ],
            $repoRoot
        ) === 0;
    }

    private function taskPhpStan(string $repoRoot, string $php, bool $quiet): bool
    {
        if (!$quiet) {
            Output::printLine('== PHPStan ==');
        }
        return $this->runProcess(
            [
                $php,
                './bin/phar/phpstan.phar',
                'analyse',
                './res/Platform/PHP/src',
                '--memory-limit',
                '500M',
                '-c',
                './res/Platform/PHP/phpstan.neon.dist',
            ],
            $repoRoot
        ) === 0;
    }

    /**
     * @param list<string> $cmd
     */
    private function runProcess(array $cmd, string $cwd): int
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, $cwd);
        if (!is_resource($process)) {
            Output::printLine('harness: failed to start process');
            return 1;
        }

        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        while (true) {
            $status = proc_get_status($process);

            $out = stream_get_contents($pipes[1]);
            if ($out !== false && $out !== '') {
                Output::print($out);
            }

            $err = stream_get_contents($pipes[2]);
            if ($err !== false && $err !== '') {
                Output::print($err);
            }

            if (!$status['running']) {
                break;
            }
            usleep(20_000);
        }

        fclose($pipes[1]);
        fclose($pipes[2]);

        return proc_close($process);
    }

    private function getRepoRoot(): ?string
    {
        $root = realpath(__DIR__ . '/../../../../../..');
        if ($root === false) {
            return null;
        }
        return $root;
    }
}
