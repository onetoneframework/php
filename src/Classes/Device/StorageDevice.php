<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Device;

use Clover\Classes\BaseClass;

/**
 * Storage Device Class
 * 
 * @package Clover\Classes\Device
 */
class StorageDevice extends BaseClass
{
    /**
     * Get bus type name from value
     *
     * @param int $busTypeVal
     *
     * @return string
     */
    public static function getBusType(int $busTypeVal): string
    {
        $busNames = [
            0 => 'Unknown',
            1 => 'Scsi',
            2 => 'Atapi',
            3 => 'Ata',
            4 => '1394',
            5 => 'Ssa',
            6 => 'Fibre',
            7 => 'Usb',
            8 => 'RAID',
            9 => 'iScsi',
            10 => 'Sas',
            11 => 'Sata',
            12 => 'Sd',
            13 => 'Mmc',
            14 => 'Virtual',
            15 => 'FileBackedVirtual',
            16 => 'Spaces',
            17 => 'Nvme',
            18 => 'Scm',
            19 => 'Ufs',
            20 => 'NvmeOf',
            21 => 'ScsiVirtual',
            22 => 'Memory'
        ];

        return $busNames[$busTypeVal] ?? ('Unknown(' . $busTypeVal . ')');
    }
}