<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Protocol;

use Clover\Enumeration\PHPProtocol;

class PHP
{
	/**
	 * Get the standard error stream constant.
	 *
	 * @return string
	 */
	public function getStandardError(): string
	{
		return PHPProtocol::STANDARD_ERROR;
	}

	/**
	 * Get the standard output stream constant.
	 *
	 * @return string
	 */
	public function getStandardOutput(): string
	{
		return PHPProtocol::STANDARD_OUTPUT;
	}

	/**
	 * Get the standard input stream constant.
	 *
	 * @return string
	 */
	public function getStandardInput(): string
	{
		return PHPProtocol::STANDARD_INPUT;
	}

	/**
	 * Get the filter stream constant.
	 *
	 * @return string
	 */
	public function getFilter(): string
	{
		return PHPProtocol::FILTER;
	}

	/**
	 * Get the temporary stream constant.
	 *
	 * @return string
	 */
	public function getTemporary(): string
	{
		return PHPProtocol::TEMPORARY;
	}

	/**
	 * Get the memory stream constant.
	 *
	 * @return string
	 */
	public function getMemory(): string
	{
		return PHPProtocol::MEMORY;
	}

	/**
	 * Get the input stream constant.
	 *
	 * @return string
	 */
	public function getInput(): string
	{
		return PHPProtocol::INPUT;
	}

	/**
	 * Get the output stream constant.
	 *
	 * @return string
	 */
	public function getOutput(): string
	{
		return PHPProtocol::OUTPUT;
	}
}
