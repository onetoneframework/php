<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace App\Database\Seeders;

use Clover\Classes\Database\Driver\PHPDataObject;
use Clover\Classes\Database\Seeder\Seeder;

/**
 * Runs the application's configured seeders in declaration order.
 */
final class DatabaseSeeder extends Seeder
{
	/** @var list<class-string<Seeder>> */
	private array $seederClasses;

	/**
	 * Configures the ordered seeder classes.
	 *
	 * @param class-string<Seeder> ...$seederClasses
	 */
	public function __construct(string ...$seederClasses)
	{
		$this->seederClasses = $seederClasses;
	}

	/** Runs every configured seeder. */
	public function run(PHPDataObject $database): void
	{
		$this->call($this->seederClasses, $database);
	}
}
