<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\AI\Orchestration;

/**
 * Vendor-neutral response returned by an orchestrated AI agent.
 */
final class AgentResponse
{
	private string $content;

	private Attributes $metadata;

	/**
	 * Create a normalized response with optional scalar metadata.
	 */
	public function __construct(string $content, ?Attributes $metadata = null)
	{
		$this->content = $content;
		$this->metadata = $metadata ?? new Attributes();
	}

	/**
	 * Return the normalized response content.
	 */
	public function content(): string
	{
		return $this->content;
	}

	/**
	 * Return vendor-neutral response metadata.
	 */
	public function metadata(): Attributes
	{
		return $this->metadata;
	}

	/**
	 * Return the normalized content and metadata payload size.
	 */
	public function sizeInBytes(): int
	{
		return strlen($this->content) + $this->metadata->sizeInBytes();
	}
}
