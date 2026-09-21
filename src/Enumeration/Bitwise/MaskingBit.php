<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Bitwise;

/**
 * Class MaskingBit
 *
 * Provides constants for common bitwise masking operations.
 */
abstract class MaskingBit
{
    /**
     * Masks the lowest 1 bits
     * 
     * Bits: 1(00000001)
     * 
     * @var int
     */
    public const BIT_1 = 0x01;

    /**
     * Masks the lowest 2 bits
     * 
     * Bits: 3(00000011)
     * 
     * @var int
     */
    public const BIT_2 = 0x03;

    /**
     * Masks the lowest 3 bits
     * 
     * Bits: 6(000000111)
     * 
     * @var int
     */
    public const BIT_3 = 0x07;

    /**
     * Masks 4 bits (1 nibble)
     * 
     * Bits: 14(00001111)
     * 
     * @var int
     */
    public const NIBBLE = 0xF;
    public const FULL_NIBBLE = 0x0F;
    
    /**
     * Masks 8 bits (1 byte)
     * 
     * Bits: 1111|1111
     * 
     * @var int
     */
    public const BYTE = 0xFF;

    /**
     * Masks lowest 7 bits
     * 
     * ASCII printable limit, Control character limit, MIDI data byte mask
     * @var int
     */
    public const LOW_BYTE_7 = 0x7F;
    /**
     * Most significant bit
     * 
     * @var int
     */
    public const MSB_BYTE_7 = 0x80;
    public const MSB_MASK = 0x0080;
    public const INTERRUPT_MASK = 0x0400;
    public const INTERRUPT_MASK_0 = 0x0001;
    public const INTERRUPT_MASK_1 = 0x0002;
    public const INTERRUPT_MASK_2 = 0x0004;
    public const INTERRUPT_MASK_3 = 0x0008;
    public const INTERRUPT_MASK_4 = 0x0010;
    public const INTERRUPT_MASK_5 = 0x0020;
    public const INTERRUPT_MASK_6 = 0x0040;
    public const INTERRUPT_MASK_7 = 0x0080;
    public const INTERRUPT_MASK_8 = 0x0100;
    public const INTERRUPT_MASK_9 = 0x0200;

    public const BITS_2_3 = 0x0C;
    public const BITS_4_5 = 0x30;

    /**
     * 12 bits
     * 
     * Bits: 4,095 (1111|1111|1111)
     * 
     * @var int
     */
    public const BIT_12 = 0xFFF;

    /**
     * Unsigned short
     *
     * 16 bits (2 bytes)
     * 
     * Bits: 1111|1111|1111|1111
     * 
     * @var int
     */
    public const WORD = 0xFFFF;

    /**
     * Summary of UNSIGNED_WORD
     * 
     * 16-bit
     * 
     * @var int
     */
    public const UNSIGNED_WORD = 0xFFFE;

    /**
     * Unsigned int or double word
     * 
     * 17-bit+
     * 
     * @var int
     */
    public const DOUBLE_WORD = 0x10000;

    /**
     * 20 bits
     * 
     * Bits: 1,048,575(1111|1111|1111|1111|1111)
     * 
     * @var int
     */
    public const BIT_20 = 0xFFFFF;

    /**
     * 24 bits
     * 
     * Bits: 16,777,215(1111|1111|1111|1111|1111|1111)
     * 
     * @var int
     */
    public const BIT_24 = 0xFFFFFF;

    /**
     * 32 bits
     * 
     * 4,294,967,295
     * @var int
     */
    public const DWORD = 0xFFFFFFFF;

    public const QWORD = PHP_INT_MAX;

    public const HIGH2_MASK = 0xC0;
    public const HIGH3_MASK = 0xE0;
    public const HIGH4_MASK = 0xF0;
    public const HIGH5_MASK = 0xF8;
    public const FLAG_BIT_4 = 0x0010;
    public const FLAG_BIT_5 = 0x0020;
    public const PROTOCOL_TCP = 0x0001;
    public const PROTOCOL_UDP = 0x0002;
    public const PROTOCOL_HTTP = 0x0004;
    public const PROTOCOL_HTTPS = 0x0008;
    public const PROTOCOL_FTP = 0x0010;
    public const PROTOCOL_SSH = 0x0020;
    public const PROTOCOL_TLS = 0x0040;
    public const PROTOCOL_DNS = 0x0080;
    public const PROTOCOL_ICMP = 0x0100;
    public const PROTOCOL_RAW = 0x0200;
    public const DEVICE_CONNECTED = 0x0001;
    public const DEVICE_DISCONNECTED = 0x0002;
    public const DEVICE_INITIALIZED = 0x0004;
    public const DEVICE_ERROR = 0x0008;
    public const DEVICE_SLEEP = 0x0010;
    public const DEVICE_WAKE = 0x0020;
    public const DEVICE_ACTIVE = 0x0040;
    public const DEVICE_IDLE = 0x0080;
    public const DEVICE_RESTART = 0x0100;
    public const DEVICE_SHUTDOWN = 0x0200;
    public const CTRL_ENABLE = 0x0001;
    public const CTRL_DISABLE = 0x0002;
    public const CTRL_START = 0x0004;
    public const CTRL_STOP = 0x0008;
    public const CTRL_PAUSE = 0x0010;
    public const CTRL_RESUME = 0x0020;
    public const CTRL_RESTART = 0x0040;
    public const CTRL_SHUTDOWN = 0x0080;
    public const CTRL_SLEEP = 0x0100;
    public const CTRL_WAKE = 0x0200;
    public const STATUS_OK = 0x0001;
    public const STATUS_ERROR = 0x0002;
    public const STATUS_BUSY = 0x0004;
    public const STATUS_TIMEOUT = 0x0008;
    public const STATUS_OVERFLOW = 0x0010;
    public const STATUS_UNDERFLOW = 0x0020;
    public const STATUS_READY = 0x0040;
    public const STATUS_RESET = 0x0080;
    public const STATUS_PENDING = 0x0100;
    public const STATUS_COMPLETE = 0x0200;
    public const IRQ_TIMER = 0x0001;
    public const IRQ_UART = 0x0002;
    public const IRQ_GPIO = 0x0004;
    public const IRQ_ADC = 0x0008;
    public const IRQ_SPI = 0x0010;
    public const IRQ_I2C = 0x0020;
    public const IRQ_DMA = 0x0040;
    public const IRQ_USB = 0x0080;
    public const IRQ_ETHERNET = 0x0100;
    public const IRQ_CAN = 0x0200;
    public const SEC_AUTHENTICATED = 0x0001;
    public const SEC_ENCRYPTED = 0x0002;
    public const SEC_SIGNED = 0x0004;
    public const SEC_TRUSTED = 0x0008;
    public const SEC_EXPIRED = 0x0010;
    public const SEC_REVOKED = 0x0020;
    public const SEC_VALID = 0x0040;
    public const SEC_INVALID = 0x0080;
    public const SEC_PENDING = 0x0100;
    public const SEC_APPROVED = 0x0200;
}
