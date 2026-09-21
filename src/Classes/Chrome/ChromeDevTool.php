<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Chrome;

use Clover\Classes\BaseClass;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\StringObject;
use Clover\Classes\OperationSystem;
use Clover\Classes\Socket\WebSocket;
use Clover\Enumeration\Windows32\RegistryKey;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use function is_resource;
use function is_array;

/**
 * Class ChromeDevTool
 * 
 * Provides methods to interact with Chrome DevTools Protocol.
 */
class ChromeDevTool extends BaseClass
{
    #region properties

    /** @var string $chromePath The path to the Chrome executable */
    private string $chromePath;

    /** @var int $debugPort The debugging port */
    private int $debugPort;

    /** @var string $debugUrl The debugging URL */
    private string $debugUrl;

    /** @var string $sessionId The session ID */
    private null|StringObject|string $sessionId = null;

    /** @var resource $socket The WebSocket connection */
    private mixed $socket = null;

    /** @var int $id The message ID counter */
    private int $id = 0;

    /** @var array $collectedUrls URLs collected during page load */
    private array $collectedUrls = [];

    /** @var array<string, string|bool|int> */
    private array $chromeOptions = [
        'headless' => 'new',
        'disable-gpu' => true,
        'remote-debugging-port' => 9222,
    ];

    /** @var resource|null $process The process handle returned by launch() */
    private mixed $process = null;

    /** @var int|null $processId Process id of the browser this instance launched */
    private ?int $processId = null;

    /** @var string|null $temporaryProfilePath Profile directory created for the launched browser */
    private ?string $temporaryProfilePath = null;

    /** Directory-name prefix identifying a throwaway profile created by this class. */
    private const PROFILE_PREFIX = 'clover-chrome-';

    /** Age at which an abandoned throwaway profile is considered safe to delete. */
    private const PROFILE_GRACE_SECONDS = 3600;

    #endregion

    #region function

    /**
     * Constructor
     *
     * @param string|null $chromePath The path to the Chrome executable
     * @param int $debugPort The debugging port (default: 9222)
     * 
     * @throws Exception
     */
    public function __construct(?string $chromePath = null, int $debugPort = 9222)
    {
        $this->chromePath = $chromePath ?? $this->getBinaryPath();
        $this->debugPort = $debugPort;
    }

    /**
     * @param array<string, string|bool|int> $options
     */
    public function setChromeOptions(array $options): static
    {
        $this->chromeOptions = array_merge($this->chromeOptions, $options);
        return $this;
    }

    /**
     * Get the next message ID
     * 
     * @return int The next message ID
     */
    private function getNextId(): int
    {
        return $this->id++;
    }

    /**
     * Set the session ID for the Chrome DevTools session
     * 
     * @param StringObject|string $sessionId The session ID
     * 
     * @return void
     */
    public function setSessionId(StringObject|string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Check whether a target session id is currently set.
     *
     * @return bool
     */
    public function hasSessionId(): bool
    {
        return $this->sessionId !== null;
    }

    /**
     * Get the Chrome binary path from the Windows registry
     * 
     * @return string The Chrome binary path
     * 
     * @throws Exception
     */
    private function getBinaryPath(): string
    {
        if (!OperationSystem::isWindows()) {
            throw new Exception("This operating system is not supported.");
        }

        // 1) Try multiple registry keys (HKLM machine-wide, HKCU per-user, App Paths)
        $registryQueries = [
            RegistryKey::LOCAL_MACHINE_CUSTOM_EXECUTABLE_PATHS->value . '\chrome.exe',
            RegistryKey::CURRENT_USER_CUSTOM_EXECUTABLE_PATHS->value . '\chrome.exe'
        ];

        foreach ($registryQueries as $key) {
            $output = OperationSystem::executeShell('reg query "' . $key . '" /ve 2>nul');
            if ($output !== null && preg_match('/\s+REG_SZ\s+(.+chrome\.exe)/i', $output, $m)) {
                $path = trim($m[1]);
                if ($path !== '') {
                    return $path;
                }
            }
        }

        // 2) Fall back to well-known filesystem locations
        foreach ($this->getChromeCandidatePaths() as $path) {
            return $path;
        }

        throw new Exception("Chrome executable not found. Please pass the path explicitly: new ChromeDevTool('C:\\path\\to\\chrome.exe')");
    }

    /**
     * Return a list of well-known Chrome installation paths on Windows
     *
     * @return string[]
     */
    private function getChromeCandidatePaths(): array
    {
        $candidates = [];

        $envVars = [
            'LOCALAPPDATA',
            'PROGRAMFILES',
            'PROGRAMFILES(X86)',
            'PROGRAMW6432',
        ];

        $bases = [];
        foreach ($envVars as $var) {
            $val = getenv($var);
            if ($val !== false && $val !== '') {
                $bases[] = rtrim($val, '\\/');
            }
        }

        $bases = array_unique($bases);

        $relativePaths = [
            'Google\\Chrome\\Application\\chrome.exe',
            'Google\\Chrome Beta\\Application\\chrome.exe',
            'Google\\Chrome SxS\\Application\\chrome.exe',
            'Chromium\\Application\\chrome.exe',
        ];

        foreach ($bases as $base) {
            foreach ($relativePaths as $rel) {
                $candidates[] = $base . '\\' . $rel;
            }
        }

        $candidates[] = 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
        $candidates[] = 'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe';

        return $candidates;
    }

    /**
     * Launch Chrome in headless mode with remote debugging enabled
     * 
     * @throws Exception
     */
    public function launch(int $timeout = 10): void
    {
        // The command is assembled as an argument vector rather than a shell string. A string
        // command is run through `cmd.exe /c` on Windows and `sh -c` elsewhere, which makes
        // proc_get_status() report the interpreter's pid instead of the browser's; once that
        // short-lived wrapper exits there is no process tree left to terminate and the browser is
        // orphaned. The vector form execs the binary directly, so the tracked pid is Chrome itself.
        // It also removes the need to quote arguments.
        $command = [$this->chromePath];

        foreach ($this->chromeOptions as $key => $value) {
            if ($value === false) {
                continue;
            }

            $command[] = $value === true ? "--{$key}" : '--' . $key . '=' . $value;
        }

        // Without a private profile directory Chrome hands the command line to an already-running
        // instance and exits, so --remote-debugging-port is ignored and the port belongs to the
        // user's own browser. Every launched browser therefore gets its own throwaway profile,
        // which also makes it a separate process tree that can be shut down on its own.
        $command[] = '--user-data-dir=' . $this->createTemporaryProfile();
        $command[] = 'about:blank';

        $descriptors = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w'],  // stderr
        ];
        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            throw new Exception("Failed to launch Chrome.\n");
        }

        $this->process = $process;

        $this->processId = (int) proc_get_status($process)['pid'];

        fclose($pipes[0]);
        stream_set_blocking($pipes[2], false);

        $webSocketUrl = null;
        $start = time();

        while (!feof($pipes[2])) {
            if (time() - $start > $timeout) {
                break;
            }

            $line = fgets($pipes[2]);

            if ($line === false) {
                usleep(50_000);
                continue;
            }

            if (preg_match('#DevTools listening on (ws://.+)#', $line, $m)) {
                $webSocketUrl = trim($m[1]);
                break;
            }
        }

        if (!$webSocketUrl) {
            $webSocketUrl = $this->pollDevToolsHttpEndpoint(max(2, $timeout - (time() - $start)));
        }

        if (!$webSocketUrl) {
            proc_close($process);
            throw new Exception("The WebSocket URL cannot be found.");
        }

        $parts = parse_url($webSocketUrl);
        $host = $parts['host'];
        $port = $parts['port'];
        $path = $parts['path'] . (isset($parts['query']) ? "?{$parts['query']}" : '');
        $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 3);
        if (!$socket) {
            throw new Exception("Socket connection failed: {$errstr} ({$errno})");
        }

        $key = base64_encode(random_bytes(16));
        $hdr = "GET {$path} HTTP/1.1\r\n";
        $hdr .= "Host: {$host}:{$port}\r\n";
        $hdr .= "Upgrade: websocket\r\n";
        $hdr .= "Connection: Upgrade\r\n";
        $hdr .= "Sec-WebSocket-Key: {$key}\r\n";
        $hdr .= "Sec-WebSocket-Version: 13\r\n\r\n";
        fwrite($socket, $hdr);

        $resp = '';
        $start = time();

        stream_set_blocking($socket, false);

        while (!feof($socket)) {
            if (time() - $start > $timeout) {
                fclose($socket);
                throw new \RuntimeException("WebSocket handshake not completed within {$timeout} seconds.");
            }

            $line = fgets($socket);

            if ($line === false) {
                usleep(50_000);
                continue;
            }

            $resp .= $line;

            if (strpos($resp, "\r\n\r\n") !== false) {
                break;
            }
        }

        if (!preg_match('#^HTTP/1\.1 101#', $resp)) {
            throw new Exception("Handshake failed: " . $resp);
        }

        stream_set_blocking($socket, true);
        stream_set_timeout($socket, 30);

        $this->socket = $socket;
    }

    /**
     * Poll the Chrome DevTools HTTP endpoint to retrieve the WebSocket URL.
     *
     * @param int $remainingTimeout Remaining seconds to keep polling
     * @return string|null The WebSocket debugger URL, or null on failure
     */
    private function pollDevToolsHttpEndpoint(int $remainingTimeout): ?string
    {
        $start = time();
        $url = "http://127.0.0.1:{$this->debugPort}/json/version";

        while (time() - $start < $remainingTimeout) {
            $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
            $body = @file_get_contents($url, false, $ctx);
            if ($body !== false) {
                $json = json_decode($body, true);
                if (isset($json['webSocketDebuggerUrl'])) {
                    return $json['webSocketDebuggerUrl'];
                }
            }
            usleep(200_000);
        }

        return null;
    }

    /**
     * Create a new target (tab) in Chrome
     * 
     * @return mixed The target ID of the created target
     * 
     * @throws Exception
     */
    public function createTarget(): mixed
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        $msgId = $this->getNextId();

        WebSocket::sendToWebSocket($this->socket, json_encode([
            'id' => $msgId,
            'method' => 'Target.createTarget',
            'params' => ['url' => 'about:blank']
        ]));

        $res = $this->readResponseForId($msgId);

        return $res['result']['targetId'] ?? null;
    }

    /**
     * Enable the Page domain in Chrome DevTools
     * 
     * @return object The response from the Page.enable command
     * 
     * @throws Exception
     */
    public function enablePage(): object
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Page.enable'
        ]);
    }

    /**
     * Enable the Runtime domain in Chrome DevTools
     * 
     * @return object The response from the Runtime.enable command
     * 
     * @throws Exception
     */
    public function enableRuntime(): object
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Runtime.enable'
        ]);
    }

    /**
     * Navigate to a specified URL in the Chrome tab
     * 
     * @param string $url The URL to navigate to
     * 
     * @return object The response from the Page.navigate command
     * 
     * @throws Exception
     */
    public function navigatePage(string $url): object
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Page.navigate',
            'params' => ['url' => $url]
        ], 4);
    }

    /**
     * Wait until the Page.loadEventFired event is received
     * 
     * @param int $timeoutMs Timeout in milliseconds
     * @return void
     * 
     * @throws Exception
     */
    public function waitUntil(int $timeoutMs = 10000): void
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        $startTime = microtime(true);
        while ($payload = WebSocket::readFrame($this->socket)) {
            if ((microtime(true) - $startTime) * 1000 > $timeoutMs) {
                break;
            }

            $data = json_decode($payload, true);

            usleep(500);

            if (!isset($data['method'])) {
                continue;
            }

            if ($data['method'] !== 'Page.loadEventFired') {
                continue;
            }

            break;
        }
    }

    /**
     * Wait for page load while collecting all resource URLs requested during loading.
     * Network domain must be enabled (enableNetwork) before calling navigatePage.
     *
     * Collected entries contain:
     *   - url          : full request URL
     *   - resourceType : Document | Stylesheet | Script | Image | Font | XHR | Fetch | Media | Other …
     *   - method       : HTTP method (GET, POST …)
     *   - initiator    : how the request was triggered (parser, script, other)
     *
     * @param int $timeoutMs Timeout in milliseconds
     * @return void
     * @throws Exception
     */
    public function waitUntilAndCollectUrls(int $timeoutMs = 10000): void
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        $this->collectedUrls = [];
        $startTime = microtime(true);

        while ($payload = WebSocket::readFrame($this->socket)) {
            if ((microtime(true) - $startTime) * 1000 > $timeoutMs) {
                break;
            }

            $data = json_decode($payload, true);
            usleep(500);

            if (!isset($data['method'])) {
                continue;
            }

            // Collect every outgoing request
            if ($data['method'] === 'Network.requestWillBeSent') {
                $p = $data['params'] ?? [];
                $this->collectedUrls[] = [
                    'url' => $p['request']['url'] ?? '',
                    'resourceType' => $p['type'] ?? 'Other',
                    'method' => $p['request']['method'] ?? 'GET',
                    'initiator' => $p['initiator']['type'] ?? 'other',
                ];
                continue;
            }

            if ($data['method'] === 'Page.loadEventFired') {
                break;
            }
        }
    }

    /**
     * Return URLs collected by the last waitUntilAndCollectUrls() call.
     *
     * @param string|null $resourceType Filter by resource type (e.g. 'Script', 'Image', 'Stylesheet').
     *                                  Pass null to return all.
     * @return array
     */
    public function getCollectedUrls(?string $resourceType = null): array
    {
        if ($resourceType === null) {
            return $this->collectedUrls;
        }

        return array_values(array_filter(
            $this->collectedUrls,
            fn(array $entry) => strcasecmp($entry['resourceType'], $resourceType) === 0
        ));
    }

    /**
     * Return only the URL strings from collected entries, optionally filtered by resource type.
     *
     * @param string|null $resourceType
     * @return string[]
     */
    public function getCollectedUrlStrings(?string $resourceType = null): array
    {
        return array_column($this->getCollectedUrls($resourceType), 'url');
    }

    /**
     * Clear the collected URL list.
     *
     * @return void
     */
    public function clearCollectedUrls(): void
    {
        $this->collectedUrls = [];
    }

    /**
     * Send a generic call to the Chrome DevTools Protocol
     * 
     * @param string $method The method name to call
     * @param array $params The parameters for the method
     * 
     * @return mixed The response from the method call
     */
    private function call(string $method, array $params = []): mixed
    {
        $message = [
            'id' => $this->getNextId(),
            'method' => $method,
        ];

        if ($this->sessionId !== null) {
            $message['sessionId'] = $this->sessionId;
        }

        if (!empty($params)) {
            $message['params'] = $params;
        }

        return $this->sendMessage($message);
    }

    /**
     * Wait for a specific event from the Chrome DevTools Protocol
     * 
     * @param string $eventName The name of the event to wait for
     * @param int $timeoutMs The timeout in milliseconds
     * 
     * @return array|null The event parameters, or null if timeout
     * 
     * @throws Exception
     */
    public function waitForEvent(string $eventName, int $timeoutMs = 10000): ?array
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        $startTime = microtime(true);
        while ($payload = WebSocket::readFrame($this->socket)) {
            if ((microtime(true) - $startTime) * 1000 > $timeoutMs) {
                return null;
            }

            $data = json_decode($payload, true);
            usleep(500);

            if (isset($data['method']) && $data['method'] === $eventName) {
                return $data['params'] ?? [];
            }
        }

        return null;
    }

    /**
     * Wait for a response to a specific message ID
     * 
     * @param int $messageId The message ID to wait for
     * @param int $timeoutMs The timeout in milliseconds
     * 
     * @return mixed The response result, or null if timeout
     * 
     * @throws Exception
     */
    public function waitForResponse(int $messageId, int $timeoutMs = 10000): mixed
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        $startTime = microtime(true);
        while ($payload = WebSocket::readFrame($this->socket)) {
            if ((microtime(true) - $startTime) * 1000 > $timeoutMs) {
                return null;
            }

            $data = json_decode($payload, true);
            usleep(500);

            if (isset($data['id']) && $data['id'] === $messageId) {
                return $data['result'] ?? $data;
            }
        }

        return null;
    }

    // --- Runtime ---

    /**
     * Evaluate a JavaScript expression in the Chrome tab
     * 
     * @param string $expression The JavaScript expression to evaluate
     * @param bool $returnByValue Whether to return the result by value
     * 
     * @return mixed The result of the evaluation
     * 
     * @throws Exception
     */
    public function evaluate(string $expression, bool $returnByValue = true): mixed
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $response = $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Runtime.evaluate',
            'params' => [
                'expression' => $expression,
                'returnByValue' => $returnByValue
            ]
        ]);

        return $response->result ?? "";
    }

    /**
     * Set the value of an element in the Chrome tab
     * 
     * @param string $expression The JavaScript expression to select the element
     * @param string $value The value to set
     * 
     * @return bool True if the value was set successfully, false otherwise
     * 
     * @throws Exception
     */
    public function setValue(string $expression, string $value): bool
    {
        $response = $this->evaluate("document.querySelector('{$expression}').value = \"{$value}\";");

        return isset($response->result);
    }

    /**
     * Get the value of an element in the Chrome tab
     * 
     * @param string $expression The JavaScript expression to select the element
     * 
     * @return mixed The value of the element
     * 
     * @throws Exception
     */
    public function getValue(string $expression): mixed
    {
        $response = $this->evaluate("document.querySelector('{$expression}').value");

        return $response->value;
    }

    /**
     * Get the outer HTML of the document in the Chrome tab
     * 
     * @return string|null The outer HTML of the document, or null if not found
     * 
     * @throws Exception
     */
    public function getOuterHTML(): mixed
    {
        $result = $this->evaluate("document.documentElement.outerHTML");
        return $result->value ?? null;
    }

    /**
     * Set the value of an input element in the Chrome tab
     * 
     * @param string $name The name attribute of the input element
     * @param string $value The value to set
     * 
     * @return bool True if the value was set successfully, false otherwise
     * 
     * @throws Exception
     */
    public function setInputValue(string $name, string $value): bool
    {
        $response = $this->setValue("input[name=\"{$name}\"]", $value);

        return $response;
    }

    /**
     * Get the value of an input element in the Chrome tab
     * 
     * @param string $name The name attribute of the input element
     * 
     * @return mixed The value of the input element
     * 
     * @throws Exception
     */
    public function getInputValue(string $name): mixed
    {
        $response = $this->getValue("input[name=\"{$name}\"]");

        return $response->result;
    }

    /**
     * Click an element in the Chrome tab
     * 
     * @param string $selector The CSS selector of the element to click
     * 
     * @return bool True if the click was successful, false otherwise
     * 
     * @throws Exception
     */
    public function clickElement(string $selector): bool
    {
        $this->evaluate("document.querySelector('{$selector}').click();");

        $response = false;

        while ($frame = WebSocket::readFrame($this->socket)) {
            $data = json_decode($frame, true);

            if (isset($data['id']) && isset($data['result'])) {
                $response = true;
                break;
            }
        }

        return $response;
    }

    /**
     * Get the document title in the Chrome tab
     * 
     * @return string|null The document title, or null if not found
     * 
     * @throws Exception
     */
    public function getDocumentTitle(): ?string
    {
        $response = $this->evaluate('document.title');

        return $response->result ?? null;
    }

    /**
     * Enable the Network domain in Chrome DevTools
     * 
     * @return object The response from the Network.enable command
     * 
     * @throws Exception
     */
    public function enableNetwork(): object
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Network.enable'
        ]);
    }

    /**
     * Clear the browser cache
     * 
     * @return object
     * @throws Exception
     */
    public function clearBrowserCache(): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Network.clearBrowserCache'
        ]);
    }

    /**
     * Clear the browser cookies
     * 
     * @return object
     * @throws Exception
     */
    public function clearBrowserCookies(): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Network.clearBrowserCookies'
        ]);
    }

    /**
     * Set the User-Agent override
     * 
     * @param string $userAgent
     * @return object
     * @throws Exception
     */
    public function setUserAgentOverride(string $userAgent): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Emulation.setUserAgentOverride',
            'params' => [
                'userAgent' => $userAgent
            ]
        ]);
    }

    /**
     * Reload the page
     * 
     * @param bool $ignoreCache
     * @return object
     * @throws Exception
     */
    public function reloadPage(bool $ignoreCache = true): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Page.reload',
            'params' => [
                'ignoreCache' => $ignoreCache
            ]
        ]);
    }

    /**
     * Capture a screenshot of the page
     *
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @param int $timeoutMs Timeout in milliseconds
     * @return string|null Base64 encoded image string
     * @throws Exception
     */
    public function captureScreenshot(string $format = 'png', int $quality = 100, int $timeoutMs = 15000): ?string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $messageId = $this->getNextId();

        WebSocket::sendToWebSocket($this->socket, json_encode([
            'id' => $messageId,
            'sessionId' => $this->sessionId,
            'method' => 'Page.captureScreenshot',
            'params' => [
                'format' => $format,
                'quality' => $quality,
            ],
        ]));

        // Read frames until we get the response matching $messageId
        $startTime = microtime(true);
        while (true) {
            if ((microtime(true) - $startTime) * 1000 > $timeoutMs) {
                throw new Exception('captureScreenshot timed out after ' . $timeoutMs . 'ms');
            }

            $frame = WebSocket::readFrame($this->socket);
            if ($frame === null || $frame === false) {
                usleep(10000); // 10 ms back-off
                continue;
            }

            $data = json_decode($frame, true);

            // Skip events and responses belonging to other commands
            if (!isset($data['id']) || (int) $data['id'] !== $messageId) {
                continue;
            }

            return $data['result']['data'] ?? null;
        }
    }

    /**
     * Capture a screenshot of the page and save it to a file
     *
     * @param string $filePath The file path to save the screenshot
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return bool True if saved successfully, false otherwise
     * @throws Exception
     */
    public function saveScreenshot(string $filePath, string $format = 'png', int $quality = 100): bool
    {
        $data = $this->captureScreenshot($format, $quality);

        if ($data === null) {
            return false;
        }

        $dir = dirname($filePath);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($filePath, base64_decode($data)) !== false;
    }

    /**
     * Capture a screenshot of a specific region (clip) of the page
     *
     * @param int $x The X coordinate of the clip region
     * @param int $y The Y coordinate of the clip region
     * @param int $width The width of the clip region
     * @param int $height The height of the clip region
     * @param float $scale The scale factor (default: 1)
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return string|null Base64 encoded image string
     * @throws Exception
     */
    public function captureScreenshotWithClip(int $x, int $y, int $width, int $height, float $scale = 1.0, string $format = 'png', int $quality = 100): ?string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $response = $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Page.captureScreenshot',
            'params' => [
                'format' => $format,
                'quality' => $quality,
                'clip' => [
                    'x' => $x,
                    'y' => $y,
                    'width' => $width,
                    'height' => $height,
                    'scale' => $scale,
                ],
            ]
        ]);

        return $response->result->data ?? null;
    }

    /**
     * Capture a full-page screenshot (including content outside the viewport)
     *
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return string|null Base64 encoded image string
     * @throws Exception
     */
    public function captureFullPageScreenshot(string $format = 'png', int $quality = 100): ?string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        // Get full page dimensions via JavaScript
        $metrics = $this->evaluate(
            'JSON.stringify({ width: document.body.scrollWidth, height: document.body.scrollHeight })'
        );
        $dimensions = json_decode($metrics->value ?? '{}', true);

        $width = max(1, (int) ($dimensions['width'] ?? 1280));
        $height = max(1, (int) ($dimensions['height'] ?? 720));

        // Expand viewport to cover the entire page
        $this->setDeviceMetricsOverride($width, $height);

        $data = $this->captureScreenshot($format, $quality);

        // Restore default viewport
        $this->setDeviceMetricsOverride(1280, 720);

        return $data;
    }

    /**
     * Capture a full-page screenshot and save it to a file
     *
     * @param string $filePath The file path to save the screenshot
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return bool True if saved successfully, false otherwise
     * @throws Exception
     */
    public function saveFullPageScreenshot(string $filePath, string $format = 'png', int $quality = 100): bool
    {
        $data = $this->captureFullPageScreenshot($format, $quality);

        if ($data === null) {
            return false;
        }

        $dir = dirname($filePath);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($filePath, base64_decode($data)) !== false;
    }

    /**
     * Capture a screenshot of a specific DOM element by CSS selector
     *
     * @param string $selector The CSS selector of the element to capture
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return string|null Base64 encoded image string, or null if element not found
     * @throws Exception
     */
    public function captureElementScreenshot(string $selector, string $format = 'png', int $quality = 100): ?string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $escapedSelector = addslashes($selector);
        $metrics = $this->evaluate(
            "(function() {" .
            "  var el = document.querySelector('{$escapedSelector}');" .
            "  if (!el) return JSON.stringify(null);" .
            "  var r = el.getBoundingClientRect();" .
            "  return JSON.stringify({ x: r.left, y: r.top, width: r.width, height: r.height });" .
            "})()"
        );

        $rect = json_decode($metrics->value ?? 'null', true);

        if (!$rect || $rect['width'] <= 0 || $rect['height'] <= 0) {
            return null;
        }

        return $this->captureScreenshotWithClip((int) $rect['x'], (int) $rect['y'], (int) $rect['width'], (int) $rect['height'], 1.0, $format, $quality);
    }

    /**
     * Capture a screenshot of a specific DOM element and save it to a file
     *
     * @param string $selector The CSS selector of the element to capture
     * @param string $filePath The file path to save the screenshot
     * @param string $format Image format ('png' or 'jpeg')
     * @param int $quality Image quality (0-100, jpeg only)
     * @return bool True if saved successfully, false otherwise
     * @throws Exception
     */
    public function saveElementScreenshot(string $selector, string $filePath, string $format = 'png', int $quality = 100): bool
    {
        $data = $this->captureElementScreenshot($selector, $format, $quality);

        if ($data === null) {
            return false;
        }

        $dir = dirname($filePath);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($filePath, base64_decode($data)) !== false;
    }

    /**
     * Start recording audio from the page.
     *
     * Injects a Web Audio API + MediaRecorder script into the page that taps into
     * every <audio> and <video> element currently in the DOM.
     * Call enablePage() and navigate to the target URL before using this method.
     *
     * @param int $timesliceMs  How often (ms) MediaRecorder flushes a chunk (default 200ms)
     * @return bool True if recording started successfully
     * @throws Exception
     */
    public function startAudioRecording(int $timesliceMs = 200): bool
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $js = <<<'JS'
(function() {
    if (window.__cdpAudioRecorder && window.__cdpAudioRecorder.state === 'recording') {
        return 'already_recording';
    }

    try {
        window.__cdpAudioChunks  = [];
        window.__cdpAudioContext = new (window.AudioContext || window.webkitAudioContext)();
        window.__cdpAudioDest    = window.__cdpAudioContext.createMediaStreamDestination();

        // Connect all existing audio/video elements
        document.querySelectorAll('audio, video').forEach(function(el) {
            try {
                var src = window.__cdpAudioContext.createMediaElementSource(el);
                src.connect(window.__cdpAudioDest);
                src.connect(window.__cdpAudioContext.destination); // keep speaker output
            } catch (e) { /* element may already be connected */ }
        });

        // Also observe future elements added dynamically
        window.__cdpAudioObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(m) {
                m.addedNodes.forEach(function(node) {
                    if (node.nodeName === 'AUDIO' || node.nodeName === 'VIDEO') {
                        try {
                            var src = window.__cdpAudioContext.createMediaElementSource(node);
                            src.connect(window.__cdpAudioDest);
                            src.connect(window.__cdpAudioContext.destination);
                        } catch (e) {}
                    }
                });
            });
        });
        window.__cdpAudioObserver.observe(document.body, { childList: true, subtree: true });

        var mimeType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : 'audio/webm';

        window.__cdpAudioRecorder = new MediaRecorder(window.__cdpAudioDest.stream, { mimeType: mimeType });
        window.__cdpAudioMimeType = mimeType;

        window.__cdpAudioRecorder.ondataavailable = function(e) {
            if (e.data && e.data.size > 0) {
                window.__cdpAudioChunks.push(e.data);
            }
        };

        window.__cdpAudioRecorder.start(TIMESLICE);
        return 'started';
    } catch (e) {
        return 'error:' + e.message;
    }
})();
JS;

        $js = str_replace('TIMESLICE', (string) $timesliceMs, $js);
        $result = $this->evaluate($js);
        $value = $result->value ?? '';

        return $value === 'started' || $value === 'already_recording';
    }

    /**
     * Stop the audio recording and return the recorded data as a base64-encoded string.
     *
     * @param int $timeoutMs How long to wait for the recorder to finalise (ms)
     * @return string|null Base64-encoded audio (WebM/Opus), or null on failure
     * @throws Exception
     */
    public function stopAudioRecording(int $timeoutMs = 10000): ?string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        // Kick off async stop; store result in a global so we can poll it
        $stopJs = <<<'JS'
(function() {
    window.__cdpAudioBase64 = null;
    window.__cdpAudioDone   = false;

    if (!window.__cdpAudioRecorder || window.__cdpAudioRecorder.state === 'inactive') {
        window.__cdpAudioDone = true;
        return 'not_recording';
    }

    window.__cdpAudioRecorder.onstop = function() {
        var blob   = new Blob(window.__cdpAudioChunks, { type: window.__cdpAudioMimeType || 'audio/webm' });
        var reader = new FileReader();
        reader.onloadend = function() {
            // Strip the data-URL prefix (data:audio/webm;base64,<DATA>)
            window.__cdpAudioBase64 = reader.result.split(',')[1] || null;
            window.__cdpAudioDone   = true;
        };
        reader.readAsDataURL(blob);

        // Clean up
        if (window.__cdpAudioObserver) {
            window.__cdpAudioObserver.disconnect();
        }
        if (window.__cdpAudioContext) {
            window.__cdpAudioContext.close();
        }
    };

    window.__cdpAudioRecorder.stop();
    return 'stopping';
})();
JS;

        $result = $this->evaluate($stopJs);
        $value = $result->value ?? '';

        if ($value === 'not_recording') {
            return null;
        }

        // Poll until FileReader finishes (max $timeoutMs)
        $pollJs = 'window.__cdpAudioDone ? (window.__cdpAudioBase64 || "__empty__") : null';
        $startTime = microtime(true);

        while ((microtime(true) - $startTime) * 1000 < $timeoutMs) {
            usleep(100000); // 100 ms
            $poll = $this->evaluate($pollJs);
            $data = $poll->value ?? null;

            if ($data === null) {
                continue; // not done yet
            }

            if ($data === '__empty__') {
                return null; // recorded but blob was empty
            }

            return $data; // base64 string
        }

        throw new Exception('stopAudioRecording timed out after ' . $timeoutMs . 'ms');
    }

    /**
     * Save the audio recording to a file.
     *
     * @param string $filePath Destination file path (e.g. 'record.webm')
     * @param int    $timeoutMs Timeout passed to stopAudioRecording()
     * @return bool True on success
     * @throws Exception
     */
    public function saveAudioRecording(string $filePath, int $timeoutMs = 10000): bool
    {
        $data = $this->stopAudioRecording($timeoutMs);

        if ($data === null) {
            return false;
        }

        $dir = dirname($filePath);
        if ($dir !== '.' && !is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return file_put_contents($filePath, base64_decode($data)) !== false;
    }

    /**
     * Return the current state of the audio recorder ('recording', 'paused', 'inactive', 'unknown').
     *
     * @return string
     * @throws Exception
     */
    public function getAudioRecordingState(): string
    {
        if (!$this->hasSessionId()) {
            throw new Exception('SessionId is empty');
        }

        $result = $this->evaluate('window.__cdpAudioRecorder ? window.__cdpAudioRecorder.state : "unknown"');

        return $result->value ?? 'unknown';
    }

    /**
     * Dispatch a mouse event
     * 
     * @param string $type ('mousePressed', 'mouseReleased', 'mouseMoved', 'mouseWheel')
     * @param int $x
     * @param int $y
     * @param string $button ('none', 'left', 'middle', 'right', 'back', 'forward')
     * @param int $clickCount
     * @return object
     * @throws Exception
     */
    public function dispatchMouseEvent(string $type, int $x, int $y, string $button = 'left', int $clickCount = 1): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Input.dispatchMouseEvent',
            'params' => [
                'type' => $type,
                'x' => $x,
                'y' => $y,
                'button' => $button,
                'clickCount' => $clickCount
            ]
        ]);
    }

    /**
     * Dispatch a key event
     * 
     * @param string $type ('keyDown', 'keyUp', 'rawKeyDown', 'char')
     * @param string|null $text
     * @param string|null $key
     * @param int|null $keyCode
     * @return object
     * @throws Exception
     */
    public function dispatchKeyEvent(string $type, ?string $text = null, ?string $key = null, ?int $keyCode = null): object
    {
        $params = ['type' => $type];
        if ($text !== null) {
            $params['text'] = $text;
        }
        if ($key !== null) {
            $params['key'] = $key;
        }
        if ($keyCode !== null) {
            $params['windowsVirtualKeyCode'] = $keyCode;
        }

        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Input.dispatchKeyEvent',
            'params' => $params
        ]);
    }

    /**
     * Insert text directly
     * 
     * @param string $text
     * @return object
     * @throws Exception
     */
    public function insertText(string $text): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'Input.insertText',
            'params' => [
                'text' => $text
            ]
        ]);
    }

    /**
     * Get DOM Document node
     * 
     * @param int $depth
     * @return object
     * @throws Exception
     */
    public function getDocument(int $depth = -1): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'DOM.getDocument',
            'params' => ['depth' => $depth]
        ]);
    }

    /**
     * Query selector
     * 
     * @param int $nodeId
     * @param string $selector
     * @return object
     * @throws Exception
     */
    public function querySelector(int $nodeId, string $selector): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'DOM.querySelector',
            'params' => [
                'nodeId' => $nodeId,
                'selector' => $selector
            ]
        ]);
    }

    /**
     * Query selector all
     * 
     * @param int $nodeId
     * @param string $selector
     * @return object
     * @throws Exception
     */
    public function querySelectorAll(int $nodeId, string $selector): object
    {
        return $this->sendMessage([
            'id' => $this->getNextId(),
            'sessionId' => $this->sessionId,
            'method' => 'DOM.querySelectorAll',
            'params' => [
                'nodeId' => $nodeId,
                'selector' => $selector
            ]
        ]);
    }

    // --- Runtime (extended) ---

    /**
     * Call a JavaScript function on a remote object
     * 
     * @param string $functionDeclaration The function declaration
     * @param string $objectId The object ID to call the function on
     * @param array $arguments The function arguments
     * @param bool $returnByValue Whether to return the result by value
     * 
     * @return mixed The function call result
     */
    public function callFunctionOn(string $functionDeclaration, string $objectId, array $arguments = [], bool $returnByValue = true): mixed
    {
        $params = [
            'functionDeclaration' => $functionDeclaration,
            'objectId' => $objectId,
            'arguments' => $arguments,
            'returnByValue' => $returnByValue,
        ];

        return $this->call('Runtime.callFunctionOn', $params);
    }

    /**
     * Wait for a promise to resolve
     * 
     * @param string $promiseObjectId The promise object ID
     * @param bool $returnByValue Whether to return the result by value
     * 
     * @return mixed The promise resolution result
     */
    public function awaitPromise(string $promiseObjectId, bool $returnByValue = true): mixed
    {
        return $this->call('Runtime.awaitPromise', [
            'promiseObjectId' => $promiseObjectId,
            'returnByValue' => $returnByValue,
        ]);
    }

    /**
     * Get properties of a remote object
     * 
     * @param string $objectId The object ID
     * @param bool $ownProperties Whether to include only own properties
     * 
     * @return mixed The object properties
     */
    public function getProperties(string $objectId, bool $ownProperties = true): mixed
    {
        return $this->call('Runtime.getProperties', [
            'objectId' => $objectId,
            'ownProperties' => $ownProperties,
        ]);
    }

    /**
     * Release a remote object
     * 
     * @param string $objectId The object ID to release
     * 
     * @return mixed The response
     */
    public function releaseObject(string $objectId): mixed
    {
        return $this->call('Runtime.releaseObject', ['objectId' => $objectId]);
    }

    /**
     * Release an entire object group
     * 
     * @param string $objectGroup The object group to release
     * 
     * @return mixed The response
     */
    public function releaseObjectGroup(string $objectGroup): mixed
    {
        return $this->call('Runtime.releaseObjectGroup', ['objectGroup' => $objectGroup]);
    }

    /**
     * Query objects by prototype
     * 
     * @param string $prototypeObjectId The prototype object ID
     * @param string $objectGroup Optional object group for result objects
     * 
     * @return mixed The array of queried object IDs
     */
    public function queryObjects(string $prototypeObjectId, string $objectGroup = ''): mixed
    {
        $params = ['prototypeObjectId' => $prototypeObjectId];

        if ($objectGroup !== '') {
            $params['objectGroup'] = $objectGroup;
        }

        return $this->call('Runtime.queryObjects', $params);
    }

    /**
     * Get the current heap usage statistics
     * 
     * @return mixed The heap usage data
     */
    public function getHeapUsage(): mixed
    {
        return $this->call('Runtime.getHeapUsage');
    }

    /**
     * Get global lexical scope names
     * 
     * @param int $executionContextId Optional execution context ID
     * 
     * @return mixed The global scope names
     */
    public function globalLexicalScopeNames(int $executionContextId = 0): mixed
    {
        $params = [];

        if ($executionContextId > 0) {
            $params['executionContextId'] = $executionContextId;
        }

        return $this->call('Runtime.globalLexicalScopeNames', $params);
    }

    /**
     * Compile a JavaScript script
     * 
     * @param string $expression The script expression
     * @param string $sourceURL The source URL for the script
     * @param bool $persistScript Whether to persist the script for later execution
     * @param int $executionContextId Optional execution context ID
     * 
     * @return mixed The compiled script ID and result
     */
    public function compileScript(string $expression, string $sourceURL, bool $persistScript = false, int $executionContextId = 0): mixed
    {
        $params = [
            'expression' => $expression,
            'sourceURL' => $sourceURL,
            'persistScript' => $persistScript,
        ];

        if ($executionContextId > 0) {
            $params['executionContextId'] = $executionContextId;
        }

        return $this->call('Runtime.compileScript', $params);
    }

    /**
     * Run a compiled script
     * 
     * @param string $scriptId The compiled script ID
     * @param int $executionContextId Optional execution context ID
     * @param bool $returnByValue Whether to return the result by value
     * 
     * @return mixed The script execution result
     */
    public function runScript(string $scriptId, int $executionContextId = 0, bool $returnByValue = true): mixed
    {
        $params = [
            'scriptId' => $scriptId,
            'returnByValue' => $returnByValue,
        ];

        if ($executionContextId > 0) {
            $params['executionContextId'] = $executionContextId;
        }

        return $this->call('Runtime.runScript', $params);
    }

    /**
     * Disable the Runtime domain
     * 
     * @return mixed The response
     */
    public function disableRuntime(): mixed
    {
        return $this->call('Runtime.disable');
    }

    /**
     * Add a binding name to be exposed in the global scope
     * 
     * @param string $name The binding name
     * @param int $executionContextId Optional execution context ID
     * 
     * @return mixed The response
     */
    public function addBinding(string $name, int $executionContextId = 0): mixed
    {
        $params = ['name' => $name];

        if ($executionContextId > 0) {
            $params['executionContextId'] = $executionContextId;
        }

        return $this->call('Runtime.addBinding', $params);
    }

    /**
     * Remove a binding name
     * 
     * @param string $name The binding name to remove
     * 
     * @return mixed The response
     */
    public function removeBinding(string $name): mixed
    {
        return $this->call('Runtime.removeBinding', ['name' => $name]);
    }

    /**
     * Set custom object formatter enabled or disabled
     * 
     * @param bool $enabled Whether to enable the custom object formatter
     * 
     * @return mixed The response
     */
    public function setCustomObjectFormatterEnabled(bool $enabled): mixed
    {
        return $this->call('Runtime.setCustomObjectFormatterEnabled', ['enabled' => $enabled]);
    }

    /**
     * Set the maximum call stack size to capture
     * 
     * @param int $size The maximum call stack size
     * 
     * @return mixed The response
     */
    public function setMaxCallStackSizeToCapture(int $size): mixed
    {
        return $this->call('Runtime.setMaxCallStackSizeToCapture', ['size' => $size]);
    }

    /**
     * Terminate execution in the runtime
     * 
     * @return mixed The response
     */
    public function terminateExecution(): mixed
    {
        return $this->call('Runtime.terminateExecution');
    }

    // --- DOM (extended) ---

    /**
     * Get the outer HTML of a DOM node by node ID
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The outer HTML of the node
     */
    public function getOuterHTMLByNodeId(int $nodeId): mixed
    {
        return $this->call('DOM.getOuterHTML', ['nodeId' => $nodeId]);
    }

    /**
     * Set an attribute value on a DOM node
     * 
     * @param int $nodeId The node ID
     * @param string $name The attribute name
     * @param string $value The attribute value
     * 
     * @return mixed The response
     */
    public function setAttributeValue(int $nodeId, string $name, string $value): mixed
    {
        return $this->call('DOM.setAttributeValue', [
            'nodeId' => $nodeId,
            'name' => $name,
            'value' => $value,
        ]);
    }

    /**
     * Set attributes on a DOM node as text
     * 
     * @param int $nodeId The node ID
     * @param string $text The attributes text
     * @param string $name Optional specific attribute name to set
     * 
     * @return mixed The response
     */
    public function setAttributesAsText(int $nodeId, string $text, string $name = ''): mixed
    {
        $params = ['nodeId' => $nodeId, 'text' => $text];

        if ($name !== '') {
            $params['name'] = $name;
        }

        return $this->call('DOM.setAttributesAsText', $params);
    }

    /**
     * Remove an attribute from a DOM node
     * 
     * @param int $nodeId The node ID
     * @param string $name The attribute name to remove
     * 
     * @return mixed The response
     */
    public function removeAttribute(int $nodeId, string $name): mixed
    {
        return $this->call('DOM.removeAttribute', [
            'nodeId' => $nodeId,
            'name' => $name,
        ]);
    }

    /**
     * Get all attributes of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The node attributes
     */
    public function getAttributes(int $nodeId): mixed
    {
        return $this->call('DOM.getAttributes', ['nodeId' => $nodeId]);
    }

    /**
     * Get the box model of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The box model (margin, border, padding, content)
     */
    public function getBoxModel(int $nodeId): mixed
    {
        return $this->call('DOM.getBoxModel', ['nodeId' => $nodeId]);
    }

    /**
     * Set focus on a DOM node
     * 
     * @param int $nodeId The node ID to focus
     * 
     * @return mixed The response
     */
    public function focusNode(int $nodeId): mixed
    {
        return $this->call('DOM.focus', ['nodeId' => $nodeId]);
    }

    /**
     * Scroll a DOM node into view if it's not visible
     * 
     * @param int $nodeId The node ID to scroll into view
     * 
     * @return mixed The response
     */
    public function scrollIntoViewIfNeeded(int $nodeId): mixed
    {
        return $this->call('DOM.scrollIntoViewIfNeeded', ['nodeId' => $nodeId]);
    }

    /**
     * Set the text content of a DOM node
     * 
     * @param int $nodeId The node ID
     * @param string $value The new text value
     * 
     * @return mixed The response
     */
    public function setNodeValue(int $nodeId, string $value): mixed
    {
        return $this->call('DOM.setNodeValue', [
            'nodeId' => $nodeId,
            'value' => $value,
        ]);
    }

    /**
     * Remove a DOM node from its parent
     * 
     * @param int $nodeId The node ID to remove
     * 
     * @return mixed The response
     */
    public function removeNode(int $nodeId): mixed
    {
        return $this->call('DOM.removeNode', ['nodeId' => $nodeId]);
    }

    /**
     * Move a DOM node to a new location in the tree
     * 
     * @param int $nodeId The node ID to move
     * @param int $targetNodeId The target parent node ID
     * @param int $insertBeforeNodeId Optional node ID to insert before
     * 
     * @return mixed The response
     */
    public function moveTo(int $nodeId, int $targetNodeId, int $insertBeforeNodeId = 0): mixed
    {
        $params = ['nodeId' => $nodeId, 'targetNodeId' => $targetNodeId];

        if ($insertBeforeNodeId > 0) {
            $params['insertBeforeNodeId'] = $insertBeforeNodeId;
        }

        return $this->call('DOM.moveTo', $params);
    }

    /**
     * Copy a DOM node to a new location in the tree
     * 
     * @param int $nodeId The node ID to copy
     * @param int $targetNodeId The target parent node ID
     * @param int $insertBeforeNodeId Optional node ID to insert before
     * 
     * @return mixed The response
     */
    public function copyTo(int $nodeId, int $targetNodeId, int $insertBeforeNodeId = 0): mixed
    {
        $params = ['nodeId' => $nodeId, 'targetNodeId' => $targetNodeId];

        if ($insertBeforeNodeId > 0) {
            $params['insertBeforeNodeId'] = $insertBeforeNodeId;
        }

        return $this->call('DOM.copyTo', $params);
    }

    /**
     * Request a DOM node from a remote object ID
     * 
     * @param string $objectId The remote object ID
     * 
     * @return mixed The requested node ID
     */
    public function requestNode(string $objectId): mixed
    {
        return $this->call('DOM.requestNode', ['objectId' => $objectId]);
    }

    /**
     * Resolve a DOM node to a remote object
     * 
     * @param int $nodeId The node ID to resolve
     * @param string $objectGroup Optional object group for the resulting object
     * 
     * @return mixed The remote object
     */
    public function resolveNode(int $nodeId, string $objectGroup = ''): mixed
    {
        $params = ['nodeId' => $nodeId];

        if ($objectGroup !== '') {
            $params['objectGroup'] = $objectGroup;
        }

        return $this->call('DOM.resolveNode', $params);
    }

    /**
     * Get a detailed description of a DOM node and its subtree
     * 
     * @param int $nodeId The node ID to describe
     * @param int $depth The maximum depth of the subtree to include
     * 
     * @return mixed The node description
     */
    public function describeNode(int $nodeId, int $depth = -1): mixed
    {
        return $this->call('DOM.describeNode', [
            'nodeId' => $nodeId,
            'depth' => $depth,
        ]);
    }

    /**
     * Set the outer HTML of a DOM node
     * 
     * @param int $nodeId The node ID
     * @param string $outerHTML The new outer HTML
     * 
     * @return mixed The response
     */
    public function setOuterHTML(int $nodeId, string $outerHTML): mixed
    {
        return $this->call('DOM.setOuterHTML', [
            'nodeId' => $nodeId,
            'outerHTML' => $outerHTML,
        ]);
    }

    /**
     * Set the inspected node in the DevTools
     * 
     * @param int $nodeId The node ID to inspect
     * 
     * @return mixed The response
     */
    public function setInspectedNode(int $nodeId): mixed
    {
        return $this->call('DOM.setInspectedNode', ['nodeId' => $nodeId]);
    }

    /**
     * Get the DOM node at a specific position
     * 
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param bool $includeUserAgentShadowDOM Whether to include shadow DOM
     * 
     * @return mixed The node at the location
     */
    public function getNodeForLocation(int $x, int $y, bool $includeUserAgentShadowDOM = false): mixed
    {
        return $this->call('DOM.getNodeForLocation', [
            'x' => $x,
            'y' => $y,
            'includeUserAgentShadowDOM' => $includeUserAgentShadowDOM,
        ]);
    }

    /**
     * Get the content quads (bounding boxes) of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The content quads
     */
    public function getContentQuads(int $nodeId): mixed
    {
        return $this->call('DOM.getContentQuads', ['nodeId' => $nodeId]);
    }

    /**
     * Request child nodes of a DOM node
     * 
     * @param int $nodeId The parent node ID
     * @param int $depth The depth of children to include
     * @param bool $pierce Whether to pierce into shadow DOM
     * 
     * @return mixed The response
     */
    public function requestChildNodes(int $nodeId, int $depth = -1, bool $pierce = false): mixed
    {
        return $this->call('DOM.requestChildNodes', [
            'nodeId' => $nodeId,
            'depth' => $depth,
            'pierce' => $pierce,
        ]);
    }

    /**
     * Set files in a file input element
     * 
     * @param array $files The file paths to set
     * @param int $nodeId The file input node ID
     * 
     * @return mixed The response
     */
    public function setFileInputFiles(array $files, int $nodeId): mixed
    {
        return $this->call('DOM.setFileInputFiles', [
            'files' => $files,
            'nodeId' => $nodeId,
        ]);
    }

    /**
     * Get search results from a previous DOM search
     * 
     * @param string $searchId The search ID
     * @param int $fromIndex The starting result index
     * @param int $toIndex The ending result index
     * 
     * @return mixed The search results
     */
    public function getSearchResults(string $searchId, int $fromIndex, int $toIndex): mixed
    {
        return $this->call('DOM.getSearchResults', [
            'searchId' => $searchId,
            'fromIndex' => $fromIndex,
            'toIndex' => $toIndex,
        ]);
    }

    /**
     * Perform a search in the DOM tree
     * 
     * @param string $query The search query
     * @param bool $includeUserAgentShadowDOM Whether to include shadow DOM
     * 
     * @return mixed The search ID and result count
     */
    public function performSearch(string $query, bool $includeUserAgentShadowDOM = false): mixed
    {
        return $this->call('DOM.performSearch', [
            'query' => $query,
            'includeUserAgentShadowDOM' => $includeUserAgentShadowDOM,
        ]);
    }

    /**
     * Discard a DOM search and cleanup resources
     * 
     * @param string $searchId The search ID to discard
     * 
     * @return mixed The response
     */
    public function discardSearchResults(string $searchId): mixed
    {
        return $this->call('DOM.discardSearchResults', ['searchId' => $searchId]);
    }

    /**
     * Collect all class names from a DOM subtree
     * 
     * @param int $nodeId The root node ID
     * 
     * @return mixed The list of class names
     */
    public function collectClassNamesFromSubtree(int $nodeId): mixed
    {
        return $this->call('DOM.collectClassNamesFromSubtree', ['nodeId' => $nodeId]);
    }

    /**
     * Get the relayout boundary node
     * 
     * @param int $nodeId The node ID to check
     * 
     * @return mixed The relayout boundary node ID
     */
    public function getRelayoutBoundary(int $nodeId): mixed
    {
        return $this->call('DOM.getRelayoutBoundary', ['nodeId' => $nodeId]);
    }

    /**
     * Enable the DOM domain
     * 
     * @return mixed The response
     */
    public function enableDOM(): mixed
    {
        return $this->call('DOM.enable');
    }

    /**
     * Disable the DOM domain
     * 
     * @return mixed The response
     */
    public function disableDOM(): mixed
    {
        return $this->call('DOM.disable');
    }

    // --- CSS ---

    /**
     * Enable the CSS domain
     * 
     * @return mixed The response
     */
    public function enableCSS(): mixed
    {
        return $this->call('CSS.enable');
    }

    /**
     * Disable the CSS domain
     * 
     * @return mixed The response
     */
    public function disableCSS(): mixed
    {
        return $this->call('CSS.disable');
    }

    /**
     * Get the computed style of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The computed style
     */
    public function getComputedStyleForNode(int $nodeId): mixed
    {
        return $this->call('CSS.getComputedStyleForNode', ['nodeId' => $nodeId]);
    }

    /**
     * Get the inline styles of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The inline styles
     */
    public function getInlineStylesForNode(int $nodeId): mixed
    {
        return $this->call('CSS.getInlineStylesForNode', ['nodeId' => $nodeId]);
    }

    /**
     * Get all matched styles for a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The matched styles
     */
    public function getMatchedStylesForNode(int $nodeId): mixed
    {
        return $this->call('CSS.getMatchedStylesForNode', ['nodeId' => $nodeId]);
    }

    /**
     * Get the text content of a stylesheet
     * 
     * @param string $styleSheetId The stylesheet ID
     * 
     * @return mixed The stylesheet text
     */
    public function getStyleSheetText(string $styleSheetId): mixed
    {
        return $this->call('CSS.getStyleSheetText', ['styleSheetId' => $styleSheetId]);
    }

    /**
     * Set the text content of a stylesheet
     * 
     * @param string $styleSheetId The stylesheet ID
     * @param string $text The new stylesheet text
     * 
     * @return mixed The response
     */
    public function setStyleSheetText(string $styleSheetId, string $text): mixed
    {
        return $this->call('CSS.setStyleSheetText', [
            'styleSheetId' => $styleSheetId,
            'text' => $text,
        ]);
    }

    /**
     * Set multiple styles at once
     * 
     * @param array $edits Array of style edits
     * 
     * @return mixed The response
     */
    public function setStyleTexts(array $edits): mixed
    {
        return $this->call('CSS.setStyleTexts', ['edits' => $edits]);
    }

    /**
     * Add a new CSS rule to a stylesheet
     * 
     * @param string $styleSheetId The stylesheet ID
     * @param string $ruleText The CSS rule text
     * @param array $location The location to insert the rule
     * 
     * @return mixed The new rule
     */
    public function addRule(string $styleSheetId, string $ruleText, array $location): mixed
    {
        return $this->call('CSS.addRule', [
            'styleSheetId' => $styleSheetId,
            'ruleText' => $ruleText,
            'location' => $location,
        ]);
    }

    /**
     * Create a new stylesheet in a frame
     * 
     * @param string $frameId The frame ID
     * 
     * @return mixed The new stylesheet ID
     */
    public function createStyleSheet(string $frameId): mixed
    {
        return $this->call('CSS.createStyleSheet', ['frameId' => $frameId]);
    }

    /**
     * Force specified pseudo-classes on DOM elements
     * 
     * @param int $nodeId The node ID
     * @param array $forcedPseudoClasses Array of pseudo-classes to force
     * 
     * @return mixed The response
     */
    public function forcePseudoState(int $nodeId, array $forcedPseudoClasses): mixed
    {
        return $this->call('CSS.forcePseudoState', [
            'nodeId' => $nodeId,
            'forcedPseudoClasses' => $forcedPseudoClasses,
        ]);
    }

    /**
     * Get the background colors of a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The background colors
     */
    public function getBackgroundColors(int $nodeId): mixed
    {
        return $this->call('CSS.getBackgroundColors', ['nodeId' => $nodeId]);
    }

    /**
     * Get the platform fonts used by a DOM node
     * 
     * @param int $nodeId The node ID
     * 
     * @return mixed The platform fonts
     */
    public function getPlatformFontsForNode(int $nodeId): mixed
    {
        return $this->call('CSS.getPlatformFontsForNode', ['nodeId' => $nodeId]);
    }

    /**
     * Get all media queries in the stylesheet
     * 
     * @return mixed The media queries
     */
    public function getMediaQueries(): mixed
    {
        return $this->call('CSS.getMediaQueries');
    }

    /**
     * Start tracking CSS rule coverage
     * 
     * @return mixed The response
     */
    public function startRuleUsageTracking(): mixed
    {
        return $this->call('CSS.startRuleUsageTracking');
    }

    /**
     * Stop tracking CSS rule coverage
     * 
     * @return mixed The response
     */
    public function stopRuleUsageTracking(): mixed
    {
        return $this->call('CSS.stopRuleUsageTracking');
    }

    /**
     * Get CSS coverage delta since last call
     * 
     * @return mixed The coverage delta
     */
    public function takeCoverageDelta(): mixed
    {
        return $this->call('CSS.takeCoverageDelta');
    }

    // --- Page (extended) ---

    /**
     * Render the current page as a PDF and return base64 payload.
     *
     * @param array $options
     *
     * @return string|null
     */
    public function printToPDF(array $options = []): ?string
    {
        $defaults = [
            'landscape' => false,
            'displayHeaderFooter' => false,
            'printBackground' => true,
            'preferCSSPageSize' => false,
        ];
        $response = $this->call('Page.printToPDF', array_merge($defaults, $options));
        return $response->result->data ?? null;
    }

    /**
     * Get the frame tree of the page
     * 
     * @return mixed The frame tree
     */
    public function getFrameTree(): mixed
    {
        return $this->call('Page.getFrameTree');
    }

    /**
     * Get the navigation history of the current frame
     * 
     * @return mixed The navigation history
     */
    public function getNavigationHistory(): mixed
    {
        return $this->call('Page.getNavigationHistory');
    }

    /**
     * Navigate to a specific entry in the history
     * 
     * @param int $entryId The history entry ID
     * 
     * @return mixed The response
     */
    public function navigateToHistoryEntry(int $entryId): mixed
    {
        return $this->call('Page.navigateToHistoryEntry', ['entryId' => $entryId]);
    }

    /**
     * Set the HTML content of a frame
     * 
     * @param string $frameId The frame ID
     * @param string $html The HTML content
     * 
     * @return mixed The response
     */
    public function setDocumentContent(string $frameId, string $html): mixed
    {
        return $this->call('Page.setDocumentContent', [
            'frameId' => $frameId,
            'html' => $html,
        ]);
    }

    /**
     * Stop loading the page
     * 
     * @return mixed The response
     */
    public function stopLoading(): mixed
    {
        return $this->call('Page.stopLoading');
    }

    /**
     * Bring the page to the front (activate the tab)
     * 
     * @return mixed The response
     */
    public function bringToFront(): mixed
    {
        return $this->call('Page.bringToFront');
    }

    /**
     * Close the page (close the tab)
     * 
     * @return mixed The response
     */
    public function closePage(): mixed
    {
        return $this->call('Page.close');
    }

    /**
     * Get the layout metrics of the page
     * 
     * @return mixed The layout metrics
     */
    public function getLayoutMetrics(): mixed
    {
        return $this->call('Page.getLayoutMetrics');
    }

    /**
     * Add a script to be evaluated on every new document
     * 
     * @param string $source The script source code
     * @param string $worldName Optional world name (isolated context)
     * 
     * @return mixed The script identifier
     */
    public function addScriptToEvaluateOnNewDocument(string $source, string $worldName = ''): mixed
    {
        $params = ['source' => $source];

        if ($worldName !== '') {
            $params['worldName'] = $worldName;
        }

        return $this->call('Page.addScriptToEvaluateOnNewDocument', $params);
    }

    /**
     * Remove a script from being evaluated on new documents
     * 
     * @param string $identifier The script identifier
     * 
     * @return mixed The response
     */
    public function removeScriptToEvaluateOnNewDocument(string $identifier): mixed
    {
        return $this->call('Page.removeScriptToEvaluateOnNewDocument', ['identifier' => $identifier]);
    }

    /**
     * Handle a JavaScript dialog (alert, confirm, prompt)
     * 
     * @param bool $accept Whether to accept the dialog
     * @param string $promptText Text to enter for prompt dialogs
     * 
     * @return mixed The response
     */
    public function handleJavaScriptDialog(bool $accept, string $promptText = ''): mixed
    {
        $params = ['accept' => $accept];

        if ($promptText !== '') {
            $params['promptText'] = $promptText;
        }

        return $this->call('Page.handleJavaScriptDialog', $params);
    }

    /**
     * Set whether to fire lifecycle events
     * 
     * @param bool $enabled Whether to enable lifecycle events
     * 
     * @return mixed The response
     */
    public function setLifecycleEventsEnabled(bool $enabled): mixed
    {
        return $this->call('Page.setLifecycleEventsEnabled', ['enabled' => $enabled]);
    }

    /**
     * Create an isolated world (execution context) in a frame
     * 
     * @param string $frameId The frame ID
     * @param string $worldName Optional world name
     * @param bool $grantUniveralAccess Whether to grant universal access
     * 
     * @return mixed The isolated world context ID
     */
    public function createIsolatedWorld(string $frameId, string $worldName = '', bool $grantUniveralAccess = false): mixed
    {
        $params = ['frameId' => $frameId];

        if ($worldName !== '') {
            $params['worldName'] = $worldName;
        }

        $params['grantUniveralAccess'] = $grantUniveralAccess;

        return $this->call('Page.createIsolatedWorld', $params);
    }

    /**
     * Get the resource tree of the page
     * 
     * @return mixed The resource tree
     */
    public function getResourceTree(): mixed
    {
        return $this->call('Page.getResourceTree');
    }

    /**
     * Get the content of a resource
     * 
     * @param string $frameId The frame ID
     * @param string $url The resource URL
     * 
     * @return mixed The resource content
     */
    public function getResourceContent(string $frameId, string $url): mixed
    {
        return $this->call('Page.getResourceContent', [
            'frameId' => $frameId,
            'url' => $url,
        ]);
    }

    /**
     * Search for text in a resource
     * 
     * @param string $frameId The frame ID
     * @param string $url The resource URL
     * @param string $query The search query
     * @param bool $caseSensitive Whether the search is case-sensitive
     * @param bool $isRegex Whether to use regex pattern
     * 
     * @return mixed The search matches
     */
    public function searchInResource(string $frameId, string $url, string $query, bool $caseSensitive = false, bool $isRegex = false): mixed
    {
        return $this->call('Page.searchInResource', [
            'frameId' => $frameId,
            'url' => $url,
            'query' => $query,
            'caseSensitive' => $caseSensitive,
            'isRegex' => $isRegex,
        ]);
    }

    /**
     * Set whether ad blocking is enabled
     * 
     * @param bool $enabled Whether to enable ad blocking
     * 
     * @return mixed The response
     */
    public function setAdBlockingEnabled(bool $enabled): mixed
    {
        return $this->call('Page.setAdBlockingEnabled', ['enabled' => $enabled]);
    }

    /**
     * Set whether to bypass Content Security Policy
     * 
     * @param bool $enabled Whether to bypass CSP
     * 
     * @return mixed The response
     */
    public function setBypassCSP(bool $enabled): mixed
    {
        return $this->call('Page.setBypassCSP', ['enabled' => $enabled]);
    }

    /**
     * Set the font families for the page
     * 
     * @param array $fontFamilies The font families to set
     * 
     * @return mixed The response
     */
    public function setFontFamilies(array $fontFamilies): mixed
    {
        return $this->call('Page.setFontFamilies', ['fontFamilies' => $fontFamilies]);
    }

    /**
     * Set the font sizes for the page
     * 
     * @param array $fontSizes The font sizes to set
     * 
     * @return mixed The response
     */
    public function setFontSizes(array $fontSizes): mixed
    {
        return $this->call('Page.setFontSizes', ['fontSizes' => $fontSizes]);
    }

    /**
     * Set whether to intercept file chooser dialog
     * 
     * @param bool $enabled Whether to intercept
     * 
     * @return mixed The response
     */
    public function setInterceptFileChooserDialog(bool $enabled): mixed
    {
        return $this->call('Page.setInterceptFileChooserDialog', ['enabled' => $enabled]);
    }

    /**
     * Disable the Page domain
     * 
     * @return mixed The response
     */
    public function disablePage(): mixed
    {
        return $this->call('Page.disable');
    }

    // --- Network (extended) ---

    /**
     * Set a cookie in the browser
     * 
     * @param string $name The cookie name
     * @param string $value The cookie value
     * @param string $url Optional URL for the cookie
     * @param string $domain Optional domain
     * @param string $path Optional path
     * @param bool $secure Whether the cookie is secure
     * @param bool $httpOnly Whether the cookie is HTTP-only
     * @param string $sameSite The SameSite attribute
     * @param float $expires The cookie expiration time
     * 
     * @return mixed The response
     */
    public function setCookie(string $name, string $value, string $url = '', string $domain = '', string $path = '', bool $secure = false, bool $httpOnly = false, string $sameSite = '', float $expires = -1): mixed
    {
        $params = ['name' => $name, 'value' => $value];

        if ($url !== '') {
            $params['url'] = $url;
        }

        if ($domain !== '') {
            $params['domain'] = $domain;
        }

        if ($path !== '') {
            $params['path'] = $path;
        }

        if ($secure) {
            $params['secure'] = true;
        }

        if ($httpOnly) {
            $params['httpOnly'] = true;
        }

        if ($sameSite !== '') {
            $params['sameSite'] = $sameSite;
        }

        if ($expires >= 0) {
            $params['expires'] = $expires;
        }

        return $this->call('Network.setCookie', $params);
    }

    /**
     * Set multiple cookies at once
     * 
     * @param array $cookies The cookies to set
     * 
     * @return mixed The response
     */
    public function setCookies(array $cookies): mixed
    {
        return $this->call('Network.setCookies', ['cookies' => $cookies]);
    }

    /**
     * Get all cookies for specified URLs
     * 
     * @param array $urls Optional URLs to filter cookies
     * 
     * @return mixed The cookies
     */
    public function getCookies(array $urls = []): mixed
    {
        $params = [];

        if (!empty($urls)) {
            $params['urls'] = $urls;
        }

        return $this->call('Network.getCookies', $params);
    }

    /**
     * Delete a cookie by name
     * 
     * @param string $name The cookie name
     * @param string $url Optional URL to filter
     * @param string $domain Optional domain to filter
     * @param string $path Optional path to filter
     * 
     * @return mixed The response
     */
    public function deleteCookies(string $name, string $url = '', string $domain = '', string $path = ''): mixed
    {
        $params = ['name' => $name];

        if ($url !== '') {
            $params['url'] = $url;
        }

        if ($domain !== '') {
            $params['domain'] = $domain;
        }

        if ($path !== '') {
            $params['path'] = $path;
        }

        return $this->call('Network.deleteCookies', $params);
    }

    /**
     * Set extra HTTP headers for all requests
     * 
     * @param array $headers The headers to add
     * 
     * @return mixed The response
     */
    public function setExtraHTTPHeaders(array $headers): mixed
    {
        return $this->call('Network.setExtraHTTPHeaders', ['headers' => $headers]);
    }

    /**
     * Get the response body of a network request
     * 
     * @param string $requestId The request ID
     * 
     * @return mixed The response body
     */
    public function getResponseBody(string $requestId): mixed
    {
        return $this->call('Network.getResponseBody', ['requestId' => $requestId]);
    }

    /**
     * Get the POST data of a network request
     * 
     * @param string $requestId The request ID
     * 
     * @return mixed The POST data
     */
    public function getRequestPostData(string $requestId): mixed
    {
        return $this->call('Network.getRequestPostData', ['requestId' => $requestId]);
    }

    /**
     * Emulate network conditions
     * 
     * @param bool $offline Whether to go offline
     * @param float $latency The latency in milliseconds
     * @param float $downloadThroughput The download speed in bytes/sec
     * @param float $uploadThroughput The upload speed in bytes/sec
     * @param string $connectionType Optional connection type
     * 
     * @return mixed The response
     */
    public function emulateNetworkConditions(bool $offline, float $latency, float $downloadThroughput, float $uploadThroughput, string $connectionType = ''): mixed
    {
        $params = [
            'offline' => $offline,
            'latency' => $latency,
            'downloadThroughput' => $downloadThroughput,
            'uploadThroughput' => $uploadThroughput,
        ];

        if ($connectionType !== '') {
            $params['connectionType'] = $connectionType;
        }

        return $this->call('Network.emulateNetworkConditions', $params);
    }

    /**
     * Set URLs to be blocked from loading
     * 
     * @param array $urls The URLs to block
     * 
     * @return mixed The response
     */
    public function setBlockedURLs(array $urls): mixed
    {
        return $this->call('Network.setBlockedURLs', ['urls' => $urls]);
    }

    /**
     * Set whether to disable the browser cache
     * 
     * @param bool $cacheDisabled Whether to disable cache
     * 
     * @return mixed The response
     */
    public function setCacheDisabled(bool $cacheDisabled): mixed
    {
        return $this->call('Network.setCacheDisabled', ['cacheDisabled' => $cacheDisabled]);
    }

    /**
     * Set whether to bypass service workers
     * 
     * @param bool $bypass Whether to bypass service workers
     * 
     * @return mixed The response
     */
    public function setBypassServiceWorker(bool $bypass): mixed
    {
        return $this->call('Network.setBypassServiceWorker', ['bypass' => $bypass]);
    }

    /**
     * Search in a response body
     * 
     * @param string $requestId The request ID
     * @param string $query The search query
     * @param bool $caseSensitive Whether the search is case-sensitive
     * @param bool $isRegex Whether to use regex pattern
     * 
     * @return mixed The search matches
     */
    public function searchInResponseBody(string $requestId, string $query, bool $caseSensitive = false, bool $isRegex = false): mixed
    {
        return $this->call('Network.searchInResponseBody', [
            'requestId' => $requestId,
            'query' => $query,
            'caseSensitive' => $caseSensitive,
            'isRegex' => $isRegex,
        ]);
    }

    /**
     * Set request interception patterns
     * 
     * @param array $patterns The URL patterns to intercept
     * 
     * @return mixed The response
     */
    public function setRequestInterception(array $patterns): mixed
    {
        return $this->call('Network.setRequestInterception', ['patterns' => $patterns]);
    }

    /**
     * Continue an intercepted request
     * 
     * @param string $interceptionId The interception ID
     * @param array $options Optional modifications to the request
     * 
     * @return mixed The response
     */
    public function continueInterceptedRequest(string $interceptionId, array $options = []): mixed
    {
        $params = array_merge(['interceptionId' => $interceptionId], $options);
        return $this->call('Network.continueInterceptedRequest', $params);
    }

    /**
     * Get all cookies in the browser
     * 
     * @return mixed The all cookies
     */
    public function getAllCookies(): mixed
    {
        return $this->call('Network.getAllCookies');
    }

    /**
     * Get security isolation status for a frame
     * 
     * @param string $frameId Optional frame ID
     * 
     * @return mixed The security isolation status
     */
    public function getSecurityIsolationStatus(string $frameId = ''): mixed
    {
        $params = [];

        if ($frameId !== '') {
            $params['frameId'] = $frameId;
        }

        return $this->call('Network.getSecurityIsolationStatus', $params);
    }

    /**
     * Disable the Network domain
     * 
     * @return mixed The response
     */
    public function disableNetwork(): mixed
    {
        return $this->call('Network.disable');
    }

    /**
     * Set device metrics override for responsive design
     * 
     * @param int $width The viewport width
     * @param int $height The viewport height
     * @param float $deviceScaleFactor The device scale factor
     * @param bool $mobile Whether to emulate mobile
     * @param int $screenWidth Optional screen width
     * @param int $screenHeight Optional screen height
     * 
     * @return mixed The response
     */
    public function setDeviceMetricsOverride(int $width, int $height, float $deviceScaleFactor = 1.0, bool $mobile = false, int $screenWidth = 0, int $screenHeight = 0): mixed
    {
        $params = [
            'width' => $width,
            'height' => $height,
            'deviceScaleFactor' => $deviceScaleFactor,
            'mobile' => $mobile,
        ];

        if ($screenWidth > 0) {
            $params['screenWidth'] = $screenWidth;
        }

        if ($screenHeight > 0) {
            $params['screenHeight'] = $screenHeight;
        }

        return $this->call('Emulation.setDeviceMetricsOverride', $params);
    }

    /**
     * Clear device metrics override
     * 
     * @return mixed The response
     */
    public function clearDeviceMetricsOverride(): mixed
    {
        return $this->call('Emulation.clearDeviceMetricsOverride');
    }

    /**
     * Set geolocation override
     * 
     * @param float $latitude The latitude
     * @param float $longitude The longitude
     * @param float $accuracy The accuracy in meters
     * 
     * @return mixed The response
     */
    public function setGeolocationOverride(float $latitude = 0, float $longitude = 0, float $accuracy = 1): mixed
    {
        return $this->call('Emulation.setGeolocationOverride', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy,
        ]);
    }

    /**
     * Clear geolocation override
     * 
     * @return mixed The response
     */
    public function clearGeolocationOverride(): mixed
    {
        return $this->call('Emulation.clearGeolocationOverride');
    }

    /**
     * Set timezone override
     * 
     * @param string $timezoneId The timezone ID (e.g., 'America/New_York')
     * 
     * @return mixed The response
     */
    public function setTimezoneOverride(string $timezoneId): mixed
    {
        return $this->call('Emulation.setTimezoneOverride', ['timezoneId' => $timezoneId]);
    }

    /**
     * Set locale override
     * 
     * @param string $locale Optional locale string
     * 
     * @return mixed The response
     */
    public function setLocaleOverride(string $locale = ''): mixed
    {
        $params = [];

        if ($locale !== '') {
            $params['locale'] = $locale;
        }

        return $this->call('Emulation.setLocaleOverride', $params);
    }

    /**
     * Set touch emulation
     * 
     * @param bool $enabled Whether to enable touch emulation
     * @param int $maxTouchPoints The maximum number of touch points
     * 
     * @return mixed The response
     */
    public function setTouchEmulationEnabled(bool $enabled, int $maxTouchPoints = 1): mixed
    {
        return $this->call('Emulation.setTouchEmulationEnabled', [
            'enabled' => $enabled,
            'maxTouchPoints' => $maxTouchPoints,
        ]);
    }

    /**
     * Set emulated media type and features
     * 
     * @param string $media The media type (e.g., 'screen', 'print')
     * @param array $features Optional media features
     * 
     * @return mixed The response
     */
    public function setEmulatedMedia(string $media = '', array $features = []): mixed
    {
        $params = [];

        if ($media !== '') {
            $params['media'] = $media;
        }

        if (!empty($features)) {
            $params['features'] = $features;
        }

        return $this->call('Emulation.setEmulatedMedia', $params);
    }

    /**
     * Set emulated vision deficiency type
     * 
     * @param string $type The vision deficiency type
     * 
     * @return mixed The response
     */
    public function setEmulatedVisionDeficiency(string $type = 'none'): mixed
    {
        return $this->call('Emulation.setEmulatedVisionDeficiency', ['type' => $type]);
    }

    /**
     * Set scrollbars as hidden or visible
     * 
     * @param bool $hidden Whether to hide scrollbars
     * 
     * @return mixed The response
     */
    public function setScrollbarsHidden(bool $hidden): mixed
    {
        return $this->call('Emulation.setScrollbarsHidden', ['hidden' => $hidden]);
    }

    /**
     * Set document.cookie as disabled
     * 
     * @param bool $disabled Whether to disable cookies
     * 
     * @return mixed The response
     */
    public function setDocumentCookieDisabled(bool $disabled): mixed
    {
        return $this->call('Emulation.setDocumentCookieDisabled', ['disabled' => $disabled]);
    }

    /**
     * Set CPU throttling rate
     * 
     * @param float $rate The throttling rate (1.0 = normal, 2.0 = 2x slower)
     * 
     * @return mixed The response
     */
    public function setCPUThrottlingRate(float $rate): mixed
    {
        return $this->call('Emulation.setCPUThrottlingRate', ['rate' => $rate]);
    }

    /**
     * Set default background color override
     * 
     * @param array|null $color Optional RGBA color array
     * 
     * @return mixed The response
     */
    public function setDefaultBackgroundColorOverride(?array $color = null): mixed
    {
        $params = [];

        if ($color !== null) {
            $params['color'] = $color;
        }

        return $this->call('Emulation.setDefaultBackgroundColorOverride', $params);
    }

    /**
     * Set focus emulation
     * 
     * @param bool $enabled Whether to enable focus emulation
     * 
     * @return mixed The response
     */
    public function setFocusEmulationEnabled(bool $enabled): mixed
    {
        return $this->call('Emulation.setFocusEmulationEnabled', ['enabled' => $enabled]);
    }

    /**
     * Set idle override
     * 
     * @param bool $isUserActive Whether the user is active
     * @param bool $isScreenUnlocked Whether the screen is unlocked
     * 
     * @return mixed The response
     */
    public function setIdleOverride(bool $isUserActive, bool $isScreenUnlocked): mixed
    {
        return $this->call('Emulation.setIdleOverride', [
            'isUserActive' => $isUserActive,
            'isScreenUnlocked' => $isScreenUnlocked,
        ]);
    }

    /**
     * Clear idle override
     * 
     * @return mixed The response
     */
    public function clearIdleOverride(): mixed
    {
        return $this->call('Emulation.clearIdleOverride');
    }

    /**
     * Dispatch a touch event
     * 
     * @param string $type The touch event type
     * @param array $touchPoints The touch points
     * @param int $modifiers Optional keyboard modifiers
     * 
     * @return mixed The response
     */
    public function dispatchTouchEvent(string $type, array $touchPoints, int $modifiers = 0): mixed
    {
        return $this->call('Input.dispatchTouchEvent', [
            'type' => $type,
            'touchPoints' => $touchPoints,
            'modifiers' => $modifiers,
        ]);
    }

    /**
     * Emulate touch from mouse event
     * 
     * @param string $type The event type
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param string $button The mouse button
     * @param int $clickCount The number of clicks
     * 
     * @return mixed The response
     */
    public function emulateTouchFromMouseEvent(string $type, int $x, int $y, string $button, int $clickCount = 1): mixed
    {
        return $this->call('Input.emulateTouchFromMouseEvent', [
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'button' => $button,
            'clickCount' => $clickCount,
        ]);
    }

    /**
     * Set whether to ignore input events
     * 
     * @param bool $ignore Whether to ignore input events
     * 
     * @return mixed The response
     */
    public function setIgnoreInputEvents(bool $ignore): mixed
    {
        return $this->call('Input.setIgnoreInputEvents', ['ignore' => $ignore]);
    }

    /**
     * Set whether to intercept drag and drop events
     * 
     * @param bool $enabled Whether to intercept drags
     * 
     * @return mixed The response
     */
    public function setInterceptDrags(bool $enabled): mixed
    {
        return $this->call('Input.setInterceptDrags', ['enabled' => $enabled]);
    }

    /**
     * Dispatch a drag event
     * 
     * @param string $type The drag event type
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param array $data The drag data
     * @param int $modifiers Optional keyboard modifiers
     * 
     * @return mixed The response
     */
    public function dispatchDragEvent(string $type, int $x, int $y, array $data, int $modifiers = 0): mixed
    {
        return $this->call('Input.dispatchDragEvent', [
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'data' => $data,
            'modifiers' => $modifiers,
        ]);
    }

    /**
     * Synthesize a pinch gesture
     * 
     * @param int $x The X coordinate center
     * @param int $y The Y coordinate center
     * @param float $scaleFactor The scale factor (>1 = zoom in, <1 = zoom out)
     * @param int $relativeSpeed The gesture speed
     * 
     * @return mixed The response
     */
    public function synthesizePinchGesture(int $x, int $y, float $scaleFactor, int $relativeSpeed = 800): mixed
    {
        return $this->call('Input.synthesizePinchGesture', [
            'x' => $x,
            'y' => $y,
            'scaleFactor' => $scaleFactor,
            'relativeSpeed' => $relativeSpeed,
        ]);
    }

    /**
     * Synthesize a scroll gesture
     * 
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param int $xDistance Optional horizontal distance
     * @param int $yDistance Optional vertical distance
     * @param int $speed The gesture speed
     * 
     * @return mixed The response
     */
    public function synthesizeScrollGesture(int $x, int $y, int $xDistance = 0, int $yDistance = 0, int $speed = 800): mixed
    {
        $params = ['x' => $x, 'y' => $y];

        if ($xDistance !== 0) {
            $params['xDistance'] = $xDistance;
        }

        if ($yDistance !== 0) {
            $params['yDistance'] = $yDistance;
        }

        $params['speed'] = $speed;

        return $this->call('Input.synthesizeScrollGesture', $params);
    }

    /**
     * Synthesize a tap gesture
     * 
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param int $duration The tap duration in milliseconds
     * @param int $tapCount The number of taps
     * 
     * @return mixed The response
     */
    public function synthesizeTapGesture(int $x, int $y, int $duration = 50, int $tapCount = 1): mixed
    {
        return $this->call('Input.synthesizeTapGesture', [
            'x' => $x,
            'y' => $y,
            'duration' => $duration,
            'tapCount' => $tapCount,
        ]);
    }

    /**
     * Set IME composition
     * 
     * @param string $text The composition text
     * @param int $selectionStart The selection start
     * @param int $selectionEnd The selection end
     * @param int $replacementStart Optional replacement start
     * @param int $replacementEnd Optional replacement end
     * 
     * @return mixed The response
     */
    public function imeSetComposition(string $text, int $selectionStart, int $selectionEnd, int $replacementStart = -1, int $replacementEnd = -1): mixed
    {
        $params = [
            'text' => $text,
            'selectionStart' => $selectionStart,
            'selectionEnd' => $selectionEnd,
        ];

        if ($replacementStart >= 0) {
            $params['replacementStart'] = $replacementStart;
        }

        if ($replacementEnd >= 0) {
            $params['replacementEnd'] = $replacementEnd;
        }

        return $this->call('Input.imeSetComposition', $params);
    }

    // --- Security ---

    /**
     * Enable the DevTools Security domain.
     *
     * @return mixed
     */
    public function enableSecurity(): mixed
    {
        return $this->call('Security.enable');
    }

    /**
     * Disable the DevTools Security domain.
     *
     * @return mixed
     */
    public function disableSecurity(): mixed
    {
        return $this->call('Security.disable');
    }

    /**
     * Set whether certificate errors should be ignored.
     *
     * @param bool $ignore
     *
     * @return mixed
     */
    public function setIgnoreCertificateErrors(bool $ignore): mixed
    {
        return $this->call('Security.setIgnoreCertificateErrors', ['ignore' => $ignore]);
    }

    // --- Performance ---

    /**
     * Enable performance metrics collection.
     *
     * @param string $timeDomain
     *
     * @return mixed
     */
    public function enablePerformance(string $timeDomain = 'timeTicks'): mixed
    {
        return $this->call('Performance.enable', ['timeDomain' => $timeDomain]);
    }

    /**
     * Disable performance metrics collection.
     *
     * @return mixed
     */
    public function disablePerformance(): mixed
    {
        return $this->call('Performance.disable');
    }

    /**
     * Fetch current performance metrics.
     *
     * @return mixed
     */
    public function getPerformanceMetrics(): mixed
    {
        return $this->call('Performance.getMetrics');
    }

    // --- Log ---

    /**
     * Enable the DevTools Log domain.
     *
     * @return mixed
     */
    public function enableLog(): mixed
    {
        return $this->call('Log.enable');
    }

    /**
     * Disable the DevTools Log domain.
     *
     * @return mixed
     */
    public function disableLog(): mixed
    {
        return $this->call('Log.disable');
    }

    /**
     * Clear collected log entries.
     *
     * @return mixed
     */
    public function clearLog(): mixed
    {
        return $this->call('Log.clear');
    }

    /**
     * Start violation reporting with the provided configuration.
     *
     * @param array $config
     *
     * @return mixed
     */
    public function startViolationsReport(array $config): mixed
    {
        return $this->call('Log.startViolationsReport', ['config' => $config]);
    }

    /**
     * Stop violation reporting.
     *
     * @return mixed
     */
    public function stopViolationsReport(): mixed
    {
        return $this->call('Log.stopViolationsReport');
    }

    // --- Console ---

    /**
     * Enable the DevTools Console domain.
     *
     * @return mixed
     */
    public function enableConsole(): mixed
    {
        return $this->call('Console.enable');
    }

    /**
     * Disable the DevTools Console domain.
     *
     * @return mixed
     */
    public function disableConsole(): mixed
    {
        return $this->call('Console.disable');
    }

    /**
     * Clear console messages.
     *
     * @return mixed
     */
    public function clearConsoleMessages(): mixed
    {
        return $this->call('Console.clearMessages');
    }

    // --- Target (extended) ---

    /**
     * Close a target by id.
     *
     * @param string $targetId
     *
     * @return mixed
     */
    public function closeTarget(string $targetId): mixed
    {
        return $this->call('Target.closeTarget', ['targetId' => $targetId]);
    }

    /**
     * Get the list of available targets.
     *
     * @return mixed
     */
    public function getTargets(): mixed
    {
        return $this->call('Target.getTargets');
    }

    /**
     * Get target information for a specific target or current context.
     *
     * @param string $targetId
     *
     * @return mixed
     */
    public function getTargetInfo(string $targetId = ''): mixed
    {
        $params = [];

        if ($targetId !== '') {
            $params['targetId'] = $targetId;
        }

        return $this->call('Target.getTargetInfo', $params);
    }

    /**
     * Set whether to discover targets automatically
     * 
     * @param bool $discover Whether to discover targets
     * 
     * @return mixed The response
     */
    public function setDiscoverTargets(bool $discover): mixed
    {
        return $this->call('Target.setDiscoverTargets', ['discover' => $discover]);
    }

    /**
     * Detach from a target
     * 
     * @param string $sessionId Optional session ID
     * @param string $targetId Optional target ID
     * 
     * @return mixed The response
     */
    public function detachFromTarget(string $sessionId = '', string $targetId = ''): mixed
    {
        $params = [];

        if ($sessionId !== '') {
            $params['sessionId'] = $sessionId;
        }

        if ($targetId !== '') {
            $params['targetId'] = $targetId;
        }

        return $this->call('Target.detachFromTarget', $params);
    }

    /**
     * Activate a target (bring to foreground)
     * 
     * @param string $targetId The target ID to activate
     * 
     * @return mixed The response
     */
    public function activateTarget(string $targetId): mixed
    {
        return $this->call('Target.activateTarget', ['targetId' => $targetId]);
    }

    /**
     * Set auto-attach to new targets
     * 
     * @param bool $autoAttach Whether to auto-attach
     * @param bool $waitForDebuggerOnStart Whether to wait for debugger
     * @param bool $flatten Whether to flatten targets
     * 
     * @return mixed The response
     */
    public function setAutoAttach(bool $autoAttach, bool $waitForDebuggerOnStart, bool $flatten = true): mixed
    {
        return $this->call('Target.setAutoAttach', [
            'autoAttach' => $autoAttach,
            'waitForDebuggerOnStart' => $waitForDebuggerOnStart,
            'flatten' => $flatten,
        ]);
    }

    /**
     * Send a message to a target
     * 
     * @param string $message The message to send
     * @param string $sessionId Optional session ID
     * @param string $targetId Optional target ID
     * 
     * @return mixed The response
     */
    public function sendMessageToTarget(string $message, string $sessionId = '', string $targetId = ''): mixed
    {
        $params = ['message' => $message];

        if ($sessionId !== '') {
            $params['sessionId'] = $sessionId;
        }

        if ($targetId !== '') {
            $params['targetId'] = $targetId;
        }

        return $this->call('Target.sendMessageToTarget', $params);
    }

    /**
     * Expose the DevTools Protocol to the inspected target
     * 
     * @param string $targetId The target ID
     * @param string $bindingName Optional binding name
     * 
     * @return mixed The response
     */
    public function exposeDevToolsProtocol(string $targetId, string $bindingName = ''): mixed
    {
        $params = ['targetId' => $targetId];

        if ($bindingName !== '') {
            $params['bindingName'] = $bindingName;
        }

        return $this->call('Target.exposeDevToolsProtocol', $params);
    }

    /**
     * Create a new browser context
     * 
     * @param bool $disposeOnDetach Whether to dispose on detach
     * @param string $proxyServer Optional proxy server
     * @param string $proxyBypassList Optional proxy bypass list
     * 
     * @return mixed The browser context ID
     */
    public function createBrowserContext(bool $disposeOnDetach = false, string $proxyServer = '', string $proxyBypassList = ''): mixed
    {
        $params = ['disposeOnDetach' => $disposeOnDetach];

        if ($proxyServer !== '') {
            $params['proxyServer'] = $proxyServer;
        }

        if ($proxyBypassList !== '') {
            $params['proxyBypassList'] = $proxyBypassList;
        }

        return $this->call('Target.createBrowserContext', $params);
    }

    /**
     * Dispose a browser context
     * 
     * @param string $browserContextId The context ID
     * 
     * @return mixed The response
     */
    public function disposeBrowserContext(string $browserContextId): mixed
    {
        return $this->call('Target.disposeBrowserContext', ['browserContextId' => $browserContextId]);
    }

    /**
     * Get all browser contexts
     * 
     * @return mixed The browser contexts
     */
    public function getBrowserContexts(): mixed
    {
        return $this->call('Target.getBrowserContexts');
    }

    /**
     * Enable the Profiler domain
     * 
     * @return mixed The response
     */
    public function enableProfiler(): mixed
    {
        return $this->call('Profiler.enable');
    }

    /**
     * Disable the Profiler domain
     * 
     * @return mixed The response
     */
    public function disableProfiler(): mixed
    {
        return $this->call('Profiler.disable');
    }

    /**
     * Start profiling
     * 
     * @return mixed The response
     */
    public function startProfiler(): mixed
    {
        return $this->call('Profiler.start');
    }

    /**
     * Stop profiling
     * 
     * @return mixed The profile data
     */
    public function stopProfiler(): mixed
    {
        return $this->call('Profiler.stop');
    }

    /**
     * Set sampling interval for profiling
     * 
     * @param int $interval The interval in microseconds
     * 
     * @return mixed The response
     */
    public function setSamplingInterval(int $interval): mixed
    {
        return $this->call('Profiler.setSamplingInterval', ['interval' => $interval]);
    }

    /**
     * Get best effort coverage
     * 
     * @return mixed The coverage data
     */
    public function getBestEffortCoverage(): mixed
    {
        return $this->call('Profiler.getBestEffortCoverage');
    }

    /**
     * Start precise coverage tracking
     * 
     * @param bool $callCount Whether to track call counts
     * @param bool $detailed Whether to track detailed ranges
     * @param bool $allowTriggeredUpdates Whether to allow triggered updates
     * 
     * @return mixed The response
     */
    public function startPreciseCoverage(bool $callCount = true, bool $detailed = true, bool $allowTriggeredUpdates = false): mixed
    {
        return $this->call('Profiler.startPreciseCoverage', [
            'callCount' => $callCount,
            'detailed' => $detailed,
            'allowTriggeredUpdates' => $allowTriggeredUpdates,
        ]);
    }

    /**
     * Stop precise coverage tracking
     * 
     * @return mixed The response
     */
    public function stopPreciseCoverage(): mixed
    {
        return $this->call('Profiler.stopPreciseCoverage');
    }

    /**
     * Get precise coverage data
     * 
     * @return mixed The coverage data
     */
    public function takePreciseCoverage(): mixed
    {
        return $this->call('Profiler.takePreciseCoverage');
    }

    /**
     * Enable the HeapProfiler domain
     * 
     * @return mixed The response
     */
    public function enableHeapProfiler(): mixed
    {
        return $this->call('HeapProfiler.enable');
    }

    /**
     * Disable the HeapProfiler domain
     * 
     * @return mixed The response
     */
    public function disableHeapProfiler(): mixed
    {
        return $this->call('HeapProfiler.disable');
    }

    /**
     * Take a heap snapshot
     * 
     * @param bool $reportProgress Whether to report progress
     * @param bool $treatGlobalObjectsAsRoots Whether to treat global objects as roots
     * @param bool $captureNumericValue Whether to capture numeric values
     * 
     * @return mixed The response
     */
    public function takeHeapSnapshot(bool $reportProgress = true, bool $treatGlobalObjectsAsRoots = true, bool $captureNumericValue = false): mixed
    {
        return $this->call('HeapProfiler.takeHeapSnapshot', [
            'reportProgress' => $reportProgress,
            'treatGlobalObjectsAsRoots' => $treatGlobalObjectsAsRoots,
            'captureNumericValue' => $captureNumericValue,
        ]);
    }

    /**
     * Start tracking heap objects
     * 
     * @param bool $trackAllocations Whether to track allocations
     * 
     * @return mixed The response
     */
    public function startTrackingHeapObjects(bool $trackAllocations = false): mixed
    {
        return $this->call('HeapProfiler.startTrackingHeapObjects', [
            'trackAllocations' => $trackAllocations,
        ]);
    }

    /**
     * Stop tracking heap objects
     * 
     * @param bool $reportProgress Whether to report progress
     * @param bool $treatGlobalObjectsAsRoots Whether to treat global objects as roots
     * @param bool $captureNumericValue Whether to capture numeric values
     * 
     * @return mixed The response
     */
    public function stopTrackingHeapObjects(bool $reportProgress = true, bool $treatGlobalObjectsAsRoots = true, bool $captureNumericValue = false): mixed
    {
        return $this->call('HeapProfiler.stopTrackingHeapObjects', [
            'reportProgress' => $reportProgress,
            'treatGlobalObjectsAsRoots' => $treatGlobalObjectsAsRoots,
            'captureNumericValue' => $captureNumericValue,
        ]);
    }

    /**
     * Start sampling heap
     * 
     * @param float $samplingInterval The sampling interval
     * 
     * @return mixed The response
     */
    public function startSampling(float $samplingInterval = 0): mixed
    {
        $params = [];

        if ($samplingInterval > 0) {
            $params['samplingInterval'] = $samplingInterval;
        }

        return $this->call('HeapProfiler.startSampling', $params);
    }

    /**
     * Stop heap sampling
     * 
     * @return mixed The sampling profile
     */
    public function stopSampling(): mixed
    {
        return $this->call('HeapProfiler.stopSampling');
    }

    /**
     * Get the heap sampling profile
     * 
     * @return mixed The sampling profile
     */
    public function getSamplingProfile(): mixed
    {
        return $this->call('HeapProfiler.getSamplingProfile');
    }

    /**
     * Collect garbage in the heap
     * 
     * @return mixed The response
     */
    public function collectGarbage(): mixed
    {
        return $this->call('HeapProfiler.collectGarbage');
    }

    /**
     * Get a remote object by its heap object ID
     * 
     * @param string $objectId The heap object ID
     * @param string $objectGroup Optional object group
     * 
     * @return mixed The remote object
     */
    public function getObjectByHeapObjectId(string $objectId, string $objectGroup = ''): mixed
    {
        $params = ['objectId' => $objectId];

        if ($objectGroup !== '') {
            $params['objectGroup'] = $objectGroup;
        }

        return $this->call('HeapProfiler.getObjectByHeapObjectId', $params);
    }

    /**
     * Get the heap object ID for a remote object
     * 
     * @param string $objectId The remote object ID
     * 
     * @return mixed The heap object ID
     */
    public function getHeapObjectId(string $objectId): mixed
    {
        return $this->call('HeapProfiler.getHeapObjectId', ['objectId' => $objectId]);
    }

    /**
     * Enable the Debugger domain
     * 
     * @return mixed The response
     */
    public function enableDebugger(): mixed
    {
        return $this->call('Debugger.enable');
    }

    /**
     * Disable the Debugger domain
     * 
     * @return mixed The response
     */
    public function disableDebugger(): mixed
    {
        return $this->call('Debugger.disable');
    }

    /**
     * Pause execution
     * 
     * @return mixed The response
     */
    public function pause(): mixed
    {
        return $this->call('Debugger.pause');
    }

    /**
     * Resume execution
     * 
     * @param bool $terminateOnResume Whether to terminate on resume
     * 
     * @return mixed The response
     */
    public function resume(bool $terminateOnResume = false): mixed
    {
        $params = [];

        if ($terminateOnResume) {
            $params['terminateOnResume'] = true;
        }

        return $this->call('Debugger.resume', $params);
    }

    /**
     * Step over the next statement
     * 
     * @param array $skipList Optional scripts to skip
     * 
     * @return mixed The response
     */
    public function stepOver(array $skipList = []): mixed
    {
        $params = [];

        if (!empty($skipList)) {
            $params['skipList'] = $skipList;
        }

        return $this->call('Debugger.stepOver', $params);
    }

    /**
     * Step into the next statement
     * 
     * @param bool $breakOnAsyncCall Whether to break on async calls
     * @param array $skipList Optional scripts to skip
     * 
     * @return mixed The response
     */
    public function stepInto(bool $breakOnAsyncCall = false, array $skipList = []): mixed
    {
        $params = [];

        if ($breakOnAsyncCall) {
            $params['breakOnAsyncCall'] = true;
        }

        if (!empty($skipList)) {
            $params['skipList'] = $skipList;
        }

        return $this->call('Debugger.stepInto', $params);
    }

    /**
     * Step out of the current function
     * 
     * @return mixed The response
     */
    public function stepOut(): mixed
    {
        return $this->call('Debugger.stepOut');
    }

    /**
     * Set a breakpoint by URL and line number
     * 
     * @param int $lineNumber The line number
     * @param string $url Optional URL
     * @param string $urlRegex Optional URL regex
     * @param string $scriptHash Optional script hash
     * @param int $columnNumber Optional column number
     * @param string $condition Optional break condition
     * 
     * @return mixed The breakpoint object
     */
    public function setBreakpointByUrl(int $lineNumber, string $url = '', string $urlRegex = '', string $scriptHash = '', int $columnNumber = 0, string $condition = ''): mixed
    {
        $params = ['lineNumber' => $lineNumber];

        if ($url !== '') {
            $params['url'] = $url;
        }

        if ($urlRegex !== '') {
            $params['urlRegex'] = $urlRegex;
        }

        if ($scriptHash !== '') {
            $params['scriptHash'] = $scriptHash;
        }

        if ($columnNumber > 0) {
            $params['columnNumber'] = $columnNumber;
        }

        if ($condition !== '') {
            $params['condition'] = $condition;
        }

        return $this->call('Debugger.setBreakpointByUrl', $params);
    }

    /**
     * Set a breakpoint at a specific location
     * 
     * @param array $location The location object with scriptId, lineNumber, columnNumber
     * @param string $condition Optional break condition
     * 
     * @return mixed The breakpoint object
     */
    public function setBreakpoint(array $location, string $condition = ''): mixed
    {
        $params = ['location' => $location];

        if ($condition !== '') {
            $params['condition'] = $condition;
        }

        return $this->call('Debugger.setBreakpoint', $params);
    }

    /**
     * Remove a breakpoint
     * 
     * @param string $breakpointId The breakpoint ID
     * 
     * @return mixed The response
     */
    public function removeBreakpoint(string $breakpointId): mixed
    {
        return $this->call('Debugger.removeBreakpoint', ['breakpointId' => $breakpointId]);
    }

    /**
     * Set whether breakpoints are active
     * 
     * @param bool $active Whether to activate breakpoints
     * 
     * @return mixed The response
     */
    public function setBreakpointsActive(bool $active): mixed
    {
        return $this->call('Debugger.setBreakpointsActive', ['active' => $active]);
    }

    /**
     * Set pause on exceptions
     * 
     * @param string $state The pause state (none, uncaught, all)
     * 
     * @return mixed The response
     */
    public function setPauseOnExceptions(string $state): mixed
    {
        return $this->call('Debugger.setPauseOnExceptions', ['state' => $state]);
    }

    /**
     * Evaluate expression in a call frame
     * 
     * @param string $callFrameId The call frame ID
     * @param string $expression The expression to evaluate
     * @param bool $returnByValue Whether to return by value
     * 
     * @return mixed The evaluation result
     */
    public function evaluateOnCallFrame(string $callFrameId, string $expression, bool $returnByValue = true): mixed
    {
        return $this->call('Debugger.evaluateOnCallFrame', [
            'callFrameId' => $callFrameId,
            'expression' => $expression,
            'returnByValue' => $returnByValue,
        ]);
    }

    /**
     * Set a variable value in a scope
     * 
     * @param int $scopeNumber The scope number
     * @param string $variableName The variable name
     * @param array $newValue The new value
     * @param string $callFrameId The call frame ID
     * 
     * @return mixed The response
     */
    public function setVariableValue(int $scopeNumber, string $variableName, array $newValue, string $callFrameId): mixed
    {
        return $this->call('Debugger.setVariableValue', [
            'scopeNumber' => $scopeNumber,
            'variableName' => $variableName,
            'newValue' => $newValue,
            'callFrameId' => $callFrameId,
        ]);
    }

    /**
     * Set skip all pauses
     * 
     * @param bool $skip Whether to skip all pauses
     * 
     * @return mixed The response
     */
    public function setSkipAllPauses(bool $skip): mixed
    {
        return $this->call('Debugger.setSkipAllPauses', ['skip' => $skip]);
    }

    /**
     * Get the source of a script
     * 
     * @param string $scriptId The script ID
     * 
     * @return mixed The script source
     */
    public function getScriptSource(string $scriptId): mixed
    {
        return $this->call('Debugger.getScriptSource', ['scriptId' => $scriptId]);
    }

    /**
     * Set the source of a script
     * 
     * @param string $scriptId The script ID
     * @param string $scriptSource The new script source
     * @param bool $dryRun Whether to do a dry run
     * 
     * @return mixed The response
     */
    public function setScriptSource(string $scriptId, string $scriptSource, bool $dryRun = false): mixed
    {
        return $this->call('Debugger.setScriptSource', [
            'scriptId' => $scriptId,
            'scriptSource' => $scriptSource,
            'dryRun' => $dryRun,
        ]);
    }

    /**
     * Search in script content
     * 
     * @param string $scriptId The script ID
     * @param string $query The search query
     * @param bool $caseSensitive Whether the search is case-sensitive
     * @param bool $isRegex Whether to use regex pattern
     * 
     * @return mixed The search matches
     */
    public function searchInContent(string $scriptId, string $query, bool $caseSensitive = false, bool $isRegex = false): mixed
    {
        return $this->call('Debugger.searchInContent', [
            'scriptId' => $scriptId,
            'query' => $query,
            'caseSensitive' => $caseSensitive,
            'isRegex' => $isRegex,
        ]);
    }

    /**
     * Set async call stack depth
     * 
     * @param int $maxDepth The maximum depth
     * 
     * @return mixed The response
     */
    public function setAsyncCallStackDepth(int $maxDepth): mixed
    {
        return $this->call('Debugger.setAsyncCallStackDepth', ['maxDepth' => $maxDepth]);
    }

    /**
     * Set blackbox patterns for scripts to ignore during debugging
     * 
     * @param array $patterns The patterns to blackbox
     * 
     * @return mixed The response
     */
    public function setBlackboxPatterns(array $patterns): mixed
    {
        return $this->call('Debugger.setBlackboxPatterns', ['patterns' => $patterns]);
    }

    /**
     * Set blackboxed ranges in a script
     * 
     * @param string $scriptId The script ID
     * @param array $positions The positions to blackbox
     * 
     * @return mixed The response
     */
    public function setBlackboxedRanges(string $scriptId, array $positions): mixed
    {
        return $this->call('Debugger.setBlackboxedRanges', [
            'scriptId' => $scriptId,
            'positions' => $positions,
        ]);
    }

    /**
     * Get the stack trace from a stack trace ID
     * 
     * @param array $stackTraceId The stack trace ID
     * 
     * @return mixed The stack trace
     */
    public function getStackTrace(array $stackTraceId): mixed
    {
        return $this->call('Debugger.getStackTrace', ['stackTraceId' => $stackTraceId]);
    }

    /**
     * Clear storage data for an origin
     * 
     * @param string $origin The origin URL
     * @param string $storageTypes The storage types to clear
     * 
     * @return mixed The response
     */
    public function clearDataForOrigin(string $origin, string $storageTypes = 'all'): mixed
    {
        return $this->call('Storage.clearDataForOrigin', [
            'origin' => $origin,
            'storageTypes' => $storageTypes,
        ]);
    }

    /**
     * Get storage usage and quota for an origin
     * 
     * @param string $origin The origin URL
     * 
     * @return mixed The usage and quota
     */
    public function getUsageAndQuota(string $origin): mixed
    {
        return $this->call('Storage.getUsageAndQuota', ['origin' => $origin]);
    }

    /**
     * Override the quota for an origin
     * 
     * @param string $origin The origin URL
     * @param float $quotaSize The quota size in bytes
     * 
     * @return mixed The response
     */
    public function overrideQuotaForOrigin(string $origin, float $quotaSize = 0): mixed
    {
        $params = ['origin' => $origin];

        if ($quotaSize > 0) {
            $params['quotaSize'] = $quotaSize;
        }

        return $this->call('Storage.overrideQuotaForOrigin', $params);
    }

    /**
     * Track cache storage for an origin
     * 
     * @param string $origin The origin URL
     * 
     * @return mixed The response
     */
    public function trackCacheStorageForOrigin(string $origin): mixed
    {
        return $this->call('Storage.trackCacheStorageForOrigin', ['origin' => $origin]);
    }

    /**
     * Stop tracking cache storage for an origin
     * 
     * @param string $origin The origin URL
     * 
     * @return mixed The response
     */
    public function untrackCacheStorageForOrigin(string $origin): mixed
    {
        return $this->call('Storage.untrackCacheStorageForOrigin', ['origin' => $origin]);
    }

    /**
     * Track IndexedDB for an origin
     * 
     * @param string $origin The origin URL
     * 
     * @return mixed The response
     */
    public function trackIndexedDBForOrigin(string $origin): mixed
    {
        return $this->call('Storage.trackIndexedDBForOrigin', ['origin' => $origin]);
    }

    /**
     * Stop tracking IndexedDB for an origin
     * 
     * @param string $origin The origin URL
     * 
     * @return mixed The response
     */
    public function untrackIndexedDBForOrigin(string $origin): mixed
    {
        return $this->call('Storage.untrackIndexedDBForOrigin', ['origin' => $origin]);
    }

    /**
     * Get cookies for storage
     * 
     * @param string $browserContextId Optional browser context ID
     * 
     * @return mixed The cookies
     */
    public function getCookiesForStorage(string $browserContextId = ''): mixed
    {
        $params = [];

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Storage.getCookies', $params);
    }

    /**
     * Set cookies for storage
     * 
     * @param array $cookies The cookies to set
     * @param string $browserContextId Optional browser context ID
     * 
     * @return mixed The response
     */
    public function setCookiesForStorage(array $cookies, string $browserContextId = ''): mixed
    {
        $params = ['cookies' => $cookies];

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Storage.setCookies', $params);
    }

    /**
     * Clear cookies for storage
     * 
     * @param string $browserContextId Optional browser context ID
     * 
     * @return mixed The response
     */
    public function clearCookiesForStorage(string $browserContextId = ''): mixed
    {
        $params = [];

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Storage.clearCookies', $params);
    }

    // --- ServiceWorker ---

    /**
     * Enable the ServiceWorker domain
     * 
     * @return mixed The response
     */
    public function enableServiceWorker(): mixed
    {
        return $this->call('ServiceWorker.enable');
    }

    /**
     * Disable the ServiceWorker domain
     * 
     * @return mixed The response
     */
    public function disableServiceWorker(): mixed
    {
        return $this->call('ServiceWorker.disable');
    }

    /**
     * Unregister a service worker
     * 
     * @param string $scopeURL The scope URL of the service worker
     * 
     * @return mixed The response
     */
    public function unregisterServiceWorker(string $scopeURL): mixed
    {
        return $this->call('ServiceWorker.unregister', ['scopeURL' => $scopeURL]);
    }

    /**
     * Update a service worker registration
     * 
     * @param string $scopeURL The scope URL of the service worker
     * 
     * @return mixed The response
     */
    public function updateServiceWorkerRegistration(string $scopeURL): mixed
    {
        return $this->call('ServiceWorker.updateRegistration', ['scopeURL' => $scopeURL]);
    }

    /**
     * Start a service worker
     * 
     * @param string $scopeURL The scope URL of the service worker
     * 
     * @return mixed The response
     */
    public function startServiceWorker(string $scopeURL): mixed
    {
        return $this->call('ServiceWorker.startWorker', ['scopeURL' => $scopeURL]);
    }

    /**
     * Stop a service worker
     * 
     * @param string $versionId The version ID of the service worker
     * 
     * @return mixed The response
     */
    public function stopServiceWorker(string $versionId): mixed
    {
        return $this->call('ServiceWorker.stopWorker', ['versionId' => $versionId]);
    }

    /**
     * Inspect a service worker
     * 
     * @param string $versionId The version ID of the service worker
     * 
     * @return mixed The response
     */
    public function inspectServiceWorker(string $versionId): mixed
    {
        return $this->call('ServiceWorker.inspectWorker', ['versionId' => $versionId]);
    }

    /**
     * Set force update on page load
     * 
     * @param bool $forceUpdateOnPageLoad Whether to force update
     * 
     * @return mixed The response
     */
    public function setForceUpdateOnPageLoad(bool $forceUpdateOnPageLoad): mixed
    {
        return $this->call('ServiceWorker.setForceUpdateOnPageLoad', [
            'forceUpdateOnPageLoad' => $forceUpdateOnPageLoad,
        ]);
    }

    /**
     * Skip waiting service worker
     * 
     * @param string $scopeURL The scope URL of the service worker
     * 
     * @return mixed The response
     */
    public function skipServiceWorkerWaiting(string $scopeURL): mixed
    {
        return $this->call('ServiceWorker.skipWaiting', ['scopeURL' => $scopeURL]);
    }

    /**
     * Deliver a push message to a service worker
     * 
     * @param string $origin The origin URL
     * @param string $registrationId The registration ID
     * @param string $data The push message data
     * 
     * @return mixed The response
     */
    public function deliverPushMessage(string $origin, string $registrationId, string $data): mixed
    {
        return $this->call('ServiceWorker.deliverPushMessage', [
            'origin' => $origin,
            'registrationId' => $registrationId,
            'data' => $data,
        ]);
    }

    /**
     * Dispatch a sync event to a service worker
     * 
     * @param string $origin The origin URL
     * @param string $registrationId The registration ID
     * @param string $tag The sync event tag
     * @param bool $lastChance Whether this is the last chance
     * 
     * @return mixed The response
     */
    public function dispatchSyncEvent(string $origin, string $registrationId, string $tag, bool $lastChance = false): mixed
    {
        return $this->call('ServiceWorker.dispatchSyncEvent', [
            'origin' => $origin,
            'registrationId' => $registrationId,
            'tag' => $tag,
            'lastChance' => $lastChance,
        ]);
    }

    /**
     * Dispatch a periodic sync event to a service worker
     * 
     * @param string $origin The origin URL
     * @param string $registrationId The registration ID
     * @param string $tag The sync event tag
     * 
     * @return mixed The response
     */
    public function dispatchPeriodicSyncEvent(string $origin, string $registrationId, string $tag): mixed
    {
        return $this->call('ServiceWorker.dispatchPeriodicSyncEvent', [
            'origin' => $origin,
            'registrationId' => $registrationId,
            'tag' => $tag,
        ]);
    }

    // --- Accessibility ---

    /**
     * Enable the Accessibility domain
     * 
     * @return mixed The response
     */
    public function enableAccessibility(): mixed
    {
        return $this->call('Accessibility.enable');
    }

    /**
     * Disable the Accessibility domain
     * 
     * @return mixed The response
     */
    public function disableAccessibility(): mixed
    {
        return $this->call('Accessibility.disable');
    }

    /**
     * Get the full accessibility tree
     * 
     * @param int $depth The depth to include
     * @param string $frameId Optional frame ID
     * 
     * @return mixed The accessibility tree
     */
    public function getFullAXTree(int $depth = 0, string $frameId = ''): mixed
    {
        $params = [];

        if ($depth > 0) {
            $params['depth'] = $depth;
        }

        if ($frameId !== '') {
            $params['frameId'] = $frameId;
        }

        return $this->call('Accessibility.getFullAXTree', $params);
    }

    /**
     * Get a partial accessibility tree
     * 
     * @param int $nodeId Optional node ID
     * @param string $objectId Optional object ID
     * @param bool $fetchRelatives Whether to fetch relatives
     * 
     * @return mixed The accessibility tree
     */
    public function getPartialAXTree(int $nodeId = 0, string $objectId = '', bool $fetchRelatives = false): mixed
    {
        $params = [];

        if ($nodeId > 0) {
            $params['nodeId'] = $nodeId;
        }

        if ($objectId !== '') {
            $params['objectId'] = $objectId;
        }

        $params['fetchRelatives'] = $fetchRelatives;

        return $this->call('Accessibility.getPartialAXTree', $params);
    }

    /**
     * Get child accessibility nodes
     * 
     * @param string $id The node ID
     * @param string $frameId Optional frame ID
     * 
     * @return mixed The child nodes
     */
    public function getChildAXNodes(string $id, string $frameId = ''): mixed
    {
        $params = ['id' => $id];

        if ($frameId !== '') {
            $params['frameId'] = $frameId;
        }

        return $this->call('Accessibility.getChildAXNodes', $params);
    }

    /**
     * Query the accessibility tree
     * 
     * @param int $nodeId Optional node ID
     * @param string $objectId Optional object ID
     * @param string $accessibleName Optional accessible name to search for
     * @param string $role Optional role to search for
     * 
     * @return mixed The query results
     */
    public function queryAXTree(int $nodeId = 0, string $objectId = '', string $accessibleName = '', string $role = ''): mixed
    {
        $params = [];

        if ($nodeId > 0) {
            $params['nodeId'] = $nodeId;
        }

        if ($objectId !== '') {
            $params['objectId'] = $objectId;
        }

        if ($accessibleName !== '') {
            $params['accessibleName'] = $accessibleName;
        }

        if ($role !== '') {
            $params['role'] = $role;
        }

        return $this->call('Accessibility.queryAXTree', $params);
    }

    // --- Animation ---

    /**
     * Enable the Animation domain
     * 
     * @return mixed The response
     */
    public function enableAnimation(): mixed
    {
        return $this->call('Animation.enable');
    }

    /**
     * Disable the Animation domain
     * 
     * @return mixed The response
     */
    public function disableAnimation(): mixed
    {
        return $this->call('Animation.disable');
    }

    /**
     * Get the playback rate of animations
     * 
     * @return mixed The playback rate
     */
    public function getPlaybackRate(): mixed
    {
        return $this->call('Animation.getPlaybackRate');
    }

    /**
     * Set the playback rate of animations
     * 
     * @param float $playbackRate The playback rate
     * 
     * @return mixed The response
     */
    public function setPlaybackRate(float $playbackRate): mixed
    {
        return $this->call('Animation.setPlaybackRate', ['playbackRate' => $playbackRate]);
    }

    /**
     * Get the current time of an animation
     * 
     * @param string $id The animation ID
     * 
     * @return mixed The current time
     */
    public function getCurrentTime(string $id): mixed
    {
        return $this->call('Animation.getCurrentTime', ['id' => $id]);
    }

    /**
     * Set animations as paused or playing
     * 
     * @param array $animations The animation IDs
     * @param bool $paused Whether to pause
     * 
     * @return mixed The response
     */
    public function setPaused(array $animations, bool $paused): mixed
    {
        return $this->call('Animation.setPaused', [
            'animations' => $animations,
            'paused' => $paused,
        ]);
    }

    /**
     * Seek animations to a specific time
     * 
     * @param array $animations The animation IDs
     * @param float $currentTime The target time
     * 
     * @return mixed The response
     */
    public function seekAnimations(array $animations, float $currentTime): mixed
    {
        return $this->call('Animation.seekAnimations', [
            'animations' => $animations,
            'currentTime' => $currentTime,
        ]);
    }

    /**
     * Release animations
     * 
     * @param array $animations The animation IDs to release
     * 
     * @return mixed The response
     */
    public function releaseAnimations(array $animations): mixed
    {
        return $this->call('Animation.releaseAnimations', ['animations' => $animations]);
    }

    /**
     * Resolve an animation to a remote object
     * 
     * @param string $animationId The animation ID
     * 
     * @return mixed The remote object
     */
    public function resolveAnimation(string $animationId): mixed
    {
        return $this->call('Animation.resolveAnimation', ['animationId' => $animationId]);
    }

    /**
     * Enable the Overlay domain
     * 
     * @return mixed The response
     */
    public function enableOverlay(): mixed
    {
        return $this->call('Overlay.enable');
    }

    /**
     * Disable the Overlay domain
     * 
     * @return mixed The response
     */
    public function disableOverlay(): mixed
    {
        return $this->call('Overlay.disable');
    }

    /**
     * Highlight a DOM node
     * 
     * @param array $highlightConfig The highlight configuration
     * @param int $nodeId Optional node ID
     * @param string $objectId Optional object ID
     * 
     * @return mixed The response
     */
    public function highlightNode(array $highlightConfig, int $nodeId = 0, string $objectId = ''): mixed
    {
        $params = ['highlightConfig' => $highlightConfig];

        if ($nodeId > 0) {
            $params['nodeId'] = $nodeId;
        }

        if ($objectId !== '') {
            $params['objectId'] = $objectId;
        }

        return $this->call('Overlay.highlightNode', $params);
    }

    /**
     * Highlight a rectangle
     * 
     * @param int $x The X coordinate
     * @param int $y The Y coordinate
     * @param int $width The width
     * @param int $height The height
     * @param array|null $color Optional RGBA color
     * @param array|null $outlineColor Optional outline color
     * 
     * @return mixed The response
     */
    public function highlightRect(int $x, int $y, int $width, int $height, ?array $color = null, ?array $outlineColor = null): mixed
    {
        $params = ['x' => $x, 'y' => $y, 'width' => $width, 'height' => $height];

        if ($color !== null) {
            $params['color'] = $color;
        }

        if ($outlineColor !== null) {
            $params['outlineColor'] = $outlineColor;
        }

        return $this->call('Overlay.highlightRect', $params);
    }

    /**
     * Highlight a quad (4-point polygon)
     * 
     * @param array $quad The quad points
     * @param array|null $color Optional RGBA color
     * @param array|null $outlineColor Optional outline color
     * 
     * @return mixed The response
     */
    public function highlightQuad(array $quad, ?array $color = null, ?array $outlineColor = null): mixed
    {
        $params = ['quad' => $quad];

        if ($color !== null) {
            $params['color'] = $color;
        }

        if ($outlineColor !== null) {
            $params['outlineColor'] = $outlineColor;
        }

        return $this->call('Overlay.highlightQuad', $params);
    }

    /**
     * Hide all highlights
     * 
     * @return mixed The response
     */
    public function hideHighlight(): mixed
    {
        return $this->call('Overlay.hideHighlight');
    }

    /**
     * Show or hide FPS counter overlay
     * 
     * @param bool $show Whether to show
     * 
     * @return mixed The response
     */
    public function setShowFPSCounter(bool $show): mixed
    {
        return $this->call('Overlay.setShowFPSCounter', ['show' => $show]);
    }

    /**
     * Show or hide paint rectangles
     * 
     * @param bool $result Whether to show
     * 
     * @return mixed The response
     */
    public function setShowPaintRects(bool $result): mixed
    {
        return $this->call('Overlay.setShowPaintRects', ['result' => $result]);
    }

    /**
     * Show or hide layout shift regions
     * 
     * @param bool $result Whether to show
     * 
     * @return mixed The response
     */
    public function setShowLayoutShiftRegions(bool $result): mixed
    {
        return $this->call('Overlay.setShowLayoutShiftRegions', ['result' => $result]);
    }

    /**
     * Show or hide scroll bottleneck rectangles
     * 
     * @param bool $show Whether to show
     * 
     * @return mixed The response
     */
    public function setShowScrollBottleneckRects(bool $show): mixed
    {
        return $this->call('Overlay.setShowScrollBottleneckRects', ['show' => $show]);
    }

    /**
     * Show or hide hit test borders
     * 
     * @param bool $show Whether to show
     * 
     * @return mixed The response
     */
    public function setShowHitTestBorders(bool $show): mixed
    {
        return $this->call('Overlay.setShowHitTestBorders', ['show' => $show]);
    }

    /**
     * Set inspect mode
     * 
     * @param string $mode The inspect mode type
     * @param array $highlightConfig Optional highlight configuration
     * 
     * @return mixed The response
     */
    public function setInspectMode(string $mode, array $highlightConfig = []): mixed
    {
        $params = ['mode' => $mode];

        if (!empty($highlightConfig)) {
            $params['highlightConfig'] = $highlightConfig;
        }

        return $this->call('Overlay.setInspectMode', $params);
    }

    /**
     * Show grid overlays
     * 
     * @param array $gridNodeHighlightConfigs The grid configurations
     * 
     * @return mixed The response
     */
    public function setShowGridOverlays(array $gridNodeHighlightConfigs): mixed
    {
        return $this->call('Overlay.setShowGridOverlays', [
            'gridNodeHighlightConfigs' => $gridNodeHighlightConfigs,
        ]);
    }

    /**
     * Show flex overlays
     * 
     * @param array $flexNodeHighlightConfigs The flex configurations
     * 
     * @return mixed The response
     */
    public function setShowFlexOverlays(array $flexNodeHighlightConfigs): mixed
    {
        return $this->call('Overlay.setShowFlexOverlays', [
            'flexNodeHighlightConfigs' => $flexNodeHighlightConfigs,
        ]);
    }

    /**
     * Enable the Fetch domain
     * 
     * @param array $patterns Optional URL patterns to intercept
     * @param bool $handleAuthRequests Whether to handle auth requests
     * 
     * @return mixed The response
     */
    public function enableFetch(array $patterns = [], bool $handleAuthRequests = false): mixed
    {
        $params = [];

        if (!empty($patterns)) {
            $params['patterns'] = $patterns;
        }

        if ($handleAuthRequests) {
            $params['handleAuthRequests'] = true;
        }

        return $this->call('Fetch.enable', $params);
    }

    /**
     * Disable the Fetch domain
     * 
     * @return mixed The response
     */
    public function disableFetch(): mixed
    {
        return $this->call('Fetch.disable');
    }

    /**
     * Fulfill an intercepted request with a response
     * 
     * @param string $requestId The request ID
     * @param int $responseCode The HTTP response code
     * @param array $responseHeaders Optional response headers
     * @param string $body Optional response body
     * @param string $responsePhrase Optional response phrase
     * 
     * @return mixed The response
     */
    public function fulfillRequest(string $requestId, int $responseCode, array $responseHeaders = [], string $body = '', string $responsePhrase = ''): mixed
    {
        $params = [
            'requestId' => $requestId,
            'responseCode' => $responseCode,
        ];

        if (!empty($responseHeaders)) {
            $params['responseHeaders'] = $responseHeaders;
        }

        if ($body !== '') {
            $params['body'] = $body;
        }

        if ($responsePhrase !== '') {
            $params['responsePhrase'] = $responsePhrase;
        }

        return $this->call('Fetch.fulfillRequest', $params);
    }

    /**
     * Continue an intercepted request
     * 
     * @param string $requestId The request ID
     * @param string $url Optional new URL
     * @param string $method Optional new method
     * @param string $postData Optional new POST data
     * @param array $headers Optional new headers
     * 
     * @return mixed The response
     */
    public function continueRequest(string $requestId, string $url = '', string $method = '', string $postData = '', array $headers = []): mixed
    {
        $params = ['requestId' => $requestId];

        if ($url !== '') {
            $params['url'] = $url;
        }

        if ($method !== '') {
            $params['method'] = $method;
        }
        if ($postData !== '') {
            $params['postData'] = $postData;
        }

        if (!empty($headers)) {
            $params['headers'] = $headers;
        }

        return $this->call('Fetch.continueRequest', $params);
    }

    /**
     * Continue with auth challenge response
     * 
     * @param string $requestId The request ID
     * @param array $authChallengeResponse The auth response
     * 
     * @return mixed The response
     */
    public function continueWithAuth(string $requestId, array $authChallengeResponse): mixed
    {
        return $this->call('Fetch.continueWithAuth', [
            'requestId' => $requestId,
            'authChallengeResponse' => $authChallengeResponse,
        ]);
    }

    /**
     * Fail an intercepted request
     * 
     * @param string $requestId The request ID
     * @param string $reason The failure reason
     * 
     * @return mixed The response
     */
    public function failRequest(string $requestId, string $reason): mixed
    {
        return $this->call('Fetch.failRequest', [
            'requestId' => $requestId,
            'reason' => $reason,
        ]);
    }

    /**
     * Get the response body of an intercepted request
     * 
     * @param string $requestId The request ID
     * 
     * @return mixed The response body
     */
    public function getResponseBodyFromFetch(string $requestId): mixed
    {
        return $this->call('Fetch.getResponseBody', ['requestId' => $requestId]);
    }

    /**
     * Continue response for an intercepted request
     * 
     * @param string $requestId The request ID
     * @param int $responseCode Optional response code
     * @param array $responseHeaders Optional response headers
     * @param string $binaryResponseHeaders Optional binary headers
     * 
     * @return mixed The response
     */
    public function continueResponse(string $requestId, int $responseCode = 0, array $responseHeaders = [], string $binaryResponseHeaders = ''): mixed
    {
        $params = ['requestId' => $requestId];

        if ($responseCode > 0) {
            $params['responseCode'] = $responseCode;
        }

        if (!empty($responseHeaders)) {
            $params['responseHeaders'] = $responseHeaders;
        }

        if ($binaryResponseHeaders !== '') {
            $params['binaryResponseHeaders'] = $binaryResponseHeaders;
        }

        return $this->call('Fetch.continueResponse', $params);
    }

    /**
     * Set a DOM breakpoint
     * 
     * @param int $nodeId The node ID
     * @param string $type The breakpoint type
     * 
     * @return mixed The response
     */
    public function setDOMBreakpoint(int $nodeId, string $type): mixed
    {
        return $this->call('DOMDebugger.setDOMBreakpoint', [
            'nodeId' => $nodeId,
            'type' => $type,
        ]);
    }

    /**
     * Remove a DOM breakpoint
     * 
     * @param int $nodeId The node ID
     * @param string $type The breakpoint type
     * 
     * @return mixed The response
     */
    public function removeDOMBreakpoint(int $nodeId, string $type): mixed
    {
        return $this->call('DOMDebugger.removeDOMBreakpoint', [
            'nodeId' => $nodeId,
            'type' => $type,
        ]);
    }

    /**
     * Set an event listener breakpoint
     * 
     * @param string $eventName The event name
     * @param string $targetName Optional target name
     * 
     * @return mixed The response
     */
    public function setEventListenerBreakpoint(string $eventName, string $targetName = ''): mixed
    {
        $params = ['eventName' => $eventName];

        if ($targetName !== '') {
            $params['targetName'] = $targetName;
        }

        return $this->call('DOMDebugger.setEventListenerBreakpoint', $params);
    }

    /**
     * Remove an event listener breakpoint
     * 
     * @param string $eventName The event name
     * @param string $targetName Optional target name
     * 
     * @return mixed The response
     */
    public function removeEventListenerBreakpoint(string $eventName, string $targetName = ''): mixed
    {
        $params = ['eventName' => $eventName];

        if ($targetName !== '') {
            $params['targetName'] = $targetName;
        }

        return $this->call('DOMDebugger.removeEventListenerBreakpoint', $params);
    }

    /**
     * Set an XHR breakpoint
     * 
     * @param string $url The URL pattern
     * 
     * @return mixed The response
     */
    public function setXHRBreakpoint(string $url): mixed
    {
        return $this->call('DOMDebugger.setXHRBreakpoint', ['url' => $url]);
    }

    /**
     * Remove an XHR breakpoint
     * 
     * @param string $url The URL pattern
     * 
     * @return mixed The response
     */
    public function removeXHRBreakpoint(string $url): mixed
    {
        return $this->call('DOMDebugger.removeXHRBreakpoint', ['url' => $url]);
    }

    /**
     * Get event listeners on an object
     * 
     * @param string $objectId The object ID
     * @param int $depth The depth to search
     * @param bool $pierce Whether to pierce shadow DOM
     * 
     * @return mixed The event listeners
     */
    public function getEventListeners(string $objectId, int $depth = 1, bool $pierce = false): mixed
    {
        return $this->call('DOMDebugger.getEventListeners', [
            'objectId' => $objectId,
            'depth' => $depth,
            'pierce' => $pierce,
        ]);
    }

    /**
     * Capture a DOM snapshot
     * 
     * @param array $computedStyles The computed styles to include
     * @param bool $includePaintOrder Whether to include paint order
     * @param bool $includeDOMRects Whether to include DOM rects
     * 
     * @return mixed The snapshot data
     */
    public function captureSnapshot(array $computedStyles = [], bool $includePaintOrder = false, bool $includeDOMRects = false): mixed
    {
        return $this->call('DOMSnapshot.captureSnapshot', [
            'computedStyles' => $computedStyles,
            'includePaintOrder' => $includePaintOrder,
            'includeDOMRects' => $includeDOMRects,
        ]);
    }

    /**
     * Enable the DOMSnapshot domain
     * 
     * @return mixed The response
     */
    public function enableDOMSnapshot(): mixed
    {
        return $this->call('DOMSnapshot.enable');
    }

    /**
     * Disable the DOMSnapshot domain
     * 
     * @return mixed The response
     */
    public function disableDOMSnapshot(): mixed
    {
        return $this->call('DOMSnapshot.disable');
    }

    /**
     * Enable the IndexedDB domain
     * 
     * @return mixed The response
     */
    public function enableIndexedDB(): mixed
    {
        return $this->call('IndexedDB.enable');
    }

    /**
     * Disable the IndexedDB domain
     * 
     * @return mixed The response
     */
    public function disableIndexedDB(): mixed
    {
        return $this->call('IndexedDB.disable');
    }

    /**
     * Request database names for an origin
     * 
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The database names
     */
    public function requestDatabaseNames(string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = [];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.requestDatabaseNames', $params);
    }

    /**
     * Request information about a database
     * 
     * @param string $databaseName The database name
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The database information
     */
    public function requestDatabase(string $databaseName, string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = ['databaseName' => $databaseName];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.requestDatabase', $params);
    }

    /**
     * Request data from an IndexedDB object store
     * 
     * @param string $databaseName The database name
     * @param string $objectStoreName The object store name
     * @param string $indexName The index name
     * @param int $skipCount Number of entries to skip
     * @param int $pageSize Number of entries to return
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * @param array $keyRange Optional key range
     * 
     * @return mixed The entries data
     */
    public function requestData(string $databaseName, string $objectStoreName, string $indexName, int $skipCount, int $pageSize, string $securityOrigin = '', string $storageKey = '', array $keyRange = []): mixed
    {
        $params = [
            'databaseName' => $databaseName,
            'objectStoreName' => $objectStoreName,
            'indexName' => $indexName,
            'skipCount' => $skipCount,
            'pageSize' => $pageSize,
        ];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        if (!empty($keyRange)) {
            $params['keyRange'] = $keyRange;
        }

        return $this->call('IndexedDB.requestData', $params);
    }

    /**
     * Clear an object store
     * 
     * @param string $databaseName The database name
     * @param string $objectStoreName The object store name
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The response
     */
    public function clearObjectStore(string $databaseName, string $objectStoreName, string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = [
            'databaseName' => $databaseName,
            'objectStoreName' => $objectStoreName,
        ];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.clearObjectStore', $params);
    }

    /**
     * Delete an IndexedDB database
     * 
     * @param string $databaseName The database name
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The response
     */
    public function deleteDatabase(string $databaseName, string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = ['databaseName' => $databaseName];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.deleteDatabase', $params);
    }

    /**
     * Get metadata for an object store
     * 
     * @param string $databaseName The database name
     * @param string $objectStoreName The object store name
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The metadata
     */
    public function getMetadataForObjectStore(string $databaseName, string $objectStoreName, string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = [
            'databaseName' => $databaseName,
            'objectStoreName' => $objectStoreName,
        ];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.getMetadata', $params);
    }

    /**
     * Delete entries from an object store
     * 
     * @param string $databaseName The database name
     * @param string $objectStoreName The object store name
     * @param array $keyRange The key range to delete
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The response
     */
    public function deleteObjectStoreEntries(string $databaseName, string $objectStoreName, array $keyRange, string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = [
            'databaseName' => $databaseName,
            'objectStoreName' => $objectStoreName,
            'keyRange' => $keyRange,
        ];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('IndexedDB.deleteObjectStoreEntries', $params);
    }

    /**
     * Request cache names for an origin
     * 
     * @param string $securityOrigin Optional security origin
     * @param string $storageKey Optional storage key
     * 
     * @return mixed The cache names
     */
    public function requestCacheNames(string $securityOrigin = '', string $storageKey = ''): mixed
    {
        $params = [];

        if ($securityOrigin !== '') {
            $params['securityOrigin'] = $securityOrigin;
        }

        if ($storageKey !== '') {
            $params['storageKey'] = $storageKey;
        }

        return $this->call('CacheStorage.requestCacheNames', $params);
    }

    /**
     * Request a cached response
     * 
     * @param string $cacheId The cache ID
     * @param string $requestURL The request URL
     * @param array $requestHeaders Optional request headers
     * 
     * @return mixed The cached response
     */
    public function requestCachedResponse(string $cacheId, string $requestURL, array $requestHeaders = []): mixed
    {
        $params = ['cacheId' => $cacheId, 'requestURL' => $requestURL];

        if (!empty($requestHeaders)) {
            $params['requestHeaders'] = $requestHeaders;
        }

        return $this->call('CacheStorage.requestCachedResponse', $params);
    }

    /**
     * Request entries from a cache
     * 
     * @param string $cacheId The cache ID
     * @param int $skipCount Number of entries to skip
     * @param int $pageSize Number of entries to return
     * @param string $pathFilter Optional path filter
     * 
     * @return mixed The cache entries
     */
    public function requestEntries(string $cacheId, int $skipCount = 0, int $pageSize = 0, string $pathFilter = ''): mixed
    {
        $params = ['cacheId' => $cacheId];

        if ($skipCount > 0) {
            $params['skipCount'] = $skipCount;
        }

        if ($pageSize > 0) {
            $params['pageSize'] = $pageSize;
        }

        if ($pathFilter !== '') {
            $params['pathFilter'] = $pathFilter;
        }

        return $this->call('CacheStorage.requestEntries', $params);
    }

    /**
     * Delete a cache
     * 
     * @param string $cacheId The cache ID
     * 
     * @return mixed The response
     */
    public function deleteCache(string $cacheId): mixed
    {
        return $this->call('CacheStorage.deleteCache', ['cacheId' => $cacheId]);
    }

    /**
     * Delete an entry from a cache
     * 
     * @param string $cacheId The cache ID
     * @param string $request The request to delete
     * 
     * @return mixed The response
     */
    public function deleteEntry(string $cacheId, string $request): mixed
    {
        return $this->call('CacheStorage.deleteEntry', [
            'cacheId' => $cacheId,
            'request' => $request,
        ]);
    }

    // --- Tracing ---

    /**
     * Start tracing
     * 
     * @param string $categories Comma-separated categories to trace
     * @param string $transferMode The transfer mode
     * @param int $bufferUsageReportingInterval Buffer reporting interval
     * 
     * @return mixed The response
     */
    public function startTracing(string $categories = '', string $transferMode = 'ReturnAsStream', int $bufferUsageReportingInterval = 0): mixed
    {
        $params = [];

        if ($categories !== '') {
            $params['traceConfig'] = ['includedCategories' => explode(',', $categories)];
        }

        if ($transferMode !== '') {
            $params['transferMode'] = $transferMode;
        }

        if ($bufferUsageReportingInterval > 0) {
            $params['bufferUsageReportingInterval'] = $bufferUsageReportingInterval;
        }

        return $this->call('Tracing.start', $params);
    }

    /**
     * End tracing
     * 
     * @return mixed The response
     */
    public function endTracing(): mixed
    {
        return $this->call('Tracing.end');
    }

    /**
     * Get available tracing categories
     * 
     * @return mixed The categories
     */
    public function getTracingCategories(): mixed
    {
        return $this->call('Tracing.getCategories');
    }

    /**
     * Request a tracing memory dump
     * 
     * @param bool $deterministic Whether to be deterministic
     * 
     * @return mixed The response
     */
    public function requestTracingMemoryDump(bool $deterministic = false): mixed
    {
        return $this->call('Tracing.requestMemoryDump', ['deterministic' => $deterministic]);
    }

    /**
     * Get browser version
     * 
     * @return mixed The browser version
     */
    public function getVersion(): mixed
    {
        return $this->call('Browser.getVersion');
    }

    /**
     * Get the window for a target
     * 
     * @param string $targetId Optional target ID
     * 
     * @return mixed The window information
     */
    public function getWindowForTarget(string $targetId = ''): mixed
    {
        $params = [];

        if ($targetId !== '') {
            $params['targetId'] = $targetId;
        }

        return $this->call('Browser.getWindowForTarget', $params);
    }

    /**
     * Set window bounds
     * 
     * @param int $windowId The window ID
     * @param array $bounds The bounds object
     * 
     * @return mixed The response
     */
    public function setWindowBounds(int $windowId, array $bounds): mixed
    {
        return $this->call('Browser.setWindowBounds', [
            'windowId' => $windowId,
            'bounds' => $bounds,
        ]);
    }

    /**
     * Get window bounds
     * 
     * @param int $windowId The window ID
     * 
     * @return mixed The bounds
     */
    public function getWindowBounds(int $windowId): mixed
    {
        return $this->call('Browser.getWindowBounds', ['windowId' => $windowId]);
    }

    /**
     * Get histograms
     * 
     * @param string $query Optional query filter
     * @param bool $delta Whether to return delta
     * 
     * @return mixed The histograms
     */
    public function getHistograms(string $query = '', bool $delta = false): mixed
    {
        $params = [];

        if ($query !== '') {
            $params['query'] = $query;
        }

        if ($delta) {
            $params['delta'] = true;
        }

        return $this->call('Browser.getHistograms', $params);
    }

    /**
     * Get a specific histogram
     * 
     * @param string $name The histogram name
     * @param bool $delta Whether to return delta
     * 
     * @return mixed The histogram
     */
    public function getHistogram(string $name, bool $delta = false): mixed
    {
        $params = ['name' => $name];

        if ($delta) {
            $params['delta'] = true;
        }

        return $this->call('Browser.getHistogram', $params);
    }

    /**
     * Grant permissions
     * 
     * @param array $permissions The permissions to grant
     * @param string $origin Optional origin
     * @param string $browserContextId Optional context ID
     * 
     * @return mixed The response
     */
    public function grantPermissions(array $permissions, string $origin = '', string $browserContextId = ''): mixed
    {
        $params = ['permissions' => $permissions];

        if ($origin !== '') {
            $params['origin'] = $origin;
        }

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Browser.grantPermissions', $params);
    }

    /**
     * Reset permissions
     * 
     * @param string $browserContextId Optional context ID
     * 
     * @return mixed The response
     */
    public function resetPermissions(string $browserContextId = ''): mixed
    {
        $params = [];

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Browser.resetPermissions', $params);
    }

    /**
     * Set a specific permission
     * 
     * @param array $permission The permission object
     * @param string $setting The permission setting
     * @param string $origin Optional origin
     * @param string $browserContextId Optional context ID
     * 
     * @return mixed The response
     */
    public function setPermission(array $permission, string $setting, string $origin = '', string $browserContextId = ''): mixed
    {
        $params = [
            'permission' => $permission,
            'setting' => $setting,
        ];

        if ($origin !== '') {
            $params['origin'] = $origin;
        }

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        return $this->call('Browser.setPermission', $params);
    }

    /**
     * Set download behavior
     * 
     * @param string $behavior The download behavior
     * @param string $downloadPath Optional download path
     * @param string $browserContextId Optional context ID
     * @param bool $eventsEnabled Whether to enable events
     * 
     * @return mixed The response
     */
    public function setDownloadBehavior(string $behavior, string $downloadPath = '', string $browserContextId = '', bool $eventsEnabled = false): mixed
    {
        $params = ['behavior' => $behavior];

        if ($downloadPath !== '') {
            $params['downloadPath'] = $downloadPath;
        }

        if ($browserContextId !== '') {
            $params['browserContextId'] = $browserContextId;
        }

        if ($eventsEnabled) {
            $params['eventsEnabled'] = true;
        }

        return $this->call('Browser.setDownloadBehavior', $params);
    }

    /**
     * Close the browser
     * 
     * @return mixed The response
     */
    public function closeBrowser(): mixed
    {
        return $this->call('Browser.close');
    }

    /**
     * Crash the browser
     * 
     * @return mixed The response
     */
    public function crashBrowser(): mixed
    {
        return $this->call('Browser.crash');
    }

    /**
     * Crash the GPU process
     * 
     * @return mixed The response
     */
    public function crashGpuProcess(): mixed
    {
        return $this->call('Browser.crashGpuProcess');
    }

    /**
     * Get system information
     * 
     * @return mixed The system information
     */
    public function getSystemInfo(): mixed
    {
        return $this->call('SystemInfo.getInfo');
    }

    /**
     * Get process information
     * 
     * @return mixed The process information
     */
    public function getProcessInfo(): mixed
    {
        return $this->call('SystemInfo.getProcessInfo');
    }

    /**
     * Read from an IO handle
     * 
     * @param string $handle The IO handle
     * @param int $offset Optional offset
     * @param int $size Optional size
     * 
     * @return mixed The data
     */
    public function readIO(string $handle, int $offset = 0, int $size = 0): mixed
    {
        $params = ['handle' => $handle];

        if ($offset > 0) {
            $params['offset'] = $offset;
        }

        if ($size > 0) {
            $params['size'] = $size;
        }

        return $this->call('IO.read', $params);
    }

    /**
     * Close an IO handle
     * 
     * @param string $handle The IO handle
     * 
     * @return mixed The response
     */
    public function closeIO(string $handle): mixed
    {
        return $this->call('IO.close', ['handle' => $handle]);
    }

    /**
     * Resolve a blob to an IO handle
     * 
     * @param string $objectId The blob object ID
     * 
     * @return mixed The IO handle
     */
    public function resolveBlobIO(string $objectId): mixed
    {
        return $this->call('IO.resolveBlob', ['objectId' => $objectId]);
    }

    /**
     * Enable the Media domain
     * 
     * @return mixed The response
     */
    public function enableMedia(): mixed
    {
        return $this->call('Media.enable');
    }

    /**
     * Disable the Media domain
     * 
     * @return mixed The response
     */
    public function disableMedia(): mixed
    {
        return $this->call('Media.disable');
    }

    /**
     * Enable the Audits domain
     * 
     * @return mixed The response
     */
    public function enableAudits(): mixed
    {
        return $this->call('Audits.enable');
    }

    /**
     * Disable the Audits domain
     * 
     * @return mixed The response
     */
    public function disableAudits(): mixed
    {
        return $this->call('Audits.disable');
    }

    /**
     * Get encoded response
     * 
     * @param string $requestId The request ID
     * @param string $encoding The encoding type
     * @param float $quality The quality
     * @param bool $sizeOnly Whether to return size only
     * 
     * @return mixed The encoded response
     */
    public function getEncodedResponse(string $requestId, string $encoding, float $quality = 1, bool $sizeOnly = false): mixed
    {
        return $this->call('Audits.getEncodedResponse', [
            'requestId' => $requestId,
            'encoding' => $encoding,
            'quality' => $quality,
            'sizeOnly' => $sizeOnly,
        ]);
    }

    /**
     * Check contrast in the page
     * 
     * @param bool $reportAAA Whether to report AAA standard
     * 
     * @return mixed The contrast check results
     */
    public function checkContrast(bool $reportAAA = false): mixed
    {
        $params = [];
        if ($reportAAA) {
            $params['reportAAA'] = true;
        }

        return $this->call('Audits.checkContrast', $params);
    }

    /**
     * Check for form issues
     * 
     * @return mixed The form issues
     */
    public function checkFormsIssues(): mixed
    {
        return $this->call('Audits.checkFormsIssues');
    }

    /**
     * Enable the LayerTree domain
     * 
     * @return mixed The response
     */
    public function enableLayerTree(): mixed
    {
        return $this->call('LayerTree.enable');
    }

    /**
     * Disable the LayerTree domain
     * 
     * @return mixed The response
     */
    public function disableLayerTree(): mixed
    {
        return $this->call('LayerTree.disable');
    }

    /**
     * Get compositing reasons for a layer
     * 
     * @param string $layerId The layer ID
     * 
     * @return mixed The compositing reasons
     */
    public function compositingReasons(string $layerId): mixed
    {
        return $this->call('LayerTree.compositingReasons', ['layerId' => $layerId]);
    }

    /**
     * Take a snapshot of a layer
     * 
     * @param string $layerId The layer ID
     * 
     * @return mixed The snapshot ID
     */
    public function makeSnapshot(string $layerId): mixed
    {
        return $this->call('LayerTree.makeSnapshot', ['layerId' => $layerId]);
    }

    /**
     * Load a snapshot from tiles
     * 
     * @param array $tiles The tile data
     * 
     * @return mixed The snapshot ID
     */
    public function loadSnapshot(array $tiles): mixed
    {
        return $this->call('LayerTree.loadSnapshot', ['tiles' => $tiles]);
    }

    /**
     * Release a snapshot
     * 
     * @param string $snapshotId The snapshot ID
     * 
     * @return mixed The response
     */
    public function releaseSnapshot(string $snapshotId): mixed
    {
        return $this->call('LayerTree.releaseSnapshot', ['snapshotId' => $snapshotId]);
    }

    /**
     * Replay a snapshot
     * 
     * @param string $snapshotId The snapshot ID
     * @param int $fromStep Starting step
     * @param int $toStep Ending step
     * @param float $scale The zoom scale
     * 
     * @return mixed The response
     */
    public function replaySnapshot(string $snapshotId, int $fromStep = 0, int $toStep = 0, float $scale = 1): mixed
    {
        $params = ['snapshotId' => $snapshotId];
        if ($fromStep > 0) {
            $params['fromStep'] = $fromStep;
        }

        if ($toStep > 0) {
            $params['toStep'] = $toStep;
        }

        if ($scale !== 1.0) {
            $params['scale'] = $scale;
        }

        return $this->call('LayerTree.replaySnapshot', $params);
    }

    /**
     * Profile a snapshot
     * 
     * @param string $snapshotId The snapshot ID
     * @param int $minRepeatCount Minimum repeat count
     * @param float $minDuration Minimum duration
     * @param array $clipRect Optional clipping rectangle
     * 
     * @return mixed The profile results
     */
    public function profileSnapshot(string $snapshotId, int $minRepeatCount = 1, float $minDuration = 0, array $clipRect = []): mixed
    {
        $params = ['snapshotId' => $snapshotId];
        if ($minRepeatCount > 1) {
            $params['minRepeatCount'] = $minRepeatCount;
        }

        if ($minDuration > 0) {
            $params['minDuration'] = $minDuration;
        }

        if (!empty($clipRect)) {
            $params['clipRect'] = $clipRect;
        }

        return $this->call('LayerTree.profileSnapshot', $params);
    }

    // --- WebAudio ---

    /**
     * Enable the WebAudio domain
     * 
     * @return mixed The response
     */
    public function enableWebAudio(): mixed
    {
        return $this->call('WebAudio.enable');
    }

    /**
     * Disable the WebAudio domain
     * 
     * @return mixed The response
     */
    public function disableWebAudio(): mixed
    {
        return $this->call('WebAudio.disable');
    }

    /**
     * Get real-time data from a web audio context
     * 
     * @param string $contextId The audio context ID
     * 
     * @return mixed The realtime data
     */
    public function getRealtimeData(string $contextId): mixed
    {
        return $this->call('WebAudio.getRealtimeData', ['contextId' => $contextId]);
    }

    /**
     * Enable the WebAuthn domain
     * 
     * @param bool $enableUI Whether to enable UI
     * 
     * @return mixed The response
     */
    public function enableWebAuthn(bool $enableUI = false): mixed
    {
        return $this->call('WebAuthn.enable', ['enableUI' => $enableUI]);
    }

    /**
     * Disable the WebAuthn domain
     * 
     * @return mixed The response
     */
    public function disableWebAuthn(): mixed
    {
        return $this->call('WebAuthn.disable');
    }

    /**
     * Add a virtual authenticator
     * 
     * @param array $options The authenticator options
     * 
     * @return mixed The authenticator ID
     */
    public function addVirtualAuthenticator(array $options): mixed
    {
        return $this->call('WebAuthn.addVirtualAuthenticator', ['options' => $options]);
    }

    /**
     * Remove a virtual authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * 
     * @return mixed The response
     */
    public function removeVirtualAuthenticator(string $authenticatorId): mixed
    {
        return $this->call('WebAuthn.removeVirtualAuthenticator', ['authenticatorId' => $authenticatorId]);
    }

    /**
     * Add a credential to an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * @param array $credential The credential
     * 
     * @return mixed The response
     */
    public function addCredential(string $authenticatorId, array $credential): mixed
    {
        return $this->call('WebAuthn.addCredential', [
            'authenticatorId' => $authenticatorId,
            'credential' => $credential,
        ]);
    }

    /**
     * Get a credential from an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * @param string $credentialId The credential ID
     * 
     * @return mixed The credential
     */
    public function getCredential(string $authenticatorId, string $credentialId): mixed
    {
        return $this->call('WebAuthn.getCredential', [
            'authenticatorId' => $authenticatorId,
            'credentialId' => $credentialId,
        ]);
    }

    /**
     * Get all credentials from an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * 
     * @return mixed The credentials
     */
    public function getCredentials(string $authenticatorId): mixed
    {
        return $this->call('WebAuthn.getCredentials', ['authenticatorId' => $authenticatorId]);
    }

    /**
     * Remove a credential from an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * @param string $credentialId The credential ID
     * 
     * @return mixed The response
     */
    public function removeCredential(string $authenticatorId, string $credentialId): mixed
    {
        return $this->call('WebAuthn.removeCredential', [
            'authenticatorId' => $authenticatorId,
            'credentialId' => $credentialId,
        ]);
    }

    /**
     * Clear all credentials from an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * 
     * @return mixed The response
     */
    public function clearCredentials(string $authenticatorId): mixed
    {
        return $this->call('WebAuthn.clearCredentials', ['authenticatorId' => $authenticatorId]);
    }

    /**
     * Set user verified status on an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * @param bool $isUserVerified Whether the user is verified
     * 
     * @return mixed The response
     */
    public function setUserVerified(string $authenticatorId, bool $isUserVerified): mixed
    {
        return $this->call('WebAuthn.setUserVerified', [
            'authenticatorId' => $authenticatorId,
            'isUserVerified' => $isUserVerified,
        ]);
    }

    /**
     * Set automatic presence simulation on an authenticator
     * 
     * @param string $authenticatorId The authenticator ID
     * @param bool $enabled Whether to enable automatic presence
     * 
     * @return mixed The response
     */
    public function setAutomaticPresenceSimulation(string $authenticatorId, bool $enabled): mixed
    {
        return $this->call('WebAuthn.setAutomaticPresenceSimulation', [
            'authenticatorId' => $authenticatorId,
            'enabled' => $enabled,
        ]);
    }

    // --- BackgroundService ---

    /**
     * Start observing background service events
     * 
     * @param string $service The service name
     * 
     * @return mixed The response
     */
    public function startObserving(string $service): mixed
    {
        return $this->call('BackgroundService.startObserving', ['service' => $service]);
    }

    /**
     * Stop observing background service events
     * 
     * @param string $service The service name
     * 
     * @return mixed The response
     */
    public function stopObserving(string $service): mixed
    {
        return $this->call('BackgroundService.stopObserving', ['service' => $service]);
    }

    /**
     * Set recording status for background services
     * 
     * @param bool $shouldRecord Whether to record
     * @param string $service The service name
     * 
     * @return mixed The response
     */
    public function setRecording(bool $shouldRecord, string $service): mixed
    {
        return $this->call('BackgroundService.setRecording', [
            'shouldRecord' => $shouldRecord,
            'service' => $service,
        ]);
    }

    /**
     * Clear all background service events
     * 
     * @param string $service The service name
     * 
     * @return mixed The response
     */
    public function clearEvents(string $service): mixed
    {
        return $this->call('BackgroundService.clearEvents', ['service' => $service]);
    }

    /**
     * Enable the Inspector domain
     * 
     * @return mixed The response
     */
    public function enableInspector(): mixed
    {
        return $this->call('Inspector.enable');
    }

    /**
     * Disable the Inspector domain
     * 
     * @return mixed The response
     */
    public function disableInspector(): mixed
    {
        return $this->call('Inspector.disable');
    }

    /**
     * Enable the DOM Storage domain
     * 
     * @return mixed The response
     */
    public function enableDOMStorage(): mixed
    {
        return $this->call('DOMStorage.enable');
    }

    /**
     * Disable the DOM Storage domain
     * 
     * @return mixed The response
     */
    public function disableDOMStorage(): mixed
    {
        return $this->call('DOMStorage.disable');
    }

    /**
     * Clear DOM storage items
     * 
     * @param array $storageId The storage ID
     * 
     * @return mixed The response
     */
    public function clearDOMStorage(array $storageId): mixed
    {
        return $this->call('DOMStorage.clear', ['storageId' => $storageId]);
    }

    /**
     * Get DOM storage items
     * 
     * @param array $storageId The storage ID
     * 
     * @return mixed The items
     */
    public function getDOMStorageItems(array $storageId): mixed
    {
        return $this->call('DOMStorage.getDOMStorageItems', ['storageId' => $storageId]);
    }

    /**
     * Set a DOM storage item
     * 
     * @param array $storageId The storage ID
     * @param string $key The item key
     * @param string $value The item value
     * 
     * @return mixed The response
     */
    public function setDOMStorageItem(array $storageId, string $key, string $value): mixed
    {
        return $this->call('DOMStorage.setDOMStorageItem', [
            'storageId' => $storageId,
            'key' => $key,
            'value' => $value,
        ]);
    }

    /**
     * Remove a DOM storage item
     * 
     * @param array $storageId The storage ID
     * @param string $key The item key
     * 
     * @return mixed The response
     */
    public function removeDOMStorageItem(array $storageId, string $key): mixed
    {
        return $this->call('DOMStorage.removeDOMStorageItem', [
            'storageId' => $storageId,
            'key' => $key,
        ]);
    }

    /**
     * Enable the Database domain
     * 
     * @return mixed The response
     */
    public function enableDatabase(): mixed
    {
        return $this->call('Database.enable');
    }

    /**
     * Disable the Database domain
     * 
     * @return mixed The response
     */
    public function disableDatabase(): mixed
    {
        return $this->call('Database.disable');
    }

    /**
     * Get table names from a database
     * 
     * @param string $databaseId The database ID
     * 
     * @return mixed The table names
     */
    public function getDatabaseTableNames(string $databaseId): mixed
    {
        return $this->call('Database.getDatabaseTableNames', ['databaseId' => $databaseId]);
    }

    /**
     * Execute SQL query on a database
     * 
     * @param string $databaseId The database ID
     * @param string $query The SQL query
     * 
     * @return mixed The query result
     */
    public function executeSQL(string $databaseId, string $query): mixed
    {
        return $this->call('Database.executeSQL', [
            'databaseId' => $databaseId,
            'query' => $query,
        ]);
    }

    /**
     * Reads frames via readFrame until a command response with the matching id is received.
     * Skips event messages (no id) and messages whose id does not match.
     *
     * @param int $expectedId Message id to wait for
     * @param int $timeout Maximum wait time in seconds
     * @return array Decoded response payload
     * @throws Exception
     */
    private function readResponseForId(int $expectedId, int $timeout = 30): array
    {
        $start = time();

        while (true) {
            if (time() - $start > $timeout) {
                throw new Exception("Timed out waiting for response id={$expectedId}");
            }

            $frame = WebSocket::readFrame($this->socket);

            if ($frame === false || $frame === null || $frame === '') {
                usleep(50_000);
                continue;
            }

            $data = json_decode($frame, true);

            if (!is_array($data)) {
                continue;
            }

            // Return when the response id matches
            if (isset($data['id']) && (int) $data['id'] === $expectedId) {
                return $data;
            }

            // Skip events without id or with a different id
        }
    }

    /**
     * Send a message to the Chrome DevTools Protocol
     * 
     * @param array $message The message to send
     * @param int $id The message ID
     * 
     * @return object The response from the DevTools Protocol
     * 
     * @throws Exception
     */
    private function sendMessage(array $message = [], $id = 3): mixed
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }

        WebSocket::sendToWebSocket($this->socket, json_encode($message));

        // If id is set, wait for a matching readFrame response; otherwise return the first frame
        if (isset($message['id'])) {
            $res = $this->readResponseForId($message['id']);
            return (object) $res;
        }

        $frame = WebSocket::readFrame($this->socket);
        return json_decode($frame ?? '{}');
    }

    /**
     * Attach to a target in Chrome DevTools
     * 
     * @param string $targetId The target ID to attach to
     * 
     * @return ArrayObject|bool|StringObject The session ID for the attached target
     * 
     * @throws Exception
     */
    public function attachTarget(string $targetId): ArrayObject|bool|StringObject|string
    {
        if (!$this->isActive()) {
            throw new Exception('Socket is not activated');
        }
        $msgId = $this->getNextId();
        WebSocket::sendToWebSocket($this->socket, json_encode([
            'id' => $msgId,
            'method' => 'Target.attachToTarget',
            'params' => ['targetId' => $targetId, 'flatten' => true]
        ]));
        $res = $this->readResponseForId($msgId);
        return $res['result']['sessionId'] ?? '';
    }

    /**
     * Shutdown the Chrome DevTools session
     * 
     * @return void
     */
    public function shutdown(): void
    {
        $cmd = 'for /f "tokens=5" %a in (\'netstat -ano ^| find \":'
            . $this->debugPort
            . '"\') do taskkill /F /PID %a';
        exec($cmd);
    }

    /**
     * Delete throwaway profiles left behind by earlier runs.
     *
     * A profile whose files were still locked when close() gave up would otherwise stay in the temp
     * directory indefinitely. Only directories older than the grace period are considered, so a
     * profile belonging to a browser launched by another process is never touched.
     *
     * @return void
     */
    private function purgeStaleProfiles(): void
    {
        $pattern = sys_get_temp_dir() . DIRECTORY_SEPARATOR . self::PROFILE_PREFIX . '*';
        $candidates = glob($pattern, GLOB_ONLYDIR);

        if ($candidates === false) {
            return;
        }

        $cutoff = time() - self::PROFILE_GRACE_SECONDS;

        foreach ($candidates as $candidate) {
            $modifiedAt = filemtime($candidate);

            if ($modifiedAt === false || $modifiedAt > $cutoff) {
                continue;
            }

            $this->temporaryProfilePath = $candidate;
            $this->removeTemporaryProfile();
        }
    }

    /**
     * Create the throwaway profile directory for a launched browser.
     *
     * @return string Absolute path to the created directory
     *
     * @throws Exception When the directory cannot be created.
     */
    private function createTemporaryProfile(): string
    {
        // A second launch() on the same instance would otherwise orphan the first profile, leaving
        // it in the temp directory with nothing holding a reference to it.
        if ($this->temporaryProfilePath !== null) {
            $this->removeTemporaryProfile();
        }

        $this->purgeStaleProfiles();

        $path = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . self::PROFILE_PREFIX
            . bin2hex(random_bytes(8));

        if (!mkdir($path, 0700, true) && !is_dir($path)) {
            throw new Exception(sprintf('Unable to create the Chrome profile directory "%s".', $path));
        }

        $this->temporaryProfilePath = $path;

        return $path;
    }

    /**
     * Block until the launched browser has actually exited.
     *
     * taskkill returns as soon as the termination is signalled, so the profile files stay locked for
     * a short while afterwards. Deleting them before the process is gone fails on Windows.
     *
     * @param int $timeoutMicroseconds Upper bound on the wait
     *
     * @return bool True when the process is known to be gone
     */
    private function waitForProcessExit(int $timeoutMicroseconds = 3_000_000): bool
    {
        if (!is_resource($this->process)) {
            return true;
        }

        $waited = 0;
        $interval = 50_000;

        while ($waited < $timeoutMicroseconds) {
            if (proc_get_status($this->process)['running'] !== true) {
                return true;
            }

            usleep($interval);
            $waited += $interval;
        }

        return false;
    }

    /**
     * Recursively delete the throwaway profile directory, if one was created.
     *
     * Cleanup is best-effort. Chrome can still hold a handle on a profile file after the process
     * has gone, and the directory lives under the system temp path, so a residual file is left for
     * the operating system to reclaim rather than treated as a failure of close(). The filesystem
     * warnings this would otherwise raise are captured and counted instead of being suppressed with
     * the error-control operator, so a caller can still see that cleanup was incomplete.
     *
     * @return bool True when the directory is gone
     */
    private function removeTemporaryProfile(): bool
    {
        $path = $this->temporaryProfilePath;
        $this->temporaryProfilePath = null;

        if ($path === null || !is_dir($path)) {
            return true;
        }

        set_error_handler(static fn (): bool => true);

        try {
            // Chrome releases its handles a short while after the process dies, so a first pass can
            // leave locked files behind that a later pass removes cleanly. The delay grows between
            // attempts because the wait is usually unnecessary and occasionally close to a second.
            $delay = 100_000;

            for ($attempt = 0; $attempt < 5; $attempt++) {
                $entries = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::CHILD_FIRST
                );

                foreach ($entries as $entry) {
                    if ($entry->isDir()) {
                        rmdir($entry->getPathname());
                        continue;
                    }

                    unlink($entry->getPathname());
                }

                rmdir($path);
                clearstatcache(true, $path);

                if (!is_dir($path)) {
                    return true;
                }

                usleep($delay);
                $delay *= 2;
            }
        } finally {
            restore_error_handler();
        }

        // Every attempt is exhausted and the directory is still present, so a file remains locked.
        // It sits under the system temp path and is left for the operating system to reclaim.
        return false;
    }

    /**
     * Shut down only the browser this instance launched, along with its child processes.
     *
     * Prefer this over {@see shutdownAll()}, which terminates every Chrome on the machine including
     * browsers the user has open.
     *
     * @return bool True when a launched browser was terminated
     */
    public function close(): bool
    {
        $terminated = false;

        if ($this->processId !== null) {
            // /T covers the renderer, GPU and utility processes Chrome spawns beneath the launcher.
            OperationSystem::executeShell(
                sprintf('taskkill /F /T /PID %d >nul 2>&1', $this->processId)
            );
            $terminated = true;
        }

        if (is_resource($this->process)) {
            proc_terminate($this->process);
            $this->waitForProcessExit();
            proc_close($this->process);
            $terminated = true;
        }

        $this->process = null;
        $this->processId = null;

        $this->removeTemporaryProfile();

        return $terminated;
    }

    /**
     * Shutdown every Chrome instance on the machine.
     *
     * This terminates browsers this object never launched, including the user's own windows and any
     * other automation running concurrently. Use {@see close()} unless killing unrelated browsers is
     * genuinely the intent.
     *
     * @return void
     */
    public function shutdownAll(): void
    {
        $cmd = "taskkill /F /IM chrome.exe >nul 2>&1";
        OperationSystem::executeShell($cmd);
    }

    /**
     * Destructor
     * 
     * @return void
     */
    public function __destruct()
    {
        if ($this->socket != null && is_resource($this->socket)) {
            fclose($this->socket);
        }

        // A caller that forgets close() would otherwise leave an orphaned browser and its profile
        // directory behind for the lifetime of the machine.
        $this->close();
    }

    /**
     * Get the debugging URL of the Chrome DevTools
     * 
     * @return string The debugging URL
     */
    public function getDebugUrl(): string
    {
        return $this->debugUrl;
    }

    /**
     * Get the debugging port
     * 
     * @return int The debugging port
     */
    public function getDebugPort(): int
    {
        return $this->debugPort;
    }

    /**
     * Get the Chrome executable path
     * 
     * @return string The Chrome executable path
     */
    public function getChromePath(): string
    {
        return $this->chromePath;
    }

    /**
     * Get the session ID
     * 
     * @return string The session ID
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Get the WebSocket connection
     * 
     * @return resource The WebSocket connection
     */
    public function getSocket(): mixed
    {
        return $this->socket;
    }

    /**
     * Get the message ID counter
     * 
     * @return int The message ID counter
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set the message ID counter
     * 
     * @param int $id The message ID counter
     * 
     * @return void
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * Set the debugging URL of the Chrome DevTools
     * 
     * @param string $debugUrl The debugging URL
     * 
     * @return void
     */
    public function setDebugUrl(string $debugUrl): void
    {
        $this->debugUrl = $debugUrl;
    }

    /**
     * Set the debugging port
     * 
     * @param int $debugPort The debugging port
     * 
     * @return void
     */
    public function setDebugPort(int $debugPort): void
    {
        $this->debugPort = $debugPort;
    }

    /**
     * Set the Chrome executable path
     * 
     * @param string $chromePath The Chrome executable path
     * 
     * @return void
     */
    public function setChromePath(string $chromePath): void
    {
        $this->chromePath = $chromePath;
    }

    /**
     * Set the WebSocket connection
     * 
     * @param resource $socket The WebSocket connection
     * 
     * @return void
     */
    public function setSocket(mixed $socket): void
    {
        $this->socket = $socket;
    }

    /**
     * Get the current state of the Chrome DevTools session
     * 
     * @return array{
     *  chromePath: string,
     *  debugPort: int,
     *  debugUrl: string,
     *  socket: resource,
     *  id: int
     * } The current state of the session
     */
    public function getState(): array
    {
        return [
            'chromePath' => $this->chromePath,
            'debugPort' => $this->debugPort,
            'debugUrl' => $this->debugUrl,
            'sessionId' => $this->sessionId,
            'socket' => $this->socket,
            'id' => $this->id
        ];
    }

    /**
     * Set the state of the Chrome DevTools session
     * 
     * @param array{
     *  chromePath: string,
     *  debugPort: int,
     *  debugUrl: string,
     *  socket: resource,
     *  id: int
     * } $state The state to set
     * 
     * @return void
     */
    public function setState(array $state): void
    {
        $this->chromePath = $state['chromePath'] ?? $this->chromePath;
        $this->debugPort = $state['debugPort'] ?? $this->debugPort;
        $this->debugUrl = $state['debugUrl'] ?? $this->debugUrl;
        $this->sessionId = $state['sessionId'] ?? $this->sessionId;
        $this->socket = $state['socket'] ?? $this->socket;
        $this->id = $state['id'] ?? $this->id;
    }

    /**
     * Reset the Chrome DevTools session
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->chromePath = $this->getBinaryPath();
        $this->debugPort = 9222;
        $this->debugUrl = '';
        $this->sessionId = '';
        $this->socket = null;
        $this->id = 0;
    }

    /**
     * Check if the Chrome DevTools session is active
     * 
     * @return bool True if the session is active, false otherwise
     */
    public function isActive(): bool
    {
        return is_resource($this->socket);
    }

    /**
     * Check if the Chrome DevTools session is connected
     * 
     * @return bool True if the session is connected, false otherwise
     */
    public function isConnected(): bool
    {
        return $this->isActive() && !feof($this->socket);
    }

    /**
     * Check if the Chrome DevTools session is closed
     * 
     * @return bool True if the session is closed, false otherwise
     */
    public function isClosed(): bool
    {
        return !$this->isActive() || feof($this->socket);
    }

    /**
     * Check if the Chrome DevTools session is valid
     * 
     * @return bool True if the session is valid, false otherwise
     */
    public function isValid(): bool
    {
        return $this->isActive() && !$this->isClosed();
    }

    /**
     * Check if the Chrome DevTools session is ready
     * 
     * @return bool True if the session is ready, false otherwise
     */
    public function isReady(): bool
    {
        return $this->isValid() && $this->sessionId !== '';
    }

    #endregion
}
