<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

/**
 * STORAGE_PROPERTY_ID, the `PropertyId` field of STORAGE_PROPERTY_QUERY (ntddstor.h).
 *
 * The four STORAGE_QUERY_TYPE values that used to sit at the top of this enum now live in
 * {@see StorageQueryType}. They are a separate Windows enumeration that also starts at 0, and the
 * aliases written for them here — `case StorageDeviceProperty = PropertyStandardQuery;` — referred
 * to bare constants that were never defined, so autoloading this file raised
 * `Error: Undefined constant Clover\Enumeration\Windows32\Kernel\PropertyStandardQuery`. Even with
 * the constants resolved they would have been duplicate backing values, which PHP rejects.
 */
enum StorageQueryProperty: int
{
    case StorageDeviceProperty = 0;
    case StorageAdapterProperty = 1;
    case StorageDeviceIdProperty = 2;
    case StorageDeviceUniqueIdProperty = 3;
    case StorageDeviceWriteCacheProperty = 4;
    case StorageMiniportProperty = 5;
    case StorageAccessAlignmentProperty = 6;
    case StorageDeviceSeekPenaltyProperty = 7;
    case StorageDeviceTrimProperty = 8;
    case StorageDeviceWriteAggregationProperty = 9;
    case StorageDeviceDeviceTelemetryProperty = 10;
    case StorageDeviceLBProvisioningProperty = 11;
    case StorageDevicePowerProperty = 12;
    case StorageDeviceCopyOffloadProperty = 13;
    case StorageDeviceResiliencyProperty = 14;
    case StorageDeviceMediumProductType = 15;
    case StorageAdapterRpmbProperty = 16;
    case StorageAdapterCryptoProperty = 17;
    case StorageDeviceIoCapabilityProperty = 18;
    case StorageAdapterProtocolSpecificProperty = 19;
    case StorageDeviceProtocolSpecificProperty = 20;
    case StorageAdapterTemperatureProperty = 21;
    case StorageDeviceTemperatureProperty = 22;
    case StorageAdapterPhysicalTopologyProperty = 23;
    case StorageDevicePhysicalTopologyProperty = 24;
    case StorageDeviceAttributesProperty = 25;
    case StorageDeviceManagementStatus = 26;
    case StorageAdapterSerialNumberProperty = 27;
    case StorageDeviceLocationProperty = 28;
    case StorageDeviceNumaProperty = 29;
    case StorageDeviceZonedDeviceProperty = 30;
    case StorageDeviceUnsafeShutdownCount = 31;
    case StorageDeviceEnduranceProperty = 32;
}
