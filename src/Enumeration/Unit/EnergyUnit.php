<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

use Clover\Annotation\Deprecated;

/**
 * Defines the supported energy units.
 */
abstract class EnergyUnit
{
	public const JOULE = 'joule';
	public const KILOJOULE = 'kilojoule';
	public const WATT_HOUR = 'watt_hour';
	public const KILOWATT_HOUR = 'kilowatt_hour';
	public const KILOCALORIE = 'kilocalorie';
	public const BTU = 'btu';
}

/**
 * Preserves the legacy energy-unit class name.
 */
#[Deprecated('Use EnergyUnit instead.')]
abstract class EnergeUnit extends EnergyUnit
{
}
