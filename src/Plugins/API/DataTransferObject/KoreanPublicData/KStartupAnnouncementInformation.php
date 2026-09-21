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

class KStartupAnnouncementInformation implements PublicDataInterface
{
    public ?string $rcrtPrgsYn;
    public ?string $aplyTrgt;
    public ?string $bizEnyy;
    public ?string $bizTrgtAge;
    public ?string $prfnMatr;
    public ?string $intgPbancYn;
    public ?string $bizPbancNm;
    public ?string $intgPbancBizNm;
    public ?string $pbancCtnt;
    public ?string $suptBizClsfc;
    public ?string $aplyTrgtCtnt;
    public ?string $suptRegin;
    public ?string $pbancRcptBgngDt;
    public ?string $pbancRcptEndDt;
    public ?string $pbancNtrpNm;
    public ?string $sprvInst;
    public ?string $bizPrchDprtNm;
    public ?string $bizGdncUrl;
    public ?string $bizAplyUrl;
    public ?string $prchCnplNo;
    public ?string $detlPgUrl;
    public ?string $aplyMthdVstRcptIstc;
    public ?string $aplyMthdPssrRcptIstc;
    public ?string $aplyMthdFaxRcptIstc;
    public ?string $aplyMthdOnliRcptIstc;
    public ?string $aplyMthdEtcIstc;
    public ?string $aplyExclTrgtCtnt;
    public ?string $pbancSn;

    public function __construct(
        ?string $rcrtPrgsYn,
        ?string $aplyTrgt,
        ?string $bizEnyy,
        ?string $bizTrgtAge,
        ?string $prfnMatr,
        ?string $intgPbancYn,
        ?string $bizPbancNm,
        ?string $intgPbancBizNm,
        ?string $pbancCtnt,
        ?string $suptBizClsfc,
        ?string $aplyTrgtCtnt,
        ?string $suptRegin,
        ?string $pbancRcptBgngDt,
        ?string $pbancRcptEndDt,
        ?string $pbancNtrpNm,
        ?string $sprvInst,
        ?string $bizPrchDprtNm,
        ?string $bizGdncUrl,
        ?string $bizAplyUrl,
        ?string $prchCnplNo,
        ?string $detlPgUrl,
        ?string $aplyMthdVstRcptIstc,
        ?string $aplyMthdPssrRcptIstc,
        ?string $aplyMthdFaxRcptIstc,
        ?string $aplyMthdOnliRcptIstc,
        ?string $aplyMthdEtcIstc,
        ?string $aplyExclTrgtCtnt,
        ?string $pbancSn
    ) {
        $this->rcrtPrgsYn = $rcrtPrgsYn;
        $this->aplyTrgt = $aplyTrgt;
        $this->bizEnyy = $bizEnyy;
        $this->bizTrgtAge = $bizTrgtAge;
        $this->prfnMatr = $prfnMatr;
        $this->intgPbancYn = $intgPbancYn;
        $this->bizPbancNm = $bizPbancNm;
        $this->intgPbancBizNm = $intgPbancBizNm;
        $this->pbancCtnt = $pbancCtnt;
        $this->suptBizClsfc = $suptBizClsfc;
        $this->aplyTrgtCtnt = $aplyTrgtCtnt;
        $this->suptRegin = $suptRegin;
        $this->pbancRcptBgngDt = $pbancRcptBgngDt;
        $this->pbancRcptEndDt = $pbancRcptEndDt;
        $this->pbancNtrpNm = $pbancNtrpNm;
        $this->sprvInst = $sprvInst;
        $this->bizPrchDprtNm = $bizPrchDprtNm;
        $this->bizGdncUrl = $bizGdncUrl;
        $this->bizAplyUrl = $bizAplyUrl;
        $this->prchCnplNo = $prchCnplNo;
        $this->detlPgUrl = $detlPgUrl;
        $this->aplyMthdVstRcptIstc = $aplyMthdVstRcptIstc;
        $this->aplyMthdPssrRcptIstc = $aplyMthdPssrRcptIstc;
        $this->aplyMthdFaxRcptIstc = $aplyMthdFaxRcptIstc;
        $this->aplyMthdOnliRcptIstc = $aplyMthdOnliRcptIstc;
        $this->aplyMthdEtcIstc = $aplyMthdEtcIstc;
        $this->aplyExclTrgtCtnt = $aplyExclTrgtCtnt;
        $this->pbancSn = $pbancSn;
    }

    public static function from(mixed $key = null, mixed $data): self
    {
        return new self(
            $data->rcrt_prgs_yn ?? null,
            $data->aply_trgt ?? null,
            $data->biz_enyy ?? null,
            $data->biz_trgt_age ?? null,
            $data->prfn_matr ?? null,
            $data->intg_pbanc_yn ?? null,
            $data->biz_pbanc_nm ?? null,
            $data->intg_pbanc_biz_nm ?? null,
            $data->pbanc_ctnt ?? null,
            $data->supt_biz_clsfc ?? null,
            $data->aply_trgt_ctnt ?? null,
            $data->supt_regin ?? null,
            $data->pbanc_rcpt_bgng_dt ?? null,
            $data->pbanc_rcpt_end_dt ?? null,
            $data->pbanc_ntrp_nm ?? null,
            $data->sprv_inst ?? null,
            $data->biz_prch_dprt_nm ?? null,
            $data->biz_gdnc_url ?? null,
            $data->biz_aply_url ?? null,
            $data->prch_cnpl_no ?? null,
            $data->detl_pg_url ?? null,
            $data->aply_mthd_vst_rcpt_istc ?? null,
            $data->aply_mthd_pssr_rcpt_istc ?? null,
            $data->aply_mthd_fax_rcpt_istc ?? null,
            $data->aply_mthd_onli_rcpt_istc ?? null,
            $data->aply_mthd_etc_istc ?? null,
            $data->aply_excl_trgt_ctnt ?? null,
            $data->pbanc_sn ?? null
        );
    }
}