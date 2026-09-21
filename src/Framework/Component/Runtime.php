<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\{ArrayObject, BaseClass};
use Clover\Classes\Event\Dispatcher;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Classes\Event\EventManager;
use Clover\Classes\Debug\DebugSubscriberProvider;
use Clover\Classes\Debug\ErrorHandler;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\HTTP\Request as Request;
use Clover\Classes\Math\MatrixOps;
use Clover\Classes\OperationSystem as OS;
use Clover\Enumeration\TimeZone;
use Clover\Framework\Event\KernelLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\ProfilerTimelineResetRequested;
use Clover\Framework\Enumeration\Environment;
use RuntimeException;
use function uniqid;
use function defined;
use function in_array;
use function is_string;
use function sprintf;

/**
 * Runtime Component
 */
class Runtime extends BaseClass
{
    protected array $options;

    protected array $environment;

    /**
     * Constructor
     *
     * @param array{
     *  server: string<'swoole', 'frankenphp', 'fpm'>, 
     *  allowed_short_open_tag: int,
     *  allowed_upload_file: int,
     *  built_operation_system: int,
     *  display_errors: int,
     *  display_startup_errors: int,
     *  error_reporting_level: int,
     *  home_path: int,
     *  hypertext_preprocessor: int,
     *  maximum_integer_size: int,
     *  max_post_size: int,
     *  max_upload_file_size: int,
     *  session_use_cookies: int,
     *  software: int,
     *  timezone_id: int,
     *  version: int,
     *  swoole_host: string,
     *  swoole_port: int
     * } $options
     *
     * @return void
     */
    public function __construct(array $options = [])
    {
        $this->options = $options ?? [];
        $this->environment = [];
        $dispatcher = new Dispatcher();
        EventManager::setInstance($dispatcher);

        MatrixOps::init();
    }

    /**
     * Execute runtime
     *
     * @return void
     */
    public function run(): void
    {
        $this->setErrorHandler();

        $this->getDovEnvironment();

		$profilerEnabled = $this->isProfilerEnabled();
		DebugSubscriberProvider::register($this->isDebuggable());
		if (!OS::isCommandLineInterface() && $profilerEnabled) {
			EventManager::getEventBus()->publish(new ProfilerTimelineResetRequested());
		}

        $kernelLifecycle = EventDispatcherAdapter::fromEventManager();
        $kernelLifecycle->dispatch(new KernelLifecycleEvent(
            KernelLifecycleEvent::RUNTIME_BOOT,
            [
                'sapi' => \PHP_SAPI,
                'is_cli' => OS::isCommandLineInterface(),
            ]
        ));

		$configureToken = '';
        $kernelLifecycle->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::RUNTIME_CONFIGURE_STARTED));
		if ($profilerEnabled) {
			$configureToken = uniqid('pf_', true);
			EventManager::getEventBus()->publish(new KernelSpanStarted(
				$configureToken,
				'Runtime::configure',
				'Runtime::run'
			));
		}
        try {
            $this->setDefaultOptions();
            $this->setEnvironmentVariables();
            $this->overrideOptionsFromEnv();
            $this->applyOptions();
        } finally {
			if ($profilerEnabled) {
				EventManager::getEventBus()->publish(new KernelSpanFinished($configureToken));
			}
            $kernelLifecycle->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::RUNTIME_CONFIGURE_FINISHED));
        }

		$mappingToken = '';
        $kernelLifecycle->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::RUNTIME_MAPPING_STARTED));
		if ($profilerEnabled) {
			$mappingToken = uniqid('pf_', true);
			EventManager::getEventBus()->publish(new KernelSpanStarted(
				$mappingToken,
				'Runtime::setMapping',
				'Runtime::run'
			));
		}
        try {
            $this->setMapping();
        } finally {
			if ($profilerEnabled) {
				EventManager::getEventBus()->publish(new KernelSpanFinished($mappingToken));
			}
            $kernelLifecycle->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::RUNTIME_MAPPING_FINISHED));
        }
    }

    private function isProfilerEnabled(): bool
    {
		return Profiler::isEnabled();
    }

    private function isDebuggable(): bool
    {
        return ($_ENV['IS_DEBUGGABLE'] ?? 'false') === 'true';
    }

    /**
     * Set error handler
     *
     * @return void
     */
    public function setErrorHandler(): void
    {
        ErrorHandler::register();
    }

    /**
     * Flush response data
     *
     * @return void
     */
    private function flushResponseData(): void
    {
        $software = $this->environment[Environment::SERVER][Environment::SOFTWARE];

        switch (strtolower($software)) {
            case 'nginx':
                Request::flushFastCgiResponseData();
                break;
            case 'lightspeed':
                Request::flushLightSpeedResponseData();
                break;
            default:
                if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
                    Response::flushOutput();
                } else {
                    flush();
                }
        }
    }

    /**
     * Set mapping
     *
     * @return void
     */
	private function setMapping(): void
	{
		$mapper = new Mapper($this->options, $this->environment);
        /**
         * @var Mapper $mapper
         */
        $mapper = parent::setBaseProxy($mapper);
		$runner = $mapper->matchRunner();

		try {
			$response = $runner->run();

			if ($response instanceof Response) {
				$response->send();
			}

			$this->flushResponseData();
		} finally {
			$mapper->terminate();
		}
	}

    /**
     * Set option with default value
     *
     * @param string $key
     * @param null|int|string|bool $default
     *
     * @return void
     */
    private function setOption(string $key, null|int|string|bool $default = null): void
    {
        $this->options[$key] ??= $default;
    }

    /**
     * Set environment variable
     *
     * @param array|string $key
     * @param string|array|bool|int $value
     *
     * @return void
     */
    private function setEnvironment(array|string $key, string|array|bool|int $value): void
    {
        $array = ArrayObject::isArray($key) ? $key : [$key];
        ArrayObject::setDeepCopy($this->environment, $array, $value);
    }

    /**
     * Apply runtime options
     *
     * @return void
     */
    protected function applyOptions(): void
    {
        OS::setDisplayErrors($this->options[Environment::DISPLAY_ERRORS]);
        OS::setErrorReportingLevel($this->options[Environment::ERROR_REPORTING_LEVEL]);
        OS::setDefaultDateTimeZone($this->options[Environment::TIMEZONE_ID]);
        OS::setDisplayStatupErrors($this->options[Environment::DISPLAY_STARTUP_ERRORS]);
    }

    /**
     * Set default options
     *
     * @return void
     */
    protected function setDefaultOptions(): void
    {
        $this->setOption(Environment::ERROR_REPORTING_LEVEL, E_ALL & ~E_NOTICE);
        $this->setOption(Environment::DISPLAY_ERRORS, 0);
        $this->setOption(Environment::TIMEZONE_ID, TimeZone::UTC);
        $this->setOption(Environment::DISPLAY_STARTUP_ERRORS, false);
    }

    /**
     * Set environment variables
     *
     * @return void
     */
    protected function setEnvironmentVariables(): void
    {
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR], []);
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::VERSION], OS::getPHPVersion());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::MAXIMUM_POST_SIZE], OS::getMaxPostSize());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::MAXIMUM_UPLOAD_FILE_SIZE], OS::getMaxUploadFileSize());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::ALLOWED_SHORT_OPEN_TAG], OS::isShortOpenTagAllowed());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::ALLOWED_UPLOAD_FILE], OS::isFileUploadAllowed());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::SESSION_USE_COOKIES], OS::isSessionUseCookies());
        $this->setEnvironment([Environment::HYPERTEXT_PREPROCESSOR, Environment::MAXIMUM_INTEGER_SIZE], OS::getMaximumIntergerSize());

        $this->setEnvironment([Environment::SERVER], []);
        $this->setEnvironment([Environment::SERVER, Environment::BUILT_OPERATION_SYSTEM], OS::getBuiltOperationSystemString());
        $this->setEnvironment([Environment::SERVER, Environment::SOFTWARE], OS::getMainServerSoftware());
        $this->setEnvironment([Environment::SERVER, Environment::HOME_PATH], OS::getHomePath());
    }

    /**
     * Load .env file
     *
     * @return void
     */
    private function getDovEnvironment(): void
    {
        if (defined('BASE_PATH')) {
            // `root/index.php` already loads these so it can pick an entry point; the loader is
            // idempotent, so this call reuses that result rather than re-reading the files.
            if (!DotenvLoader::load(BASE_PATH)) {
                throw new RuntimeException(sprintf('Dotenv is failed to load environment file in `%s` directory', BASE_PATH));
            }
        }
    }

    /**
     * Override options from environment variables
     *
     * @return void
     */
    private function overrideOptionsFromEnv(): void
    {
        $appDebug = filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL) === true;
        if ($appDebug) {
            ini_set('error_log', __DIR__ . '/error.log');
            $this->options[Environment::DISPLAY_ERRORS] = 1;
            $this->options[Environment::DISPLAY_STARTUP_ERRORS] = true;
            $this->options[Environment::ERROR_REPORTING_LEVEL] = E_ALL;
        }

        $timezone = getenv('APP_TIMEZONE');
        if ($timezone !== false && is_string($timezone) && $timezone !== '') {
            $this->options[Environment::TIMEZONE_ID] = $timezone;
        }

        $errorLevel = getenv('APP_ERROR_REPORTING');
        if ($errorLevel !== false && $errorLevel !== '') {
            $parsed = is_numeric($errorLevel) ? (int) $errorLevel : null;
            if ($parsed !== null) {
                $this->options[Environment::ERROR_REPORTING_LEVEL] = $parsed;
            }
        }
    }

    /**
     * Serialize runtime
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize([$this->environment]);
    }
}
