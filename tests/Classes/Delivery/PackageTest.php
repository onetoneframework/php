<?php

declare(strict_types=1);

namespace Clover\Tests\Classes\Delivery;

use Clover\Classes\Delivery\Package;
use Clover\Enumeration\Delivery\KoreanDeliveryCompany;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PackageTest extends TestCase
{
	#[DataProvider('trackingUrlProvider')]
	public function testTrackingUrlIsGeneratedForSupportedCompany(string $company, string $expected): void
	{
		$this->assertSame($expected, Package::getKoreanTrackingUri($company, '123456789'));
	}

	public static function trackingUrlProvider(): array
	{
		return [
			'cj logistics' => [
				KoreanDeliveryCompany::CJ_LOGISTICS,
				'https://www.cjlogistics.com/ko/tool/parcel/tracking?gnbInvcNo=123456789',
			],
			'hanjin' => [
				KoreanDeliveryCompany::HANJIN,
				'https://www.hanjin.com/kor/CMS/DeliveryMgr/WaybillResult.do?mession=1&wblnumText2=123456789',
			],
			'ems' => [
				KoreanDeliveryCompany::EMS,
				'https://service.epost.go.kr/trace.RetrieveEmsRi498.postal?POST_CODE=123456789',
			],
			'logen' => [
				KoreanDeliveryCompany::LOGEN,
				'https://www.ilogen.com/web/personal/trace/123456789',
			],
		];
	}

	public function testUnknownCompanyReturnsEmptyTrackingUrl(): void
	{
		$this->assertSame('', Package::getKoreanTrackingUri('unknown', '123'));
	}
}
