<?php

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

use Clover\Framework\Component\DotenvLoader;
use Clover\Framework\Component\Runtime;
use Clover\Framework\Enumeration\EntryPoint;
use Clover\Component\Foundation\Application;
use Clover\Component\Kernel\HttpKernel;
use Clover\Component\Http\Request;
use Clover\Contract\KernelInterface;

define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FILE_PATH', __DIR__ . "/App/File");
define('VENDOR_PATH', BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR);

if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $documentRoot = realpath(__DIR__);

    if (is_string($requestPath) && $requestPath !== '' && $documentRoot !== false) {
        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, ltrim($requestPath, '/'));
        $staticPath = realpath($documentRoot . DIRECTORY_SEPARATOR . $relativePath);
        $documentRootPrefix = $documentRoot . DIRECTORY_SEPARATOR;

        if ($staticPath !== false && is_file($staticPath) && str_starts_with($staticPath, $documentRootPrefix)) {
            return false;
        }
    }
}

$autoload = BASE_PATH . "/./../vendor/autoload.php";

if (!file_exists($autoload)) {
    exit("Please run 'composer install' under application root directory");
}

include $autoload;

/**
 * The environment has to be readable before a kernel can be chosen, so it is loaded here rather
 * than inside `Runtime`. `DotenvLoader` is idempotent: `Runtime::run()` calls it again and gets
 * the same result, and it is `Runtime` that reports a missing `.env`, once its error handler is
 * installed.
 */
DotenvLoader::load(BASE_PATH);

/**
 * Which kernel stack serves this request, from `APP_ENTRY_POINT`. Unset means
 * `EntryPoint::RUNTIME`, the stack the bundled application is written against.
 */
$entryPoint = EntryPoint::fromEnvironment();

if ($entryPoint === EntryPoint::RUNTIME) {
    $runtimeOptions = [];
    if (isset($_ENV['APP_SERVER']) && is_string($_ENV['APP_SERVER']) && $_ENV['APP_SERVER'] !== '') {
        $runtimeOptions['server'] = $_ENV['APP_SERVER'];
    }
    $runtime = new Runtime($runtimeOptions);
    $runtime->run();
} else {
    /**
     * `configure()` reads `App/Configure/providers.php` and runs `App/Configure/kernel.php`, which
     * is where the application binds its own kernel. Resolving `KernelInterface` out of the
     * container rather than constructing `HttpKernel` here is what makes that binding count - a
     * `new HttpKernel(...)` would ignore it.
     */
    $application = Application::configure(BASE_PATH);
    $kernel = $application->make(KernelInterface::class);

    if (!$kernel instanceof HttpKernel) {
        exit(sprintf(
            'The bound %s must be an instance of %s.',
            KernelInterface::class,
            HttpKernel::class
        ));
    }

    $response = $kernel->handle(Request::createFromGlobals());
    $response->send();

    // Post-response work runs after the client has its bytes.
    $kernel->terminate();
}
