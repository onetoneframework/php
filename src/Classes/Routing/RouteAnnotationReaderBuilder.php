<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Routing;

/**
 * Builder for RouteAnnotationReader.
 */
class RouteAnnotationReaderBuilder
{
	private ?RouteAnnotationReader $annotationReader = null;

	/**
	 * Static factory method to create a new builder instance.
	 * @return RouteAnnotationReaderBuilder A new builder instance
	 */
	public static function create(): self
	{
		return new self();
	}

	/**
	 * Set a custom RouteAnnotationReader instance to use instead of building a new one.
	 * @param RouteAnnotationReader $annotationReader The custom reader to use
	 * @return $this The builder instance for chaining
	 */
	public function withAnnotationReader(RouteAnnotationReader $annotationReader): self
	{
		$this->annotationReader = $annotationReader;
		return $this;
	}

	/**
	 * Build and return a RouteAnnotationReader instance based on the builder's configuration.
	 * If a custom reader was set with withAnnotationReader(), it will be returned instead.
	 * @return RouteAnnotationReader The built or provided RouteAnnotationReader instance
	 */
	public function build(): RouteAnnotationReader
	{
		if ($this->annotationReader !== null) {
			return $this->annotationReader;
		}

		return new RouteAnnotationReader();
	}
}
