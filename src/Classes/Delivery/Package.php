<?php
declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\Delivery;

use Clover\Classes\BaseClass;
use Clover\Enumeration\Delivery\KoreanDeliveryCompany;

/**
 * Package Delivery Class
 * 
 * @package Clover\Classes\Delivery
 */
class Package extends BaseClass
{
    /**
     * Get Korean Delivery Tracking URI
     * 
     * @param string $company
     * @param string $identifier
     * 
     * @return string
     */
    public static function getKoreanTrackingUri(string $company, string $identifier): string
    {
        switch ($company) {
            case KoreanDeliveryCompany::CJ_LOGISTICS:
                return "https://www.cjlogistics.com/ko/tool/parcel/tracking?gnbInvcNo={$identifier}";
            case KoreanDeliveryCompany::HANJIN:
                return "https://www.hanjin.com/kor/CMS/DeliveryMgr/WaybillResult.do?mession=1&wblnumText2={$identifier}";
            case KoreanDeliveryCompany::HYUNDAI:
                return "https://www.hdexp.com/tracking/waybill?waybillNo={$identifier}";
            case KoreanDeliveryCompany::LOTTE:
                return "https://www.lotteglogis.com/home/reservation/tracking/linkView?InvNo={$identifier}";
            case KoreanDeliveryCompany::KGB:
                return "https://www.kgbls.co.kr/tracking/trace?waybillNo={$identifier}";
            case KoreanDeliveryCompany::DAESIN:
                return "https://www.ds3211.co.kr/freight/internalFreightSearch.ht?billno={$identifier}";
            case KoreanDeliveryCompany::KDEXPRESS:
                return "https://kdexp.com/basicNew498.kd?barcode={$identifier}";
            case KoreanDeliveryCompany::EMS:
                return "https://service.epost.go.kr/trace.RetrieveEmsRi498.postal?POST_CODE={$identifier}";
            case KoreanDeliveryCompany::PARCEL:
                return "https://www.courier.or.kr/mobile/tracking/tracking.jsp?invoiceNo={$identifier}";
            case KoreanDeliveryCompany::POST:
                return "https://service.epost.go.kr/trace.RetrieveDomRi498.postal?sid1={$identifier}";
            case KoreanDeliveryCompany::CU_POST:
                return "https://www.cupost.co.kr/postbox/delivery/localResult.cupost?invoice_no={$identifier}";
            case KoreanDeliveryCompany::GS_POST:
                return "https://www.cvsnet.co.kr/invoice/tracking.do?invoice_no={$identifier}";
            case KoreanDeliveryCompany::LOGEN:
                return "https://www.ilogen.com/web/personal/trace/{$identifier}";
            default:
                return "";
        }
    }
}