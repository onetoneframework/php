<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Assembly;

/**
 * Assembly Instructions Enumeration
 */
abstract class Instruction
{
    /**
     * Copies the value from the `source` to the `destination`
     * 
     * Transfers byte or word from source to destination. 
     * 
     * The source and destination operands cannot both be memory operands.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * $0x60 : Keyboard data port
     * 
     * $0x64 : Keyboard command port
     * 
     * $0x3F8 : COM1 serial port (UART)
     * 
     * $0x2F8 : COM2 serial port (UART)
     * 
     * $0x378 : Parallel port (LPT1, Printer)
     * 
     * $0x70 : CMOS RTC Port
     * 
     * $0x1F0 : ATA Hard Disk Data Port (Primary)
     * 
     * $0x1F0 : ATA Hard Disk Port (Secondary)
     * 
     * $0x61 : PPI (Programmable Device Interface)
     * 
     * $0x43 : PIT Timer Control Port
     * 
     * $0x3C0–0x3CF : VGA Control Port
     * 
     * **@2 Parameter** : Source
     * 
     * @var string
     */
    public const DATA_MOVEMENT = 'mov';
    
    public const DAVA_MOVEMENT_LONG = 'movl';

    public const DAVA_MOVEMENT_WORD = 'movw';

    public const DAVA_MOVEMENT_BYTE = 'movb';

    public const DAVA_MOVEMENT_QUAD = 'movq';
    
    /**
     * Calculates the address of the `source` and puts that address into the `destination`
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * %eax : AT&T In linux
     * 
     * 8(%ebp) : far 8bytes from Extended base pointer
     * 
     * @var string
     */
    public const LOAD_EFFECTIVE_ADDRESS = 'lea';

    /**
     * Load register `destination` with the value from the memory address pointed to by `source` register *`ARM Comparison` 
     * 
     * ARM is a "load-store" architecture, meaning it can't perform arithmetic directly on data in memory. You must first `load` it into a register, operate on it, and then `store` it back.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * @var string
     */
    public const LOAD_REGISTER = 'ldr';

    /**
     * Store register `destination` with the value from the memory address pointed to by `source` register *`ARM Comparison` 
     * 
     * ARM is a "load-store" architecture, meaning it can't perform arithmetic directly on data in memory. You must first `load` it into a register, operate on it, and then `store` it back.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * @var string
     */
    public const STORE_REGISTER = 'str';

    /**
     * Adds the value from the `source` to the `destination`
     * 
     * Adds "src" to "dest" and replacing the original contents of "dest". 
     * 
     * Both operands are binary.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const ADD = 'add';

    public const ADD_LONG = 'addl';

    /**
     * Substracts the value from the `source` to the `destination`
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const SUBSTRACT = 'sub';

    /**
     * Increments the value from the `source` to the `destination`
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const INCREMENT = 'inc';

    /**
     * Increments the value from the `source` to the `destination`
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const INCREMENT_LONG = 'incl';

    /**
     * Decrements the value from the `source` to the `destination`
     * 
     * Unsigned binary subtraction of one from the destination.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const DECREMENT = 'dec';

    /**
     * Multiplies the value from the `source` to the `destination`
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const INTEGER_MULTIPLY = 'imul';

    /**
     * Divides the value from the `source` to the `destination`
     * 
     * It's complex: it typically divides the 128-bit value in `RDX:RAX` by the source, storing the quotient in `RAX` and the remainder in `RDX`
     * 
     * Unsigned binary division of accumulator by source. If the source divisor is a byte value then AX is divided by "src" and the quotient is placed in AL and the remainder in AH. If source operand is a word value, then DX:AX is divided by "src" and the quotient is stored in AX and the remainder in DX.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * 
     * Pointer or integer
     * 
     * @var string
     */
    public const INTEGER_DIVIDE = 'div';

    /**
     * Logical and
     * @var string
     */
    public const BITWISE_AND = 'and';

    /**
     * Inclusive Logical OR
     * 
     * Logical or
     * @var string
     */
    public const BITWISE_OR = 'or';

    /**
     * A common trick is to `XOR` a register with itself to set it to zero
     * @var string
     */
    public const BITWISE_EXCLUSIVE_OR = 'xor';

    /**
     * One's Compliment Negation
     * 
     * Flips all the bits in the operand
     * @var string
     */
    public const BITWISE_NOT = 'not';

    /**
     * Shifts bits left
     * 
     * filling the empty spots with 0s. This is a very fast way to multiply/divide by powers of 2
     * @var string
     */
    public const BITWISE_SHIFT_LEFT = 'shl';

    /**
     * Shifts bits right
     * 
     * filling the empty spots with 0s. This is a very fast way to multiply/divide by powers of 2
     * @var string
     */
    public const BITWISE_SHIFT_RIGHT = 'shr';

    /**
     * Like shifting, but the bit that falls off one end is put back on the other end
     * @var string
     */
    public const BITWISE_ROTATE_LEFT = 'rol';

    /**
     * Like shifting, but the bit that falls off one end is put back on the other end
     * @var string
     */
    public const BITWISE_ROTATE_RIGHT = 'ror';

    /**
     * Subtracts `source` from `destination` internally but **discards the result**. 
     * 
     * It only sets the CPU flags (Zero Flag, Sign Flag, etc.) 
     * 
     * based on the outcome. This is the foundation for decision making.
     * 
     * **@1 Parameter** : Destination
     * 
     * Pointer or `[named memory adress]`
     * 
     * **@2 Parameter** : Source
     * @var string
     */
    public const COMPARE = 'cmp';

    public const COMPARE_LONG = 'cmpl';

    /**
     * Unconditionally jumps to a different part of the code, identified by a label
     * 
     * Subtracts source from destination and updates the flags but does not save result. Flags can subsequently be checked for conditions (e.g. with [Jxx instructions]).
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const JUMP = 'jmp';

    /**
     * Unconditional Jump
     * 
     * Jumps if the last `CMP` resulted in equality (Zero Flag is set)
     * 
     * It checks the flags set by the last comparison instruction, usually cmpl or testl
     * 
     * If you use je(`and samethings`) without a preceding cmp or sub instruction, it will still execute—but its behavior may be unpredictable or incorrect
     * 
     * because it relies on the CPU flags that were set by the last instruction that affected them
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const JUMP_IF_EQUAL = 'je';

    /**
     * Jumps if the last `CMP` resulted in equality (Zero Flag is set)
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const JUMP_IF_ZERO = 'jz';

    /**
     * Jumps if the last `CMP` was not equal
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const JUMP_IF_NOT_EQUAL = 'jne';

    /**
     * Jumps if the last `CMP` was not equal
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const JUMP_IF_NOT_ZERO = 'jnz';

    /**
     * Jump if Greater For signed numbers (`-5`, `10`)
     * @var string
     */
    public const JUMP_IF_GREATER = 'jg';

    /**
     * Jump if Greater or equal For signed numbers (`-5`, `10`)
     * @var string
     */
    public const JUMP_IF_GREATER_OR_EQUAL = 'jge';

    /**
     * Jump if Less For signed numbers (`-5`, `10`)
     * @var string
     */
    public const JUMP_IF_LESS = 'jl';

    /**
     * Jump if Above For unsigned numbers (like memory addresses)
     * 
     * If CF=0 and ZF=0 transfers control to label, else continues with next instruction. Operands must be of same size. If they are not, you will need to use the data size override operator.
     * @var string
     */
    public const JUMP_IF_ABOVE = 'ja';

    /**
     * Jump if Below For unsigned numbers (like memory addresses)
     * @var string
     */
    public const JUMP_IF_BELOW = 'jb';

    /**
     * Call a function or procedure
     * 
     * Pushes the address of the next instruction onto the stack (so it knows where to return) and then jumps to the function's label
     * 
     * Pushes Instruction Pointer (and Code Segment for far calls) onto stack and loads Instruction Pointer with the address of proc-name. 
     * 
     * Code continues with execution at CS:IP.
     * 
     * **@1 Parameter** : Destination
     * 
     * @var string
     */
    public const CALL_FUNCTION = 'call';

    /**
     * Return from function
     * 
     * Pops the return address from the stack and jumps back to it. The `CALL`'s counterpart
     * @var string
     */
    public const RETURN_FUNCTION = 'call';

    /**
     * Push onto Stack / Push Word onto Stack
     * 
     * Decrements the stack pointer (`RSP`) and then copies the source value to that new location on the stack
     * 
     * **@1 Parameter** : Value
     * 
     * $10 : Store the number 10
     * @var string
     */
    public const PUSH_STACK = 'push';

    /**
     * Push Long onto Stack
     * 
     * Decrements the stack pointer (`RSP`) and then copies the source value to that new location on the stack
     * 
     * **@1 Parameter** : Value
     * 
     * $10 : Store the number 10
     * @var string
     */
    public const PUSH_STACK_LONG = 'pushl';

    /**
     * Push All Registers onto Stack
     * @var string
     */
    public const PUSH_ALL_STACK = 'pusha';

    /**
     * Push Flags onto Stack
     * @var string
     */
    public const PUSH_FLAG_STACK = 'pusha';

    /**
     * Pop from Stack / Pop Word off Stack
     * 
     * Copies the value from the top of the stack into the destination and then increments the stack pointer (`RSP`)
     * @var string
     */
    public const POP_STACK = 'pop';

    /**
     * System Call
     * 
     * Tells the OS kernel to perform an action. 
     * 
     * The action is determined by the value in the `RAX` register, and arguments are passed in other specific registers (`RDI`, `RSI`, `RDX`, etc.)
     * 
     * `MOV RDI, 1` ; Arg 1: File descriptor 1 (stdout) 
     * 
     * `MOV RSI, msg` ; Arg 2: Address of the message to print 
     * 
     * `MOV RDX, len` ; Arg 3: Length of the message 
     * 
     * `SYSCALL` ; Execute the system call
     * @var string
     */
    public const SYSTEM_CALL = 'syscall';

    /**
     * Sums two binary operands placing the result in the destination
     * @var string
     */
    public const ADD_WITH_CARY = 'adc';

    /**
     * Converts byte in AL to word Value in AX by extending sign of AL throughout register AH
     * 
     * @var string
     */
    public const CONVERT_BYTE_TO_WORD = 'cbw';

    public const CLEAR_CARRY = 'clc';

    /**
     * Clears the Direction Flag causing string instructions to increment the SI and DI index registers
     * @var string
     */
    public const CLEAR_DIRECTION_FLAG = 'cld';

    /**
     * Disables the maskable hardware interrupts by clearing the Interrupt flag. NMI's and software interrupts are not inhibited.
     * @var string
     */
    public const CLEAR_INTERRUPT_FLAG = 'cli';

    public const COMPLEMENT_CARRY_FLAG = 'cmc';

    /**
     * Subtracts destination value from source without saving results. Updates flags based on the subtraction and the index registers SI and DI are incremented or decremented depending on the state of the Direction Flag. CMPSB inc/decrements the index registers by 1, CMPSW inc/decrements by 2, while CMPSD increments or decrements by 4. The REP prefixes can be used to process entire data items.
     * @var string
     */
    public const COMPARE_STRING = 'cmps';

    /**
     * Extends sign of word in register AX throughout register DX forming a doubleword quantity in DX:AX.
     * 
     * @var string
     */
    public const CONVERT_WORD_TO_DOUBLE = 'cwd';

    public const CONVERT_DOUBLEWORD_TO_QUADWORD = 'cdq';

    /**
     * Halts CPU until RESET line is activated, NMI or maskable interrupt received. The CPU becomes dormant but retains the current CS:IP for later restart.
     * @var string
     */
    public const HALT_CPU = 'hlt';

    /**
     * Signed binary division of accumulator by source. If source is a byte value, AX is divided by "src" and the quotient is stored in AL and the remainder in AH. If source is a word value, DX:AX is divided by "src", and the quotient is stored in AL and the remainder in DX.
     * @var string
     */
    public const SIGNED_INTEGER_DIVISION = 'idiv';

    public const SIGNED_INTEGER_DIVISION_LONG = 'idivl';

    /**
     * Signed multiplication of accumulator by "src" with result placed in the accumulator. If the source operand is a byte value, it is multiplied by AL and the result stored in AX. If the source operand is a word value it is multiplied by AX and the result is stored in DX:AX.
     * @var string
     */
    public const SIGNED_MULTIPLY = 'imul';

    /**
     * Input Byte or Word From Port
     * 
     * A byte or word is read from "port" and placed in AL or AX respectively. If the port number is in the range of 0-255 it can be specified as an immediate, otherwise the port number must be specified in DX. Valid port ranges on the PC are 0-1024, though values through 65535 may be specified and recognized by third party vendors and PS/2's.
     * 
     * @var string
     */
    public const INPUT_BYTE = 'in';

    /**
     * Input String from Port (80188+)
     * 
     * Adds one to destination unsigned binary operand
     * 
     * Loads data from port to the destination ES:DI (even if a destination operand is supplied). DI is adjusted by the size of the operand and increased if the Direction Flag is cleared and decreased if the Direction Flag is set. For INSB, INSW no operands are allowed and the size is determined by the mnemonic.
     * @var string
     */
    public const INPUT_STRING_FROM_PORT = 'ins';

    /**
     * Initiates a software interrupt by pushing the flags, clearing the Trap and Interrupt Flags, pushing CS followed by IP and loading CS:IP with the value found in the interrupt vector table. Execution then begins at the location addressed by the new CS:IP.
     * @var string
     */
    public const INTERRUPT = 'int';

    /**
     * If the Overflow Flag is set this instruction generates an INT 4 which causes the code addressed by 0000:0010 to be executed.
     *
     * You can syscall in x86 `int $0x80`
     * @var string
     */
    public const INTERRUPT_ON_OVERFLOW = 'into';

    /**
     * Returns from an interrupt procedure by popping IP, CS, and flags from stack, in that order.
     * @var string
     */
    public const INTERRUPT_RETURN = 'iret';

    public const JUMP_IF_REGISTER_CX_IS_ZERO = 'jcxz';

    /**
     * Two's Complement Negation
     * @var string
     */
    public const COMPLEMENT_NEGATIVE = 'neg';

    /**
     * No Operation
     * @var string
     */
    public const NO_OPERATION = 'nop';
    
    public const OUTPUT_DATA_TO_PORT = 'out';

    public const OUTPUT_STRING_TO_PORT = 'outs';

    /**
     * Pop All Registers off Stack
     * @var string
     */
    public const POP_ALL_STACK = 'popa';

    /**
     * Pop Flags off Stack
     * @var string
     */
    public const POP_FLAG_STACK = 'popf';

    /**
     * Rotate Through Carry LEFT
     * @var string
     */
    public const ROTATE_CARRY_LEFT = 'rcl';

    /**
     * Rotate Through Carry RIGHT
     * @var string
     */
    public const ROTATE_CARRY_RIGHT = 'rcr';

    public const REPEAT_STRING_OPERATION = 'rep';

    public const REPEAT_EQUAL = 'repe';

    public const REPEAT_NOT_EQUAL = 'repne';

    /**
     * Return From Procedure
     * @var string
     */
    public const RETURN = 'ret';

    public const SHIFT_ARITHMETIC_LEFT = 'sal';

    public const SHIFT_ARITHMETIC_RIGHT = 'sar';

    public const SUBSTRACT_WITH_BORROW = 'sbb';

    public const SCAN_STRING = 'scas';

    public const SET_CARRY = 'stc';

    /**
     * Set Direction Flag
     * @var string
     */
    public const SET_DIRECTION_FLAG = 'std';

    /**
     * Set Interrupt Flag
     * @var string
     */
    public const SET_INTERRUPT_FLAG = 'sti';

    /**
     * Store String
     * @var string
     */
    public const STORE_STRING = 'stos';

    /**
     * Test For Bit Pattern
     * @var string
     */
    public const TEST_BIT_PATTERN = 'test';

    /**
     * Exchange Register/Memory with Register
     * @var string
     */
    public const EXCHANGE_WITH_REGISTER = 'xchg';

    /**
     * Translate Byte in AL Using Table in Memory
     * @var string
     */
    public const TRANSlATE_BYTE_IN_AL = 'xlat';

    /**
     * Moves data from source to destination with zero extension. Useful for moving smaller data types into larger registers.
     * @var string
     */
    public const MOVE_WITH_ZERO_EXTENSION = 'movzx';

    /**
     * Moves data from source to destination with sign extension. Preserves the sign of the source value.
     * @var string
     */
    public const MOVE_WITH_SIGN_EXTENSION = 'movsx';

    /**
     * Decrements the count register (CX/ECX/RCX) and loops if it's not zero.
     * @var string
     */
    public const LOOP = 'loop';

    /**
     * Decrements the count register and loops if it's not zero and the Zero Flag is not set.
     * @var string
     */
    public const LOOP_IF_NOT_EQUAL = 'loopne';

    /**
     * Decrements the count register and loops if it's not zero and the Zero Flag is set.
     * @var string
     */
    public const LOOP_IF_EQUAL = 'loopz';

    /**
     * Alias for LOOPNE. Loops while not zero.
     * @var string
     */
    public const LOOP_IF_NOT_ZERO = 'loopnz';

    /**
     * Pushes the EFLAGS register onto the stack.
     * @var string
     */
    public const PUSH_FLAGS = 'pushfd';

    /**
     * Pops the top of the stack into the EFLAGS register.
     * @var string
     */
    public const POP_FLAGS = 'popfd';

    /**
     * Creates a stack frame for a procedure. Allocates space and saves base pointer.
     * @var string
     */
    public const ENTER_STACK_FRAME = 'enter';

    /**
     * Dismantles the stack frame created by ENTER. Restores base pointer and stack pointer.
     * @var string
     */
    public const LEAVE_STACK_FRAME = 'leave';

    /**
     * Jumps if ECX is zero. Often used in loop termination.
     * @var string
     */
    public const JUMP_IF_ECX_ZERO = 'jecxz';

    /**
     * Jumps if not above or equal (i.e., if Carry Flag is set).
     * @var string
     */
    public const JUMP_IF_NOT_ABOVE_OR_EQUAL = 'jnae';

    /**
     * Jumps if not greater or equal (i.e., if Sign Flag ≠ Overflow Flag).
     * @var string
     */
    public const JUMP_IF_NOT_GREATER_OR_EQUAL = 'jnge';

    /**
     * Jumps if not less or equal (i.e., if Zero Flag is clear and Sign Flag ≠ Overflow Flag).
     * @var string
     */
    public const JUMP_IF_NOT_LESS_OR_EQUAL = 'jnle';

    /**
     * Performs a double-precision shift left. Shifts bits in destination and fills with bits from source.
     * @var string
     */
    public const SHIFT_LEFT_DOUBLE = 'shld';

    /**
     * Moves a doubleword from source to destination. Often used with string operations.
     * @var string
     */
    public const MOVE_STRING_DOUBLEWORD = 'movsd';

    /**
     * Compares doublewords in memory pointed by ESI and EDI. Updates flags.
     * @var string
     */
    public const COMPARE_STRING_DOUBLEWORD = 'cmpsd';

    /**
     * Compares words in memory pointed by SI and DI. Updates flags.
     * @var string
     */
    public const COMPARE_STRING_WORD = 'cmpsw';

    /**
     * Compares accumulator (EAX) with memory byte pointed by EDI. Updates flags.
     * @var string
     */
    public const SCAN_STRING_DWORD = 'scasd';

    /**
     * Compares accumulator (AL) with memory byte pointed by EDI. Updates flags.
     * @var string
     */
    public const SCAN_STRING_BYTE = 'scasb';

    /**
     * Stores byte from AL into memory pointed by EDI. Often used in string operations.
     * @var string
     */
    public const STORE_STRING_BYTE = 'stosb';

    /**
     * Loads byte from memory pointed by ESI into AL. Often used in string operations.
     * @var string
     */
    public const LOAD_STRING_BYTE = 'lodsb';
}