<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Emulator;

use PHPUnit\Framework\TestCase;
use Clover\Classes\Emulator\Assembly\CPU;
use Clover\Classes\Emulator\Assembly\Memory;
use Clover\Classes\Emulator\Assembly\InterruptController;
use Clover\Enumeration\Assembly\EmulatorOpcode;
use RuntimeException;

class CPUTest extends TestCase
{
    private CPU $cpu;
    private Memory $mem;
    private InterruptController $intc;

    protected function setUp(): void
    {
        ini_set('memory_limit', '1256M');

        $this->mem = new Memory(0x8000);
        $this->intc = new InterruptController();
        $this->cpu = new CPU($this->mem, $this->intc);
    }

    private function execute(array $program, int $startAddr = 0x0000): void
    {
        $this->cpu->load($startAddr, $program);
        $this->cpu->run(500);
    }

    // ── MOV / MOV_REG ────────────────────────────────────────────

    public function testMovImmediate(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x42,
            EmulatorOpcode::MOV, 0x01, 0xFF,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x42, $this->cpu->r[0]);
        $this->assertSame(0xFF, $this->cpu->r[1]);
    }

    public function testMovReg(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xAB,
            EmulatorOpcode::MOV_REG, 0x03, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xAB, $this->cpu->r[3]);
    }

    // ── ADD / SUB / MUL ─────────────────────────────────────────

    public function testAdd(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x10,
            EmulatorOpcode::MOV, 0x01, 0x20,
            EmulatorOpcode::ADD, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x30, $this->cpu->r[0]);
    }

    public function testAddCarry(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::MOV, 0x01, 0x02,
            EmulatorOpcode::ADD, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['carry']);
    }

    public function testSub(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x30,
            EmulatorOpcode::MOV, 0x01, 0x10,
            EmulatorOpcode::SUB, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x20, $this->cpu->r[0]);
    }

    public function testSubBorrow(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x05,
            EmulatorOpcode::MOV, 0x01, 0x10,
            EmulatorOpcode::SUB, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xF5, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['carry']);
    }

    public function testMul(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x07,
            EmulatorOpcode::MOV, 0x01, 0x06,
            EmulatorOpcode::MUL, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(42, $this->cpu->r[0]);
    }

    public function testMulOverflow(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x80,
            EmulatorOpcode::MOV, 0x01, 0x03,
            EmulatorOpcode::MUL, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame((0x80 * 0x03) & 0xFF, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['carry']);
    }

    // ── ADD_IMM / SUB_IMM / CMP_IMM ────────────────────────────

    public function testAddImm(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x02, 0x10,
            EmulatorOpcode::ADD_IMM, 0x02, 0x05,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x15, $this->cpu->r[2]);
    }

    public function testSubImm(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x02, 0x20,
            EmulatorOpcode::SUB_IMM, 0x02, 0x05,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x1B, $this->cpu->r[2]);
    }

    public function testCmpImm(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x0A,
            EmulatorOpcode::CMP_IMM, 0x00, 0x0A,
            EmulatorOpcode::HLT,
        ]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['zero']);
        $this->assertSame(0x0A, $this->cpu->r[0]);
    }

    // ── INC / DEC ───────────────────────────────────────────────

    public function testInc(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x09,
            EmulatorOpcode::INC, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x0A, $this->cpu->r[0]);
    }

    public function testIncWrap(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::INC, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x00, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['zero']);
        $this->assertTrue($state['flags']['carry']);
    }

    public function testDec(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x0A,
            EmulatorOpcode::DEC, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x09, $this->cpu->r[0]);
    }

    public function testDecToZero(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x01,
            EmulatorOpcode::DEC, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x00, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['zero']);
    }

    // ── NEG / XCHG ─────────────────────────────────────────────

    public function testNeg(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x01,
            EmulatorOpcode::NEG, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xFF, $this->cpu->r[0]);
    }

    public function testXchg(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xAA,
            EmulatorOpcode::MOV, 0x01, 0xBB,
            EmulatorOpcode::XCHG, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xBB, $this->cpu->r[0]);
        $this->assertSame(0xAA, $this->cpu->r[1]);
    }

    // ── AND / OR / XOR / NOT ────────────────────────────────────

    public function testAnd(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0b11001100,
            EmulatorOpcode::MOV, 0x01, 0b10101010,
            EmulatorOpcode::AND, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0b10001000, $this->cpu->r[0]);
    }

    public function testOr(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0b11000000,
            EmulatorOpcode::MOV, 0x01, 0b00001111,
            EmulatorOpcode::OR, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0b11001111, $this->cpu->r[0]);
    }

    public function testXor(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::MOV, 0x01, 0xFF,
            EmulatorOpcode::XOR, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x00, $this->cpu->r[0]);
        $state = $this->cpu->getState();
        $this->assertTrue($state['flags']['zero']);
    }

    public function testNot(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0b10101010,
            EmulatorOpcode::NOT, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0b01010101, $this->cpu->r[0]);
    }

    // ── SHL / SHR ───────────────────────────────────────────────

    public function testShl(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0b00000011,
            EmulatorOpcode::MOV, 0x01, 0x04,
            EmulatorOpcode::SHL, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0b00110000, $this->cpu->r[0]);
    }

    public function testShr(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0b11000000,
            EmulatorOpcode::MOV, 0x01, 0x04,
            EmulatorOpcode::SHR, 0x00, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0b00001100, $this->cpu->r[0]);
    }

    // ── LOAD / STORE ────────────────────────────────────────────

    public function testLoadStore(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x77,
            EmulatorOpcode::STORE, 0x00, 0x50, 0x00,
            EmulatorOpcode::MOV, 0x00, 0x00,
            EmulatorOpcode::LOAD, 0x01, 0x00, 0x50,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x77, $this->cpu->r[1]);
    }

    public function testLoadRegIndirect(): void
    {
        $this->mem->writeByte(0x0300, 0xEE);
        $this->execute([
            EmulatorOpcode::MOV, 0x02, 0x00,
            EmulatorOpcode::MOV, 0x03, 0x03,
            EmulatorOpcode::LOAD_REG, 0x00, 0x02,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xEE, $this->cpu->r[0]);
    }

    public function testStoreRegIndirect(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x04, 0x00,
            EmulatorOpcode::MOV, 0x05, 0x04,
            EmulatorOpcode::MOV, 0x00, 0xDD,
            EmulatorOpcode::STORE_REG, 0x04, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xDD, $this->mem->readByte(0x0400));
    }

    // ── JMP / Conditional Jumps ────────────────────────────────

    public function testJmp(): void
    {
        $this->execute([
            EmulatorOpcode::JMP, 0x07, 0x00,
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x00, 0x42,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x42, $this->cpu->r[0]);
    }

    public function testJzTaken(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x00,
            EmulatorOpcode::JZ, 0x0A, 0x00,
            EmulatorOpcode::MOV, 0x01, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x01, 0x42,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x42, $this->cpu->r[1]);
    }

    public function testJzNotTaken(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x01,
            EmulatorOpcode::JZ, 0x0A, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[0]);
    }

    public function testJnz(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x05,
            EmulatorOpcode::JNZ, 0x0A, 0x00,
            EmulatorOpcode::MOV, 0x01, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x01, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[1]);
    }

    public function testJcTaken(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::MOV, 0x01, 0x01,
            EmulatorOpcode::ADD, 0x00, 0x01,
            EmulatorOpcode::JC, 0x10, 0x00,
            EmulatorOpcode::MOV, 0x02, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x02, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[2]);
    }

    public function testJncNotTaken(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::MOV, 0x01, 0x01,
            EmulatorOpcode::ADD, 0x00, 0x01,
            EmulatorOpcode::JNC, 0x10, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertTrue($this->cpu->isHalted());
    }

    public function testCmpJeJne(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x0A,
            EmulatorOpcode::MOV, 0x01, 0x0A,
            EmulatorOpcode::CMP, 0x00, 0x01,
            EmulatorOpcode::JE, 0x10, 0x00,
            EmulatorOpcode::MOV, 0x02, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x02, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[2]);
    }

    public function testJsSign(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x01,
            EmulatorOpcode::MOV, 0x01, 0x05,
            EmulatorOpcode::SUB, 0x00, 0x01,
            EmulatorOpcode::JS, 0x10, 0x00,
            EmulatorOpcode::MOV, 0x02, 0xFF,
            EmulatorOpcode::HLT,
            EmulatorOpcode::MOV, 0x02, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x01, $this->cpu->r[2]);
    }

    // ── PUSH / POP ──────────────────────────────────────────────

    public function testPushPop(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xAA,
            EmulatorOpcode::MOV, 0x01, 0xBB,
            EmulatorOpcode::PUSH, 0x00,
            EmulatorOpcode::PUSH, 0x01,
            EmulatorOpcode::POP, 0x02,
            EmulatorOpcode::POP, 0x03,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0xBB, $this->cpu->r[2]);
        $this->assertSame(0xAA, $this->cpu->r[3]);
    }

    // ── CALL / RET ──────────────────────────────────────────────

    public function testCallRet(): void
    {
        $this->execute([
            // 0x0000: CALL 0x0008
            EmulatorOpcode::CALL, 0x08, 0x00,
            // 0x0003: after return, R0 should be set by subroutine
            EmulatorOpcode::MOV, 0x01, 0xCC,
            EmulatorOpcode::HLT,
            // padding
            0x00,
            // 0x0008: subroutine
            EmulatorOpcode::MOV, 0x00, 0x55,
            EmulatorOpcode::RET,
        ]);
        $this->assertSame(0x55, $this->cpu->r[0]);
        $this->assertSame(0xCC, $this->cpu->r[1]);
    }

    // ── CALLBACK ────────────────────────────────────────────────

    public function testCallback(): void
    {
        $called = false;
        $this->cpu->setCallback(0x01, function (CPU $cpu, Memory $mem) use (&$called) {
            $called = true;
            $cpu->r[7] = 0x99;
        });
        $this->execute([
            EmulatorOpcode::CALLBACK, 0x01,
            EmulatorOpcode::HLT,
        ]);
        $this->assertTrue($called);
        $this->assertSame(0x99, $this->cpu->r[7]);
    }

    // ── Interrupts ──────────────────────────────────────────────

    public function testInterruptAndIret(): void
    {
        // ISR at 0x0100: set R7=0xEE, IRET
        $this->mem->writeByte(0x0100, EmulatorOpcode::MOV);
        $this->mem->writeByte(0x0101, 0x07);
        $this->mem->writeByte(0x0102, 0xEE);
        $this->mem->writeByte(0x0103, EmulatorOpcode::IRET);

        $this->intc->setVector(0x10, 0x0100);

        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0x42,
            EmulatorOpcode::INT, 0x10,
            EmulatorOpcode::MOV, 0x01, 0xAA,
            EmulatorOpcode::HLT,
        ]);

        $this->assertSame(0xEE, $this->cpu->r[7]);
        $this->assertSame(0xAA, $this->cpu->r[1]);
    }

    public function testInterruptMasking(): void
    {
        $this->intc->setMask(0x00);
        $this->intc->request(0x03);
        $this->assertFalse($this->intc->hasPending());
    }

    public function testNmiIgnoresMask(): void
    {
        $this->intc->disable();
        $this->intc->request(2);
        $this->assertTrue($this->intc->hasPending());
        $this->assertSame(2, $this->intc->next());
    }

    public function testCliSti(): void
    {
        $this->mem->writeByte(0x0100, EmulatorOpcode::MOV);
        $this->mem->writeByte(0x0101, 0x07);
        $this->mem->writeByte(0x0102, 0xBB);
        $this->mem->writeByte(0x0103, EmulatorOpcode::IRET);

        $this->intc->setVector(0x05, 0x0100);

        $this->execute([
            EmulatorOpcode::CLI,
            EmulatorOpcode::INT, 0x05,
            EmulatorOpcode::MOV, 0x00, 0x11,
            EmulatorOpcode::STI,
            EmulatorOpcode::NOP,
            EmulatorOpcode::HLT,
        ]);

        $this->assertSame(0xBB, $this->cpu->r[7]);
    }

    // ── I/O Ports ───────────────────────────────────────────────

    public function testInOut(): void
    {
        $portValue = 0;
        $this->mem->registerPort(
            0x42,
            fn() => 0xAB,
            function (int $v) use (&$portValue) { $portValue = $v; }
        );

        $this->execute([
            EmulatorOpcode::IN, 0x00, 0x42,
            EmulatorOpcode::MOV, 0x01, 0xCD,
            EmulatorOpcode::OUT, 0x42, 0x01,
            EmulatorOpcode::HLT,
        ]);

        $this->assertSame(0xAB, $this->cpu->r[0]);
        $this->assertSame(0xCD, $portValue);
    }

    // ── Memory ──────────────────────────────────────────────────

    public function testMemoryReadWriteWord(): void
    {
        $this->mem->writeWord(0x0200, 0xBEEF);
        $this->assertSame(0xBEEF, $this->mem->readWord(0x0200));
    }

    public function testMemoryReadWriteDWord(): void
    {
        $this->mem->writeDWord(0x0200, 0xDEADBEEF);
        $this->assertSame(0xDEADBEEF, $this->mem->readDWord(0x0200));
    }

    public function testMemoryCopy(): void
    {
        $this->mem->writeByte(0x0100, 0xAA);
        $this->mem->writeByte(0x0101, 0xBB);
        $this->mem->writeByte(0x0102, 0xCC);
        $this->mem->copy(0x0200, 0x0100, 3);
        $this->assertSame(0xAA, $this->mem->readByte(0x0200));
        $this->assertSame(0xBB, $this->mem->readByte(0x0201));
        $this->assertSame(0xCC, $this->mem->readByte(0x0202));
    }

    public function testMemoryFill(): void
    {
        $this->mem->fill(0x0300, 0x55, 4);
        for ($i = 0; $i < 4; $i++) {
            $this->assertSame(0x55, $this->mem->readByte(0x0300 + $i));
        }
    }

    public function testMemoryCompare(): void
    {
        $this->mem->fill(0x0100, 0xAA, 4);
        $this->mem->fill(0x0200, 0xAA, 4);
        $this->assertSame(0, $this->mem->compare(0x0100, 0x0200, 4));

        $this->mem->writeByte(0x0202, 0xBB);
        $this->assertNotSame(0, $this->mem->compare(0x0100, 0x0200, 4));
    }

    public function testMemoryReadOnlyRegion(): void
    {
        $this->mem->addReadOnlyRegion(0x0500, 0x05FF);
        $this->expectException(RuntimeException::class);
        $this->mem->writeByte(0x0510, 0xFF);
    }

    // ── Heap ────────────────────────────────────────────────────

    public function testMallocFree(): void
    {
        $a1 = $this->mem->malloc(32);
        $a2 = $this->mem->malloc(64);
        $this->assertNotSame($a1, $a2);

        $this->mem->free($a1);
        $a3 = $this->mem->malloc(16);
        $this->assertSame($a1, $a3);
    }

    public function testFreeCoalesce(): void
    {
        $a1 = $this->mem->malloc(32);
        $a2 = $this->mem->malloc(32);
        $this->mem->free($a1);
        $this->mem->free($a2);

        $a3 = $this->mem->malloc(64);
        $this->assertSame($a1, $a3);
    }

    public function testDoubleFreeThrows(): void
    {
        $a = $this->mem->malloc(16);
        $this->mem->free($a);
        $this->expectException(RuntimeException::class);
        $this->mem->free($a);
    }

    // ── CPU State / Step ────────────────────────────────────────

    public function testStep(): void
    {
        $this->cpu->load(0x0000, [
            EmulatorOpcode::MOV, 0x00, 0x11,
            EmulatorOpcode::MOV, 0x01, 0x22,
            EmulatorOpcode::HLT,
        ]);
        $this->cpu->step();
        $this->assertSame(0x11, $this->cpu->r[0]);
        $this->assertSame(0x00, $this->cpu->r[1]);
        $this->cpu->step();
        $this->assertSame(0x22, $this->cpu->r[1]);
    }

    public function testReset(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::HLT,
        ]);
        $this->cpu->reset();
        $state = $this->cpu->getState();
        $this->assertSame(0, $state['registers'][0]);
        $this->assertFalse($state['halted']);
        $this->assertSame(0, $state['cycles']);
    }

    public function testCycleCount(): void
    {
        $this->execute([
            EmulatorOpcode::NOP,
            EmulatorOpcode::NOP,
            EmulatorOpcode::NOP,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(4, $this->cpu->getCycleCount());
    }

    public function testUnknownOpcodeThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->execute([0xFE]);
    }

    // ── Loop Program ────────────────────────────────────────────

    public function testCountdownLoop(): void
    {
        $this->execute([
            // R0 = 5
            EmulatorOpcode::MOV, 0x00, 0x05,
            // loop: DEC R0
            EmulatorOpcode::DEC, 0x00,
            // JNZ loop (0x0003)
            EmulatorOpcode::JNZ, 0x03, 0x00,
            EmulatorOpcode::HLT,
        ]);
        $this->assertSame(0x00, $this->cpu->r[0]);
    }

    // ── Dump Methods ────────────────────────────────────────────

    public function testDumpRegisters(): void
    {
        $this->execute([
            EmulatorOpcode::MOV, 0x00, 0xFF,
            EmulatorOpcode::HLT,
        ]);
        $dump = $this->cpu->dumpRegisters();
        $this->assertStringContainsString('R0=0xFF', $dump);
        $this->assertStringContainsString('PC=', $dump);
    }

    public function testDumpRange(): void
    {
        $this->mem->writeByte(0x0000, 0x41);
        $this->mem->writeByte(0x0001, 0x42);
        $dump = $this->mem->dumpRange(0x0000, 16);
        $this->assertStringContainsString('0x0000:', $dump);
        $this->assertStringContainsString('41', $dump);
    }

    public function testDumpHeapEmpty(): void
    {
        $this->assertStringContainsString('No heap', $this->mem->dumpHeap());
    }

    public function testDumpHeapWithAllocation(): void
    {
        $this->mem->malloc(32);
        $dump = $this->mem->dumpHeap();
        $this->assertStringContainsString('Heap Allocations', $dump);
    }
}