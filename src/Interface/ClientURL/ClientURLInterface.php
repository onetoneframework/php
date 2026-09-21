<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Implement;

use Clover\Classes\Data\StringObject;

/**
 * Client URL Interface
 *
 * Defines the contract for URL client operations.
 * Provides methods for making HTTP requests and managing client sessions.
 */
interface ClientURLInterface
{
	public function close();
	public function execute();
	public function getLastErrorMessage();
	public function getLastErrorNumber();
	public function getSession();
	public function information();
	public function initialize(string|StringObject|null $instance = null): mixed;
	public function option();
	public function reset();
	public function setOption(int $option, $value);
}
