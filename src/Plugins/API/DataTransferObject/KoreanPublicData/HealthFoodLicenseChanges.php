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

class HealthFoodLicenseChanges implements PublicDataInterface
{
    private ?string $BSSH_NM;
    private ?string $INDUTY_CD_NM;
    private ?string $PRMT_NO;
    private ?string $TELNO;
    private ?string $BSN_LCTN_ADDR;
    private ?string $CHNG_DT;
    private ?string $CHNG_BF_CN;
    private ?string $CHNG_AF_CN;
    private ?string $CHNG_PRVNS;

    public function __construct($BSSH_NM, $INDUTY_CD_NM, $PRMT_NO, $TELNO, $BSN_LCTN_ADDR, $CHNG_DT, $CHNG_BF_CN, $CHNG_AF_CN, $CHNG_PRVNS)
    {
        $this->BSSH_NM = $BSSH_NM;
        $this->INDUTY_CD_NM = $INDUTY_CD_NM;
        $this->PRMT_NO = $PRMT_NO;
        $this->TELNO = $TELNO;
        $this->BSN_LCTN_ADDR = $BSN_LCTN_ADDR;
        $this->CHNG_DT = $CHNG_DT;
        $this->CHNG_BF_CN = $CHNG_BF_CN;
        $this->CHNG_AF_CN = $CHNG_AF_CN;
        $this->CHNG_PRVNS = $CHNG_PRVNS;
    }

    public static function from(mixed $key = null, mixed $data): self 
    {
        return new self($data->BSSH_NM, $data->INDUTY_CD_NM, $data->PRMT_NO, $data->TELNO, $data->BSN_LCTN_ADDR, $data->CHNG_DT, $data->CHNG_BF_CN, $data->CHNG_AF_CN, $data->CHNG_PRVNS);
    }
    
}