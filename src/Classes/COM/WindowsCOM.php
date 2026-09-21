<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\COM;
use Clover\Classes\COM\CommonCOM;
use COM;

class WindowsCOM extends CommonCOM
{
    /** 
     * @var object{
     *   AssociatorsOf: callable, 
     *   AssociatorsOfAsync: callable,
     *   Delete: callable,
     *   DeleteAsync: callable,
     *   ExecMethod: callable,
     *   ExecMethodAsync: callable,
     *   ExecNotificationQuery: callable,
     *   ExecNotificationQueryAsync: callable,
     *   ExecQuery: callable(string): array, 
     *   ExecQueryAsync: callable,
     *   Get: callable,
     *   GetAsync: callable,
     *   InstancesOf: callable,
     *   InstancesOfAsync: callable,
     *   ReferencesTo: callable,
     *   ReferencesToAsync: callable,
     *   SubclassesOf: callable,
     *   SubclassesOfAsync: callable
     * } $wmi */
    private object $wmi;

    public function __construct()
    {
        parent::__construct();
        // @phpstan-ignore-next-line
        parent::$com = new COM('WbemScripting.SWbemLocator');
        $this->wmi = parent::$com->ConnectServer();
    }

    public function getWin32ComputerSystemViaQuery()
    {
        $hardwareAttributes = [];
        $query = $this->wmi->ExecQuery('Select * from Win32_ComputerSystem');

        foreach ($query as $object) {
            $hardwareAttributes[] = $object->Model;
        }

        return $hardwareAttributes;
    }

    public function getWin32ProcessorViaQuery()
    {
        $hardwareAttributes = [];
        /**
         * @var array{object{
         *  Name: string,
         *  NumberOfCores: number, //uint32
         *  MaxClockSpeed: number //uint32
         * }}
         */
        $query = $this->wmi->ExecQuery('SELECT * FROM Win32_Processor');

        foreach ($query as $object) {
            $hardwareAttributes[] = $object->Name;
            $hardwareAttributes[] = $object->Family;
            $hardwareAttributes[] = $object->DeviceID;
            $hardwareAttributes[] = $object->Manufacturer;
            $hardwareAttributes[] = $object->Description;
            $hardwareAttributes[] = $object->ProcessorId;
            $hardwareAttributes[] = $object->Architecture;
            $hardwareAttributes[] = $object->NumberOfCores;
            $hardwareAttributes[] = $object->ProcessorType;
            $hardwareAttributes[] = $object->MaxClockSpeed;
        }

        return $hardwareAttributes;
    }

    public function getWin32BaseBoardViaQuery()
    {
        $hardwareAttributes = [];
        $query = $this->wmi->ExecQuery('SELECT * FROM Win32_BaseBoard');

        foreach ($query as $object) {
            $hardwareAttributes[] = $object->Manufacturer;
            $hardwareAttributes[] = $object->Product;
            $hardwareAttributes[] = $object->SerialNumber;
            $hardwareAttributes[] = $object->Version;
        }

        return $hardwareAttributes;
    }

    public function getWin32NetworkPhysicalAdapterViaQuery()
    {
        $hardwareAttributes = [];
        $query = $this->wmi->ExecQuery('SELECT * FROM Win32_NetworkAdapter WHERE PhysicalAdapter = TRUE');

        foreach ($query as $object) {
            $hardwareAttributes[] = $object->Name;
            $hardwareAttributes[] = $object->MACAddress;
            $hardwareAttributes[] = $object->PNPDeviceID;
            $hardwareAttributes[] = $object->AdapterType;
        }

        return $hardwareAttributes;
    }

    public function getWin32BiosViaQuery()
    {
        $hardwareAttributes = [];
        $query = $this->wmi->ExecQuery('SELECT * FROM Win32_BIOS');

        foreach ($query as $object) {
            $hardwareAttributes[] = $object->Manufacturer;
            $hardwareAttributes[] = $object->SerialNumber;
        }

        return $hardwareAttributes;
    }
}
