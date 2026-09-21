<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Module;

use InvalidArgumentException;
use function array_key_exists;
use function array_unique;
use function array_values;
use function is_array;
use function is_string;
use function sprintf;

/**
 * Parsed module enablement and ordering configuration.
 */
final class ModuleConfiguration
{
	/**
	 * @param string[]|null $enabled Explicit allowlist; null means all discovered modules.
	 * @param string[] $disabled Module names excluded from loading.
	 * @param string[] $order Optional explicit load order before dependency resolution.
	 */
	public function __construct(
		public readonly ?array $enabled,
		public readonly array $disabled,
		public readonly array $order,
	) {
	}

	/**
	 * @param array<string, mixed> $configuration
	 */
	public static function fromArray(array $configuration): self
	{
		$enabled = null;

		if (array_key_exists('enabled', $configuration)) {
			if ($configuration['enabled'] !== null && !is_array($configuration['enabled'])) {
				throw new InvalidArgumentException('Module configuration key "enabled" must be null or an array of names.');
			}

			if (is_array($configuration['enabled'])) {
				$enabled = self::normalizeStringList($configuration['enabled'], 'enabled');
			}
		}

		$disabled = [];

		if (array_key_exists('disabled', $configuration)) {
			if (!is_array($configuration['disabled'])) {
				throw new InvalidArgumentException('Module configuration key "disabled" must be an array of names.');
			}

			$disabled = self::normalizeStringList($configuration['disabled'], 'disabled');
		}

		$order = [];

		if (array_key_exists('order', $configuration)) {
			if (!is_array($configuration['order'])) {
				throw new InvalidArgumentException('Module configuration key "order" must be an array of names.');
			}

			$order = self::normalizeStringList($configuration['order'], 'order');
		}

		return new self($enabled, $disabled, $order);
	}

	public function isEnabled(string $moduleName): bool
	{
		if (in_array($moduleName, $this->disabled, true)) {
			return false;
		}

		if ($this->enabled === null) {
			return true;
		}

		return in_array($moduleName, $this->enabled, true);
	}

	/**
	 * @param mixed[] $values
	 * @return string[]
	 */
	private static function normalizeStringList(array $values, string $configurationKey): array
	{
		$normalized = [];

		foreach ($values as $value) {
			if (!is_string($value) || $value === '') {
				throw new InvalidArgumentException(sprintf(
					'Module configuration key "%s" must contain only non-empty module names.',
					$configurationKey
				));
			}

			$normalized[] = $value;
		}

		return array_values(array_unique($normalized));
	}
}
