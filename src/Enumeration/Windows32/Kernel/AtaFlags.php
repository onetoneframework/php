<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Windows32\Kernel;

enum AtaFlags: int
{
    // Data direction flags 
    case ATA_FLAGS_DATA_OUT = 0x0001;
    case ATA_FLAGS_DATA_IN = 0x0002;

    // Capability / mode flags
    case ATA_FLAGS_48BIT_COMMANDS = 0x0004;
    case ATA_FLAGS_QUEUED = 0x0008;
    case ATA_FLAGS_DRDY_REQUIRED = 0x0010;
    case ATA_FLAGS_USE_DMA = 0x0020;
    case ATA_FLAGS_PIO = 0x0040;
    case ATA_FLAGS_LBA = 0x0080;
    case ATA_FLAGS_ATAPI = 0x0100;
    case ATA_FLAGS_DEVICE_PRESENT = 0x0200;
    case ATA_FLAGS_SECURITY = 0x0400;

    // SMART (uses ATA_CMD_SMART with subcommands)
    case ATA_CMD_SMART = 0xB0;
    case ATA_SMART_READ_DATA = 0xD0;
    case ATA_SMART_ENABLE_OPERATIONS = 0xD8;
    case ATA_SMART_DISABLE_OPERATIONS = 0xD9;
    case ATA_SMART_RETURN_STATUS = 0xDA;

    case ATA_CMD_IDENTIFY_DEVICE = 0xEC;
    case ATA_CMD_STANDBY_IMMEDIATE = 0xE0;
    case ATA_CMD_IDLE_IMMEDIATE = 0xE1;
    case ATA_CMD_CHECK_POWER_MODE = 0xE5;
    case ATA_CMD_PACKET = 0xA0;
}