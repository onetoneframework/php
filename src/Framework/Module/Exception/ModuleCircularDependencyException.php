<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Module\Exception;

use RuntimeException;

final class ModuleCircularDependencyException extends RuntimeException
{
	/**
	 * @param string[] $dependencyChain
	 */
	public function __construct(array $dependencyChain)
	{
		parent::__construct(sprintf(
			'Circular module dependency detected: %s',
			implode(' -> ', $dependencyChain)
		));
	}
}
