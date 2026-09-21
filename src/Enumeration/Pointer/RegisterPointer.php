<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Pointer;

/**
 * Enumeration class for CPU register pointers.
 */
abstract class RegisterPointer
{
    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_8 = 'r8';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_9 = 'r9';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_10 = 'r10';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_11 = 'r11';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_12 = 'r12';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_13 = 'r13';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_14 = 'r14';

    /**
     * Additional general-purpose register
     * 
     * * Type : Extended General-Purpose Registers / Integer Registers
     * 
     * @var string
     */
    public const GENERAL_PURPOSE_REGISTER_15 = 'r15';

    public const PROGRAM_COUNTER = 'pc';

    public const STACK_POINTER = 'sp';

    /**
     * Stack Pointer Register
     * 
     * Points at the top of the current stack
     * 
     * * Type : Index Registers
     * 
     * @var string
     */
    public const STACK_POINTER_REGISTER = 'rsp';

    /**
     * Base Register
     * 
     * Sometimes used to hold data or memory addresses
     * 
     * * Type : General Purpose Register
     * 
     * @var string
     */
    public const BASE_REGISTER = 'rbx';

    /**
     * Base Pointer Register
     * 
     * Points at the base of the current stack frame
     * 
     * * Type : Index Registers
     * 
     * @var string
     */
    public const BASE_POINT_REGISTER = 'rbp';

    /**
     * Destination Index Register
     * 
     * Commonly used in memory copying and string operations
     * 
     * * Type : Index Registers
     * 
     * @var string
     */
    public const DESTINATION_INDEX_REGISTER = 'rdi';

    /**
     * Source Index Register
     * 
     * Commonly used in memory copying and string operations
     * 
     * * Type : Index Registers
     * 
     * @var string
     */
    public const SOURCE_INDEX_REGISTER = 'rsi';

    /**
     * Holds the address of the next instruction to be executed
     * 
     * Points to the address of the next instruction to be executed
     * 
     * * Type : Special-Purpose Register / Instruction pointer
     * 
     * @var string
     */
    public const INSTRUCTION_POINTER_REGISTER = 'rip';

    /**
     * Data Register
     * 
     * Used in arithmetic and I/O operations
     * 
     * * Type : General Purpose Register
     * 
     * @var string
     */
    public const DATA_REGISTER = 'rdx';
    /**
     * Full 64-bit accumulator register
     * 
     * Used in arithmetic operations and function return values
     * 
     * * Type : General Purpose Register
     * 
     * @var string
     */
    public const ACCUMULATOR_REGISTER = 'rax';

    /**
     * Counter register
     * 
     * Often used for loops and shifts
     * 
     * * Type : General Purpose Register
     * 
     * @var string
     */
    public const COUNTER_REGISTER = 'rcx';

    /**
     * Lower 32 bit accumulator register
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_ACCUMULATOR_REGISTER = 'eax';
    
    /**
     * Lower 32 bit base register
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_BASE_REGISTER = 'ebx';
    
    /**
     * Lower 32 bit counter register
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_COUNTER_REGISTER = 'ecx';

    /**
     * Lower 32 bit data register
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_DATA_REGISTER = 'edx';

    /**
     * Bit Extended (16 -> 32bit) Stack pointer register
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_STACK_POINTER = 'esp';

    /**
     * 16bit Base Pointer
     * 
     * @var string
     */
    public const BASE_POINTER = 'bp';

    /**
     * Bit Extended (16 -> 32bit) Base Pointer
     * 
     * * Architecture: 32bit
     * 
     * @var string
     */
    public const EXTENDED_BASE_POINTER = 'ebp';

    /**
     * Bit Extended (16 -> 32bit) Source Index
     * 
     * @var string
     */
    public const EXTENDED_SOURCE_INDEX = 'esi';

    /**
     * Bit Extended (16 -> 32bit) Destination Index
     * 
     * @var string
     */
    public const EXTENDED_DESTINATION_INDEX = 'edi';

    /**
     * Bit Extended (16 -> 32bit) Instruction Pointer register
     * 
     * @var string
     */
    public const EXTENDED_INSTRUCTION_POINTER = 'eip';

    public const EXTENDED_FLAGS_REGISTER = 'eflags';
}