<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class ElectronicSymbol
{
    public const ADC = 'ADC';
    public const AND_GATE = '&';
    public const ANTENNA = '^';
    public const BATTERY = '+ -';
    public const CAPACITOR = 'C';
    public const COMPARATOR = '> <';
    public const CONNECTION_POINT = '.';
    public const CURRENT_SOURCE_AC = 'IAC';
    public const CURRENT_SOURCE_DC = 'IDC';
    public const DAC = 'DAC';
    public const DIODE = '>|';
    public const DPDT_SWITCH = '//o o o o';
    public const DPST_SWITCH = '//o o';
    public const FUSE = '-/\-';
    public const GROUND = 'GND';
    public const INDUCTOR = 'L';
    public const INTEGRATED_CIRCUIT = 'IC';
    public const LAMP = '(*)';
    public const LED = '>|>';
    public const METER = '|>';
    public const MICROCONTROLLER = 'MCU';
    public const MICROPHONE = '( )';
    public const NAND_GATE = '&o';
    public const NOR_GATE = '>=1o';
    public const NOT_CONNECTED = 'x';
    public const NOT_GATE = '1';
    public const OP_AMP = '△';
    public const OR_GATE = '>=1';
    public const PHOTORESISTOR = 'RL';
    public const RESISTOR = 'R';
    public const SPDT_SWITCH = '/o o';
    public const SPEAKER = '()';
    public const SPST_SWITCH = '/o';
    public const THERMISTOR = 'R°';
    public const TRANSFORMER = '|| ||';
    public const TRANSISTOR_NPN = 'npn';
    public const TRANSISTOR_PNP = 'pnp';
    public const VOLTAGE_SOURCE_AC = 'VAC';
    public const VOLTAGE_SOURCE_DC = 'VDC';
    public const WIRE = '-';
    public const XNOR_GATE = '=1o';
    public const XOR_GATE = '=1';
}