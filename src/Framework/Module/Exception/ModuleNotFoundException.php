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

final class ModuleNotFoundException extends RuntimeException
{
	public function __construct(string $moduleName)
	{
		parent::__construct(sprintf('Module "%s" is not registered.', $moduleName));
	}
}
