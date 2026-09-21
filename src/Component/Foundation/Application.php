<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Component\Foundation;

use Clover\Component\Container\Container;
use Clover\Component\Kernel\HttpKernel;
use Clover\Contract\ContainerInterface as ContainerContract;
use Clover\Contract\KernelInterface;
use Clover\Support\ServiceProvider;
use function array_key_exists;
use function array_values;
use function get_class;
use function in_array;
use function is_array;
use function is_callable;
use function is_file;
use function is_null;
use function is_string;
use function rtrim;
use const DIRECTORY_SEPARATOR;

/**
 * Application Class
 *
 * Core application container the manages bindings, paths, and core services.
 */
class Application extends Container
{
    /**
     * Directory under the base path holding the application's own code.
     */
    protected const APPLICATION_DIRECTORY = 'App';

    /**
     * Directory under the application path holding its configuration files.
     */
    protected const CONFIGURE_DIRECTORY = 'Configure';

    /**
     * Directory under the application path that the framework may write to.
     */
    protected const STORAGE_DIRECTORY = 'Cache';

    /**
     * File that binds the application's own kernel, exception handler and anything else that has
     * to exist before start-up runs. The analogue of Laravel's `bootstrap/app.php`.
     */
    protected const KERNEL_CONFIGURATION_FILE = 'kernel.php';

    /**
     * File returning the list of service providers to register.
     */
    protected const PROVIDER_CONFIGURATION_FILE = 'providers.php';

    /**
     * @var string The base path for the application installation.
     */
    protected string $basePath = '';

    /**
     * @var string|null Overridden location of the environment files, when one was set.
     */
    protected ?string $environmentPath = null;

    /**
     * @var array<int, string> Service provider class names to register at start-up.
     */
    protected array $declaredProviders = [];

    /**
     * @var ServiceProvider[] Array of loaded service providers.
     */
    protected array $loadedProviders = [];

    /**
     * @var bool Indicates whether the application providers have booted.
     */
    protected bool $booted = false;

    /**
     * Application constructor.
     *
     * @param string|null $basePath Optional base path for the application.
     */
    public function __construct(?string $basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerCoreContainerAliases();
    }

    /**
     * Build an application and apply the bundled configuration files.
     *
     * This is the entry point's constructor. It performs the two steps that have to happen before
     * a kernel can be resolved: read `App/Configure/providers.php` for the provider list, and run
     * `App/Configure/kernel.php`, which is where an application binds its own kernel over the
     * framework default.
     *
     * Both files are optional. An application that adds neither gets the framework's kernel and no
     * providers, which is a working - if empty - application rather than an error.
     *
     * @param string      $basePath      Directory holding `App/` and the environment files.
     * @param string|null $configurePath Override for the configuration directory.
     *
     * @return static
     */
    public static function configure(string $basePath, ?string $configurePath = null): static
    {
        $application = new static($basePath);
        $configurePath ??= $application->getConfigurePath();

        $providerFile = $configurePath . DIRECTORY_SEPARATOR . static::PROVIDER_CONFIGURATION_FILE;

        if (is_file($providerFile)) {
            $providers = require $providerFile;

            if (is_array($providers)) {
                $application->setDeclaredProviders($providers);
            }
        }

        $kernelFile = $configurePath . DIRECTORY_SEPARATOR . static::KERNEL_CONFIGURATION_FILE;

        if (is_file($kernelFile)) {
            $configureKernel = require $kernelFile;

            if (is_callable($configureKernel)) {
                $configureKernel($application);
            }
        }

        return $application;
    }

    /**
     * Set the base path for the application.
     *
     * @param string $basePath The base path directory.
     * @return void
     */
    public function setBasePath(string $basePath): void
    {
        $this->basePath = rtrim($basePath, '\/');
        $this->bindPathsInContainer();
    }

    /**
     * Bind basic application paths within the container.
     *
     * @return void
     */
    protected function bindPathsInContainer(): void
    {
        $this->singleton('path', $this->basePath);
        $this->singleton('path.base', $this->basePath);
        $this->singleton('path.config', $this->basePath . DIRECTORY_SEPARATOR . 'config');
        $this->singleton('path.app', $this->getApplicationPath());
        $this->singleton('path.configure', $this->getConfigurePath());
        $this->singleton('path.storage', $this->getStoragePath());
    }

    /**
     * Directory holding the application's own code.
     *
     * @return string
     */
    public function getApplicationPath(): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . static::APPLICATION_DIRECTORY;
    }

    /**
     * Directory holding the application's configuration files.
     *
     * @return string
     */
    public function getConfigurePath(): string
    {
        return $this->getApplicationPath() . DIRECTORY_SEPARATOR . static::CONFIGURE_DIRECTORY;
    }

    /**
     * Directory the framework may write to - logs, caches, compiled output.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        return $this->getApplicationPath() . DIRECTORY_SEPARATOR . static::STORAGE_DIRECTORY;
    }

    /**
     * Directory holding `.env` and `.env.local`.
     *
     * Defaults to the base path, which is where they live in this layout.
     *
     * @return string
     */
    public function getEnvironmentPath(): string
    {
        return $this->environmentPath ?? $this->basePath;
    }

    /**
     * Look for the environment files somewhere other than the base path.
     *
     * @param string $environmentPath Directory holding the environment files.
     *
     * @return void
     */
    public function useEnvironmentPath(string $environmentPath): void
    {
        $this->environmentPath = rtrim($environmentPath, '\/');
    }

    /**
     * The service providers this application registers at start-up.
     *
     * @return array<int, string>
     */
    public function getDeclaredProviders(): array
    {
        return $this->declaredProviders;
    }

    /**
     * Replace the list of service providers to register at start-up.
     *
     * @param array<int, string> $providers Provider class names.
     *
     * @return void
     */
    public function setDeclaredProviders(array $providers): void
    {
        $this->declaredProviders = array_values($providers);
    }

    /**
     * Add a service provider to the start-up list.
     *
     * @param string $provider Provider class name.
     *
     * @return void
     */
    public function addDeclaredProvider(string $provider): void
    {
        if (in_array($provider, $this->declaredProviders, true)) {
            return;
        }

        $this->declaredProviders[] = $provider;
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);
        $this->singleton('app', $this);
        $this->singleton(self::class, $this);
        $this->singleton(Container::class, $this);
        $this->singleton(ContainerContract::class, $this);
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    protected function registerCoreContainerAliases(): void
    {
        // Registered as a lazy default rather than an alias. An alias is resolved before instances
        // and definitions are consulted, so aliasing the interface here would pin it to the
        // framework kernel and leave an application no way to bind its own - which is the whole
        // point of `App/Configure/kernel.php`. A singleton under the same identifier is replaced
        // by whatever the application binds later.
        $this->singleton(KernelInterface::class, static function (ContainerContract $container): HttpKernel {
            return new HttpKernel($container);
        });
    }

    /**
     * Register a service provider with the application.
     *
     * @param string|ServiceProvider $provider The provider to register.
     * @param bool $force Whether to force registration even if loaded.
     * @return ServiceProvider The registered provider instance.
     */
    public function register(string|ServiceProvider $provider, bool $force = false): ServiceProvider
    {
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        if (array_key_exists($name = get_class($provider), $this->loadedProviders) && !$force) {
            return $this->loadedProviders[$name];
        }

        $this->loadedProviders[$name] = $provider;

        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        if ($this->isBooted()) {
            $this->bootProvider($provider);
        }

        return $provider;
    }

    /**
     * Boot all registered service providers once.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        foreach ($this->loadedProviders as $provider) {
            $this->bootProvider($provider);
        }

        $this->booted = true;
    }

    /**
     * Determine whether the application has finished booting.
     *
     * @return bool True when all currently registered providers have booted.
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Get the base path for this application instance.
     *
     * @return string The normalized base path.
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * @var Container|null Static instance of the container.
     */
    protected static $instance;

    /**
     * Set the static instance of the application container.
     *
     * @param Container|null $container The container instance.
     * @return Container|null The assigned instance.
     */
    public static function setInstance(?Container $container = null): Container|null
    {
        return static::$instance = $container;
    }

    /**
     * Get the globally available instance of the container.
     *
     * @return static
     */
    public static function getInstance(): Container|null
    {
        if (is_null(static::$instance)) {
            static::$instance = new static;
        }

        return static::$instance;
    }

    /**
     * Boot a single provider instance.
     *
     * @param ServiceProvider $provider The provider to boot.
     *
     * @return void
     */
    protected function bootProvider(ServiceProvider $provider): void
    {
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }
}
