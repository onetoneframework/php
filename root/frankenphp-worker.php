<?php

declare(strict_types=1);

use Clover\Framework\Component\Runtime;

define('BASE_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FILE_PATH', __DIR__ . "/App/File");
define('VENDOR_PATH', BASE_PATH . 'vendor' . DIRECTORY_SEPARATOR);

$autoload = BASE_PATH . "/./../vendor/autoload.php";
if (!file_exists($autoload)) {
	exit("Please run 'composer install' under application root directory");
}

include $autoload;

$handler = static function (): void {
	$runtime = new Runtime(['server' => 'frankenphp']);
	$runtime->run();
};

if (function_exists('frankenphp_handle_request')) {
	while (frankenphp_handle_request($handler)) {
	}
} else {
	$handler();
}
