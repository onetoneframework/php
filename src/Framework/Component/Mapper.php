<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Component;

use Clover\Classes\BaseClass;
use Clover\Classes\Broadcasting\BroadcastManager;
use Clover\Classes\Broadcasting\BroadcastManagerFacade;
use Clover\Classes\Broadcasting\Broadcasters\EventBusBroadcaster;
use Clover\Classes\Broadcasting\Broadcasters\NullBroadcaster;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Event\Dispatcher as EventDispatcher;
use Clover\Classes\Event\EventBus;
use Clover\Classes\Event\EventManager;
use Clover\Classes\Event\Middleware\CorrelationMiddleware;
use Clover\Classes\File\{Functions as FileFunctions, Handler as FileHandler};
use Clover\Classes\HTTP\Request as HTTPRequest;
use Clover\Classes\OperationSystem as OperationSystem;
use Clover\Classes\Proxy\BaseProxy;
use Clover\Classes\XML\SimpleXML;
use Clover\Framework\Context\ApplicationContext;
use Clover\Framework\Event\{OnBoot, OnTerminate};
use Clover\Framework\Livewire\LivewireManager;
use Clover\Framework\Module\ModuleLoader;
use Clover\Implement\BroadcastManagerInterface;
use Clover\Implement\EventBusInterface;
use Clover\Implement\EventDispatcherInterface;
use RuntimeException;
use function array_key_exists;
use function count;
use function defined;
use function filter_var;
use function getenv;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;
use function trim;

/**
 * HTTP Request Mapper
 * 
 * The Mapper class is responsible for mapping incoming HTTP requests to the appropriate handlers within the application. It collects various details about the request, such as the HTTP method, accepted encodings, remote IP address, user agent, and more. The Mapper also sets up the dependency injection container, finds and registers interceptors and event subscribers, and ultimately determines whether to route the request to the CLI kernel or the HTTP kernel based on the execution environment. This class serves as a crucial component in ensuring that requests are processed correctly and efficiently within the application.
 */
class Mapper extends BaseClass
{
	private const ASSET_VERSION_ENVIRONMENT_VARIABLE = 'ASSET_VERSION';
	private const INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE = 'APP_INTERCEPTOR_DISCOVERY';
	private const SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE = 'APP_SUBSCRIBER_DISCOVERY';

    /** @var ?string $method HTTP method of the request (e.g., GET, POST, PUT, DELETE) */
    private ?string $method;
    /** @var $acceptEncoding Accepted encodings from the request headers (e.g., gzip, deflate) */
    private ?string $acceptEncoding;
    /** @var $remoteIPAddress Remote IP address of the client making the request */
    private string $remoteIPAddress;
    /** @var $httpConnection Type of HTTP connection (e.g., keep-alive, close) */
    private string $httpConnection;
    /** @var $acceptLanguage Accepted languages from the request headers (e.g., en-US, fr-FR) */
    private ?string $acceptLanguage;
    /** @var $hasReferer Indicates whether the request has a referer header */
    private bool $hasReferer;
    /** @var $isMobile Indicates whether the request is from a mobile device */
    private bool $isMobile;
    /** @var $isCrawler Indicates whether the request is from a web crawler or bot */
    private bool $isCrawler;
    /** @var $requestUri The URI of the incoming request */
    private string $requestUri;
    /** @var $scheme The scheme of the request (e.g., http, https) */
    private ?string $scheme;
    /** @var $userAgent The user agent string from the request headers */
    private string $userAgent;
    /** @var $isCommnadLineInterface Indicates whether the application is running in a command line interface (CLI) environment */
    private bool $isCommnadLineInterface;
    /** @var $isXMLHttpRequest Indicates whether the request is an XMLHttpRequest (AJAX) */
    private bool $isXMLHttpRequest;
    /** @var $queryString The query string from the request URI */
    private string $queryString;
    /** @var $requestUrl The full URL of the incoming request */
    private string $requestUrl;
    /** @var $urlPath The path component of the request URL */
    private string $urlPath;
    /** @var Container $container The dependency injection container for managing application services and dependencies */
    private Container $container;
    /** @var array $options Application options passed to the Mapper */
    private array $options;
    /** @var array $environment Environment details collected from the HTTP request and server variables */
    private array $environment;
    /** @var string $localeCode The locale code derived from the request's accepted languages, used for localization and internationalization purposes */
    private ?string $localeCode;
    /** @var BaseProxy|EventDispatcher $eventDispatcher The event dispatcher responsible for managing and dispatching events within the application, allowing for decoupled communication between components and facilitating the implementation of event-driven architecture */
    private BaseProxy|EventDispatcher $eventDispatcher;
    private EventBus $eventBus;

    /**
     * Constructor
     *
     * @param array $options Application options passed to the Mapper
     * @param array $environment Environment details collected from the HTTP request and server variables
     */
    public function __construct(array $options, array $environment)
    {
        $this->options = $options ?? [];
        $this->environment = $environment ?? [];

        $this->setMethod(HTTPRequest::getMethod());
        $this->setAcceptEncoding(HTTPRequest::getAcceptEncoding());
        $this->remoteIPAddress = HTTPRequest::getRemoteIPAddress()->__toString();
        $this->httpConnection = HTTPRequest::getHTTPConnection();
		$currentLanguage = HTTPRequest::getCurrentLanguage();
		$this->acceptLanguage = $currentLanguage;
		$this->localeCode = $currentLanguage;
        $this->hasReferer = HTTPRequest::hasReferer();
        $this->isMobile = HTTPRequest::isMobile();
        $this->isCrawler = HTTPRequest::isCrawler();
        $this->requestUri = HTTPRequest::getRequestUri()->__toString();
        $this->scheme = HTTPRequest::getScheme();
        $this->userAgent = HTTPRequest::getUserAgent();
        $this->isXMLHttpRequest = HTTPRequest::isAjax();
        $this->queryString = HTTPRequest::getQueryString()->__toString();
        $this->requestUrl = HTTPRequest::getRequestURL()->__toString();
        $this->urlPath = HTTPRequest::getUrlPath();
        $this->isCommnadLineInterface = OperationSystem::isCommandLineInterface();

        ApplicationContext::setEnvironment($this->getEnvironment());

        $eventDispatcher = EventManager::getInstance();
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = parent::setBaseProxy($eventDispatcher);
        $this->eventDispatcher = $eventDispatcher;
        $this->eventBus = EventManager::getEventBus();

        $this->findSubscriber();
    }

    /**
     * Set Accept-Encoding
     *
     * @param ?string $acceptEncoding Accepted encodings from the request headers (e.g., gzip, deflate)
     *
     * @return void
     */
    public function setAcceptEncoding(?string $acceptEncoding): void
    {
        $this->acceptEncoding = $acceptEncoding;
    }

    /**
     * Set Method
     *
     * @param mixed $method HTTP method of the request (e.g., GET, POST, PUT, DELETE)
     *
     * @return void
     */
    public function setMethod(mixed $method): void
    {
        $this->method = $method;
    }

    /**
     * Get application environment details
     *
     * @return array{
     *  method: ?string, 
     *  acceptEncoding: ?string, 
     *  remoteIPAddress: string, 
     *  httpConnection: string, 
     *  acceptLanguage: ?string, 
     *  hasReferer: bool, 
     *  isMobile: bool, 
     *  isCrawler: bool, 
     *  requestUri: string, 
     *  scheme: ?string, 
     *  userAgent: string, 
     *  isXMLHttpRequest: bool, 
     *  queryString: string, 
     *  requestUrl: string, 
     *  urlPath: string
     * }
     */
    private function getEnvironment(): array
    {
        return [
            'method' => $this->method,
            'acceptEncoding' => $this->acceptEncoding,
            'remoteIPAddress' => $this->remoteIPAddress,
            'httpConnection' => $this->httpConnection,
            'acceptLanguage' => $this->acceptLanguage,
            'hasReferer' => $this->hasReferer,
            'isMobile' => $this->isMobile,
            'isCrawler' => $this->isCrawler,
            'requestUri' => $this->requestUri,
            'scheme' => $this->scheme,
            'userAgent' => $this->userAgent,
            'isXMLHttpRequest' => $this->isXMLHttpRequest,
            'queryString' => $this->queryString,
            'requestUrl' => $this->requestUrl,
            'urlPath' => $this->urlPath,
        ];
    }

    /**
     * Check if running in command line interface
     *
     * @return bool
     */
    public function isCommandLineInterface(): bool
    {
        return $this->isCommnadLineInterface;
    }

    /**
     * Find and register interceptors
     *
     * @return void
     */
    private function findInterceptor(): void
    {
		if (!$this->isDiscoveryEnabled(self::INTERCEPTOR_DISCOVERY_ENVIRONMENT_VARIABLE)) {
			ApplicationContext::clearInterceptors();
			return;
		}

        $interceptors = [];
        $list = DirectoryHandler::getList(BASE_PATH . "/App/Configure/Interceptor", 'file', true, true, ['php']);

        foreach ($list as $file) {
            $classNames = FileFunctions::getClassNames($file);
            if (!isset($classNames) || count($classNames) === 0) {
                continue;
            }

            $className = $classNames[array_key_first($classNames)];
            if (!class_exists($className)) {
                continue;
            }

            $interceptor = new $className();
            $interceptors[] = $interceptor;
        }

        ApplicationContext::setInterceptors($interceptors);
    }

    /**
     * Find and register event subscribers
     *
     * @return void
     */
    private function findSubscriber(): void
    {
		if (!$this->isDiscoveryEnabled(self::SUBSCRIBER_DISCOVERY_ENVIRONMENT_VARIABLE)) {
			return;
		}

        $dir = BASE_PATH . "/App/Configure/EventDispatcher";
        if (DirectoryHandler::isEmpty($dir)) {
            return;
        }

        $list = DirectoryHandler::getList($dir, 'file', true, true, ['php']);

        foreach ($list as $file) {
            $classNames = FileFunctions::getClassNames($file);
            if (!isset($classNames) || count($classNames) === 0) {
                continue;
            }

            $className = $classNames[array_key_first($classNames)];
            if (!class_exists($className)) {
                continue;
            }

            $subscriber = new $className();
            if ($subscriber instanceof \Clover\Implement\SubscriberInterface) {
                $this->eventDispatcher->addSubscriber($subscriber);
            }
        }
    }

    /**
     * Set up the dependency injection container
     *
     * @return void
     */
    private function setContainer(): void
    {
        $this->container = new Container();
        $dispatcher = EventManager::getInstance();
        $eventBus = new EventBus($dispatcher);
        $eventBus->addMiddleware(new CorrelationMiddleware());
        $this->eventBus = $eventBus;
        $this->container->set(EventDispatcher::class, $dispatcher);
        $this->container->set(EventBus::class, $eventBus);
        $this->container->bind(EventDispatcherInterface::class, EventDispatcher::class);
        $this->container->bind(EventBusInterface::class, EventBus::class);
        EventManager::setContainer($this->container);

        // Broadcasting — a safe default manager with two built-in drivers:
        //  * "null"       : swallows broadcasts (default — never fails)
        //  * "event-bus"  : republishes on the in-process bus so PHP
        //                   listeners can react without an external transport.
        // Applications opt in to transport drivers (SSE, WebSocket, ...)
        // from App/Configure/dependencies.php by calling
        // $container->get(BroadcastManager::class)->registerDriver(...).
        $broadcastManager = new BroadcastManager();
        $broadcastManager->registerDriver(new NullBroadcaster());
        $broadcastManager->registerDriver(new EventBusBroadcaster($eventBus));
        $this->container->set(BroadcastManager::class, $broadcastManager);
        $this->container->bind(BroadcastManagerInterface::class, BroadcastManager::class);
        BroadcastManagerFacade::setInstance($broadcastManager);
        BroadcastManagerFacade::setContainer($this->container);

		// The manager is shared after its first resolution within the request.
		$this->container->set(LivewireManager::class, static function (Container $container): LivewireManager {
			$livewireManager = new LivewireManager($container);
			if (defined('BASE_PATH')) {
				$livewireManager->autoloadFrom(
					BASE_PATH . DIRECTORY_SEPARATOR . 'App' . DIRECTORY_SEPARATOR . 'Livewire'
				);
			}

			return $livewireManager;
		});

        $parser = new SimpleXML();
        /**
         * @var SimpleXML $parser
         */
        $parser = parent::setBaseProxy($parser);
        $parser->fromFile(sprintf("%s/src/Framework/Configure/default_services.xml", dirname(BASE_PATH)));

        $services = [];
        if ($parser->hasChildren() && $parser->getChildren()->hasChildren() && $parser->getChildren()->getChildren()->hasData()) {
            $services = $parser->getChildren()->getChildren()->getData();
        }

        /** @var \SimpleXMLElement[] $services */
        foreach ($services as $service) {
            $attributes = $service->attributes();
            if (!$attributes) {
                continue;
            }

            $class = $attributes->class->__toString();
            if (!class_exists($class)) {
                continue;
            }

			$this->container->set($class, $class);
		}

		$configuredAssetVersion = array_key_exists(self::ASSET_VERSION_ENVIRONMENT_VARIABLE, $_ENV)
			? $_ENV[self::ASSET_VERSION_ENVIRONMENT_VARIABLE]
			: getenv(self::ASSET_VERSION_ENVIRONMENT_VARIABLE);
		$assetVersion = is_string($configuredAssetVersion) ? $configuredAssetVersion : '';
		if ($this->container->has(Resource::class)) {
			$argumentsRegistered = $this->container->setPassArguments(
				Resource::class,
				['assetVersion' => $assetVersion]
			);
			if (!$argumentsRegistered) {
				throw new RuntimeException('Resource constructor arguments could not be registered');
			}
		}

        $appDependencyConfigureFilePath = sprintf("%s/App/Configure/dependencies.php", BASE_PATH);
        if (FileHandler::isExists($appDependencyConfigureFilePath)) {
            $appDependencies = require $appDependencyConfigureFilePath;

            if (is_callable($appDependencies)) {
                $appDependencies($this->container);
            } elseif (is_array($appDependencies)) {
                foreach ($appDependencies as $key => $dependency) {
                    if ($this->container->has($key)) {
                        throw new RuntimeException(sprintf('Dependency "%s" is already registered in the container', $key));
                    }

                    $this->container->set($key, $dependency);
                }
            } else {
                throw new RuntimeException('App dependency configuration must return array or callable');
            }
        }

		$modulesDirectory = sprintf('%sApp%sModules', BASE_PATH, DIRECTORY_SEPARATOR);
		$moduleConfigurationPath = sprintf(
			'%sApp%sConfigure%smodules.php',
			BASE_PATH,
			DIRECTORY_SEPARATOR,
			DIRECTORY_SEPARATOR
		);
		$moduleLoader = new ModuleLoader($modulesDirectory, $moduleConfigurationPath);
		$moduleLoader->load($this->container);

        ApplicationContext::setContainer($this->container);
    }

	/**
	 * Determine whether optional class discovery should run.
	 *
	 * @param string $environmentVariableName Environment variable controlling discovery.
	 *
	 * @return bool True unless the configured value explicitly disables discovery.
	 */
	private function isDiscoveryEnabled(string $environmentVariableName): bool
	{
		$isDefinedInEnvironment = array_key_exists($environmentVariableName, $_ENV);
		$configuredValue = $isDefinedInEnvironment
			? $_ENV[$environmentVariableName]
			: getenv($environmentVariableName);

		if (!$isDefinedInEnvironment && $configuredValue === false) {
			return true;
		}

		if (is_bool($configuredValue)) {
			return $configuredValue;
		}

		if (!is_string($configuredValue) || trim($configuredValue) === '') {
			return true;
		}

		return filter_var($configuredValue, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) !== false;
	}

    /**
     * Get application options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Boot the application
     *
     * @return void
     */
    public function boot(): void
    {
        $this->eventBus->publish(new OnBoot());
    }

    /**
     * Terminate the application
     *
     * @return void
     */
    public function terminate(): void
    {
        $this->eventBus->publish(new OnTerminate());
    }

    /**
     * Match and return the appropriate runner (CLI or HTTP)
     *
     * @return CliKernel|HttpKernel|BaseProxy
     */
    public function matchRunner(): CliKernel|HttpKernel|BaseProxy
    {
        $this->findInterceptor();

        $this->setContainer();

        $this->boot();

        if ($this->isCommandLineInterface()) {
            $response = new CliKernel($this->container);
            /**
             * @var CliKernel $response
             */
            $response = parent::setBaseProxy($response);
        } else {
            $response = new HttpKernel($this->eventDispatcher, $this->container, $this->getEnvironment(), $this->getOptions());
            /**
             * @var HttpKernel $response
             */
            $response = parent::setBaseProxy($response);
        }

		return $response;
	}
}
