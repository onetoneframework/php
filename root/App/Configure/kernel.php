<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 *
 * Kernel configuration for the `application` entry point (`APP_ENTRY_POINT=application`).
 *
 * `Application::configure()` runs this before any kernel is resolved. It is where the application
 * takes over the slots the framework only supplies defaults for: the HTTP kernel here, and the
 * exception handler if you want your own.
 *
 * The default `runtime` entry point never reads this file.
 */

use App\Http\Kernel;
use Clover\Component\Foundation\Application;
use Clover\Contract\KernelInterface;

return static function (Application $application): void {
	$application->singleton(KernelInterface::class, new Kernel($application));

	// To use your own exception handler, bind it here - HandleExceptions leaves an existing
	// binding alone and only installs the framework default when this slot is empty:
	//
	// $application->singleton(ExceptionHandlerInterface::class, new App\Exceptions\Handler($application));
};
