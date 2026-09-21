<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin\API\KoreaPublicData;

use Clover\Plugin\API\PublicDataInterface;

class BusinessNumberStatus implements PublicDataInterface
{
    private $businessNumber;
    private $status;
    private $statusCode;
    private $taxType;
    private $taxTypeCode;
    private $endDate;
    private $taxTypeChangeDate;
    private $invoiceApplyDate;

    public function __construct($businessNumber, $status, $statusCode, $taxType, $taxTypeCode, $endDate, $taxTypeChangeDate, $invoiceApplyDate)
    {
        $this->businessNumber = $businessNumber;
        $this->status = $status;
        $this->statusCode = $statusCode;
        $this->taxType = $taxType;
        $this->taxTypeCode = $taxTypeCode;
        $this->endDate = $endDate;
        $this->taxTypeChangeDate = $taxTypeChangeDate;
        $this->invoiceApplyDate = $invoiceApplyDate;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->b_no, $data->b_stt, $data->b_stt_cd, $data->tax_type, $data->tax_type_cd, $data->end_dt, $data->tax_type_change_dt, $data->invoice_apply_dt);
    }

    public function getBusinessNumber(): string
    {
        return $this->businessNumber;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getTaxType(): string
    {
        return $this->taxType;
    }
}