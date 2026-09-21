<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

#region use

use Closure;
use Clover\Classes\BaseClass;
use Clover\Classes\Data\{ArrayObject, StringObject};
use Clover\Classes\DependencyInjection\Container;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use Clover\Classes\Regex as Regexr;
use Clover\Classes\Routing\{RouteExecutor, StackableRequestHandler};
use Clover\Enumeration\{Regex, RegularRegex};
use Clover\Implement\MiddlewareInterface;
use InvalidArgumentException;
use Stringable;
use function count;
use function in_array;
use function is_array;
use function is_scalar;
use function sprintf;
use function str_replace;
use function is_string;
use function is_object;
use function strtolower;

#endregion

/**
 * Class Route
 * 
 * Represents a route in the routing system, handling pattern matching, callbacks, and middlewares.
 */
class Route extends BaseClass
{
    #region properties

    /**
     * Grammar of a parameterised route segment: `{name}`, optionally followed by `?` (the
     * segment may be omitted), `=default` (value substituted when it is omitted) and
     * `:type` / `:(type)` (constraint the supplied value must satisfy).
     *
     * The groups are named on purpose. Every trailing group here is optional, and preg_match
     * truncates the result at the last participating group instead of padding it, so a plain
     * `{name}` yields a shorter array than `{name}:(\d+)` does. Reading such a result
     * positionally either warns on a missing key or, once padded, silently binds the wrong
     * capture to the wrong name; named keys read with `??` cannot do either.
     *
     * @var string
     **/
    private const PARAMETER_SEGMENT_PATTERN = '/^(?<name>{\w+})(?<optional>\?)?(?:=(?<default>[^\/]+))?(?::(?<type>\(?[^\/]+\)?)?)?$/';

    /**
     * The callback to be executed when the route is matched; can be a Closure, a "Class@method" string, or an array callable.
     * @var Closure|string|array $callback 
     **/
    private Closure|string|array $callback;

    /**
     * Container for dependency injection; can be set on a per-route basis to provide route-specific dependencies when executing callbacks or middlewares.
     * @var Container|null $container 
     **/
    private ?Container $container = null;

    /**
     * Middlewares to be executed before the route's main callback when handling a request; can be empty if no middleware is needed.
     * @var Closure[] $middlewares 
     **/
    private array $middlewares = [];

    /**
     * NotFound handler to be called when route matching fails; can be set per route for custom 404 handling.
     * @var ?Closure $notFoundHandler 
     **/
    private ?Closure $notFoundHandler = null;

    /**
     * Route pattern for matching URLs; supports wildcards and parameterized segments.
     * @var StringObject|null $pattern 
     **/
    private ?StringObject $pattern;

    /** 
     * Content type constraint for route matching; supports wildcard '*' for any content type.
     * @var string $contentType 
     **/
    private string $contentType = "*";

    /** 
     * Host constraint for route matching; supports wildcard '*' for any host.
     * @var string $host 
     **/
    private string $host = "*";

    /** 
     * Arguments extracted from URL when matching route pattern, to be passed to callback when executing route.
     * @var string[] $arguments 
     **/
    protected array $arguments = [];

    /** 
     * Cached class and method for the callback. Both entries are null for a
     * closure, which has neither.
     * @var array{0:string|null,1:string|null}|null $cachedClassAndMethod 
     **/
    private ?array $cachedClassAndMethod = null;

    /**
     * Route name for referencing the route when generating URLs or for other purposes; optional.
     * @var string|null
     */
    private ?string $name = null;

    /**
     * Defaults for optional parameters in the route pattern, used when matching URLs that omit those parameters.
     * @var array
     */
    private array $defaults = [];

    /**
     * Prefix for route pattern, useful for grouping routes under a common path segment; automatically prepended to the route's pattern.
     * @var string
     */
    private string $prefix = '';

    /**
     * Domain constraint for route matching, allowing routes to be limited to specific domains or subdomains; mirrors the host property but semantically indicates domain-level constraints.
     * @var string|null
     */
    private ?string $domain = null;

    /**
     * Where constraints for route parameters, allowing regex patterns to be specified for validating parameter values when matching routes.
     * @var array
     */
    private array $whereConstraints = [];

    /**
     * Exclude middleware list for the route, allowing specific middlewares to be excluded from execution when handling this route, even if they are globally applied or applied at a higher level.
     * @var array
     */
    private array $excludedMiddlewares = [];

    /**
     * Required $_GET keys and exact string values for this route to match (Spring-style params condition).
     *
     * @var array<string, string>
     */
    private array $requiredQueryParams = [];

    /**
     * GET parameter name for virtual path (mirrors annotation; used for route cache export).
     */
    private string $pathQueryKey = '';

    #endregion

    #region function

    /**
     * Constructor of route
     * 
     * @param string $pattern Route pattern for matching URLs; supports wildcards and parameterized segments.
     * @param mixed $callback The callback to be executed when the route is matched; can be a Closure, a "Class@method" string, or an array callable.
     * @param MiddlewareInterface[] $middleware Middlewares to be executed before the route's main callback when handling a request; can be empty if no middleware is needed.
     * 
     * @return void
     */
    public function __construct(string $pattern = "*", mixed $callback = null, string|array $middleware = [])
    {
        $this->setPattern($pattern);
        $this->setCallback($callback);

        if (!empty($middleware)) {
            $this->setMiddlewares($middleware);
        }
    }

    /**
     * Get arguments extracted from URL when matching route pattern, to be passed to callback when executing route.
     * 
     * @return string[] Arguments extracted from URL when matching route pattern
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Set required query parameters for route matching; the route will only match if the specified $_GET keys exist and have the exact string values.
     * 
     * @param array<string, string> $params Required $_GET keys and exact string values for this route to match
     */
    public function setRequiredQueryParams(array $params): void
    {
        $this->requiredQueryParams = $params;
    }

    /**
     * Get required query parameters for route matching; these are the $_GET keys and exact string values that must be present for the route to match.
     * 
     * @return array<string, string> Required $_GET keys and exact string values for this route to match
     */
    public function getRequiredQueryParams(): array
    {
        return $this->requiredQueryParams;
    }

    /**
     * Set the GET parameter name for virtual path (mirrors annotation; used for route cache export).
     * 
     * @param string $pathQueryKey The GET parameter name to use for virtual path
     */
    public function setPathQueryKey(string $pathQueryKey): void
    {
        $this->pathQueryKey = $pathQueryKey;
    }

    /**
     * Get the GET parameter name for virtual path (mirrors annotation; used for route cache export).
     * 
     * @return string The GET parameter name used for virtual path
     */
    public function getPathQueryKey(): string
    {
        return $this->pathQueryKey;
    }

    /**
     * Get class and method from callback
     * 
     * @return array{0:string|null,1:string|null} The class and method the callback names, or [null, null] for a closure, which names neither.
     */
    public function getClassAndMethod(): array
    {
        if ($this->cachedClassAndMethod !== null) {
            return $this->cachedClassAndMethod;
        }

        // A closure has no class and no method name. getExecutor() has always
        // said so by building RouteExecutor(null, null, $closure); asking the
        // reflection helper instead threw "Target class is empty", which took
        // out toArray() - and therefore the route cache - dump(), stats() and
        // merge() for any router holding a closure route.
        if ($this->getCallback() instanceof Closure) {
            $this->cachedClassAndMethod = [null, null];

            return $this->cachedClassAndMethod;
        }

        [$class, $method] = ReflectionHandler::getCallMethodFromString($this->getCallback());
        $this->cachedClassAndMethod = [$class, $method];

        return [$class, $method];
    }

    /**
     * Get the callback this route was registered with.
     *
     * @return Closure|string|array<int, mixed> The registered handler.
     */
    public function getRegisteredCallback(): Closure|string|array
    {
        return $this->callback;
    }

    /**
     * Set a middleware for using on route
     * 
     * @param MiddlewareInterface[] $middleware An array of middlewares to be executed before the route's main callback when handling a request; can be empty if no middleware is needed.
     * 
     * @return void
     */
    public function setMiddlewares(array $middleware): void
    {
        $this->middlewares = $middleware ?? [];
    }

    /**
     * Check if has middleware for route
     * 
     * @return bool True if the route has middlewares to be executed before the main callback, false otherwise.
     */
    public function hasMiddleware(): bool
    {
        return count($this->middlewares) > 0;
    }

    /**
     * Set a NotFound Handler
     * 
     * @param null|Closure $notFoundHandler A Closure to be called when route matching fails; can be set per route for custom 404 handling.
     * 
     * @return void
     */
    public function setNotFoundHandler(?Closure $notFoundHandler): void
    {
        $this->notFoundHandler = $notFoundHandler;
    }

    /**
     * Get a NotFound Handler
     * 
     * @return ?Closure A Closure to be called when route matching fails, or null if no NotFound handler is set for this route.
     */
    public function getNotFoundHandler(): ?Closure
    {
        return $this->notFoundHandler;
    }

    /**
     * Set a ContentType constraint for route matching; supports wildcard '*' for any content type.
     * 
     * @param string $contentType The content type constraint to set for this route; can be a specific content type string or '*' to allow any content type.
     * 
     * @return void
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * Get a ContentType constraint for route matching; returns the content type constraint that is set for this route, which can be a specific content type string or '*' to allow any content type.
     * 
     * @return string The content type constraint set for this route; can be a specific content type string or '*' to allow any content type.
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Set a host constraint for route matching; supports wildcard '*' for any host.
     * 
     * @param string $host The host constraint to set for this route; can be a specific host string or '*' to allow any host.
     * 
     * @return void
     */
    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    /**
     * Get a host constraint for route matching; returns the host constraint that is set for this route, which can be a specific host string or '*' to allow any host.
     * 
     * @return string The host constraint set for this route; can be a specific host string or '*' to allow any host.
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * Set a callback for route execution; the callback can be a Closure, a "Class@method" string, or an array callable. Setting the callback will also clear any cached class and method information to ensure it is updated on the next execution.
     * 
     * @param Closure|string|array $callback The callback to set for this route; can be a Closure, a "Class@method" string, or an array callable.
     * 
     * @return void
     */
    public function setCallback(Closure|string|array $callback): void
    {
        $this->callback = $callback;
        $this->cachedClassAndMethod = null;
    }

    /**
     * Gets middlewares to be executed before the route's main callback when handling a request; returns an array of Closure middlewares, which can be empty if no middleware is set for this route.
     * 
     * @return Closure[] An array of Closure middlewares to be executed before the route's main callback; can be empty if no middleware is set for this route.
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Set a container for dependency injection; this container can be used to provide route-specific dependencies when executing callbacks or middlewares. Setting a container on the route allows for more granular control over the dependencies available during route execution, as opposed to relying solely on a global container.
     * 
     * @param Container $container The Container instance to set for this route, which can be used for dependency injection when executing callbacks or middlewares.
     * 
     * @return void
     */
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    /**
     * Get a container for dependency injection; returns the Container instance that is set for this route, which can be used for dependency injection when executing callbacks or middlewares. If no container is set for this route, it may return null, indicating that the route should rely on a global container or other means of dependency resolution.
     * 
     * @return Container|null The Container instance set for this route, or null if no container is set.
     */
    public function getContainer(): ?Container
    {
        return $this->container;
    }

    /**
     * Get a callback to be executed when the route is matched; returns the callback that is set for this route, which can be a Closure, a "Class@method" string, or an array callable. The callback is what will be executed when the route is successfully matched and handled, and it defines the main logic for processing the request associated with this route.
     * 
     * @return Closure|string|[] The callback set for this route, which can be a Closure, a "Class@method" string, or an array callable.
     */
    private function getCallback(): Closure|string|array
    {
        return $this->callback;
    }

    /**
     * Set a route pattern for matching URLs; the pattern supports wildcards and parameterized segments, and is used to determine if an incoming request's URL matches this route. The pattern is typically defined using a syntax that allows for dynamic segments (e.g., "/users/{id}") and can include optional parameters and constraints.
     * 
     * @param string $pattern The route pattern to set for this route, which supports wildcards and parameterized segments for matching URLs.
     * 
     * @return void
     */
    public function setPattern(string $pattern): void
    {
        $this->pattern = new StringObject($pattern);
    }

    /**
     * Checks if pattern empty or not; returns true if the route's pattern is empty, which typically means that the route should not match any URLs, and false if the pattern is non-empty and can be used for matching incoming request URLs.
     * 
     * @return bool True if the route's pattern is empty, false otherwise.
     */
    private function isPatternEmpty(): bool
    {
        return $this->pattern->isEmpty();
    }

    /**
     * Get a pattern for matching URLs; returns the StringObject representing the route's pattern, which is used for matching incoming request URLs. The pattern defines the structure of URLs that this route should match, and can include static segments, dynamic parameters, and wildcards.
     * 
     * @return StringObject|null The StringObject representing the route's pattern for matching URLs, or null if no pattern is set.
     */
    public function getPattern(): StringObject|null
    {
        return $this->pattern;
    }

    /**
     * Get a executor for route execution; returns a RouteExecutor instance that is configured to execute this route's callback with the appropriate class and method if the callback is in "Class@method" string format, or directly with the callback if it is a Closure or array callable. The executor is responsible for invoking the route's callback when the route is handled, and it takes into account any middlewares that need to be executed before the main callback.
     * 
     * @return RouteExecutor A RouteExecutor instance configured to execute this route's callback.
     */
    private function getExecutor(): RouteExecutor
    {
        $callback = $this->getCallback();
        if ($callback instanceof Closure) {
            return new RouteExecutor(null, null, $callback, $this->getArguments(), $this->getContainer());
        }
        if (is_array($callback) && isset($callback[0], $callback[1])) {
            $class = is_string($callback[0]) ? $callback[0] : (is_object($callback[0]) ? $callback[0]::class : null);
            $method = is_string($callback[1]) ? $callback[1] : null;

            return new RouteExecutor($class, $method, $callback, $this->getArguments(), $this->getContainer());
        }

        [$class, $method] = $this->getClassAndMethod();

        return new RouteExecutor($class, $method, $callback, $this->getArguments(), $this->getContainer());
    }

    /**
     * Handle a routes execution; this method is responsible for executing the route's callback with the provided arguments, and it also handles the execution of any middlewares that are associated with this route. If the route has middlewares, it will create a StackableRequestHandler to manage the middleware execution before invoking the main callback. If there are no middlewares, it will directly invoke the executor for this route.
     * 
     * @return mixed The result of the route's callback execution, which can be of any type depending on what the callback returns.
     */
    public function handle(): mixed
    {
        $executor = $this->getExecutor();

        if ($this->hasMiddleware()) {
            $stackRequestHandler = new StackableRequestHandler();
            $stackRequestHandler->pushItem($executor);
            $stackRequestHandler->addMiddlewares($this->getMiddlewares(), $this->getContainer());

            return $stackRequestHandler->handle();
        }

        return $executor();
    }

    /**
     * Validate an argument by type using predefined regex patterns or custom regex; this method checks if a given value matches the expected format based on the specified type, which can be one of several predefined types (e.g., 'SLUG', 'DATE', 'EMAIL') or a custom regex pattern. The method uses the Regexr class to perform the matching and returns true if the value matches the expected format for the type, or false otherwise.
     * 
     * @param string $type The type to validate against, which can be a predefined type (e.g., 'SLUG', 'DATE', 'EMAIL') or a custom regex pattern.
     * @param string $value The value to validate against the specified type; this is typically a string extracted from the URL that needs to be validated before being passed to the route's callback.
     * 
     * @return bool True if the value matches the expected format for the specified type, false otherwise.
     */
	public function isValidArgument(string $type, string $value): bool
	{
		$pattern = match (strtolower($type)) {
			'slug' => '/^[a-z0-9-]+$/',
			'uslug' => '/^[\w-]+$/',
			'date' => '/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/',
			Regex::NUMBER => RegularRegex::NUMBER,
			Regex::ALPHABET => RegularRegex::ALPHABET,
			Regex::ALPHABET_NUMBER => RegularRegex::ALPHABET_NUMBER,
			Regex::PHONE_NUMBER => RegularRegex::PHONE_NUMBER,
			Regex::JAPANESE => RegularRegex::JAPANESE,
			Regex::KANJI => RegularRegex::KANJI,
			Regex::HIRAGANA => RegularRegex::HIRAGANA,
			Regex::KATAKANA => RegularRegex::KATAKANA,
			Regex::EMAIL => RegularRegex::EMAIL,
			Regex::KOREAN => RegularRegex::KOREAN,
			Regex::BASE64 => RegularRegex::BASE64,
			Regex::KOREAN_ENGLISH => RegularRegex::ENGLISH_KOREAN,
			default => sprintf('/%s/', $type),
		};

		return Regexr::isValid($pattern) && Regexr::match($pattern, $value)->hasResult();
	}

    /**
     * Parse a single route pattern segment into its parameter parts.
     *
     * Both loops in {@see self::match()} used to carry their own copy of this regular expression,
     * and the two copies did not agree on how many capture groups the type constraint spans, so the
     * same segment text parsed to different values depending on which loop read it. One shared
     * pattern with named groups removes that entire class of skew.
     *
     * @param StringObject|string $segment The route pattern segment to parse, e.g. `{id}?:(\d+)`.
     * @param array{name: string, optional: bool, default: string|null, type: string}|null $parsed Receives the parsed parts when the segment is parameterised, null otherwise.
     *
     * @return bool True when the segment is a parameterised segment, false when it is a literal one.
     */
    private function parseParameterSegment(StringObject|string $segment, ?array &$parsed = null): bool
    {
        $parsed = null;

        if (!$segment instanceof StringObject) {
            $segment = new StringObject($segment);
        }

        if (!$segment->match(self::PARAMETER_SEGMENT_PATTERN, $match)) {
            return false;
        }

        // Trailing groups that do not participate are absent from $match rather than empty, so
        // every read below supplies its own default instead of relying on the array's length.
        $name = (string) ($match['name'] ?? '');
        $isOptional = (string) ($match['optional'] ?? '') !== '';
        $defaultValue = (string) ($match['default'] ?? '');
        $type = (string) ($match['type'] ?? '');

        // `:(\d+)` carries the constraint wrapped in parentheses; `:number` names a built-in
        // constraint and carries none. Unwrap the first shape, leave the second alone.
        if ($type !== '' && str_starts_with($type, '(') && str_ends_with($type, ')')) {
            $type = substr($type, 1, -1);
        }

        $parsed = [
            'name' => $name,
            'optional' => $isOptional,
            'default' => $defaultValue !== '' ? $defaultValue : null,
            'type' => $type,
        ];

        return true;
    }

    /**
     * Match a pattern by url segments and other conditions; this method checks if the given URL segments match the route's pattern, and also checks additional conditions such as host and content type constraints. If the URL segments match the pattern and all conditions are satisfied, it extracts any parameters from the URL and stores them in the route's arguments for later use when executing the callback. The method returns true if the URL matches the route, or false if it does not match.
     * 
     * @param array|ArrayObject $urlSegments The URL segments to match against the route's pattern; this is typically an array or ArrayObject containing the individual segments of the incoming request's URL, which will be compared to the route's pattern to determine if there is a match.
     * @param null|string $currentHost The current host of the incoming request, used for matching against the route's host constraint; can be null if no host information is available.
     * @param string $currentContentType The current content type of the incoming request, used for matching against the route's content type constraint; can be '*' to indicate any content type.
     * 
     * @return bool True if the URL segments match the route's pattern and all conditions are satisfied, false otherwise.
     */
    public function match(array|ArrayObject $urlSegments, null|string $currentHost = null, string $currentContentType = '*'): bool
    {
        $this->arguments = [];

        if ($this->isPatternEmpty()) {
            return false;
        }

        if ($this->requiredQueryParams !== []) {
            foreach ($this->requiredQueryParams as $qk => $qv) {
                if (!isset($_GET[$qk]) || (string) $_GET[$qk] !== (string) $qv) {
                    return false;
                }
            }
        }

        $contentType = $this->getContentType();
        if ($contentType != "*" && !str_starts_with($currentContentType, $contentType)) {
            return false;
        }

        $host = $this->getHost();
        if ($host != "*" && $host != $currentHost) {
            return false;
        }

        // Matching must not edit the route. StringObject::trim() rewrites in place
        // and returns $this, so calling it on the pattern turned "/users" into
        // "users" for the lifetime of the route object: every later
        // removeRoute(), hasRoute(), listRoutes() and toArray() - and therefore
        // the written route cache - stopped recognising the pattern it was
        // registered under, from the first request onwards.
        $separatedSegments = (new StringObject((string) $this->getPattern()))->trim('/')->split('/');

        $count = $separatedSegments->size();

        if ($count <= 0) {
            return false;
        }

        // Pre-return: a cheap reject on the first segment before the full walk.
        // It only applies to a literal segment - comparing "{slug}" against the
        // request's first segment rejected every route whose first segment is a
        // parameter, which silently made catchAll() and any "/{path}" route
        // unmatchable.
        if ($urlSegments->has(0) && $separatedSegments->has(0)) {
            $firstRouteSegment = $separatedSegments->getByIndex(0);

            if (
                !$this->parseParameterSegment((string) $firstRouteSegment)
                && $urlSegments->getByIndex(0) != $firstRouteSegment
            ) {
                return false;
            }
        }

        $optionalCount = 0;
        foreach ($separatedSegments as $segment) {
            $segment = new StringObject($segment);

            if ($this->parseParameterSegment($segment, $parsed)) {
                if ($parsed['optional'] || $parsed['default'] !== null) {
                    $optionalCount++;
                }
            }
        }

        if ($urlSegments->sizeGreaterThan($count) && !$urlSegments->sizeEquals($count - $optionalCount)) {
            return false;
        }

        for ($i = 0; $i < $count; $i++) {
            $segment = $urlSegments[$i] ?? null;
            $routeSegment = $separatedSegments->get($i);

            if ($this->parseParameterSegment($routeSegment, $parsed)) {
                if ($segment === null) {
                    if ($parsed['optional']) {
                        $this->arguments[] = $parsed['default'] ?? "";
                        continue;
                    }

                    return false;
                }

                if ($parsed['type'] !== '' && !$this->isValidArgument($parsed['type'], (string) $segment)) {
                    return false;
                }

                $this->arguments[] = $segment;

                continue;
            }

            if ($routeSegment != $segment) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set route name
     * 
     * @param string $name The name to set for this route, which can be used for referencing the route when generating URLs or for other purposes; route names should be unique within the routing system to avoid conflicts when generating URLs.
     * 
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get route name
     * 
     * @return string|null The name of this route, which can be used for referencing the route when generating URLs or for other purposes; returns null if no name is set for this route.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Add parameter constraint
     * 
     * @param string $param The name of the parameter to add a constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{id}").
     * @param string $regex The regex pattern to use as a constraint for the specified parameter; this pattern will be used to validate the parameter's value when matching routes, ensuring that it conforms to the expected format defined by the regex.
     * 
     * @return self
     */
    public function where(string $param, string $regex): self
    {
        $this->whereConstraints[$param] = $regex;
        return $this;
    }

    /**
     * Add multiple constraints at once using an associative array where keys are parameter names and values are regex patterns; this method allows for convenient bulk addition of parameter constraints for a route, making it easier to define multiple constraints in a single call.
     * 
     * @param array $constraints An associative array where keys are parameter names (corresponding to parameters defined in the route's pattern) and values are regex patterns to use as constraints for those parameters; these patterns will be used to validate the parameter values when matching routes, ensuring that they conform to the expected formats defined by the regex patterns.
     * 
     * @return self
     */
    public function whereArray(array $constraints): self
    {
        foreach ($constraints as $param => $regex) {
            $this->where($param, $regex);
        }
        return $this;
    }

    /**
     * Constraint: numeric only
     * 
     * @param string $param The name of the parameter to add a numeric constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{id}"), and the constraint will ensure that the parameter's value consists only of digits (0-9) when matching routes.
     * 
     * @return self
     */
    public function whereNumber(string $param): self
    {
        return $this->where($param, '[0-9]+');
    }

    /**
     * Constraint: alpha only
     * 
     * @param string $param The name of the parameter to add an alpha constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{name}"), and the constraint will ensure that the parameter's value consists only of letters (a-z, A-Z) when matching routes.
     * 
     * @return self
     */
    public function whereAlpha(string $param): self
    {
        return $this->where($param, '[a-zA-Z]+');
    }

    /**
     * Constraint: alphanumeric only
     * 
     * @param string $param The name of the parameter to add an alphanumeric constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{username}"), and the constraint will ensure that the parameter's value consists only of letters (a-z, A-Z) and digits (0-9) when matching routes.
     * 
     * @return self
     */
    public function whereAlphaNumeric(string $param): self
    {
        return $this->where($param, '[a-zA-Z0-9]+');
    }

    /**
     * Constraint: UUID
     * 
     * @param string $param The name of the parameter to add a UUID constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{id}"), and the constraint will ensure that the parameter's value matches the standard UUID format (8-4-4-4-12 hexadecimal characters) when matching routes.
     * @return self
     */
    public function whereUuid(string $param): self
    {
        return $this->where($param, '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}');
    }

    /**
     * Constraint: slug
     * 
     * @param string $param The name of the parameter to add a slug constraint for; this should correspond to a parameter defined in the route's pattern (e.g., "{slug}"), and the constraint will ensure that the parameter's value consists of lowercase letters, digits, and hyphens (a-z, 0-9, and '-') when matching routes, which is a common format for slugs used in URLs.
     * 
     * @return self
     */
    public function whereSlug(string $param): self
    {
        return $this->where($param, '[a-z0-9-]+');
    }

    /**
     * Get where constraints
     * 
     * @return array An associative array of parameter constraints for this route, where keys are parameter names (corresponding to parameters defined in the route's pattern) and values are regex patterns that define the constraints for those parameters; these constraints are used to validate parameter values when matching routes, ensuring that they conform to the expected formats defined by the regex patterns.
     */
    public function getWhereConstraints(): array
    {
        return $this->whereConstraints;
    }

    /**
     * Set default value for parameter
     * 
     * @param string $param The name of the parameter to set a default value for; this should correspond to a parameter defined in the route's pattern (e.g., "{id}"), and the default value will be used when matching URLs that omit this parameter, allowing the route to still match and providing a fallback value for the parameter when it is not present in the URL.
     * @param mixed $value The default value to set for the specified parameter; this value will be used when matching URLs that omit the parameter, allowing the route to still match and providing a fallback value for the parameter when it is not present in the URL. The type of the default value can be mixed, depending on what is appropriate for the parameter (e.g., string, number, etc.).
     * 
     * @return self
     */
    public function defaults(string $param, mixed $value): self
    {
        $this->defaults[$param] = $value;
        return $this;
    }

    /**
     * Get defaults
     * 
     * @return array An associative array of default values for optional parameters in the route pattern, where keys are parameter names and values are the default values to use when matching URLs that omit those parameters.
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Set prefix for route pattern; this prefix will be automatically prepended to the route's pattern, allowing for grouping routes under a common path segment (e.g., "/api") and enabling more organized route definitions. Setting a prefix will also update the route's pattern to include the prefix, ensuring that the route matches URLs that start with the specified prefix followed by the original pattern.
     * 
     * @param string $prefix The prefix to set for this route, which will be automatically prepended to the route's pattern; this is useful for grouping routes under a common path segment (e.g., "/api") and allows for more organized route definitions.
     * 
     * @return self
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = $prefix;
        $this->setPattern(sprintf("%s%s", $prefix, $this->pattern));
        return $this;
    }

    /**
     * Get prefix
     * 
     * @return string The prefix set for this route, which is automatically prepended to the route's pattern; this is useful for grouping routes under a common path segment (e.g., "/api") and allows for more organized route definitions.
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set domain constraint
     * 
     * @param string $domain The domain constraint to set for this route; this allows the route to be limited to specific domains or subdomains, and it mirrors the host property but semantically indicates that the constraint is at the domain level. Setting a domain constraint will also set the host property to the same value to ensure consistency in route matching.
     * 
     * @return self
     */
    public function domain(string $domain): self
    {
        $this->domain = $domain;
        $this->setHost($domain);
        return $this;
    }

    /**
     * Get domain constraint
     * 
     * @return string|null The domain constraint set for this route, or null if no domain constraint is set; this mirrors the host property but semantically indicates that the constraint is at the domain level.
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Add single middleware
     * 
     * @param mixed $middleware The middleware to be added to this route; can be a Closure, a "Class@method" string, or an array callable. This method allows for adding a single middleware to the route's list of middlewares, which will be executed before the main callback when handling a request that matches this route.
     * 
     * @return self
     */
    public function middleware(mixed $middleware): self
    {
        if (is_array($middleware)) {
            $this->middlewares = array_merge($this->middlewares, $middleware);
        } else {
            $this->middlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Exclude middleware from route
     * 
     * @param mixed $middleware The middleware or array of middlewares to exclude from execution when handling this route; this allows for specific middlewares to be skipped for this route even if they are globally applied or applied at a higher level in the routing system.
     * 
     * @return self
     */
    public function withoutMiddleware(mixed $middleware): self
    {
        if (is_array($middleware)) {
            $this->excludedMiddlewares = array_merge($this->excludedMiddlewares, $middleware);
        } else {
            $this->excludedMiddlewares[] = $middleware;
        }

        return $this;
    }

    /**
     * Get excluded middlewares
     * 
     * @return array
     */
    public function getExcludedMiddlewares(): array
    {
        return $this->excludedMiddlewares;
    }

    /**
     * Get effective middlewares (excluding excluded ones)
     * 
     * @return array
     */
    public function getEffectiveMiddlewares(): array
    {
        return array_filter($this->middlewares, function ($m) {
            return !in_array($m, $this->excludedMiddlewares);
        });
    }

    /**
     * Generate URL from route pattern and given parameters; this method takes an associative array of parameters and replaces the corresponding placeholders in the route's pattern with the provided values. It also handles optional parameters and defaults, ensuring that the generated URL is correctly formatted based on the route's pattern and the given parameters. The resulting URL is returned as a string, which can be used for linking to this route or for other purposes where a URL representation of the route is needed.
     * 
     * @param array $params An associative array of parameters to replace in the route's pattern; the keys should correspond to the parameter names defined in the route's pattern (e.g., "id" for a "{id}" parameter), and the values are the actual values to substitute into the pattern when generating the URL. This method will handle both required and optional parameters, as well as any defaults defined for optional parameters, to produce a correctly formatted URL based on the route's pattern.
     * 
     * @return string
     */
    public function generateUrl(array $params = []): string
    {
        $pattern = (string) $this->pattern;

        foreach ($params as $key => $value) {
			$replacement = $this->normalizeUrlParameter((string) $key, $value);
			$pattern = str_replace(
				['{' . $key . '}?', '{' . $key . '}'],
				$replacement,
				$pattern
			);
        }

        foreach ($this->defaults as $key => $value) {
			$replacement = $this->normalizeUrlParameter((string) $key, $value);
			$pattern = str_replace('{' . $key . '}?', $replacement, $pattern);
        }

        $pattern = preg_replace('/\{[^}]+\}\?/', '', $pattern);

        return sprintf('/%s', trim($pattern, '/'));
    }

	/**
	 * Normalize a route parameter for safe placeholder replacement.
	 */
	private function normalizeUrlParameter(string $parameterName, mixed $value): string
	{
		if (!is_scalar($value) && !$value instanceof Stringable) {
			throw new InvalidArgumentException(sprintf(
				'Route parameter "%s" must be scalar or stringable.',
				$parameterName
			));
		}

		return (string) $value;
	}

    /**
     * Check if route matches given method
     * 
     * @param string $method The HTTP method to check against the route's allowed methods; this method can be used to determine if the route should be considered a match for an incoming request based on the request's HTTP method (e.g., GET, POST, PUT, DELETE). In this implementation, it currently returns true for all methods, but it can be extended to check against specific allowed methods for the route.
     * 
     * @return bool
     */
    public function matchesMethod(string $method): bool
    {
        return true;
    }

    /**
     * Convert route to array for caching or debugging purposes; this method returns an associative array representation of the route, including its pattern, name, host, content type, middlewares, where constraints, and defaults. This can be useful for caching the route configuration or for debugging purposes to inspect the properties of the route in a structured format.
     * 
     * @return array{
     *  pattern: ?StringObject, 
     *  name: ?string, 
     *  host: ?string, 
     *  contentType: string, 
     *  middlewares: Closure[], 
     *  whereConstraints: array, 
     *  defaults: array
     * }
     */
    public function toArray(): array
    {
        return [
            'pattern' => (string) $this->pattern,
            'name' => $this->name,
            'host' => $this->host,
            'contentType' => $this->contentType,
            'middlewares' => $this->middlewares,
            'constraints' => $this->whereConstraints,
            'defaults' => $this->defaults,
        ];
    }

    /**
     * Clone route with new pattern
     * 
     * @param string $pattern The new route pattern to set for the cloned route; this allows for creating a new route instance that shares the same callback, middlewares, and other properties as the original route, but with a different pattern for matching URLs. The cloned route will have its arguments reset to an empty array, as it will need to extract new arguments based on the new pattern when matching URLs.
     * 
     * @return self
     */
    public function cloneWithPattern(string $pattern): self
    {
        $clone = clone $this;
        $clone->setPattern($pattern);
        $clone->arguments = [];
        return $clone;
    }

    #endregion
}
