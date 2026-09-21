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

class InterruptController
{
    private const NMI_LINE = 2;

    private array $vectorTable = [];
    private array $pending = [];
    private bool $globalEnable = true;
    private int $maskRegister = 0xFF;

    public function __construct()
    {
        for ($i = 0; $i < 256; $i++) {
            $this->vectorTable[$i] = 0;
        }
    }

    public function setVector(int $num, int $addr): void
    {
        $this->vectorTable[$num & MaskingBit::BYTE] = $addr & MaskingBit::WORD;
    }

    public function getVector(int $num): int
    {
        return $this->vectorTable[$num & MaskingBit::BYTE];
    }

    public function request(int $num): void
    {
        $this->pending[$num & MaskingBit::BYTE] = true;
    }

    public function next(): ?int
    {
        if (empty($this->pending)) {
            return null;
        }

        if (isset($this->pending[self::NMI_LINE])) {
            unset($this->pending[self::NMI_LINE]);
            return self::NMI_LINE;
        }

        if (!$this->globalEnable) {
            return null;
        }

        $keys = array_keys($this->pending);
        sort($keys);
        foreach ($keys as $num) {
            if ($this->isUnmasked($num)) {
                unset($this->pending[$num]);
                return $num;
            }
        }
        return null;
    }

    public function hasPending(): bool
    {
        if (isset($this->pending[self::NMI_LINE])) {
            return true;
        }
        if (!$this->globalEnable) {
            return false;
        }
        foreach (array_keys($this->pending) as $num) {
            if ($this->isUnmasked($num)) {
                return true;
            }
        }
        return false;
    }

    public function enable(): void
    {
        $this->globalEnable = true;
    }

    public function disable(): void
    {
        $this->globalEnable = false;
    }

    public function isEnabled(): bool
    {
        return $this->globalEnable;
    }

    public function setMask(int $mask): void
    {
        $this->maskRegister = $mask & MaskingBit::BYTE;
    }

    public function getMask(): int
    {
        return $this->maskRegister;
    }

    public function clearPending(): void
    {
        $this->pending = [];
    }

    private function isUnmasked(int $num): bool
    {
        if ($num >= 8) {
            return true;
        }
        return (bool)(($this->maskRegister >> $num) & 1);
    }
}