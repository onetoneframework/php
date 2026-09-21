<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\SuperRich;

use Clover\Plugin\API\PublicDataInterface;

/**
 * Represents a single SuperRich branch location.
 *
 * Raw JSON shape:
 * {
 *   "branchId": 13,
 *   "shortName": "RDR",
 *   "branchName_en": "Headquarter Rajdamri 1",
 *   "branchName_th": "สำนักงานใหญ่ ราชดำริ 1",
 *   "acctype": 1
 * }
 */
final class BranchInfo implements PublicDataInterface
{
    public function __construct(
        private int $branchId,
        private string $shortName,
        private string $nameEn,
        private string $nameTh,
        private int $accountType,
    ) {
    }

    /**
     * Build an instance from a raw stdClass object produced by the API decoder.
     */
    public static function from(mixed $key = null, mixed $data = null): self
    {
        $raw = $data ?? $key;

        return new self(
            branchId: (int) ($raw->branchId ?? 0),
            shortName: (string) ($raw->shortName ?? ''),
            nameEn: (string) ($raw->branchName_en ?? ''),
            nameTh: (string) ($raw->branchName_th ?? ''),
            accountType: (int) ($raw->acctype ?? 0),
        );
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }
    public function getShortName(): string
    {
        return $this->shortName;
    }
    public function getNameEn(): string
    {
        return $this->nameEn;
    }
    public function getNameTh(): string
    {
        return $this->nameTh;
    }
    public function getAccountType(): int
    {
        return $this->accountType;
    }
}
