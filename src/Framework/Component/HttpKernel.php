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
use Clover\Classes\ContentType;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\JSONHandler;
use Clover\Classes\Data\StringObject;
use Clover\Classes\Debug\DebugSubscriberProvider;
use Clover\Classes\Debug\Profiler;
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Directory\Handler as DirectoryHandler;
use Clover\Classes\Event\Dispatcher as EventDispatcher;
use Clover\Classes\Event\EventDispatcherAdapter;
use Clover\Classes\Event\EventManager;
use Clover\Classes\File\Functions as FileFunctions;
use Clover\Classes\File\Handler as FileHandler;
use Clover\Classes\Header;
use Clover\Classes\Image\Handler as ImageHandler;
use Clover\Classes\Logging\StructuredKernelLogger;
use Clover\Classes\OperationSystem;
use Clover\Classes\Proxy\BaseProxy;
use Clover\Classes\System\Output;
use Clover\Classes\XML\SimpleXML;
use Clover\Enumeration\FileSizeUnit;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Event\AfterResponseSend;
use Clover\Framework\Event\BeforeResponseSend;
use Clover\Framework\Event\KernelBoot;
use Clover\Framework\Event\KernelLifecycleEvent;
use Clover\Framework\Event\KernelSpanFinished;
use Clover\Framework\Event\KernelSpanStarted;
use Clover\Framework\Event\ProfilerTimelineResetRequested;
use Clover\Framework\Middleware\ExceptionHandlingMiddleware;
use Clover\Framework\Middleware\MaintenanceMiddleware;
use Clover\Framework\Middleware\RoutingMiddleware;
use Clover\Framework\Middleware\SecurityHeadersMiddleware;
use Clover\Framework\Middleware\StackRequestHandler;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server as SwooleHttpServer;
use Exception;
use DOMDocument;
use SimpleXMLElement;
use function is_array;
use function is_object;
use function in_array;
use function is_string;
use function microtime;
use function round;
use function uniqid;

/**
 * HTTP Kernel
 * 
 * The HttpKernel is responsible for handling incoming HTTP requests, processing them through a series of middlewares, and sending the appropriate HTTP responses back to the client. It supports both traditional FPM and Swoole servers, allowing for flexible deployment options.
 */
class HttpKernel extends BaseClass
{
    /** @var Container $container The dependency injection container for managing application services and dependencies */
    private Container $container;
    /** @var array $environment Environment details collected from the HTTP request and server variables, which can be used for debugging and informational purposes in the response. */
    private array $environment;
    /** @var array $options Application options passed to the HttpKernel, which can include settings for server type (e.g., FPM or Swoole) and other configuration parameters that influence how the kernel handles requests and responses. */
    private array $options;
    /** @var BaseProxy|EventDispatcher $eventDispatcher The event dispatcher used to manage and dispatch events throughout the request handling process, allowing for extensibility and customization of the kernel's behavior by listening to events such as before sending a response or after sending a response. */
    private BaseProxy|EventDispatcher $eventDispatcher;
    private EventDispatcherAdapter $kernelEventDispatcher;
    private float $handleStartedAt = 0.0;
    private float $handleFinishedAt = 0.0;

    private function isProfilerEnabled(): bool
    {
        return Profiler::isEnabled();
    }

    /**
     * Swoole workers serve many requests per process; Runtime bootstrap runs once, so reset per request.
     */
	private function shouldResetProfilerTimelineForSwooleRequest(bool $profilerEnabled): bool
    {
		if (!$profilerEnabled) {
			return false;
		}

		return ($this->options['server'] ?? '') === 'swoole';
    }

    /**
     * Constructor
     *
     * @param BaseProxy|EventDispatcher $eventDispatcher The event dispatcher used to manage and dispatch events throughout the request handling process, allowing for extensibility and customization of the kernel's behavior by listening to events such as before sending a response or after sending a response.
     * @param Container $container The dependency injection container for managing application services and dependencies, which allows the kernel to resolve and utilize various services needed during request handling, such as routing, database access, and more.
     * @param array $environment Environment details collected from the HTTP request and server variables, which can be used for debugging and informational purposes in the response. This may include information such as memory usage, PHP version, server software, and other relevant environment details that can help developers understand the context of the request and diagnose issues.
     * @param array $options Application options passed to the HttpKernel, which can include settings for server type (e.g., FPM or Swoole) and other configuration parameters that influence how the kernel handles requests and responses. These options allow developers to customize the behavior of the kernel based on their specific needs and deployment environment.
     */
    public function __construct(BaseProxy|EventDispatcher $eventDispatcher, Container $container, array $environment = [], array $options = [])
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->container = $container;
        $this->environment = $environment;
        $this->options = $options;
        $this->kernelEventDispatcher = EventDispatcherAdapter::fromEventManager();
		$debuggable = isset($_ENV['IS_DEBUGGABLE']) && $_ENV['IS_DEBUGGABLE'] === 'true';
		DebugSubscriberProvider::register($debuggable);
    }

    /**
     * Get essential body (header/footer)
     *
     * @param ArrayObject|array $resource The resource data to be used in the template rendering, which may include variables and information needed to populate the header or footer templates. This allows for dynamic content to be included in the essential body of the response, such as user information, site settings, or other relevant data that should be available in the header or footer of the rendered HTML response.
     * @param string $filePath The file path to the template that should be rendered for the essential body (header or footer). This path should point to a valid template file that can be processed and rendered by the kernel, allowing for consistent and reusable header and footer content across different responses.
     *
     * @return string|StringObject
     */
    private function getEssentialBody(ArrayObject|array $resource, string $filePath): string|StringObject
    {
        return FileFunctions::getInterpretedContent($filePath, $resource);
    }

    /**
     * Handle the incoming HTTP request and return a Response
     *
     * @param Request $request The incoming HTTP request object that encapsulates all the information about the request, such as headers, query parameters, body content, and more. This object is used by the kernel to process the request through the middleware stack and generate an appropriate response based on the routing and application logic defined in the middlewares and controllers.
     *
     * @return Response The HTTP response object that contains the content, headers, status code, and other relevant information that should be sent back to the client as a result of processing the incoming request. This response is generated by the middlewares and controllers during the request handling process and is ultimately sent to the client by the kernel after all processing is complete.
     *
     * @throws Exception
     */
	public function handleRequest(Request $request): Response
	{
		TraceContext::bootstrap($request->server);
		$requestMethod = $request->getMethod();
		$requestUri = $request->getUri();
		$profilerEnabled = $this->isProfilerEnabled();
		if ($this->shouldResetProfilerTimelineForSwooleRequest($profilerEnabled)) {
            $this->kernelEventDispatcher->dispatch(new ProfilerTimelineResetRequested());
        }
        $this->handleStartedAt = microtime(true);
		$this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(
			KernelLifecycleEvent::HANDLE_STARTED,
			TraceContext::appendTrace(['uri' => $requestUri, 'method' => $requestMethod])
		));
		StructuredKernelLogger::info('request.started', [
			'uri' => $requestUri,
			'method' => $requestMethod,
		]);
        $this->kernelEventDispatcher->dispatch(new KernelBoot());
		$detectLocaleSpan = '';
		if ($profilerEnabled) {
			$detectLocaleSpan = uniqid('pf_', true);
			$this->kernelEventDispatcher->dispatch(new KernelSpanStarted(
				$detectLocaleSpan,
				'Kernel::DetectLocale',
				'HttpKernel::handleRequest'
			));
		}
		try {
			$acceptLanguage = $request->getHeader('Accept-Language');
			Translator::detectAndSetFromAcceptLanguage($acceptLanguage !== '' ? $acceptLanguage : null);
			$this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(
				KernelLifecycleEvent::LOCALE_DETECTED,
				['acceptLanguage' => $acceptLanguage]
			));
		} finally {
			if ($profilerEnabled) {
				$this->kernelEventDispatcher->dispatch(new KernelSpanFinished($detectLocaleSpan));
			}
		}

		$middlewares = [
			new SecurityHeadersMiddleware(),
			new ExceptionHandlingMiddleware(),
			new MaintenanceMiddleware(),
            new RoutingMiddleware($this->container, function (object $event): void {
                $this->kernelEventDispatcher->dispatch($event);
            }),
        ];

        // Create a fallback handler
        $fallbackHandler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response(Translator::trans('errors.404', [], 'Not Found'), [], 'text', 404);
            }
        };

        $handler = new StackRequestHandler($middlewares, $fallbackHandler, $this->kernelEventDispatcher);

		$middlewareStackSpan = '';
        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::MIDDLEWARE_STACK_STARTED));
		if ($profilerEnabled) {
			$middlewareStackSpan = uniqid('pf_', true);
			$this->kernelEventDispatcher->dispatch(new KernelSpanStarted(
				$middlewareStackSpan,
				'Kernel::MiddlewareStack',
				'StackRequestHandler'
			));
		}
        try {
            $response = $handler->handle($request);
			if (!$response->hasHeader(TraceContext::getResponseHeaderName())) {
				$response->setHeader(TraceContext::getResponseHeaderName(), TraceContext::getTraceId());
			}
		} catch (\Throwable $exception) {
			StructuredKernelLogger::error('request.failed', [
				'uri' => $requestUri,
				'method' => $requestMethod,
				'exception_class' => $exception::class,
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
				'trace' => $exception->getTraceAsString(),
			]);
			throw $exception;
		} finally {
			if ($profilerEnabled) {
				$this->kernelEventDispatcher->dispatch(new KernelSpanFinished($middlewareStackSpan));
			}
		}
        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::MIDDLEWARE_STACK_FINISHED));
        $this->handleFinishedAt = microtime(true);
        $durationMs = round(($this->handleFinishedAt - $this->handleStartedAt) * 1000, 3);
        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(
            KernelLifecycleEvent::HANDLE_FINISHED,
            TraceContext::appendTrace(['durationMs' => $durationMs])
        ));
		StructuredKernelLogger::info('request.finished', [
			'uri' => $requestUri,
			'method' => $requestMethod,
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $durationMs,
        ]);

        return $response;
    }

    /**
	 * Prepare a response consistently before a transport sends it.
     *
	 * @param Response $response The response produced by the middleware stack.
     *
     * @return Response
     *
     * @throws Exception
     */
	protected function prepareResponse(Response $response): Response
	{
        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::RESPONSE_SENDING));
        EventManager::getEventBus()->publish(new BeforeResponseSend($response));

        if (!$response) {
            throw new Exception(Translator::trans('framework.http_kernel.request_not_routed', [], 'This request is not routing correctly'));
        }

        if (!($response instanceof Response)) {
            throw new Exception(Translator::trans('framework.http_kernel.response_must_be_instance', [], 'Response must be instance of Response'));
        }

        Header::responseHeader(TraceContext::getResponseHeaderName(), TraceContext::getTraceId());
		$type = $response->getType() ?? '';

        switch ($type) {
            case 'json':
                $this->sendJson($response);
                break;

            case 'image':
                $this->sendImage($response);
                break;

            case 'xml':
                $this->sendXml($response);
                break;

            case 'redirect':
                $this->sendRedirect($response);
                break;

            case 'html':
                $this->sendHtml($response);
                break;

			default:
				$response->setHeader('Content-Type', 'text/plain; charset=utf-8');
				$response->setBody((string) $response->getBody());
				break;
        }

        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(
            KernelLifecycleEvent::RESPONSE_SENT,
            ['type' => $type]
        ));

        return $response;
    }

    /**
     * Send JSON response
     *
     * @param Response $response
     *
     * @return void
     */
    private function sendJson(Response $response): void
    {
        ContentType::responseContentType('application/json; charset=utf-8');

		$body = $response->getBody();
        if (is_array($body) || is_object($body)) {
            $json = JSONHandler::encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $response->setBody($json);
        } else {
            $response->setBody((string) $body);
        }
    }

    /**
     * Send image response
     *
     * @param Response $response
     *
     * @return void
     *
     * @throws Exception
     */
    private function sendImage(Response $response): void
    {
        $path = (string) $response->getBody();
        if (!file_exists($path)) {
            throw new Exception(Translator::trans('framework.http_kernel.image_not_found', ['path' => $path], 'Image not found: {path}'));
        }

        $imageType = ImageHandler::getType($path) ?: 'application/octet-stream';
        $fileContents = FileHandler::read($path);

		$response->setHeader('Content-Type', $imageType);
		$response->setBody($fileContents);
    }

    /**
     * Send XML response
     *
     * @param Response $response
     *
     * @return void
     *
     * @throws Exception
     */
    private function sendXml(Response $response): void
    {
        $body = $response->getBody();

        if ($body === null || $body === '' || (is_array($body) && empty($body))) {
            $xmlString = '<root/>';
        } elseif ($body instanceof SimpleXMLElement) {
            $xmlString = $body->asXML();
        } elseif (is_array($body)) {
            $simpleXML = new SimpleXML();
            $xmlString = $simpleXML->fromArray($body)->toXML();

            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = true;
            $dom->formatOutput = false;
            $dom->loadXML($xmlString);

            $xmlString = $dom->saveXML();
        } else {
            $xmlString = (string) $body;
            if (trim($xmlString) === '') {
                $xmlString = '<root/>';
            }
        }

        ContentType::responseContentType('application/xml; charset=utf-8');
		$response->setBody($xmlString);
    }

    /**
     * Send redirect response
     *
     * @param Response $response
     *
     * @return void
     *
     * @throws Exception
     */
    private function sendRedirect(Response $response): void
    {
        $location = (string) $response->getBody();
        if ($location === '') {
            throw new Exception(Translator::trans('framework.http_kernel.redirect_location_empty', [], 'Redirect location is empty'));
        }

        Header::responseRedirectLocation($location);
    }

    /**
     * Send HTML response
     *
     * @param Response $response
     *
     * @return void
     *
     * @throws Exception
     */
    private function sendHtml(Response $response): void
    {
        $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::HTML_RENDER_STARTED));
        $renderStartedAt = microtime(true);
        $resources = $response->getResource();
        $profilerEnabled = $this->isProfilerEnabled();

        $header = $this->getEssentialBody($resources, __DIR__ . '/../Template/Header.php');
        $response->preAppendBody($header);

        $footer = $this->getEssentialBody($resources, __DIR__ . '/../Template/Footer.php');
        $response->appendBody($footer);

        if (isset($_ENV['IS_DEBUGGABLE']) && $_ENV['IS_DEBUGGABLE'] === 'true') {
            $requestStartedAt = (float) ($_SERVER['REQUEST_TIME_FLOAT'] ?? $renderStartedAt);
            $now = microtime(true);
            $totalDurationMs = round(($now - $requestStartedAt) * 1000, 3);
            $kernelOverheadMs = round(($this->handleStartedAt - $requestStartedAt) * 1000, 3);
            $handlingMs = round(($this->handleFinishedAt - $this->handleStartedAt) * 1000, 3);
            $renderMs = round(($now - $renderStartedAt) * 1000, 3);

            $phaseFlow = [
                [
                    'step' => 0,
                    'call' => 'Kernel bootstrap',
                    'location' => ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . ($_SERVER['REQUEST_URI'] ?? '/'),
                    'durationMs' => $kernelOverheadMs < 0 ? 0.0 : $kernelOverheadMs,
                ],
                [
                    'step' => 1,
                    'call' => 'Routing + controller',
                    'location' => 'HttpKernel::handleRequest',
                    'durationMs' => $handlingMs < 0 ? 0.0 : $handlingMs,
                ],
                [
                    'step' => 2,
                    'call' => 'Response rendering',
                    'location' => 'HttpKernel::sendHtml',
                    'durationMs' => $renderMs < 0 ? 0.0 : $renderMs,
                ],
            ];

            $profileId = null;
            if ($profilerEnabled) {
                $timelineFlow = Profiler::getTimelineFlow();
                $flow = !empty($timelineFlow) ? $timelineFlow : $phaseFlow;
                $profileId = Profiler::recordRequestProfile($totalDurationMs, $flow);
                $this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(
                    KernelLifecycleEvent::PROFILER_SNAPSHOT_STORED,
                    ['profileId' => $profileId, 'durationMs' => $totalDurationMs]
                ));
            }

            $debuggerInformation = [
                'memoryUsage' => FileFunctions::formatSize(OperationSystem::getMemoryUsage(), FileSizeUnit::SHORT),
                'phpVersion' => OperationSystem::getPHPVersion(),
                'serverSoftware' => OperationSystem::getMainServerSoftware(),
                'builtOperationSystem' => OperationSystem::getBuiltOperationSystemString(),
                'freeSpace' => FileFunctions::formatSize(DirectoryHandler::getFreeSpace(), FileSizeUnit::SHORT),
                'environment' => $this->environment,
                'profilerEnabled' => $profilerEnabled,
                'profilerUrl' => '/profiler' . (is_string($profileId) ? ('?id=' . $profileId) : ''),
            ];

            $debugContent = FileFunctions::getInterpretedContent(__DIR__ . '/../Template/Debugger.php', $debuggerInformation);
            $response->appendBody($debugContent);
        }

        ContentType::responseContentType('text/html; charset=utf-8');
		$this->kernelEventDispatcher->dispatch(new KernelLifecycleEvent(KernelLifecycleEvent::HTML_RENDER_FINISHED));
    }

    public function setKernelEventDispatcher(EventDispatcherAdapter $eventDispatcher): static
    {
        $this->kernelEventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Run http kernel with FPM
     * 
     * @return void
     */
	public function runWithFPM(): void
	{
		$request = Request::createFromGlobals();
		$response = $this->prepareResponse($this->handleRequest($request));
		$response->send();
		$this->publishAfterResponseSend();
	}

    /**
     * Run http kernel with FrankenPHP.
     *
     * FrankenPHP handles the worker/event loop lifecycle. This method processes
     * a single request so it can run in both classic and worker modes.
     *
     * @return void
     */
	public function runWithFrankenPHP(): void
	{
		$request = Request::createFromGlobals();
		$response = $this->prepareResponse($this->handleRequest($request));
		$response->send();
		$this->publishAfterResponseSend();
	}

    /**
     * Run http kernel with Swoole
     *
     * @param string $host The host address to bind the Swoole HTTP server to, typically '
     * @param int $port The port number to listen on for incoming HTTP requests, commonly set to 9501 for Swoole applications. This allows the server to accept and handle requests sent to this specific port, enabling the application to serve content over HTTP when running in a Swoole environment.
     *
     * @return void
     *
     * @throws Exception
     */
    public function runWithSwoole(string $host = '0.0.0.0', int $port = 9501): void
    {
        if (!class_exists('Swoole\Http\Server') || !class_exists('Swoole\Http\Request')) {
            throw new Exception(Translator::trans('framework.http_kernel.swoole_not_available', [], 'class SwooleHttpServer is not exists'));
        }

        $http = new SwooleHttpServer($host, $port);

		$http->on('request', function (SwooleRequest $swooleRequest, SwooleResponse $swooleResponse): void {
            // Emulate global $_GET, $_POST, $_FILES, $_COOKIE, $_SESSION, $_SERVER for backward compatibility if needed
            // But we should rely on the Clover Request object now.

            $_GET = $swooleRequest->get ?? [];
            $_POST = $swooleRequest->post ?? [];
            $_FILES = $swooleRequest->files ?? [];
            $_COOKIE = $swooleRequest->cookie ?? [];
            $_SERVER = [];
            foreach ($swooleRequest->server as $key => $value) {
                $_SERVER[strtoupper($key)] = $value;
            }
            $_SERVER['REQUEST_METHOD'] = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $request = Request::createFromSwoole($swooleRequest);

            // Handle the request and get the Clover Response object
			$response = $this->prepareResponse($this->handleRequest($request));

			if ($response) {
				$swooleResponse->status($response->getStatusCode());
				// Send headers
                foreach (Header::getHeaders() as $name => $value) {
                    $swooleResponse->header($name, $value);
                }
                // Send response headers
                foreach ($response->getResponseHeaders() as $name => $value) {
                    $swooleResponse->header($name, $value);
                }

                // Send cookies
                foreach (Header::getCookies() as $cookie) {
                    $swooleResponse->setcookie($cookie['name'], $cookie['value'], $cookie['expire'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
                }
                foreach ($response->getCookies() as $name => $cookie) {
                    $swooleResponse->setcookie($name, $cookie['value'], (int) $cookie['expires'], $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly'], $cookie['samesite']);
                }

                // Send response body based on the type
                if (in_array($response->getType(), ['json', 'html', 'redirect'], true)) {
                    $swooleResponse->end($response->getBody());
				} elseif ($response->getType() === 'image') {
                    $swooleResponse->end($response->getBody());
                } else {
                    $swooleResponse->end((string) $response->getBody());
                }
			} else {
				$swooleResponse->status(404);
				$swooleResponse->end(Translator::trans('errors.404', [], 'Not Found'));
			}

			$this->publishAfterResponseSend();

			// Clear headers and cookies for the next request
			Header::clearHeaders();
			Header::clearCookies();
		});

        $http->start();
    }

    /**
     * Run http kernel, automatically choosing between FPM and Swoole
     * based on the 'swoole' option.
     * 
     * @return void
     */
	public function run(): void
	{
		if (isset($this->options['server'])) {
			switch ($this->options['server']) {
				case 'swoole':
					$host = $this->options['swoole_host'] ?? '0.0.0.0';
					$port = $this->options['swoole_port'] ?? 9501;
					$this->runWithSwoole($host, $port);
					break;
				case 'frankenphp':
					$this->runWithFrankenPHP();
					break;
				default:
					$this->runWithFPM();
					break;
			}
		} else {
			$this->runWithFPM();
		}
	}

	/**
	 * Publish the response-complete event after a transport has sent its response.
	 *
	 * @return void
	 */
	protected function publishAfterResponseSend(): void
	{
		EventManager::getEventBus()->publish(new AfterResponseSend());
	}
}
