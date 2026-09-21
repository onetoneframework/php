<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Assembly;

final class EmulatorOpcode
{
    public const NOP = 0x00;
    public const MOV = 0x01;
    public const LOAD = 0x02;
    public const STORE = 0x03;
    public const ADD = 0x04;
    public const SUB = 0x05;
    public const JMP = 0x06;
    public const JZ = 0x07;
    public const JNZ = 0x08;
    public const CALL = 0x09;
    public const RET = 0x0A;
    public const PUSH = 0x0B;
    public const POP = 0x0C;
    public const INT = 0x0D;
    public const HLT = 0x0E;
    public const CMP = 0x0F;
    public const JE = 0x10;
    public const JNE = 0x11;
    public const CALLBACK = 0x12;
    public const AND = 0x13;
    public const OR = 0x14;
    public const XOR = 0x15;
    public const NOT = 0x16;
    public const SHL = 0x17;
    public const SHR = 0x18;
    public const INC = 0x19;
    public const DEC = 0x1A;
    public const MOV_REG = 0x1B;
    public const LOAD_REG = 0x1C;
    public const STORE_REG = 0x1D;
    public const JC = 0x1E;
    public const JNC = 0x1F;
    public const IRET = 0x20;
    public const MUL = 0x21;
    public const ADD_IMM = 0x22;
    public const SUB_IMM = 0x23;
    public const CMP_IMM = 0x24;
    public const XCHG = 0x25;
    public const NEG = 0x26;
    public const CLI = 0x27;
    public const STI = 0x28;
    public const IN = 0x29;
    public const OUT = 0x2A;
    public const JS = 0x2B;
    public const JNS = 0x2C;
    public const JO = 0x2D;
    public const JNO = 0x2E;
}