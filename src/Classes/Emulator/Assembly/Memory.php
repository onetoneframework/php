<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Emulator\Assembly;

use Clover\Enumeration\Bitwise\MaskingBit;
use RuntimeException;
use function sprintf;

/**
 * Memory class manages raw byte storage, stack, and heap.
 */
class Memory
{
    private const SIZE = MaskingBit::DOUBLE_WORD;

    private array $mem = [];
    private int $heapBase;
    private int $heapPtr;
    private array $allocations = [];
    private array $freeList = [];
    private array $readOnlyRegions = [];
    private array $ioPorts = [];

    public function __construct(int $heapBase = 0x0100)
    {
        $this->heapBase = $heapBase;
        $this->heapPtr = $heapBase;
        // initialize all memory to zero
        $this->mem = array_fill(0, self::SIZE, 0);
    }

    public function readByte(int $addr): int
    {
        return $this->mem[$addr & MaskingBit::WORD];
    }

    public function writeByte(int $addr, int $value): void
    {
        $addr &= MaskingBit::WORD;
        if ($this->isReadOnly($addr)) {
            throw new RuntimeException(sprintf("Write to read-only address 0x%04X", $addr));
        }
        $this->mem[$addr] = $value & MaskingBit::BYTE;
    }

    public function readWord(int $addr): int
    {
        $lo = $this->readByte($addr);
        $hi = $this->readByte($addr + 1);
        return ($hi << 8) | $lo;
    }

    public function writeWord(int $addr, int $value): void
    {
        $this->writeByte($addr, $value & MaskingBit::BYTE);
        $this->writeByte($addr + 1, ($value >> 8) & MaskingBit::BYTE);
    }

    public function readDWord(int $addr): int
    {
        $lo = $this->readWord($addr);
        $hi = $this->readWord($addr + 2);
        return ($hi << 16) | $lo;
    }

    public function writeDWord(int $addr, int $value): void
    {
        $this->writeWord($addr, $value & MaskingBit::WORD);
        $this->writeWord($addr + 2, ($value >> 16) & MaskingBit::WORD);
    }

    public function copy(int $dst, int $src, int $length): void
    {
        for ($i = 0; $i < $length; $i++) {
            $this->writeByte($dst + $i, $this->readByte($src + $i));
        }
    }

    public function fill(int $addr, int $value, int $length): void
    {
        $value &= MaskingBit::BYTE;
        for ($i = 0; $i < $length; $i++) {
            $this->writeByte($addr + $i, $value);
        }
    }

    public function compare(int $addr1, int $addr2, int $length): int
    {
        for ($i = 0; $i < $length; $i++) {
            $a = $this->readByte($addr1 + $i);
            $b = $this->readByte($addr2 + $i);
            if ($a !== $b) {
                return $a - $b;
            }
        }
        return 0;
    }

    public function addReadOnlyRegion(int $start, int $end): void
    {
        $this->readOnlyRegions[] = [
            'start' => $start & MaskingBit::WORD,
            'end'   => $end & MaskingBit::WORD
        ];
    }

    public function clearReadOnlyRegions(): void
    {
        $this->readOnlyRegions = [];
    }

    public function registerPort(int $port, callable $readFn, callable $writeFn): void
    {
        $this->ioPorts[$port & MaskingBit::BYTE] = [
            'read'  => $readFn,
            'write' => $writeFn
        ];
    }

    public function readPort(int $port): int
    {
        $port &= MaskingBit::BYTE;
        if (!isset($this->ioPorts[$port])) {
            return 0;
        }
        return ($this->ioPorts[$port]['read'])() & MaskingBit::BYTE;
    }

    public function writePort(int $port, int $value): void
    {
        $port &= MaskingBit::BYTE;
        if (isset($this->ioPorts[$port])) {
            ($this->ioPorts[$port]['write'])($value & MaskingBit::BYTE);
        }
    }

    public function malloc(int $size): int
    {
        // 1. Find a free block big enough
        foreach ($this->freeList as $idx => $blk) {
            if ($blk['size'] >= $size) {
                $addr = $blk['start'];
                // shrink or remove free block
                if ($blk['size'] > $size) {
                    $this->freeList[$idx]['start'] += $size;
                    $this->freeList[$idx]['size'] -= $size;
                } else {
                    array_splice($this->freeList, $idx, 1);
                }
                $this->allocations[$addr] = ['start' => $addr, 'size' => $size];
                return $addr;
            }
        }

        // 2. Otherwise bump‐pointer
        $addr = $this->heapPtr;
        $this->heapPtr += $size;
        if ($this->heapPtr > self::SIZE) {
            throw new RuntimeException("Out of heap memory");
        }
        $this->allocations[$addr] = ['start' => $addr, 'size' => $size];
        return $addr;
    }

    /**
     * Free a previously malloc’d block.
     */
    public function free(int $addr): void
    {
        if (!isset($this->allocations[$addr])) {
            throw new RuntimeException(sprintf("Invalid free at 0x%04X", $addr));
        }
        $blk = $this->allocations[$addr];
        unset($this->allocations[$addr]);
        $this->freeList[] = ['start' => $blk['start'], 'size' => $blk['size']];
        $this->coalesceFree();
    }

    public function getAllocations(): array
    {
        return $this->allocations;
    }

    public function dumpHeap(): string
    {
        if (empty($this->allocations)) {
            return "No heap allocations.\n";
        }

        $out = "Heap Allocations:\n";
        $out .= str_pad("ID", 4) . str_pad("Start", 10) . str_pad("End", 10) . "Size\n";
        $out .= str_repeat("-", 34) . "\n";

        foreach ($this->allocations as $i => $blk) {
            $start = $blk['start'];
            $end = $start + $blk['size'] - 1;
            $out .= str_pad((string)$i, 4)
                . str_pad(sprintf("0x%04X", $start), 10)
                . str_pad(sprintf("0x%04X", $end), 10)
                . $blk['size'] . "\n";
        }

        return $out;
    }

    /**
     * Returns a hex-dump of the top of the stack.
     *
     * @param int $sp      Current stack pointer (16-bit).
     * @param int $bytes   Number of bytes to display.
     */
    public function dumpStack(int $sp, int $bytes = 16): string
    {
        $out = "Stack Dump (top {$bytes} bytes):\n";
        $start = ($sp + 1) & MaskingBit::WORD;
        $end = ($start + $bytes - 1) & MaskingBit::WORD;
        $addr = $start;

        while (true) {
            $out .= sprintf("0x%04X: %02X\n", $addr, $this->mem[$addr]);
            if ($addr === $end) {
                break;
            }
            $addr = ($addr + 1) & MaskingBit::WORD;
        }

        return $out;
    }

    public function dumpRange(int $addr, int $length): string
    {
        $out = '';
        for ($i = 0; $i < $length; $i += 16) {
            $lineAddr = ($addr + $i) & MaskingBit::WORD;
            $hex = '';
            $ascii = '';
            for ($j = 0; $j < 16 && ($i + $j) < $length; $j++) {
                $byte = $this->readByte($lineAddr + $j);
                $hex .= sprintf("%02X ", $byte);
                $ascii .= ($byte >= 0x20 && $byte <= 0x7E) ? chr($byte) : '.';
            }
            $out .= sprintf("0x%04X: %-48s %s\n", $lineAddr, $hex, $ascii);
        }
        return $out;
    }

    private function isReadOnly(int $addr): bool
    {
        foreach ($this->readOnlyRegions as $region) {
            if ($addr >= $region['start'] && $addr <= $region['end']) {
                return true;
            }
        }
        return false;
    }

    private function coalesceFree(): void
    {
        usort($this->freeList, fn($a, $b) => $a['start'] <=> $b['start']);
        $merged = [];
        foreach ($this->freeList as $blk) {
            if (empty($merged)) {
                $merged[] = $blk;
            } else {
                $last = &$merged[count($merged) - 1];
                if ($last['start'] + $last['size'] === $blk['start']) {
                    $last['size'] += $blk['size'];
                } else {
                    $merged[] = $blk;
                }
            }
        }
        $this->freeList = $merged;
    }
}