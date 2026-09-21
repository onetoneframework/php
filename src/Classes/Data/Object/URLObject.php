<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Data;

use InvalidArgumentException;
use Clover\Classes\Data\StringObject as StringObject;
use function is_array;
use function sprintf;
use function defined;
use function array_key_exists;

/**
 * Class URLObject
 *
 * Represents a URL and provides methods to extract components and manipulate query parameters.
 */
#[\AllowDynamicProperties]
class URLObject extends StringObject
{
    #region Properties

    /**
     * List of known two-part public suffixes used by TLD/registrable-domain heuristics.
     *
     * This is a pragmatic subset; full Public Suffix List support is out of scope.
     *
     * @var string[]
     */
    private const MULTI_PART_TLDS = [
        'co.uk',
        'co.jp',
        'co.kr',
        'co.in',
        'co.za',
        'co.nz',
        'co.il',
        'co.id',
        'co.th',
        'com.au',
        'com.br',
        'com.mx',
        'com.tw',
        'com.cn',
        'com.hk',
        'com.sg',
        'com.ar',
        'com.tr',
        'com.ua',
        'ac.uk',
        'gov.uk',
        'org.uk',
        'net.uk',
        'ac.jp',
        'go.jp',
        'ne.jp',
        'or.jp',
        'edu.au',
        'gov.au',
        'org.au',
        'net.au',
        'or.kr',
        'ne.kr',
        'go.kr',
        'ac.kr',
        're.kr',
        'pe.kr',
    ];

    /**
     * Common tracking / analytics query parameters stripped by {@see stripCommonTrackingParams()}.
     *
     * @var string[]
     */
    private const TRACKING_PARAMS = [
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'utm_id',
        'gclid',
        'gbraid',
        'wbraid',
        'dclid',
        'fbclid',
        'msclkid',
        'yclid',
        'mc_cid',
        'mc_eid',
        '_hsenc',
        '_hsmi',
        'mkt_tok',
        'vero_id',
        'igshid',
        'ref',
        'ref_src',
        'spm',
        'scm',
    ];

    /**
     * The raw URL string.
     *
     * @var mixed
     */
    protected $rawData;

    /**
     * @var array{query: string, scheme: string, host: string, port: int, user: string, pass: string, fragment: string} $parsedComponents
     */
    private array $parsedComponents = [];

    #end region

    #region Function

    /**
     * Constructor for URLObject.
     *
     * @param string $data The URL as a string.
     */
    public function __construct(string $data)
    {
        $this->rawData = $data;
        $this->parseComponents();
        parent::__construct($data);
    }

    /**
     * Returns the URL as a string.
     *
     * @return string The URL.
     */
    public function __toString(): string
    {
        return (string) $this->rawData;
    }

    /**
     * Parses the raw URL string into its components and stores them in the $parsedComponents property.
     *
     * @return void
     */
    private function parseComponents(): void
    {
        $this->parsedComponents = parse_url((string) $this->rawData) ?: [];
    }

    /**
     * Retrieves a specific component of the URL using parse_url.
     *
     * @param int $component The component to retrieve (e.g., PHP_URL_SCHEME, PHP_URL_HOST).
     * @return string|null The value of the component, or null if not found.
     */
    private function getComponent(int $component): ?string
    {
        $value = parse_url((string) $this->rawData, $component);

        if ($value === false) {
            throw new InvalidArgumentException("The uri \"{$this->rawData}\" appears to be malformed");
        }

        return $value !== false && $value !== null ? (string) $value : null;
    }

    /**
     * Builds a URL string from its components.
     *
     * @param array{query: string, scheme: string, host: string, port: int, user: string, pass: string, fragment: string} $components An associative array of URL components (scheme, host, port, user, pass, path, query, fragment).
     * @return string The constructed URL string.
     */
    private function buildUrl(array $components): string
    {
        $url = '';

        if (isset($components['scheme'])) {
            $url .= $components['scheme'] . '://';
        }

        if (isset($components['user'])) {
            $url .= $components['user'];
            if (isset($components['pass'])) {
                $url .= ':' . $components['pass'];
            }
            $url .= '@';
        }

        if (isset($components['host'])) {
            $url .= $components['host'];
        }

        if (isset($components['port'])) {
            $url .= ':' . $components['port'];
        }

        if (isset($components['path'])) {
            $url .= $components['path'];
        }

        if (isset($components['query'])) {
            $url .= '?' . $components['query'];
        }

        if (isset($components['fragment'])) {
            $url .= '#' . $components['fragment'];
        }

        return $url;
    }

    /**
     * Updates the raw URL data and re-parses the components.
     *
     * @param string $data The new URL string to set.
     * @return void
     */
    private function updateRawData(string $data): void
    {
        $this->rawData = $data;
        $this->parseComponents();
        $this->setRawData($data);
    }

    /**
     * Gets the scheme (protocol) from the URL.
     *
     * @return StringObject The scheme as a StringObject.
     */
    public function getScheme(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_SCHEME) ?? '');
    }

    /**
     * Gets the protocol from the URL.
     *
     * @return StringObject The protocol as a StringObject.
     */
    public function getProtocol(): StringObject
    {
        if (!defined('PHP_URL_SCHEME')) {
            preg_match("/^[^:]+(?=:\/\/)/i", $this->rawData, $matches);
            $data = is_array($matches) ? $matches[0] : "";
            return new StringObject($data);
        }

        return $this->getScheme();
    }

    /**
     * Gets the host from the URL.
     *
     * @return StringObject The host as a StringObject.
     */
    public function getHost(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_HOST) ?? '');
    }

    /**
     * Gets the host from the URL.
     *
     * @return StringObject The host as a StringObject.
     */
    public function getPort(): ?int
    {
        $port = $this->getComponent(PHP_URL_PORT);

        return $port !== null ? (int) $port : null;
    }

    /**
     * Gets the path from the URL.
     *
     * @return StringObject The path as a StringObject.
     */
    public function getPath(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_PATH) ?? '');
    }

    /**
     * Gets the username from the URL.
     *
     * @return StringObject The username as a StringObject.
     */
    public function getFragment(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_FRAGMENT) ?? '');
    }

    /**
     * Gets the username from the URL.
     *
     * @return StringObject The username as a StringObject.
     */
    public function getUser(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_USER) ?? '');
    }

    /**
     * Gets the password from the URL.
     *
     * @return StringObject The password as a StringObject.
     */
    public function getPass(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_PASS) ?? '');
    }

    /**
     * Retrieves the value of a specific query parameter from the URL.
     *
     * @param string $key The key of the query parameter to retrieve.
     * @return mixed The value of the query parameter, or null if not found.
     */
    public function getQueryParameter(string $key): mixed
    {
        $queryString = $this->getComponent(PHP_URL_QUERY);
        if ($queryString === null) {
            return null;
        }

        parse_str($queryString, $queryParameters);

        return $queryParameters[$key] ?? null;
    }

    /**
     * Retrieves all query parameters from the URL as an associative array.
     *
     * @return array An associative array of query parameters, or an empty array if no query string is present.
     */
    public function getQueryParameters(): array
    {
        $queryString = $this->getComponent(PHP_URL_QUERY);
        if ($queryString === null) {
            return [];
        }

        parse_str($queryString, $queryParameters);

        return $queryParameters;
    }

    /**
     * Sets or updates query parameters in the URL.
     *
     * @param array $queries An associative array of query parameters to set or update.
     * 
     * @return self
     */
    public function setQueryString(array $queries = []): self
    {
        $components = $this->parsedComponents;

        $existingParameters = [];
        if (isset($components['query'])) {
            parse_str($components['query'], $existingParameters);
        }

        $mergedParameters = array_merge($existingParameters, $queries);

        if (!empty($mergedParameters)) {
            $components['query'] = http_build_query($mergedParameters);
        } else {
            unset($components['query']);
        }

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Clears query parameters in the URL.
     *
     * @return self
     */
    public function clearQueryParameters(): static
    {
        $components = $this->parsedComponents;
        unset($components['query']);

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Removes a specific query parameter from the URL.
     *
     * @param string $key The key of the query parameter to remove.
     * @return $this The URLObject with the specified query parameter removed.
     */
    public function removeQueryParameter(string $key): static
    {
        $components = $this->parsedComponents;

        $parameters = [];
        if (isset($components['query'])) {
            parse_str($components['query'], $parameters);
        }

        unset($parameters[$key]);

        if (!empty($parameters)) {
            $components['query'] = http_build_query($parameters);
        } else {
            unset($components['query']);
        }

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Gets the file name from the URL.
     *
     * @return StringObject The file name as a StringObject.
     */
    public function getFileName(): StringObject
    {
        $path = $this->getComponent(PHP_URL_PATH);

        return new StringObject($path !== null ? basename($path) : '');
    }

    /**
     * Gets the file name from the URL.
     *
     * @return StringObject The file name as a StringObject.
     */
    public function getFileNameFromURL(): StringObject
    {
        return $this->getFileName();
    }

    /**
     * Gets the file extension from the URL.
     *
     * @return StringObject The file extension as a StringObject.
     */
    public function getExtension(): StringObject
    {
        $fileName = (string) $this->getFileName();
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return new StringObject($extension);
    }

    /**
     * Gets the domain from the URL.
     *
     * @param bool $withProtocol Whether to include the protocol in the returned domain.
     * @return StringObject The domain as a StringObject.
     */
    public function getDomain(bool $withProtocol = false): StringObject
    {
        $host = $this->getComponent(PHP_URL_HOST);
        if ($host === null) {
            return new StringObject('');
        }

        if ($withProtocol) {
            $scheme = $this->getComponent(PHP_URL_SCHEME);
            if ($scheme !== null) {
                return new StringObject(sprintf('%s://%s', $scheme, $host));
            }
        }

        return new StringObject($host);
    }

    /**
     * Gets the origin of the URL, which includes the scheme, host, and port (if present).
     *
     * @return StringObject The origin as a StringObject.
     */
    public function getOrigin(): StringObject
    {
        $scheme = $this->getComponent(PHP_URL_SCHEME);
        $host = $this->getComponent(PHP_URL_HOST);

        if ($scheme === null || $host === null) {
            return new StringObject('');
        }

        $origin = sprintf('%s://%s', $scheme, $host);

        $port = $this->getPort();
        if ($port !== null) {
            $origin .= ":{$port}";
        }

        return new StringObject($origin);
    }

    /**
     * Removes the domain from the URL.
     *
     * @return self The URLObject without the domain.
     */
    public function removeDomain(): static
    {
        $components = $this->parsedComponents;

        unset($components['scheme'], $components['host'], $components['port'], $components['user'], $components['pass']);

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Removes the port number from the URL if present.
     *
     * @return StringObject The URL without the port number.
     */
    public function removePort(): static
    {
        $components = $this->parsedComponents;

        unset($components['port']);

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Removes the fragment from the URL if present.
     *
     * @return StringObject The URL without the fragment.
     */
    public function removeFragment(): static
    {
        $components = $this->parsedComponents;

        unset($components['fragment']);

        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Retrieves the query string from the URL.
     *
     * @return StringObject The query string as a StringObject.
     */
    public function getQueryString(): StringObject
    {
        return new StringObject($this->getComponent(PHP_URL_QUERY) ?? '');
    }

    /**
     * Checks if a specific query parameter exists in the URL.
     *
     * @param string $key The key of the query parameter to check.
     * @return bool True if the query parameter exists, false otherwise.
     */
    public function hasQueryParameter(string $key): bool
    {
        $parameters = $this->getQueryParameters();

        return array_key_exists($key, $parameters);
    }

    /**
     * Checks if the URL is an absolute URL (i.e., has a scheme).
     *
     * @return bool True if the URL is absolute, false otherwise.
     */
    public function isAbsolute(): bool
    {
        return $this->getComponent(PHP_URL_SCHEME) !== null;
    }

    /**
     * Checks if the URL is a relative URL (i.e., does not have a scheme).
     *
     * @return bool True if the URL is relative, false otherwise.
     */
    public function isRelative(): bool
    {
        return !$this->isAbsolute();
    }

    /**
     * Returns a new URLObject with the specified scheme.
     *
     * @param string $scheme The scheme to set in the URL.
     * @return static A new URLObject instance with the updated scheme.
     */
    public function withScheme(string $scheme): static
    {
        $components = $this->parsedComponents;
        $components['scheme'] = $scheme;

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Returns a new URLObject with the specified host.
     *
     * @param string $host The host to set in the URL.
     * @return static A new URLObject instance with the updated host.
     */
    public function withHost(string $host): static
    {
        $components = $this->parsedComponents;
        $components['host'] = $host;

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Returns a new URLObject with the specified port.
     *
     * @param int|null $port The port number to set in the URL, or null to remove the port.
     * @return static A new URLObject instance with the updated port.
     */
    public function withPort(?int $port): static
    {
        $components = $this->parsedComponents;

        if ($port !== null) {
            $components['port'] = $port;
        } else {
            unset($components['port']);
        }

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Returns a new URLObject with the specified path.
     *
     * @param string $path The path to set in the URL.
     * @return static A new URLObject instance with the updated path.
     */
    public function withPath(string $path): static
    {
        $components = $this->parsedComponents;
        $components['path'] = $path;

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Returns a new URLObject with the specified fragment.
     *
     * @param string $fragment The fragment to set in the URL.
     * @return static A new URLObject instance with the updated fragment.
     */
    public function withFragment(string $fragment): static
    {
        $components = $this->parsedComponents;
        $components['fragment'] = $fragment;

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Authority component: user:pass@host:port (empty when host is missing).
     *
     * @return StringObject
     */
    public function getAuthority(): StringObject
    {
        $host = $this->getComponent(PHP_URL_HOST);
        if ($host === null || $host === '') {
            return new StringObject('');
        }
        $authority = '';
        $userInfo = (string) $this->getUserInfo();
        if ($userInfo !== '') {
            $authority .= $userInfo . '@';
        }
        $authority .= $host;
        $port = $this->getPort();
        if ($port !== null) {
            $authority .= ':' . $port;
        }

        return new StringObject($authority);
    }

    /**
     * User-info component: user[:pass]. Empty when absent.
     *
     * @return StringObject
     */
    public function getUserInfo(): StringObject
    {
        $user = $this->getComponent(PHP_URL_USER);
        if ($user === null || $user === '') {
            return new StringObject('');
        }
        $pass = $this->getComponent(PHP_URL_PASS);
        if ($pass !== null && $pass !== '') {
            return new StringObject($user . ':' . $pass);
        }

        return new StringObject($user);
    }

    /**
     * Host with port component: host[:port]. Empty when host is missing.
     *
     * @return StringObject
     */
    public function getHostAndPort(): StringObject
    {
        $host = $this->getComponent(PHP_URL_HOST);
        if ($host === null || $host === '') {
            return new StringObject('');
        }
        $port = $this->getPort();

        return new StringObject($port !== null ? "{$host}:{$port}" : $host);
    }

    /**
     * Path segments as an array (leading/trailing slashes stripped).
     *
     * @return string[]
     */
    public function getPathSegments(): array
    {
        $path = $this->getComponent(PHP_URL_PATH) ?? '';
        if ($path === '' || $path === '/') {
            return [];
        }

        return array_values(array_filter(explode('/', $path), static fn(string $s): bool => $s !== ''));
    }

    /**
     * First path segment, or null when the path is empty.
     *
     * @return string|null
     */
    public function getFirstPathSegment(): ?string
    {
        $segments = $this->getPathSegments();

        return $segments === [] ? null : $segments[0];
    }

    /**
     * Last path segment, or null when the path is empty.
     *
     * @return string|null
     */
    public function getLastPathSegment(): ?string
    {
        $segments = $this->getPathSegments();

        return $segments === [] ? null : $segments[count($segments) - 1];
    }

    /**
     * Parent directory portion of the path (similar to dirname).
     *
     * @return StringObject
     */
    public function getParentPath(): StringObject
    {
        $path = $this->getComponent(PHP_URL_PATH) ?? '';
        if ($path === '' || $path === '/') {
            return new StringObject($path);
        }
        $normalized = rtrim($path, '/');
        $idx = strrpos($normalized, '/');
        if ($idx === false || $idx === 0) {
            return new StringObject('/');
        }

        return new StringObject(substr($normalized, 0, $idx));
    }

    /**
     * Number of non-empty path segments (depth of the path).
     *
     * @return int
     */
    public function getPathDepth(): int
    {
        return count($this->getPathSegments());
    }

    /**
     * Top-level domain — uses a built-in two-part suffix list; falls back to the last label.
     *
     * @return StringObject
     */
    public function getTld(): StringObject
    {
        $host = strtolower($this->getComponent(PHP_URL_HOST) ?? '');
        if ($host === '' || $this->isIpHost()) {
            return new StringObject('');
        }
        foreach (self::MULTI_PART_TLDS as $tld) {
            if (str_ends_with($host, '.' . $tld)) {
                return new StringObject($tld);
            }
        }
        $parts = explode('.', $host);

        return new StringObject($parts === [] ? '' : (string) end($parts));
    }

    /**
     * Second-level-domain label (the label immediately left of the TLD).
     *
     * @return StringObject
     */
    public function getSecondLevelDomain(): StringObject
    {
        $host = strtolower($this->getComponent(PHP_URL_HOST) ?? '');
        if ($host === '' || $this->isIpHost()) {
            return new StringObject('');
        }
        $tld = (string) $this->getTld();
        if ($tld === '') {
            return new StringObject('');
        }
        $base = substr($host, 0, -(strlen($tld) + 1));
        if ($base === '' || $base === false) {
            return new StringObject('');
        }
        $parts = explode('.', $base);

        return new StringObject($parts === [] ? '' : (string) end($parts));
    }

    /**
     * Sub-domain portion — everything left of the registrable domain. Empty when absent.
     *
     * @return StringObject
     */
    public function getSubdomain(): StringObject
    {
        $host = strtolower($this->getComponent(PHP_URL_HOST) ?? '');
        $registrable = (string) $this->getRegistrableDomain();
        if ($host === '' || $registrable === '' || $host === $registrable) {
            return new StringObject('');
        }
        $sub = substr($host, 0, -(strlen($registrable) + 1));

        return new StringObject($sub === false ? '' : $sub);
    }

    /**
     * Registrable domain — second-level label joined with the TLD (heuristic).
     *
     * @return StringObject
     */
    public function getRegistrableDomain(): StringObject
    {
        $sld = (string) $this->getSecondLevelDomain();
        $tld = (string) $this->getTld();
        if ($sld === '' || $tld === '') {
            return new StringObject('');
        }

        return new StringObject($sld . '.' . $tld);
    }

    /**
     * Directory portion of the path (equivalent to dirname applied to the path).
     *
     * @return StringObject
     */
    public function getDirectory(): StringObject
    {
        $path = $this->getComponent(PHP_URL_PATH) ?? '';

        return new StringObject($path === '' ? '' : dirname($path));
    }

    /**
     * File name without extension (e.g. "/a/b/c.txt" → "c").
     *
     * @return StringObject
     */
    public function getFileBaseName(): StringObject
    {
        $file = (string) $this->getFileName();
        $dot = strrpos($file, '.');
        if ($dot === false || $dot === 0) {
            return new StringObject($file);
        }

        return new StringObject(substr($file, 0, $dot));
    }

    /**
     * Host split into its dot-separated labels.
     *
     * @return string[]
     */
    public function getDomainParts(): array
    {
        $host = $this->getComponent(PHP_URL_HOST) ?? '';
        if ($host === '' || $this->isIpHost()) {
            return $host === '' ? [] : [$host];
        }

        return explode('.', $host);
    }

    /**
     * Number of labels in the host (0 when there is no host).
     *
     * @return int
     */
    public function getHostLevel(): int
    {
        return count($this->getDomainParts());
    }

    /**
     * Query string prefixed with "?" when non-empty, empty string otherwise.
     *
     * @return StringObject
     */
    public function getQueryStringWithPrefix(): StringObject
    {
        $query = $this->getComponent(PHP_URL_QUERY);

        return new StringObject($query === null || $query === '' ? '' : '?' . $query);
    }

    /**
     * True when the scheme is exactly "http".
     *
     * @return bool
     */
    public function isHttp(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'http';
    }

    /**
     * True when the scheme is exactly "https".
     *
     * @return bool
     */
    public function isHttps(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'https';
    }

    /**
     * Alias for {@see isHttps()}.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        return $this->isHttps();
    }

    /**
     * True when the host points at the local machine (localhost, 127.0.0.0/8, ::1).
     *
     * @return bool
     */
    public function isLocalhost(): bool
    {
        $host = strtolower((string) $this->getComponent(PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        if ($host === 'localhost' || $host === '::1') {
            return true;
        }

        return str_starts_with($host, '127.');
    }

    /**
     * True when the host is a literal IP address (v4 or v6).
     *
     * @return bool
     */
    public function isIpHost(): bool
    {
        return $this->isIpv4Host() || $this->isIpv6Host();
    }

    /**
     * True when the host is a literal IPv4 address.
     *
     * @return bool
     */
    public function isIpv4Host(): bool
    {
        $host = (string) $this->getComponent(PHP_URL_HOST);

        return $host !== '' && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * True when the host is a literal IPv6 address.
     *
     * @return bool
     */
    public function isIpv6Host(): bool
    {
        $host = trim((string) $this->getComponent(PHP_URL_HOST), '[]');

        return $host !== '' && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    /**
     * True when the URL uses the mailto scheme.
     *
     * @return bool
     */
    public function isMailto(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'mailto';
    }

    /**
     * True when the URL uses the data scheme.
     *
     * @return bool
     */
    public function isDataUrl(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'data';
    }

    /**
     * True when the URL uses the file scheme.
     *
     * @return bool
     */
    public function isFileUrl(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'file';
    }

    /**
     * True when the URL uses the ftp scheme.
     *
     * @return bool
     */
    public function isFtp(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'ftp';
    }

    /**
     * True when the URL uses the ws scheme.
     *
     * @return bool
     */
    public function isWs(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'ws';
    }

    /**
     * True when the URL uses the wss scheme.
     *
     * @return bool
     */
    public function isWss(): bool
    {
        return strtolower((string) $this->getComponent(PHP_URL_SCHEME)) === 'wss';
    }

    /**
     * True when the URL has a non-empty query string.
     *
     * @return bool
     */
    public function hasQuery(): bool
    {
        $q = $this->getComponent(PHP_URL_QUERY);

        return $q !== null && $q !== '';
    }

    /**
     * True when the URL has a non-empty fragment.
     *
     * @return bool
     */
    public function hasFragment(): bool
    {
        $f = $this->getComponent(PHP_URL_FRAGMENT);

        return $f !== null && $f !== '';
    }

    /**
     * True when the URL has an explicit port.
     *
     * @return bool
     */
    public function hasPort(): bool
    {
        return $this->getComponent(PHP_URL_PORT) !== null;
    }

    /**
     * True when the URL has user-info (username or username:password).
     *
     * @return bool
     */
    public function hasUserInfo(): bool
    {
        return $this->getComponent(PHP_URL_USER) !== null;
    }

    /**
     * True when the URL has a non-empty path.
     *
     * @return bool
     */
    public function hasPath(): bool
    {
        $p = $this->getComponent(PHP_URL_PATH);

        return $p !== null && $p !== '';
    }

    /**
     * True when the URL has a scheme component.
     *
     * @return bool
     */
    public function hasScheme(): bool
    {
        return $this->getComponent(PHP_URL_SCHEME) !== null;
    }

    /**
     * True when the URL has a host component.
     *
     * @return bool
     */
    public function hasHost(): bool
    {
        return $this->getComponent(PHP_URL_HOST) !== null;
    }

    /**
     * Validate using filter_var FILTER_VALIDATE_URL.
     *
     * @return bool
     */
    public function isValidUrl(): bool
    {
        return filter_var((string) $this->rawData, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * True when the path is empty or "/".
     *
     * @return bool
     */
    public function isRoot(): bool
    {
        $p = $this->getComponent(PHP_URL_PATH) ?? '';

        return $p === '' || $p === '/';
    }

    /**
     * True when the path ends with a trailing slash (excluding root).
     *
     * @return bool
     */
    public function isDirectoryPath(): bool
    {
        $p = $this->getComponent(PHP_URL_PATH) ?? '';

        return strlen($p) > 1 && str_ends_with($p, '/');
    }

    /**
     * Add (or overwrite) a single query parameter in place.
     *
     * @param string $key Parameter name.
     * @param mixed $value Scalar or array value.
     *
     * @return static
     */
    public function addQueryParameter(string $key, mixed $value): static
    {
        return $this->setQueryString([$key => $value]);
    }

    /**
     * Set a single query parameter in place (alias for {@see addQueryParameter()}).
     *
     * @param string $key Parameter name.
     * @param mixed $value Scalar or array value.
     *
     * @return static
     */
    public function setQueryParameter(string $key, mixed $value): static
    {
        return $this->addQueryParameter($key, $value);
    }

    /**
     * Append a value to a repeating query parameter (turns scalar into array).
     *
     * @param string $key Parameter name.
     * @param mixed $value Scalar to append.
     *
     * @return static
     */
    public function appendQueryParameter(string $key, mixed $value): static
    {
        $params = $this->getQueryParameters();
        if (!array_key_exists($key, $params)) {
            $params[$key] = [$value];
        } elseif (is_array($params[$key])) {
            $params[$key][] = $value;
        } else {
            $params[$key] = [$params[$key], $value];
        }

        return $this->replaceQueryParameters($params);
    }

    /**
     * Merge the given parameters on top of the existing query string (non-mutating semantics).
     *
     * @param array<string, mixed> $params Parameters to merge in.
     *
     * @return static
     */
    public function mergeQueryParameters(array $params): static
    {
        return $this->setQueryString($params);
    }

    /**
     * Replace the entire query string with the given parameters.
     *
     * @param array<string, mixed> $params New query parameter set.
     *
     * @return static
     */
    public function replaceQueryParameters(array $params): static
    {
        $components = $this->parsedComponents;
        if ($params === []) {
            unset($components['query']);
        } else {
            $components['query'] = http_build_query($params);
        }
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Toggle a query parameter — add it when missing, remove it when present.
     *
     * @param string $key Parameter name.
     * @param mixed $value Value to use when the parameter is added.
     *
     * @return static
     */
    public function toggleQueryParameter(string $key, mixed $value = '1'): static
    {
        if ($this->hasQueryParameter($key)) {
            return $this->removeQueryParameter($key);
        }

        return $this->addQueryParameter($key, $value);
    }

    /**
     * Sort the query parameters alphabetically by key.
     *
     * @return static
     */
    public function sortQueryParameters(): static
    {
        $params = $this->getQueryParameters();
        ksort($params);

        return $this->replaceQueryParameters($params);
    }

    /**
     * Reverse the current ordering of query parameters.
     *
     * @return static
     */
    public function reverseQueryParameters(): static
    {
        $params = $this->getQueryParameters();

        return $this->replaceQueryParameters(array_reverse($params, true));
    }

    /**
     * Keep only parameters for which the callback returns a truthy value.
     *
     * @param callable(mixed, string): bool $fn Callback receives (value, key).
     *
     * @return static
     */
    public function filterQueryParameters(callable $fn): static
    {
        $params = $this->getQueryParameters();
        $kept = [];
        foreach ($params as $k => $v) {
            if ($fn($v, $k)) {
                $kept[$k] = $v;
            }
        }

        return $this->replaceQueryParameters($kept);
    }

    /**
     * Map every query parameter value via a callback.
     *
     * @param callable(mixed, string): mixed $fn Callback receives (value, key) and returns the new value.
     *
     * @return static
     */
    public function mapQueryParameters(callable $fn): static
    {
        $params = $this->getQueryParameters();
        $mapped = [];
        foreach ($params as $k => $v) {
            $mapped[$k] = $fn($v, $k);
        }

        return $this->replaceQueryParameters($mapped);
    }

    /**
     * Count the query parameters.
     *
     * @return int
     */
    public function countQueryParameters(): int
    {
        return count($this->getQueryParameters());
    }

    /**
     * Get a query parameter or the given default when missing.
     *
     * @param string $key Parameter name.
     * @param mixed $default Default value when the parameter is absent.
     *
     * @return mixed
     */
    public function getQueryParameterOrDefault(string $key, mixed $default = null): mixed
    {
        $params = $this->getQueryParameters();

        return array_key_exists($key, $params) ? $params[$key] : $default;
    }

    /**
     * True when every given parameter key exists.
     *
     * @param string[] $keys Required parameter names.
     *
     * @return bool
     */
    public function hasAllQueryParameters(array $keys): bool
    {
        $params = $this->getQueryParameters();
        foreach ($keys as $key) {
            if (!is_string($key) || !array_key_exists($key, $params)) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when at least one of the given parameter keys exists.
     *
     * @param string[] $keys Candidate parameter names.
     *
     * @return bool
     */
    public function hasAnyQueryParameter(array $keys): bool
    {
        $params = $this->getQueryParameters();
        foreach ($keys as $key) {
            if (is_string($key) && array_key_exists($key, $params)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rename a query parameter. No-op when $old is absent.
     *
     * @param string $old Existing parameter name.
     * @param string $new New parameter name.
     *
     * @return static
     */
    public function renameQueryParameter(string $old, string $new): static
    {
        $params = $this->getQueryParameters();
        if (!array_key_exists($old, $params) || $old === $new) {
            return $this;
        }
        $reordered = [];
        foreach ($params as $k => $v) {
            $reordered[$k === $old ? $new : $k] = $v;
        }

        return $this->replaceQueryParameters($reordered);
    }

    /**
     * Keep only the given query parameters.
     *
     * @param string[] $keys Parameter names to keep.
     *
     * @return static
     */
    public function onlyQueryParameters(array $keys): static
    {
        $keep = array_flip(array_filter($keys, 'is_string'));
        $params = $this->getQueryParameters();

        return $this->replaceQueryParameters(array_intersect_key($params, $keep));
    }

    /**
     * Remove the given query parameters.
     *
     * @param string[] $keys Parameter names to remove.
     *
     * @return static
     */
    public function exceptQueryParameters(array $keys): static
    {
        $remove = array_flip(array_filter($keys, 'is_string'));
        $params = $this->getQueryParameters();

        return $this->replaceQueryParameters(array_diff_key($params, $remove));
    }

    /**
     * Stable hash (sha1) of the sorted query parameters — handy for caching/deduplication.
     *
     * @return string
     */
    public function getQueryHash(): string
    {
        $params = $this->getQueryParameters();
        ksort($params);

        return sha1(http_build_query($params));
    }

    /**
     * Append a path segment to the existing path (mutating).
     *
     * @param string $segment Segment to append; slashes are normalized.
     *
     * @return static
     */
    public function appendPath(string $segment): static
    {
        $components = $this->parsedComponents;
        $current = $components['path'] ?? '';
        $segment = ltrim($segment, '/');
        $components['path'] = ($current === '' || str_ends_with($current, '/')) ? $current . $segment : $current . '/' . $segment;
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Prepend a path segment in front of the existing path (mutating).
     *
     * @param string $segment Segment to prepend; slashes are normalized.
     *
     * @return static
     */
    public function prependPath(string $segment): static
    {
        $components = $this->parsedComponents;
        $current = $components['path'] ?? '';
        $segment = '/' . ltrim($segment, '/');
        if ($current === '') {
            $components['path'] = $segment;
        } elseif ($current[0] === '/') {
            $components['path'] = rtrim($segment, '/') . $current;
        } else {
            $components['path'] = rtrim($segment, '/') . '/' . $current;
        }
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Collapse "/./" and "/../" segments in the path.
     *
     * @return static
     */
    public function normalizePath(): static
    {
        $components = $this->parsedComponents;
        $path = $components['path'] ?? '';
        if ($path === '') {
            return $this;
        }
        $components['path'] = self::removeDotSegments($path);
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Ensure the path ends with "/".
     *
     * @return static
     */
    public function withTrailingSlash(): static
    {
        $components = $this->parsedComponents;
        $path = $components['path'] ?? '';
        if ($path === '' || !str_ends_with($path, '/')) {
            $components['path'] = $path . '/';
        }
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Remove any trailing "/" from the path (keeps "/" for root).
     *
     * @return static
     */
    public function withoutTrailingSlash(): static
    {
        $components = $this->parsedComponents;
        $path = $components['path'] ?? '';
        if ($path !== '' && $path !== '/' && str_ends_with($path, '/')) {
            $components['path'] = rtrim($path, '/');
        }
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with the segment appended to the path (non-mutating).
     *
     * @param string $segment Path segment to append.
     *
     * @return static
     */
    public function withAppendedPath(string $segment): static
    {
        $clone = clone $this;
        $clone->appendPath($segment);

        return $clone;
    }

    /**
     * Replace the file-name extension of the path.
     *
     * @param string $ext New extension, with or without leading dot.
     *
     * @return static
     */
    public function changePathExtension(string $ext): static
    {
        $components = $this->parsedComponents;
        $path = $components['path'] ?? '';
        if ($path === '' || str_ends_with($path, '/')) {
            return $this;
        }
        $ext = ltrim($ext, '.');
        $lastSlash = (int) strrpos($path, '/');
        $file = substr($path, $lastSlash + 1);
        $lastDot = strrpos($file, '.');
        if ($lastDot !== false) {
            $file = substr($file, 0, $lastDot);
        }
        $components['path'] = substr($path, 0, $lastSlash + 1) . $file . ($ext === '' ? '' : '.' . $ext);
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Collapse consecutive slashes in the path (preserves leading single slash).
     *
     * @return static
     */
    public function collapsePath(): static
    {
        $components = $this->parsedComponents;
        $path = $components['path'] ?? '';
        if ($path === '') {
            return $this;
        }
        $leading = $path[0] === '/' ? '/' : '';
        $collapsed = (string) preg_replace('#/+#', '/', $path);
        if ($leading === '' && str_starts_with($collapsed, '/')) {
            $collapsed = ltrim($collapsed, '/');
        }
        $components['path'] = $collapsed;
        $this->updateRawData($this->buildUrl($components));

        return $this;
    }

    /**
     * Force the scheme to http.
     *
     * @return static
     */
    public function toHttp(): static
    {
        return $this->withScheme('http');
    }

    /**
     * Force the scheme to https.
     *
     * @return static
     */
    public function toHttps(): static
    {
        return $this->withScheme('https');
    }

    /**
     * Upgrade http → https (no-op for other schemes).
     *
     * @return static
     */
    public function upgradeScheme(): static
    {
        return $this->isHttp() ? $this->withScheme('https') : $this;
    }

    /**
     * Downgrade https → http (no-op for other schemes).
     *
     * @return static
     */
    public function downgradeScheme(): static
    {
        return $this->isHttps() ? $this->withScheme('http') : $this;
    }

    /**
     * True when scheme, host, and port all match the given URL.
     *
     * @param URLObject|string $other URL to compare against.
     *
     * @return bool
     */
    public function isSameOrigin(URLObject|string $other): bool
    {
        $o = $other instanceof self ? $other : new self($other);

        return strtolower((string) $this->getScheme()) === strtolower((string) $o->getScheme())
            && strtolower((string) $this->getHost()) === strtolower((string) $o->getHost())
            && $this->getPort() === $o->getPort();
    }

    /**
     * True when only the host (case-insensitive) matches.
     *
     * @param URLObject|string $other URL to compare against.
     *
     * @return bool
     */
    public function isSameHost(URLObject|string $other): bool
    {
        $o = $other instanceof self ? $other : new self($other);

        return strtolower((string) $this->getHost()) === strtolower((string) $o->getHost());
    }

    /**
     * True when only the scheme (case-insensitive) matches.
     *
     * @param URLObject|string $other URL to compare against.
     *
     * @return bool
     */
    public function isSameScheme(URLObject|string $other): bool
    {
        $o = $other instanceof self ? $other : new self($other);

        return strtolower((string) $this->getScheme()) === strtolower((string) $o->getScheme());
    }

    /**
     * Compare two URLs as strings (after rebuilding from components).
     *
     * @param URLObject|string $other URL to compare against.
     *
     * @return bool
     */
    public function equalsUrl(URLObject|string $other): bool
    {
        $o = $other instanceof self ? $other : new self($other);

        return (string) $this === (string) $o;
    }

    /**
     * True when this URL's path is a sub-path of the given URL's path (and origins match).
     *
     * @param URLObject|string $other URL considered the base.
     *
     * @return bool
     */
    public function isSubPathOf(URLObject|string $other): bool
    {
        $o = $other instanceof self ? $other : new self($other);
        if (!$this->isSameOrigin($o)) {
            return false;
        }
        $self = rtrim((string) $this->getPath(), '/') . '/';
        $base = rtrim((string) $o->getPath(), '/') . '/';

        return str_starts_with($self, $base);
    }

    /**
     * True when the URL's host equals $domain or is a subdomain thereof.
     *
     * @param string $domain Base domain.
     *
     * @return bool
     */
    public function isWithinDomain(string $domain): bool
    {
        $host = strtolower((string) $this->getComponent(PHP_URL_HOST));
        $domain = strtolower(trim($domain));
        if ($host === '' || $domain === '') {
            return false;
        }

        return $host === $domain || str_ends_with($host, '.' . $domain);
    }

    /**
     * True when the host is a strict subdomain of $domain (host !== domain).
     *
     * @param string $domain Base domain.
     *
     * @return bool
     */
    public function isSubdomainOf(string $domain): bool
    {
        $host = strtolower((string) $this->getComponent(PHP_URL_HOST));
        $domain = strtolower(trim($domain));

        return $host !== '' && $domain !== '' && $host !== $domain && str_ends_with($host, '.' . $domain);
    }

    /**
     * Resolve a reference URL against this URL as a base (RFC 3986 §5.3).
     *
     * @param string $reference Relative or absolute URL reference.
     *
     * @return static A new URLObject with the resolved URL.
     */
    public function resolve(string $reference): static
    {
        $refParts = parse_url($reference) ?: [];
        $base = $this->parsedComponents;
        $target = [];
        if (isset($refParts['scheme'])) {
            $target = $refParts;
            if (isset($target['path'])) {
                $target['path'] = self::removeDotSegments($target['path']);
            }
        } else {
            if (isset($refParts['host'])) {
                $target = $refParts;
                if (isset($base['scheme'])) {
                    $target['scheme'] = $base['scheme'];
                }
                if (isset($target['path'])) {
                    $target['path'] = self::removeDotSegments($target['path']);
                }
            } else {
                if (isset($base['scheme'])) {
                    $target['scheme'] = $base['scheme'];
                }
                foreach (['host', 'port', 'user', 'pass'] as $k) {
                    if (isset($base[$k])) {
                        $target[$k] = $base[$k];
                    }
                }
                $refPath = $refParts['path'] ?? '';
                if ($refPath === '') {
                    if (isset($base['path'])) {
                        $target['path'] = $base['path'];
                    }
                    if (isset($refParts['query'])) {
                        $target['query'] = $refParts['query'];
                    } elseif (isset($base['query'])) {
                        $target['query'] = $base['query'];
                    }
                } else {
                    if ($refPath[0] === '/') {
                        $target['path'] = self::removeDotSegments($refPath);
                    } else {
                        $basePath = $base['path'] ?? '';
                        if ($basePath === '' && isset($base['host'])) {
                            $merged = '/' . $refPath;
                        } else {
                            $idx = strrpos($basePath, '/');
                            $merged = $idx !== false ? substr($basePath, 0, $idx + 1) . $refPath : $refPath;
                        }
                        $target['path'] = self::removeDotSegments($merged);
                    }
                    if (isset($refParts['query'])) {
                        $target['query'] = $refParts['query'];
                    }
                }
            }
        }
        if (isset($refParts['fragment'])) {
            $target['fragment'] = $refParts['fragment'];
        }

        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($target));

        return $clone;
    }

    /**
     * Remove "." / ".." segments from a path (RFC 3986 §5.2.4).
     *
     * @param string $path Path to normalize.
     *
     * @return string
     */
    private static function removeDotSegments(string $path): string
    {
        $input = $path;
        $output = '';
        while ($input !== '') {
            if (str_starts_with($input, '../')) {
                $input = substr($input, 3);
            } elseif (str_starts_with($input, './')) {
                $input = substr($input, 2);
            } elseif (str_starts_with($input, '/./')) {
                $input = '/' . substr($input, 3);
            } elseif ($input === '/.') {
                $input = '/';
            } elseif (str_starts_with($input, '/../')) {
                $input = '/' . substr($input, 4);
                $idx = strrpos($output, '/');
                $output = $idx === false ? '' : substr($output, 0, $idx);
            } elseif ($input === '/..') {
                $input = '/';
                $idx = strrpos($output, '/');
                $output = $idx === false ? '' : substr($output, 0, $idx);
            } elseif ($input === '.' || $input === '..') {
                $input = '';
            } else {
                $pos = strpos($input, '/', 1);
                if ($pos === false) {
                    $output .= $input;
                    $input = '';
                } else {
                    $output .= substr($input, 0, $pos);
                    $input = substr($input, $pos);
                }
            }
        }

        return $output;
    }

    /**
     * Express this URL as relative to the given base URL.
     *
     * Falls back to the absolute form when origins differ.
     *
     * @param URLObject|string $base Base URL.
     *
     * @return static
     */
    public function relativeTo(URLObject|string $base): static
    {
        $baseUrl = $base instanceof self ? $base : new self($base);
        $clone = clone $this;
        if (!$this->isSameOrigin($baseUrl)) {
            $clone->updateRawData((string) $this->rawData);

            return $clone;
        }
        $baseSegs = $baseUrl->getPathSegments();
        $thisSegs = $this->getPathSegments();
        $common = 0;
        while ($common < count($baseSegs) && $common < count($thisSegs) && $baseSegs[$common] === $thisSegs[$common]) {
            $common++;
        }
        $up = str_repeat('../', max(0, count($baseSegs) - $common));
        $down = implode('/', array_slice($thisSegs, $common));
        $path = $up . $down;
        if ($this->isDirectoryPath() && $path !== '' && !str_ends_with($path, '/')) {
            $path .= '/';
        }
        $query = (string) $this->getQueryString();
        $fragment = (string) $this->getFragment();
        $out = $path === '' ? '' : $path;
        if ($query !== '') {
            $out .= '?' . $query;
        }
        if ($fragment !== '') {
            $out .= '#' . $fragment;
        }
        $clone->updateRawData($out);

        return $clone;
    }

    /**
     * Return a clone with user:pass user-info applied.
     *
     * @param string $user Username.
     * @param string|null $pass Password, or null to omit.
     *
     * @return static
     */
    public function withUserInfo(string $user, ?string $pass = null): static
    {
        $components = $this->parsedComponents;
        if ($user === '') {
            unset($components['user'], $components['pass']);
        } else {
            $components['user'] = $user;
            if ($pass === null || $pass === '') {
                unset($components['pass']);
            } else {
                $components['pass'] = $pass;
            }
        }
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with user-info removed.
     *
     * @return static
     */
    public function withoutUserInfo(): static
    {
        $components = $this->parsedComponents;
        unset($components['user'], $components['pass']);
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with the port removed (non-mutating).
     *
     * @return static
     */
    public function withoutPort(): static
    {
        return $this->withPort(null);
    }

    /**
     * Return a clone with the query string removed.
     *
     * @return static
     */
    public function withoutQuery(): static
    {
        $components = $this->parsedComponents;
        unset($components['query']);
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with the fragment removed.
     *
     * @return static
     */
    public function withoutFragment(): static
    {
        $components = $this->parsedComponents;
        unset($components['fragment']);
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with the path removed.
     *
     * @return static
     */
    public function withoutPath(): static
    {
        $components = $this->parsedComponents;
        unset($components['path']);
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Return a clone with the scheme removed.
     *
     * @return static
     */
    public function withoutScheme(): static
    {
        $components = $this->parsedComponents;
        unset($components['scheme']);
        $clone = clone $this;
        $clone->updateRawData($this->buildUrl($components));

        return $clone;
    }

    /**
     * Structured representation of all URL components.
     *
     * @return array{
     *  scheme: ?string, 
     *  user: ?string, 
     *  pass: ?string, 
     *  host: ?string, 
     *  port: ?int, 
     *  path: ?string, 
     *  query: ?string, 
     *  fragment: ?string
     * }
     */
    public function toComponents(): array
    {
        return [
            'scheme' => $this->getComponent(PHP_URL_SCHEME),
            'user' => $this->getComponent(PHP_URL_USER),
            'pass' => $this->getComponent(PHP_URL_PASS),
            'host' => $this->getComponent(PHP_URL_HOST),
            'port' => $this->getPort(),
            'path' => $this->getComponent(PHP_URL_PATH),
            'query' => $this->getComponent(PHP_URL_QUERY),
            'fragment' => $this->getComponent(PHP_URL_FRAGMENT),
        ];
    }

    /**
     * Alias for {@see toComponents()}.
     *
     * @return array<string, mixed>
     */
    public function getComponents(): array
    {
        return $this->toComponents();
    }

    /**
     * JSON representation of this URL's components.
     *
     * @param int $flags json_encode flags.
     *
     * @return string
     */
    public function toJson(int $flags = 0): string
    {
        $json = json_encode($this->toComponents(), $flags | JSON_UNESCAPED_SLASHES);

        return $json === false ? '{}' : $json;
    }

    /**
     * Render this URL as a Markdown link: [label](url).
     *
     * @param string $label Link label.
     *
     * @return StringObject
     */
    public function toMarkdownLink(string $label): StringObject
    {
        $safe = str_replace(['[', ']'], ['\\[', '\\]'], $label);

        return new StringObject('[' . $safe . '](' . (string) $this->rawData . ')');
    }

    /**
     * Render this URL as an HTML anchor.
     *
     * @param string $label Anchor text. When empty the URL itself is used.
     * @param string $target Optional "target" attribute (e.g. "_blank").
     * @param string $rel Optional "rel" attribute.
     *
     * @return StringObject
     */
    public function toHtmlAnchor(string $label = '', string $target = '', string $rel = ''): StringObject
    {
        $href = htmlspecialchars((string) $this->rawData, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = htmlspecialchars($label === '' ? (string) $this->rawData : $label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attrs = '';
        if ($target !== '') {
            $attrs .= ' target="' . htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }
        if ($rel !== '') {
            $attrs .= ' rel="' . htmlspecialchars($rel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        return new StringObject('<a href="' . $href . '"' . $attrs . '>' . $text . '</a>');
    }

    /**
     * Host converted to ASCII / Punycode form (requires intl; falls back to raw host).
     *
     * @return StringObject
     */
    public function punycodeHost(): StringObject
    {
        $host = (string) $this->getComponent(PHP_URL_HOST);
        if ($host === '' || !function_exists('idn_to_ascii')) {
            return new StringObject($host);
        }
        $ascii = @idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return new StringObject($ascii === false ? $host : $ascii);
    }

    /**
     * Host converted to Unicode form from Punycode (requires intl; falls back to raw host).
     *
     * @return StringObject
     */
    public function unicodeHost(): StringObject
    {
        $host = (string) $this->getComponent(PHP_URL_HOST);
        if ($host === '' || !function_exists('idn_to_utf8')) {
            return new StringObject($host);
        }
        $unicode = @idn_to_utf8($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

        return new StringObject($unicode === false ? $host : $unicode);
    }

    /**
     * Return a clone of this URL with the host encoded as Punycode.
     *
     * @return static
     */
    public function toPunycodeUrl(): static
    {
        $ascii = (string) $this->punycodeHost();
        if ($ascii === '' || $ascii === (string) $this->getComponent(PHP_URL_HOST)) {
            $clone = clone $this;
            $clone->updateRawData((string) $this->rawData);

            return $clone;
        }

        return $this->withHost($ascii);
    }

    /**
     * Return a clone of this URL with the host decoded from Punycode.
     *
     * @return static
     */
    public function toUnicodeUrl(): static
    {
        $unicode = (string) $this->unicodeHost();
        if ($unicode === '' || $unicode === (string) $this->getComponent(PHP_URL_HOST)) {
            $clone = clone $this;
            $clone->updateRawData((string) $this->rawData);

            return $clone;
        }

        return $this->withHost($unicode);
    }

    /**
     * Strip well-known tracking query parameters (UTM, fbclid, gclid, etc.).
     *
     * @param string[] $extra Additional parameter names to remove.
     *
     * @return static
     */
    public function stripCommonTrackingParams(array $extra = []): static
    {
        $remove = array_merge(self::TRACKING_PARAMS, array_filter($extra, 'is_string'));

        return $this->exceptQueryParameters($remove);
    }

    /**
     * Stable fingerprint (sha1) of a canonicalized URL — handy for dedup and caching.
     *
     * The fingerprint ignores fragment, tracking parameters, and query ordering.
     *
     * @return string
     */
    public function getFingerprint(): string
    {
        $clone = clone $this;
        $clone->updateRawData((string) $this->rawData);
        $clone = $clone->withoutFragment()->stripCommonTrackingParams()->sortQueryParameters();

        return sha1((string) $clone);
    }

    /**
     * Return a copy of the URL with user-info masked as "***".
     *
     * @return StringObject
     */
    public function maskUserInfo(): StringObject
    {
        if (!$this->hasUserInfo()) {
            return new StringObject((string) $this->rawData);
        }
        $components = $this->parsedComponents;
        $components['user'] = '***';
        if (isset($components['pass'])) {
            $components['pass'] = '***';
        }

        return new StringObject($this->buildUrl($components));
    }

    /**
     * Path together with the query string (including leading "?" when present).
     *
     * @return StringObject
     */
    public function getPathWithQuery(): StringObject
    {
        $path = (string) $this->getPath();
        $query = (string) $this->getQueryString();

        return new StringObject($query === '' ? $path : $path . '?' . $query);
    }

    /**
     * Path with the file-extension removed.
     *
     * @return StringObject
     */
    public function getPathWithoutExtension(): StringObject
    {
        $path = $this->getComponent(PHP_URL_PATH) ?? '';
        if ($path === '' || str_ends_with($path, '/')) {
            return new StringObject($path);
        }
        $lastSlash = (int) strrpos($path, '/');
        $file = substr($path, $lastSlash + 1);
        $lastDot = strrpos($file, '.');
        if ($lastDot === false) {
            return new StringObject($path);
        }

        return new StringObject(substr($path, 0, $lastSlash + 1) . substr($file, 0, $lastDot));
    }

    /**
     * Set or replace a single query parameter (mutating).
     *
     * @param string $key Parameter name.
     * @param mixed $value Parameter value.
     *
     * @return static
     */
    public function setParameter(string $key, mixed $value): static
    {
        return $this->replaceQueryParameters([$key => $value] + $this->getQueryParameters());
    }

    public function setParameters(array $queryParams): bool|static
    {
        if (empty($queryParams)) {
            return false;
        }

        foreach ($queryParams as $key => $value) {
            $this->setParameter($key, (string) $value);
        }

        return $this;
    }

    #end region
}
