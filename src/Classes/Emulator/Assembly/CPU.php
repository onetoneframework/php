<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Emulator\Assembly;

use Clover\Enumeration\Assembly\EmulatorOpcode;
use Clover\Enumeration\Bitwise\MaskingBit;
use RuntimeException;
use function sprintf;
use function is_int;

/**
 * CPU class implements the registers, flags, instruction decode, and run loop.
 */
class CPU
{
    private Memory $mem;
    private InterruptController $intc;
    private array $callbacks = [];

    public array $r = [0, 0, 0, 0, 0, 0, 0, 0];
    private int $programCounter = 0x0000;
    private int $stackPointer = MaskingBit::UNSIGNED_WORD;
    private bool $zero = false;
    private bool $carry = false;
    private bool $sign = false;
    private bool $overflow = false;
    private bool $halted = false;
    private int $cycleCount = 0;

    public function __construct(Memory $mem, InterruptController $intc)
    {
        $this->mem = $mem;
        $this->intc = $intc;
    }

    /**
     * Load machine code bytes into memory at a given address.
     */
    public function load(int $addr, array $bytes): void
    {
        foreach ($bytes as $key => $b) {
            if (is_int($key)) {
                $this->mem->writeByte($addr + $key, $b);
            }
        }
        if (isset($bytes['start'])) {
            $this->programCounter = $bytes['start'];
        }
    }

    public function reset(): void
    {
        $this->r = [0, 0, 0, 0, 0, 0, 0, 0];
        $this->programCounter = 0x0000;
        $this->stackPointer = MaskingBit::UNSIGNED_WORD;
        $this->zero = false;
        $this->carry = false;
        $this->sign = false;
        $this->overflow = false;
        $this->halted = false;
        $this->cycleCount = 0;
    }

    public function run(int $maxCycles = 1000): bool
    {
        $cycles = 0;
        while (!$this->halted && $cycles++ < $maxCycles) {
            $this->cycleCount++;
            $this->processInterrupts();
            $this->executeOne();
        }
        return !$this->halted;
    }

    public function step(): bool
    {
        if ($this->halted) {
            return false;
        }
        $this->cycleCount++;
        $this->processInterrupts();
        $this->executeOne();
        return !$this->halted;
    }

    public function getState(): array
    {
        return [
            'registers' => $this->r,
            'pc' => $this->programCounter,
            'sp' => $this->stackPointer,
            'flags' => [
                'zero' => $this->zero,
                'carry' => $this->carry,
                'sign' => $this->sign,
                'overflow' => $this->overflow,
            ],
            'halted' => $this->halted,
            'cycles' => $this->cycleCount,
        ];
    }

    public function getProgramCounter(): int
    {
        return $this->programCounter;
    }

    public function getStackPointer(): int
    {
        return $this->stackPointer;
    }

    public function isHalted(): bool
    {
        return $this->halted;
    }

    public function getCycleCount(): int
    {
        return $this->cycleCount;
    }

    public function setCallback(int $num, callable $fn): void
    {
        $this->callbacks[$num & MaskingBit::BYTE] = $fn;
    }

    public function dumpStack(int $count = 16): string
    {
        return $this->mem->dumpStack($this->stackPointer, $count);
    }

    /**
     * Return a summary of heap allocations.
     */
    public function dumpHeap(): string
    {
        return $this->mem->dumpHeap();
    }

    public function dumpRegisters(): string
    {
        $out = '';
        for ($i = 0; $i < 8; $i++) {
            $out .= sprintf("R%d=0x%02X ", $i, $this->r[$i]);
        }
        $out .= sprintf("PC=0x%04X SP=0x%04X [%s%s%s%s]", $this->programCounter, $this->stackPointer, $this->zero ? 'Z' : '.', $this->carry ? 'C' : '.', $this->sign ? 'S' : '.', $this->overflow ? 'O' : '.');
        return $out;
    }

    private function fetchByte(): int
    {
        $val = $this->mem->readByte($this->programCounter);
        $this->programCounter = ($this->programCounter + 1) & MaskingBit::WORD;
        return $val;
    }

    private function fetchWord(): int
    {
        $val = $this->mem->readWord($this->programCounter);
        $this->programCounter = ($this->programCounter + 2) & MaskingBit::WORD;
        return $val;
    }

    private function fetchRegIndex(): int
    {
        return $this->fetchByte() & MaskingBit::BIT_3;
    }

    private function alu(int $a, int $b, bool $subtract = false): int
    {
        $result = $subtract ? ($a - $b) : ($a + $b);
        $masked = $result & MaskingBit::BYTE;
        $this->zero = ($masked === 0);
        $this->sign = (bool) (($masked >> 7) & 1);
        $this->carry = $subtract ? ($a < $b) : ($result > MaskingBit::BYTE);

        $signA = ($a >> 7) & 1;
        $signB = ($b >> 7) & 1;
        $signR = ($masked >> 7) & 1;
        $this->overflow = $subtract
            ? (($signA !== $signB) && ($signR !== $signA))
            : (($signA === $signB) && ($signR !== $signA));

        return $masked;
    }

    private function logic(int $result): int
    {
        $masked = $result & MaskingBit::BYTE;
        $this->zero = ($masked === 0);
        $this->sign = (bool) (($masked >> 7) & 1);
        $this->carry = false;
        $this->overflow = false;
        return $masked;
    }

    private function processInterrupts(): void
    {
        $num = $this->intc->next();
        if ($num !== null) {
            $this->handleInterrupt($num);
        }
    }

    private function handleInterrupt(int $num): void
    {
        $flags = $this->packFlags();
        $this->pushByte($flags);
        $this->pushWord($this->programCounter);
        $this->intc->disable();
        $this->programCounter = $this->intc->getVector($num);
    }

    private function packFlags(): int
    {
        return ($this->zero ? 1 : 0)
            | (($this->carry ? 1 : 0) << 1)
            | (($this->sign ? 1 : 0) << 2)
            | (($this->overflow ? 1 : 0) << 3);
    }

    private function unpackFlags(int $flags): void
    {
        $this->zero = (bool) ($flags & 1);
        $this->carry = (bool) (($flags >> 1) & 1);
        $this->sign = (bool) (($flags >> 2) & 1);
        $this->overflow = (bool) (($flags >> 3) & 1);
    }

    private function regPairAddress(int $regIndex): int
    {
        $lo = $this->r[$regIndex];
        $hi = $this->r[($regIndex + 1) & MaskingBit::BIT_3];
        return (($hi << 8) | $lo) & MaskingBit::WORD;
    }

    private function conditionalJump(bool $condition): void
    {
        $addr = $this->fetchWord();
        if ($condition) {
            $this->programCounter = $addr;
        }
    }

    private function pushByte(int $val): void
    {
        $this->mem->writeByte($this->stackPointer, $val);
        $this->stackPointer = ($this->stackPointer - 1) & MaskingBit::WORD;
    }

    private function popByte(): int
    {
        $this->stackPointer = ($this->stackPointer + 1) & MaskingBit::WORD;
        return $this->mem->readByte($this->stackPointer);
    }

    private function pushWord(int $val): void
    {
        $this->pushByte(($val >> 8) & MaskingBit::BYTE);
        $this->pushByte($val & MaskingBit::BYTE);
    }

    private function popWord(): int
    {
        $lo = $this->popByte();
        $hi = $this->popByte();
        return ($hi << 8) | $lo;
    }

    private function executeOne(): void
    {
        $opcode = $this->fetchByte();

        switch ($opcode) {
            case EmulatorOpcode::NOP:
                break;

            case EmulatorOpcode::MOV:
                $r = $this->fetchRegIndex();
                $v = $this->fetchByte();
                $this->r[$r] = $v;
                $this->zero = ($v === 0);
                $this->sign = (bool) (($v >> 7) & 1);
                break;

            case EmulatorOpcode::LOAD:
                $r = $this->fetchRegIndex();
                $addr = $this->fetchWord();
                $v = $this->mem->readByte($addr);
                $this->r[$r] = $v;
                $this->zero = ($v === 0);
                $this->sign = (bool) (($v >> 7) & 1);
                break;

            case EmulatorOpcode::STORE:
                $addr = $this->fetchWord();
                $r = $this->fetchRegIndex();
                $this->mem->writeByte($addr, $this->r[$r]);
                break;

            case EmulatorOpcode::ADD:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->alu($this->r[$r], $this->r[$m]);
                break;

            case EmulatorOpcode::SUB:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->alu($this->r[$r], $this->r[$m], true);
                break;

            case EmulatorOpcode::JMP:
                $this->programCounter = $this->fetchWord();
                break;

            case EmulatorOpcode::JZ:
                $this->conditionalJump($this->zero);
                break;

            case EmulatorOpcode::JNZ:
                $this->conditionalJump(!$this->zero);
                break;

            case EmulatorOpcode::CALL:
                $addr = $this->fetchWord();
                $this->pushWord($this->programCounter);
                $this->programCounter = $addr;
                break;

            case EmulatorOpcode::RET:
                $this->programCounter = $this->popWord();
                break;

            case EmulatorOpcode::PUSH:
                $r = $this->fetchRegIndex();
                $this->pushByte($this->r[$r]);
                break;

            case EmulatorOpcode::POP:
                $r = $this->fetchRegIndex();
                $this->r[$r] = $this->popByte();
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                break;

            case EmulatorOpcode::INT:
                $num = $this->fetchByte();
                $this->intc->request($num);
                break;

            case EmulatorOpcode::HLT:
                $this->halted = true;
                break;

            case EmulatorOpcode::CMP:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->alu($this->r[$r], $this->r[$m], true);
                break;

            case EmulatorOpcode::JE:
                $this->conditionalJump($this->zero);
                break;

            case EmulatorOpcode::JNE:
                $this->conditionalJump(!$this->zero);
                break;

            case EmulatorOpcode::CALLBACK:
                $num = $this->fetchByte();
                if (isset($this->callbacks[$num])) {
                    ($this->callbacks[$num])($this, $this->mem);
                }
                break;

            case EmulatorOpcode::AND:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->logic($this->r[$r] & $this->r[$m]);
                break;

            case EmulatorOpcode::OR:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->logic($this->r[$r] | $this->r[$m]);
                break;

            case EmulatorOpcode::XOR:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->logic($this->r[$r] ^ $this->r[$m]);
                break;

            case EmulatorOpcode::NOT:
                $r = $this->fetchRegIndex();
                $this->r[$r] = $this->logic(~$this->r[$r]);
                break;

            case EmulatorOpcode::SHL:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $shift = $this->r[$m] & MaskingBit::BIT_3;
                $val = $this->r[$r];
                $this->carry = $shift > 0 && (bool) (($val >> (8 - $shift)) & 1);
                $this->r[$r] = ($val << $shift) & MaskingBit::BYTE;
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                break;

            case EmulatorOpcode::SHR:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $shift = $this->r[$m] & MaskingBit::BIT_3;
                $val = $this->r[$r];
                $this->carry = $shift > 0 && (bool) (($val >> ($shift - 1)) & 1);
                $this->r[$r] = ($val >> $shift) & MaskingBit::BYTE;
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                break;

            case EmulatorOpcode::INC:
                $r = $this->fetchRegIndex();
                $this->r[$r] = $this->alu($this->r[$r], 1);
                break;

            case EmulatorOpcode::DEC:
                $r = $this->fetchRegIndex();
                $this->r[$r] = $this->alu($this->r[$r], 1, true);
                break;

            case EmulatorOpcode::MOV_REG:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $this->r[$r] = $this->r[$m];
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                break;

            case EmulatorOpcode::LOAD_REG:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $addr = $this->regPairAddress($m);
                $v = $this->mem->readByte($addr);
                $this->r[$r] = $v;
                $this->zero = ($v === 0);
                $this->sign = (bool) (($v >> 7) & 1);
                break;

            case EmulatorOpcode::STORE_REG:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $addr = $this->regPairAddress($r);
                $this->mem->writeByte($addr, $this->r[$m]);
                break;

            case EmulatorOpcode::JC:
                $this->conditionalJump($this->carry);
                break;

            case EmulatorOpcode::JNC:
                $this->conditionalJump(!$this->carry);
                break;

            case EmulatorOpcode::IRET:
                $this->programCounter = $this->popWord();
                $this->unpackFlags($this->popByte());
                $this->intc->enable();
                break;

            case EmulatorOpcode::MUL:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $product = $this->r[$r] * $this->r[$m];
                $this->carry = $product > MaskingBit::BYTE;
                $this->r[$r] = $product & MaskingBit::BYTE;
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                $this->overflow = $this->carry;
                break;

            case EmulatorOpcode::ADD_IMM:
                $r = $this->fetchRegIndex();
                $imm = $this->fetchByte();
                $this->r[$r] = $this->alu($this->r[$r], $imm);
                break;

            case EmulatorOpcode::SUB_IMM:
                $r = $this->fetchRegIndex();
                $imm = $this->fetchByte();
                $this->r[$r] = $this->alu($this->r[$r], $imm, true);
                break;

            case EmulatorOpcode::CMP_IMM:
                $r = $this->fetchRegIndex();
                $imm = $this->fetchByte();
                $this->alu($this->r[$r], $imm, true);
                break;

            case EmulatorOpcode::XCHG:
                $r = $this->fetchRegIndex();
                $m = $this->fetchRegIndex();
                $tmp = $this->r[$r];
                $this->r[$r] = $this->r[$m];
                $this->r[$m] = $tmp;
                break;

            case EmulatorOpcode::NEG:
                $r = $this->fetchRegIndex();
                $this->carry = ($this->r[$r] !== 0);
                $this->r[$r] = $this->alu(0, $this->r[$r], true);
                break;

            case EmulatorOpcode::CLI:
                $this->intc->disable();
                break;

            case EmulatorOpcode::STI:
                $this->intc->enable();
                break;

            case EmulatorOpcode::IN:
                $r = $this->fetchRegIndex();
                $port = $this->fetchByte();
                $this->r[$r] = $this->mem->readPort($port);
                $this->zero = ($this->r[$r] === 0);
                $this->sign = (bool) (($this->r[$r] >> 7) & 1);
                break;

            case EmulatorOpcode::OUT:
                $port = $this->fetchByte();
                $r = $this->fetchRegIndex();
                $this->mem->writePort($port, $this->r[$r]);
                break;

            case EmulatorOpcode::JS:
                $this->conditionalJump($this->sign);
                break;

            case EmulatorOpcode::JNS:
                $this->conditionalJump(!$this->sign);
                break;

            case EmulatorOpcode::JO:
                $this->conditionalJump($this->overflow);
                break;

            case EmulatorOpcode::JNO:
                $this->conditionalJump(!$this->overflow);
                break;

            default:
                throw new RuntimeException(sprintf("Unknown opcode 0x%02X at 0x%04X", $opcode, ($this->programCounter - 1) & MaskingBit::WORD));
        }
    }
}