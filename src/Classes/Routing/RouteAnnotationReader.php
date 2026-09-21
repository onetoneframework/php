<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Routing;

use Clover\Annotation\ContentType;
use Clover\Annotation\Controller;
use Clover\Annotation\DeleteMapping;
use Clover\Annotation\GetMapping;
use Clover\Annotation\Middleware;
use Clover\Annotation\NotFound;
use Clover\Annotation\PatchMapping;
use Clover\Annotation\PostMapping;
use Clover\Annotation\Prefix;
use Clover\Annotation\PutMapping;
use Clover\Annotation\RequestMapping;
use Clover\Annotation\RestController;
use Clover\Annotation\Route;
use Clover\Classes\BaseClass;
use Clover\Classes\Reflection\Handler as ReflectionHandler;
use ReflectionMethod;
use ReflectionClass;
use function sprintf;
use function is_string;
use function is_array;

/**
 * Class RouteAnnotationReader
 * 
 * Reads route annotations from classes and methods.
 */
class RouteAnnotationReader extends BaseClass
{
	private RouteDescriptorBuilder $routeDescriptorBuilder;
	/** @var array<string, Route[]> */
	private static array $readCache = [];

	/**
	 * Constructor.
	 * 
	 * @param RouteDescriptorBuilder|null $routeDescriptorBuilder Optional custom builder for route descriptors.
	 */
	public function __construct(?RouteDescriptorBuilder $routeDescriptorBuilder = null)
	{
		$this->routeDescriptorBuilder = $routeDescriptorBuilder ?? RouteDescriptorBuilder::create();
	}

	/**
	 * Build a Route descriptor from mapping.
	 *
	 * @param string $method
	 * @param object $mapping
	 *
	 * @return Route
	 */
	private function routeFromMapping(string $method, object $mapping): Route
	{
		$pattern = $mapping->value ?? '*';
		$host = $mapping->host ?? '*';
		$priority = $mapping->priority ?? 0;

		$route = $this->routeDescriptorBuilder
			->withMethod($method)
			->withPattern($pattern)
			->withHost($host)
			->withPriority((int) $priority)
			->build();

		if (isset($mapping->pathQueryKey) && is_string($mapping->pathQueryKey)) {
			$route->pathQueryKey = $mapping->pathQueryKey;
		}
		if (isset($mapping->query) && is_array($mapping->query)) {
			$route->query = $mapping->query;
		}

		return $route;
	}

	/**
	 * Inherit pathQueryKey and required query params from class-level #[RequestMapping] / #[Route] when the method omits them.
	 * @param Route $descriptor The method-level route descriptor to potentially supplement with class-level info
	 * @param ReflectionClass $class The declaring class to read class-level annotations from
	 * @return void
	 */
	private function mergeClassLevelQueryRouting(Route $descriptor, ReflectionClass $class): void
	{
		$classPathKey = '';
		$classQuery = [];

		foreach (ReflectionHandler::getAnnotations($class, RequestMapping::class) as $cm) {
			if ($cm->pathQueryKey !== '') {
				$classPathKey = $cm->pathQueryKey;
			}
			if ($cm->query !== []) {
				$classQuery = array_merge($classQuery, $cm->query);
			}
		}

		foreach (ReflectionHandler::getAnnotations($class, Route::class) as $cm) {
			if ($cm->pathQueryKey !== '') {
				$classPathKey = $cm->pathQueryKey;
			}
			if ($cm->query !== []) {
				$classQuery = array_merge($classQuery, $cm->query);
			}
		}

		if ($descriptor->pathQueryKey === '' && $classPathKey !== '') {
			$descriptor->pathQueryKey = $classPathKey;
		}

		$descriptor->query = array_merge($classQuery, $descriptor->query);
	}

	/**
	 * Resolve method-level route descriptor from Route or mapping.
	 *
	 * @param ReflectionMethod $method
	 *
	 * @return Route|null
	 */
	private function resolveMethodRouteDescriptor(ReflectionMethod $method): ?Route
	{
		/** @var Route[] $routeAnnotation */
		$routeAnnotation = ReflectionHandler::getAnnotations($method, Route::class);
		if (isset($routeAnnotation[0])) {
			return $routeAnnotation[0];
		}

		/** @var RequestMapping[] $requestMappings */
		$requestMappings = ReflectionHandler::getAnnotations($method, RequestMapping::class);
		if (isset($requestMappings[0])) {
			$mapping = $requestMappings[0];
			return $this->routeFromMapping($mapping->method ?? '*', $mapping);
		}

		/** @var GetMapping[] $getMappings */
		$getMappings = ReflectionHandler::getAnnotations($method, GetMapping::class);
		if (isset($getMappings[0])) {
			return $this->routeFromMapping('GET', $getMappings[0]);
		}

		/** @var PostMapping[] $postMappings */
		$postMappings = ReflectionHandler::getAnnotations($method, PostMapping::class);
		if (isset($postMappings[0])) {
			return $this->routeFromMapping('POST', $postMappings[0]);
		}

		/** @var PutMapping[] $putMappings */
		$putMappings = ReflectionHandler::getAnnotations($method, PutMapping::class);
		if (isset($putMappings[0])) {
			return $this->routeFromMapping('PUT', $putMappings[0]);
		}

		/** @var PatchMapping[] $patchMappings */
		$patchMappings = ReflectionHandler::getAnnotations($method, PatchMapping::class);
		if (isset($patchMappings[0])) {
			return $this->routeFromMapping('PATCH', $patchMappings[0]);
		}

		/** @var DeleteMapping[] $deleteMappings */
		$deleteMappings = ReflectionHandler::getAnnotations($method, DeleteMapping::class);
		if (isset($deleteMappings[0])) {
			return $this->routeFromMapping('DELETE', $deleteMappings[0]);
		}

		return null;
	}

	/**
	 * Prepends the class-level RequestMapping path to a handler method route (class mapping).
	 *
	 * Not used for the synthetic class-only route registered when a class has #[RequestMapping] without
	 * a separate #[Route], so the class-level pattern is not applied twice.
	 *
	 * @param Route            $descriptor Route descriptor built from the handler method.
	 * @param ReflectionClass  $class      Declaring controller class.
	 */
	private function prependClassLevelRequestMappingForHandler(Route $descriptor, ReflectionClass $class): void
	{
		/** @var RequestMapping[] $classRequestMapping */
		$classRequestMapping = ReflectionHandler::getAnnotations($class, RequestMapping::class);
		if (!isset($classRequestMapping[0])) {
			return;
		}

		$basePath = $classRequestMapping[0]->value ?? '*';
		if ($basePath === '*' || $basePath === '') {
			return;
		}

		$descriptor->pattern = sprintf("%s%s", $basePath, $descriptor->pattern);
	}

	/**
	 * Set supplement for route descriptor from class-level annotations and method-level annotations.
	 * 
	 * @param Route $descriptor
	 * @param ReflectionClass|ReflectionMethod $reflection
	 * 
	 * @return void
	 */
	public function supplement(Route $descriptor, ReflectionClass|ReflectionMethod $reflection): void
	{
		/** @var Prefix[] $prefixAnnotation */
		$prefixAnnotation = ReflectionHandler::getAnnotations($reflection, Prefix::class);
		if (isset($prefixAnnotation[0])) {
			$descriptor->pattern = sprintf("%s%s", $prefixAnnotation[0]->value, $descriptor->pattern);
		}

		/** @var RestController[] $restControllerAnnotation */
		$restControllerAnnotation = ReflectionHandler::getAnnotations($reflection, RestController::class);
		if (isset($restControllerAnnotation[0]) && $restControllerAnnotation[0]->value !== '') {
			$descriptor->pattern = sprintf("%s%s", $restControllerAnnotation[0]->value, $descriptor->pattern);
		} else {
			/** @var Controller[] $controllerAnnotation */
			$controllerAnnotation = ReflectionHandler::getAnnotations($reflection, Controller::class);
			if (isset($controllerAnnotation[0]) && $controllerAnnotation[0]->value !== '') {
				$descriptor->pattern = sprintf("%s%s", $controllerAnnotation[0]->value, $descriptor->pattern);
			}
		}

		/** @var ContentType[] $contentTypeAnnotation */
		$contentTypeAnnotation = ReflectionHandler::getAnnotations($reflection, ContentType::class);
		if (isset($contentTypeAnnotation[0])) {
			$descriptor->contentType = sprintf("%s", $contentTypeAnnotation[0]->value);
		}

		/** @var Middleware[] $middlewareAnnotation */
		$middlewareAnnotation = ReflectionHandler::getAnnotations($reflection, Middleware::class);
		foreach ($middlewareAnnotation as $annotation) {
			$descriptor->middleware[] = $annotation->value;
		}

		/** @var NotFound[] $notFoundAnnotation */
		$notFoundAnnotation = ReflectionHandler::getAnnotations($reflection, NotFound::class);
		if (isset($notFoundAnnotation[0])) {
			$descriptor->notFoundHandler = $notFoundAnnotation[0]->value;
		}
	}

	/**
	 * Reads an array of annotations from the given class, including method-level annotations, and supplements them with class-level info.
	 * 
	 * @param string $className
	 * 
	 * @return Route[]
	 */
	public function read(string $className): array
	{
		if (isset(self::$readCache[$className])) {
			return self::$readCache[$className];
		}

		/** @var Route[] $annotations */
		$annotations = [];

		$class = new ReflectionClass($className);

		/** @var Route[] $routeAnnotation */
		$routeAnnotation = ReflectionHandler::getAnnotations($class, Route::class);
		if ($routeAnnotation !== null && isset($routeAnnotation[0])) {
			$descriptor = $routeAnnotation[0];
			$this->supplement($descriptor, $class);

			$annotations[] = $descriptor;
		}

		/** @var RequestMapping[] $requestMappingAnnotation */
		$requestMappingAnnotation = ReflectionHandler::getAnnotations($class, RequestMapping::class);
		if (isset($requestMappingAnnotation[0])) {
			$descriptor = $this->routeFromMapping($requestMappingAnnotation[0]->method ?? '*', $requestMappingAnnotation[0]);
			$this->supplement($descriptor, $class);

			$annotations[] = $descriptor;
		}

		/** @var ReflectionMethod[] $methods */
		$methods = $class->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED);
		foreach ($methods as $method) {
			if (!$class->hasMethod($method->getName())) {
				continue;
			}

			if ($method->isDestructor() || $method->isConstructor()) {
				continue;
			}

			if ($method->isStatic() || $method->isPrivate() || $method->isProtected()) {
				continue;
			}

			/** @var Route|null $descriptor */
			$descriptor = $this->resolveMethodRouteDescriptor($method);
			if (!isset($descriptor)) {
				continue;
			}

			$this->mergeClassLevelQueryRouting($descriptor, $class);
			$this->prependClassLevelRequestMappingForHandler($descriptor, $class);
			$this->supplement($descriptor, $class);
			$this->supplement($descriptor, $method);
			$descriptor->holder = [$class->getName(), $method->getName()];

			$annotations[] = $descriptor;
		}

		self::$readCache[$className] = $annotations;

		return $annotations;
	}
}
