<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

abstract class ComputerArchitecture
{
    /**
     * The CPU or processor is the brain of the computer. It performs all the communications and executes instructions.
     * @var string
     */
    public const CENTRAL_PROCESS_UNIT = 'Central processing unit';

    /**
     * Hanles arithmetic and logical operations
     * @var string
     */
    public const ARITHMETIC_LOGIC_UNIT = 'Arithmetic Logic Unit';

    /**
     * Directs the operations of a processor
     * @var string
     */
    public const CONTROL_UNIT = 'Control Unit';

    /**
     * Carries information on where the data is to be written or loaded from
     * 
     * The CPU places the memory address it wants to access on the address bus (target memory address). This bus carries the address of the memory location where the data is to be read from or written to.The CPU places the memory address it wants to access on the address bus (target memory address). This bus carries the address of the memory location where the data is to be read from or written to.
     * 
     * @var string
     */
    public const ADDRESS_BUS = 'Address Bus';

    /**
     * Carries `actual data`, that is to be written or loaded.
     * 
     * Carries the actual data that is to be written to the memory or read from the memory.
     * 
     * Read operation -> The value on the target memory address is placed on the data bus which is then read by CPU.
     * 
     * Write operation -> The value that is to be written on the target memory address is placed on data bus.
     * 
     * @var string
     */
    public const DATA_BUS = 'Data Bus';

    /**
     * Carries signals related to control operations (example: read or write signals)
     * 
     * The CPU sends a `control signal` over the control bus to indicate whether it wants to perform a read or write operation.
     * 
     * Read -> The control bus will instruct the memory bus to `send` data back to the CPU.
     * 
     * Write -> The control bus will instruct the memory bus to `accept` data from the CPU.
     * @var string
     */
    public const CONTROL_BUS = 'Control Bus';

    /**
     * Small, fast, expensive storage locations in the CPU used for immediate data access.
     * @var string
     */
    public const REGISTER = 'Register';

    public const ENDIANNESS = 'Endianness';

    /**
     * The least significatnt byte (the "small end") is stored at the smallest memory address.
     *
     * The most significant byte (the "big end") is stored at the highest memory address.
     * 
     * @var string
     */
    public const LITTLE_ENDIAN = 'Little Endian';

    /**
     * The least significant byte (the "small end") is stored at the `highest address`.
     * 
     * The most significant byte (the "big end") is stored at the `smallest memory address`.
     * 
     * @var string
     */
    public const BIG_ENDIAN = 'Big Endian';

    /**
     * Many network protocols use big-endian format (AKA Network Byte Order) to ensure consistency across different systems
     * 
     * @var string
     */
    public const NETWORK_BYTE_ORDER = 'Network Byte Order';

    public const CACHE_MEMORY = 'Cache Memory';

    public const RANDOM_ACCESS_MEMORY = 'Random Access Memory';

    /**
     * Instruction Set Architecture (ISA) refers to the part of a computer architecture that defines the set of instructions that a processor can execute. It serves as an interface between software and hardware, enabling the development of programs that can interact with the CPU.
     * 
     * @var string
     */
    public const INSTRUCTION_SET_ARCHITECTURE = 'Instruction Set Architecture';

    /**
     * Moves data between registers, memory, and I/O devices. (eg: LOAD, STORE)
     * @var string
     */
    public const DATA_MOVEMENT_INSTRUCTIONS = 'Data Movement Instructions';

    /**
     * Performs mathematical operations. (eg: ADD, SUB, MUL, DIV)
     * @var string
     */
    public const ARITHMETIC_INSTRUCTIONS = 'Arithmetic Instructions';

    /**
     * Executes bitwise operations. (eg: AND, OR, NOT, XOR)
     * @var string
     */
    public const LOGICAL_INSTRUCTIONS = 'Logical Instructions';

    /**
     * Alter the flow of executions of instructions. (eg: JUMP, CALL, RETURN)
     * @var string
     */
    public const CONTROL_FLOW_INSTRUCTIONS = 'Control Flow Instructions';

    /**
     * Manages data transfer to and from peripheral devices.
     * @var string
     */
    public const INPUT_OUTPUT_INSTRUCTIONS = 'Input/Output Instructions';

    /**
     * The operand is specified directly in the instruction.
     * @var string
     */
    public const IMMEDIATE_ADDRESSING = 'Immediate Addressing';

    /**
     * The address of the operand is given directly.
     * @var string
     */
    public const DIRECT_ADDRESSING = 'Direct Addressing';

    /**
     * The address of the operand is speicifed by a register or memory location.
     * @var string
     */
    public const INDIRECT_ADDRESSING = 'Indirect Addressing';

    /**
     * The operand's address is generated by adding a constant value to a register value.
     * @var string
     */
    public const INDEXED_ADDRESSING = 'Indexed Addressing';

    /**
     * The operand is located in a specific register.
     * @var string
     */
    public const REGISTER_ADDRESSING = 'Register Addressing';

    /**
     * Used for various data operations.(eg: R0, R1, R2, etc)
     * @var string
     */
    public const GENERAL_PURPSE_REGISTERS = 'General-Purpse Registers';

    /**
     * Used for specific functions.(eg: RSP, RBP, etc)
     * @var string
     */
    public const SPECIAL_PURPSE_REGISTERS = 'Special-Purpse Registers';

    /**
     * Specifies the operation that is to be performed.
     * @var string
     */
    public const OPCODE = 'Opcode';

    /**
     * Specifies the data to be processed, which may include registers or immediate values or memory addresses.
     * @var string
     */
    public const OPERANDS = 'Operands';

    /**
     * Indicates how to interpret the operands.
     * @var string
     */
    public const ADDRESSING_MODE = 'Addressing Mode';

    /**
     * The signals generated by the control unit (CU) of the CPU to manage the execution of the instructions, including reading from or writing to memory, activating the arithmetic logic unit(ALU), and controlling flow.
     * @var string
     */
    public const CONTROL_SIGNALS = 'Control Signals';

    /**
     * Contains a large number of instructions, including complex instructions that can perform multiple operations in a single instruction.
     * 
     * example: x86 architecture (Intel, AMD)
     * 
     * @var string
     */
    public const COMPLEX_INSTRUCTION_SET_ARCHITECTURE = 'Complex Instruction Set Architecture';

    /**
     * Contains a smaller number of instructions, each designed to execute in a single cycle.
     * 
     * Emphasizes a load/store architecture where memory access is limited to specific load and store instructions.
     *
     * example: ARM architecture, MIPS architecture
     * 
     * @var string
     */
    public const REDUCED_INSTRUCTION_SET_ARCHITECTURE = 'Reduced Instruction Set Architecture';

    /**
     * Uses long instruction words that bundles multiple operations together.
     * 
     * The compiler is responsible for scheduling instructions to be executed together, in parallel.
     *
     * example: Itanium architecture
     * @var string
     */
    public const VERY_LONG_INSTRUCTION_WORD = 'Very Long Instruction Word';

    /**
     * Designed to exploit instruction-level parallelism by explicitly specifying parallel operation in the instructin set.
     * 
     * example: Intel's Itanium
     * 
     * @var string
     */
    public const EXPLICITLY_PARALLEL_INSTRUCTION_COMPUTING = 'Explicitly Parallel Instruction Computing';

    /**
     * Interrupts are signal sent by hardwares or softwares to indicate that an event needs immediate attention. The CPU temporarily pauses it's current task to handle the interrupt and then resumes the task afterward.
     * @var string
     */
    public const INTERRUPTS = 'Interrupts';

    /**
     * Generated by external devices (like a keyboard, mouse or network card) to signal the CPU when attention is needed.
     * 
     * For example, pressing a key on the keyboard sends an interrupt to the CPU, allowing it to process a keystroke.
     * 
     * Common uses are:
     * 
     * I/O operations, Timers, System events
     * 
     * @var string
     */
    public const HARDWARE_INTERRUPTS = 'Hardware Interrupts';

    /**
     * Triggered by executing a special instruction in the program, often for system calls or special functions.
     * 
     * An example is a system call to request service from the OS, such as reading or writing files.
     * @var string
     */
    public const SOFTWARE_INTERRUPTS = 'Software Interrupts';

    /**
     * The CPU jumps to a special function called an interrupt service routine (ISR) or interrupt handler, which is responsible for handlingthe interrupt.
     * @var string
     */
    public const INTERRUPT_SERVICE_ROUTINE = 'Interrupt service routine';

    /**
     * Can happen at any time during the execution of a program.
     * @var string
     */
    public const ASYNCHRONOUS = 'Asynchronous';

    /**
     * Interrupts can have different priority levels, meaning higher-priority interrupts can interrupt lower-priority ones.
     * @var string
     */
    public const PRIORITIZED = 'Prioritized';

    /**
     * Generated intentionally by software, typically through a system call or other instruction designed to invoke the OS.
     * 
     * For example: A program might use a trap to request a service from the OS, like memroy allocation or file handling.
     * @var string
     */
    public const TRAPS_EXCEPTION = 'Traps Exception';

    /**
     * Occur due to errors in instruction exection, such as accessing an `invalid memory address(segmentation fault)` or `dividing by zero`.
     * 
     * The CPU attempts to correct the issue. if it can, the program may resume. if not, the program is terminated, often with an error message.
     * @var string
     */
    public const FAULTS_EXCEPTION = 'Faults Exception';

    /**
     * Generated by severe hardware or system-level errors, such as `memory parity errors` or `hardware malfunctions`.
     * 
     * Typically unrecoverable, leading to program termination or system failure.
     * 
     * @var string
     */
    public const ABORTS_EXCEPTION = 'Aborts Exception';
    /**
     * The CPU jumps to an exception handler, which deals with the specific exception.
     * @var string
     */
    public const EXCEPTION_HANDLER = 'Exception handler';

    /**
     * The handler may try to resolve the issue.
     * @var string
     */
    public const HANDLE_THE_EXCEPTION = 'Handle the exception';

    /**
     * Exceptions are directly tied to the execution of specific instructions, meaning they occurr in response to particular operations.
     * 
     * @var string
     */
    public const SYNCHRONOUS = 'Synchronous';

    /**
     * Virtual Memory is a memory management technique used by modern operating systems to create the illusion of a large, continuous memory space for each process, regardless of the actual size of the physical RAM.
     * 
     * It allows each process to have it's own isolated memory address space, preventing it from accessing another process's memory. This isloation ensures process security and memory management efficiency.
     * 
     * Key Benefits:
     * 
     * 1.`Isolation`: Each process gets its own virtual address space, so they don't interfere with each other.
     * 
     * 2.`Larger Address Space`: Processes can use more memory than is physically available by using disk storage (paging)
     * 
     * 3.`Efficient Use Of Memory`: Virtual memory can allocate memory dynamically and only load the parts of the program that are in use.
     * 
     * @var string
     */
    public const VIRTUAL_MEMORY = 'Virtual Memory';

    /**
     * Stores the `executable instructions` of the program (read-only)
     * 
     * Contains instructions that are mapped as read-only to prevent accidental or malicious modifications.
     * 
     * Example: The compiled machine code for functions.
     * 
     * @var string
     */
    public const CODE_SEGMENT = 'Code Segment';

    /**
     * Stores global and static variables that are intialized in the program.
     * 
     * Divided into initialized and uninialized parts (explained next).
     * 
     * @var string
     */
    public const DATA_SEGMENT = 'Data Segment';

    /**
     * Holds uninitialized global and static variables.
     * 
     * The operating system initializes this memory to zero.
     *
     * Size increases when more variables are declared.
     * 
     * @var string
     */
    public const BSS_SEGMENT = 'BSS Segment';

    /**
     * Used for dynamic memory allocation. Grows upwards in virtual memory as new memory is requested.
     * 
     * Explicitly managed by the programmer via system calls or library functions (malloc in C).
     *
     * @var string
     */
    public const HEAP_SEGMENT = 'Heap Segment';

    /**
     * Used to store local variables, function arguments, return addresses, and control information.
     * 
     * Grows downwards in memory (from higher address to lower address).
     * 
     * Managed automatically by the CPU using push and pop operations.
     * 
     * @var string
     */
    public const STACK_SEGMENT = 'Stack Segment';

    /**
     *  The last value pushed onto the stack is the first one to be removed (popped off).
     * 
     * @var string
     */
    public const LAST_IN_FIRST_OUT = 'Last In, First Out';

    /**
     * The CPU manages the stack as you make function calls and return from them.
     * 
     * @var string
     */
    public const AUTOMATIC_MANAGEMENT = 'Automatic Management';

    /**
     * Function arguments, return addresses, and local variables are stored onto the stack.
     * 
     * @var string
     */
    public const LOCAL_STORAGE = 'Local Storage';

    /**
     * Each function call creates a `new stack frame` that contains the function's local variables and return address.
     * 
     * @var string
     */
    public const STACK_FRAMES = 'Stack Frames';
    
}