<?php

declare(strict_types=1);

namespace App\Modules\Board\Exception;

use RuntimeException;

/**
 * Raised when a requested board post does not exist.
 */
final class PostNotFoundException extends RuntimeException
{
	public function __construct(int $postIdentifier)
	{
		parent::__construct(sprintf('Post "%d" was not found.', $postIdentifier));
	}
}
