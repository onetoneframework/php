<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Database\Migration;

use InvalidArgumentException;

/**
 * Describes the persisted and filesystem state of a migration.
 */
final class MigrationStatus
{
	public const PENDING_BATCH = 0;

	private string $identifier;
	private int $batch;
	private bool $available;

	public function __construct(string $identifier, int $batch, bool $available)
	{
		if ($identifier === '') {
			throw new InvalidArgumentException('Migration identifier cannot be empty.');
		}

		if ($batch < self::PENDING_BATCH) {
			throw new InvalidArgumentException('Migration batch cannot be negative.');
		}

		$this->identifier = $identifier;
		$this->batch = $batch;
		$this->available = $available;
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getBatch(): int
	{
		return $this->batch;
	}

	public function isApplied(): bool
	{
		return $this->batch > self::PENDING_BATCH;
	}

	public function isAvailable(): bool
	{
		return $this->available;
	}
}
