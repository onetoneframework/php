<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration\Delivery;

/**
 * Korean Delivery Company Enumeration
 * 
 * @package Clover\Enumeration\Delivery
 */
abstract class KoreanDeliveryCompany
{
    public const CJ_LOGISTICS = 'cjlogistics';
    public const HANJIN = 'hanjin';
    public const HYUNDAI = 'hyundai';
    public const LOTTE = 'lotte';
    public const KGB = 'kgb';
    public const DAESIN = 'daesin';
    public const KDEXPRESS = 'kdexpress';
    public const EMS = 'ems';
    public const PARCEL = 'parcel';
    public const POST = 'post';
    public const CU_POST = 'cupost';
    public const GS_POST = 'gspost';
    public const LOGEN = 'logen';
}