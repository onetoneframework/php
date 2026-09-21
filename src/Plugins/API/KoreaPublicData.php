<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\ClientURL;
use Clover\Classes\Data\ArrayObject;
use Clover\Classes\Data\URLObject;
use Clover\Classes\XML\SimpleXML;
use Clover\Plugin\API\KoreaPublicData\AccidentDeathBoardInformation;
use Clover\Plugin\API\KoreaPublicData\AirPollutionData;
use Clover\Plugin\API\KoreaPublicData\AirQualityForecastDetail;
use Clover\Plugin\API\KoreaPublicData\AnniversaryInformation;
use Clover\Plugin\API\KoreaPublicData\BusanLibraryInformation;
use Clover\Plugin\API\KoreaPublicData\BusinessNumberStatus;
use Clover\Plugin\API\KoreaPublicData\BusinessNumberValidate;
use Clover\Plugin\API\KoreaPublicData\Covid19VaccinationCenter;
use Clover\Plugin\API\KoreaPublicData\ForestVersion;
use Clover\Plugin\API\KoreaPublicData\HealthFoodLicenseChanges;
use Clover\Plugin\API\KoreaPublicData\MeasurmentStationList;
use Clover\Plugin\API\KoreaPublicData\MidForest;
use Clover\Plugin\API\KoreaPublicData\MidTemperature;
use Clover\Plugin\API\KoreaPublicData\MsitBusinessAnnouncement;
use Clover\Plugin\API\KoreaPublicData\MssBusinessAnnouncement;
use Clover\Plugin\API\KoreaPublicData\NearMeasurmentStationList;
use Clover\Plugin\API\KoreaPublicData\TMStandardCoordinates;
use Clover\Plugin\API\KoreaPublicData\UltraShorttermNowcast;
use Clover\Plugin\API\KoreaPublicData\VilageForest;
use Clover\Plugin\API\PublicDataResponse;
use function count;

/**
 * KoreaPublicData class handles various Korean government public data APIs.
 * This class provides methods to access financial information, weather data,
 * business announcements, and other public datasets from Korean government services.
 */
class KoreaPublicData
{
    /**
     * Service key for Korean public data API authentication.
     * @var string
     */
    private string $SERVICE_KEY;

    /**
     * Constructor for KoreaPublicData class.
     * Initializes the client with the service key for API access.
     *
     * @param string $serviceKey The service key for Korean public data APIs.
     */
    public function __construct(string $serviceKey)
    {
        $this->SERVICE_KEY = $serviceKey;
    }

    /**
     * Retrieves personal financial total liability information.
     * Gets debt information for small businesses based on various criteria.
     *
     * @param mixed $basYm Base year and month.
     * @param mixed $bizAreaNm Business area name.
     * @param mixed $bizBzcCdNm Business classification code name.
     * @param mixed $bizBzcCd Business classification code.
     * @param mixed $debtTsumAmtMin Minimum total debt amount.
     * @param mixed $debtTsumAmtMax Maximum total debt amount.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getPersonalFinalcianlTotalLiabilityInformation($basYm, $bizAreaNm, $bizBzcCdNm, $bizBzcCd, $debtTsumAmtMin, $debtTsumAmtMax, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'basYm ' => $basYm,
            'bizAreaNm ' => $bizAreaNm,
            'bizBzcCdNm ' => $bizBzcCdNm,
            'bizBzcCd ' => $bizBzcCd,
            'debtTsumAmtMin ' => $debtTsumAmtMin,
            'debtTsumAmtMax ' => $debtTsumAmtMax,
        ];

        $requestUrl = new URLObject('https://apis.data.go.kr/1160100/service/GetSBFinanceInfoService/getDbtInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves personal financial income information.
     * Gets sales information for small businesses based on various criteria.
     *
     * @param mixed $basYm Base year and month.
     * @param mixed $bizAreaNm Business area name.
     * @param mixed $bizBzcCdNm Business classification code name.
     * @param mixed $bizBzcCd Business classification code.
     * @param mixed $saleAmtMin Minimum sales amount.
     * @param mixed $saleAmtMax Maximum sales amount.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getPersonalFinalcianlIncomeInformation($basYm, $bizAreaNm, $bizBzcCdNm, $bizBzcCd, $saleAmtMin, $saleAmtMax, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'basYm ' => $basYm,
            'bizAreaNm ' => $bizAreaNm,
            'bizBzcCdNm ' => $bizBzcCdNm,
            'bizBzcCd ' => $bizBzcCd,
            'saleAmtMin ' => $saleAmtMin,
            'saleAmtMax ' => $saleAmtMax,
        ];

        $requestUrl = new URLObject('https://apis.data.go.kr/1160100/service/GetSBFinanceInfoService/getSlsInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves personal financial information.
     * Gets financial information for small businesses based on capital amount criteria.
     *
     * @param mixed $basYm Base year and month.
     * @param mixed $bizAreaNm Business area name.
     * @param mixed $bizBzcCdNm Business classification code name.
     * @param mixed $bizBzcCd Business classification code.
     * @param mixed $cptlAmtMin Minimum capital amount.
     * @param mixed $cptlAmtMax Maximum capital amount.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getPersonalFinalcianlInformation($basYm, $bizAreaNm, $bizBzcCdNm, $bizBzcCd, $cptlAmtMin, $cptlAmtMax, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'basYm ' => $basYm,
            'bizAreaNm ' => $bizAreaNm,
            'bizBzcCdNm ' => $bizBzcCdNm,
            'bizBzcCd ' => $bizBzcCd,
            'cptlAmtMin ' => $cptlAmtMin,
            'cptlAmtMax ' => $cptlAmtMax,
        ];

        $requestUrl = new URLObject('https://apis.data.go.kr/1160100/service/GetSBFinanceInfoService/getFnafInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves financial income statement information.
     * Gets income statement data for a specific company and business year.
     *
     * @param mixed $crno Company registration number.
     * @param mixed $bizYear Business year.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getFinancialIncomeStatement($crno, $bizYear, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'crno ' => $crno,
            'bizYear ' => $bizYear,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1160100/service/GetFinaStatInfoService_V2/getIncoStat_V2');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves financial business status information.
     * Gets balance sheet data for a specific company and business year.
     *
     * @param mixed $crno Company registration number.
     * @param mixed $bizYear Business year.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getFinancialBusinessStatus($crno, $bizYear, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'crno ' => $crno,
            'bizYear ' => $bizYear,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1160100/service/GetFinaStatInfoService_V2/getBs_V2');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves financial summary status information.
     * Gets summarized financial statement data for a specific company and business year.
     *
     * @param mixed $crno Company registration number.
     * @param mixed $bizYear Business year.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getFinancialSummaryStatus($crno, $bizYear, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'crno ' => $crno,
            'bizYear ' => $bizYear,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1160100/service/GetFinaStatInfoService_V2/getSummFinaStat_V2');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves Ulsan bus timetable information.
     * Gets bus schedule data for a specific route and day of the week.
     *
     * @param mixed $routeNo Bus route number.
     * @param mixed $dayOfWeek Day of the week.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getUlsanBusTimeTable($routeNo, $dayOfWeek, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'apiType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows ' => $numOfRows,
            'routeNo' => $routeNo,
            'dayOfWeek' => $dayOfWeek,
        ];

        $requestUrl = new URLObject('http://openapi.its.ulsan.kr/UlsanAPI/BusTimetable.xo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Location : D:\tomcat7.0\webapps\UlsanAPI\WEB-INF\classes\sql\arr\mybatis-query-arr.xml
     * 
     * Query : select 
     *  *
     * from 
     * (
     *     select 
     *     t1.node_id AS STOPID, 
     *     t1.node_nm AS STOPNM, 
     *     t1.remark AS REMARK, 
     *     T3.route_id AS ROUTEID, 
     *     t3.route_nm AS ROUTENM, 
     *     t4.vehicle_no as VEHICLENO, 
     *     t2.arrival_time AS ARRIVALTIME, 
     *     t2.arr_prev_station_count AS PREVSTOPCNT, 
     *     t2.stop_node_nm AS PRESENTSTOPNM, 
     *     row_number() over(
     *         order by 
     *         t1.node_nm, 
     *         t2.arrival_time
     *     ) as RNUM 
     *     from 
     *     bistago.node t1 
     *     left join bistago.stnarrinfo t2 on (
     *         t2.node_id = t1.node_id 
     *         and t2.arrival_time > 0
     *     ) 
     *     left join bistago.route t3 on (t3.route_id = t2.route_id) 
     *     left join bistago.vehicle t4 on (t4.vehicle_id = t2.vehicle_id) 
     *     where 
     *     1 = 1 
     *     AND t1.node_id = ?
     * ) T 
     * where 
     * T.RNUM between ? 
     * and ?
     * 
     * Retrieves Ulsan bus arrival information.
     * Gets real-time bus arrival data for a specific bus stop.
     *
     * @param mixed $stopId Bus stop ID.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getUlsanBusArrivalInformation($stopId, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'apiType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows ' => $numOfRows,
            'stopid' => $stopId,
        ];

        $requestUrl = new URLObject('http://openapi.its.ulsan.kr/UlsanAPI/getBusArrivalInfo.xo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves Ulsan bus stop information.
     * Gets information about bus stops in Ulsan.
     *
     * @param mixed $pageNo Page number for pagination.
     * @param int $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getUlsanBusStopInformation($pageNo, int $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'apiType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows ' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://openapi.its.ulsan.kr/UlsanAPI/BusStopInfo.xo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves Ulsan bus route information.
     * Gets information about bus routes in Ulsan.
     *
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getUlsanBusRouteInformation($pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'apiType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows ' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://openapi.its.ulsan.kr/UlsanAPI/RouteInfo.xo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Location : D:\tomcat7.0\webapps\UlsanAPI\WEB-INF\classes\sql\mybatis-query-AllRouteDetailInfo.xml
     * 
     * Query : select 
     *   * 
     * from 
     *   (
     *     select 
     *       BRS.BRT_ID as ROUTEID, 
     *       BRS.BRS_SEQNO as BRSSEQNO, 
     *       BS.STOP_ID as STOPID, 
     *       BS.STOP_NAME AS STOPNM, 
     *       ROW_NUMBER() OVER(
     *         ORDER BY 
     *           BRS.BRT_ID
     *       ) as RNUM 
     *     from 
     *       COMBUSROUTESTOP BRS, 
     *       BITBUSSTOP BS, 
     *       COMBUSROUTE BR, 
     *       COMBNODE_BIS BN 
     *     where 
     *       BRS.BRT_ID = BR.BRT_ID 
     *       and BS.STOP_TYPE != '3' 
     *       and BS.STOP_ID = BRS.BNODE_ID 
     *       and BN.BNODE_TYPE > '0' 
     *       and BN.BNODE_TYPE < '9' 
     *       and BRS.BNODE_ID = BN.BNODE_ID 
     *       and BRS.BRT_ID = ? 
     *     order by 
     *       BRS.BRT_ID, 
     *       BRS.BRS_SEQNO
     *   ) 
     * where 
     *   RNUM between ? 
     *   and ?* 
     * 
     * Retrieves all Ulsan bus route information.
     * Gets detailed information about all bus routes in Ulsan.
     *
     * @param mixed $routeid Route ID.
     * @param mixed $pageNo Page number for pagination.
     * @param mixed $numOfRows Number of rows per page.
     * @return mixed The response data from the API.
     */
    public function getAllUlsanBusRouteInformation($routeid, $pageNo, $numOfRows)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'Routeid' => $routeid,
            'apiType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows ' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://openapi.its.ulsan.kr/UlsanAPI/AllRouteDetailInfo.xo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    public function getCreamationStation(string $CTPV, int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'pageNo' => $pageNo,
            'apiType' => 'json',
            'numOfRows' => $numOfRows,
            'CTPV ' => $CTPV,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1352000/ODMS_DATA_05/callData05Api');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    public function getDeathReason(string $year, string $type = '남자', int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'pageNo' => $pageNo,
            'apiType' => 'json',
            'numOfRows' => $numOfRows,
            'year' => $year,
            'dvs' => $type,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1352000/ODMS_STAT_05/callStat05Api');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves cremation dead reason status.
     * Gets statistics on reasons for cremation deaths.
     *
     * @param string $startDate Start date for the query.
     * @param string $endDate End date for the query.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return mixed The response data from the API.
     */
    public function getCremationDeadReasonStatus(string $startDate, string $endDate, int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'pfromym' => $startDate,
            'ptoym' => $endDate,
        ];

        $requestUrl = new URLObject('http://data.sisul.or.kr/AutoAPI/service/OpenDB/CremationDeadReasonStat/getCremationDeadReasonStatQry');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves accident death board information.
     * Gets news and information about accident deaths.
     *
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @param mixed $apiId API ID for the request (default: "1040").
     * @return mixed The response data from the API.
     */
    public function getAccidentDeathBoardInformation(int $pageNo = 1, int $numOfRows = 100, $apiId = "1040")
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'callApiId' => $apiId
        ];

        $requestUrl = new URLObject('https://apis.data.go.kr/B552468/news_api02/getNews_api02');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack($response['body']->items->item);
        $data->setData(AccidentDeathBoardInformation::class);

        return $response;
    }

    /**
     * Retrieves COVID-19 vaccination center information.
     * Gets data about vaccination centers in Korea.
     *
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing vaccination center data.
     */
    public function getCovid19VaccinationCenter(int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'page' => $pageNo,
            'perPage' => $numOfRows
        ];

        $requestUrl = new URLObject('https://api.odcloud.kr/api/15077586/v1/centers');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack($response['data']);
        $data->setData(Covid19VaccinationCenter::class);

        return $data;
    }

    /**
     * Retrieves rest date information.
     * Gets information about holidays and rest days.
     *
     * @param string $year The year for the query.
     * @param string $month The month for the query.
     * @return PublicDataResponse The response containing rest date data.
     */
    public function getRestDateInformation(string $year, string $month)
    {
        $queries = [
            'solYear' => $year,
            'solMonth' => $month,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getRestDeInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();

        if ($response->cmmMsgHeader) {
            $data->setStatusCode($response->cmmMsgHeader->returnReasonCode);
            $data->setStatusMessage($response->cmmMsgHeader->returnAuthMsg);
        } else {
            $data->setStack((array) $response->body->items);
            $data->setStatusCode($response->header->resultMsg);
            $data->setData(AnniversaryInformation::class);
        }

        return $data;
    }

    /**
     * Retrieves anniversary information.
     * Gets information about anniversaries and special dates.
     *
     * @param string $year The year for the query.
     * @param string $month The month for the query.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing anniversary data.
     */
    public function getAnniversaryInformation(string $year, string $month, int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'solYear' => $year,
            'solMonth' => $month,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B090041/openapi/service/SpcdeInfoService/getAnniversaryInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response->body->items);
        $data->setStatusCode($response->header->resultMsg);
        $data->setData(AnniversaryInformation::class);

        return $data;
    }

    /**
     * Retrieves health food license changes.
     * Gets information about changes in health food licenses.
     *
     * @param string|null $nusinessName Business name (optional).
     * @param string|null $permitNumber Permit number (optional).
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing license change data.
     */
    public function getHealthFoodLicenseChanges(?string $nusinessName = null, ?string $permitNumber = null, int $pageNo = 1, int $numOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'BSSH_NM' => $nusinessName,
            'PRMT_NO' => $permitNumber,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1471000/HtfsLcnsChgInfo/getHtfsLcnsChgInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response->body->items->item);
        $data->setStatusCode($response->header->resultCode);
        $data->setData(HealthFoodLicenseChanges::class);

        return $data;
    }

    /**
     * Retrieves air quality forecast details by notice code and time.
     * Gets detailed air quality forecast information.
     *
     * @param string $searchDate Search date.
     * @param string $InformationCode Information code.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing air quality forecast data.
     */
    public function getAirQualityForecastDetailsByNoticeCodeAndTime(string $searchDate, string $InformationCode, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'searchDate' => $searchDate,
            'InformCode' => $InformationCode,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/ArpltnInforInqireSvc/getMinuDustFrcstDspth');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response['response']->body->items);
        $data->setStatusCode($response['response']->header->resultMsg);
        $data->setData(AirQualityForecastDetail::class);

        return $data;
    }

    /**
     * Retrieves TM standard coordinates by region name.
     * Gets TM coordinates for a given region.
     *
     * @param string $localDistrictName Local district name.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing TM coordinates data.
     */
    public function getTMStandardCoordinatesByRegionName(string $localDistrictName, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'umdName' => $localDistrictName
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/MsrstnInfoInqireSvc/getTMStdrCrdnt');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();

        $response = json_decode($response);
        $data = new PublicDataResponse();
        $data->setStack($response->response->body->items);
        $data->setStatusCode($response->response->header->resultMsg);
        $data->setData(TMStandardCoordinates::class);

        return $data;
    }

    /**
     * Retrieves near measurement station list.
     * Gets list of nearby measurement stations based on TM coordinates.
     *
     * @param float $tmX TM X coordinate.
     * @param float $tmY TM Y coordinate.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @param string|null $version Version parameter (optional).
     * @return PublicDataResponse<NearMeasurmentStationList> The response containing station list data.
     */
    public function getNearMeasurementStationList(float $tmX, float $tmY, int $pageNo = 1, int $numOfRows = 100, ?string $version = null): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'tmX' => $tmX,
            'tmY' => $tmY,
            'version' => $version,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/MsrstnInfoInqireSvc/getNearbyMsrstnList');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode(false, true);

        $data = new PublicDataResponse();
        $data->setStack($response->response->body->items);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setStatusMessage($response->response->header->resultMsg);
        $data->setData(NearMeasurmentStationList::class);

        return $data;
    }

    /**
     * Retrieves Busan library information.
     * Gets information about libraries in Busan.
     *
     * @param string $area Library area.
     * @param string $name Library name.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 10).
     * @return PublicDataResponse The response containing library information.
     */
    public function getBusanLibraryInformation(string $area, string $name, int $pageNo = 1, int $numOfRows = 10)
    {
        $queries = [
            'ServiceKey' => $this->SERVICE_KEY,
            'resultType' => 'json',
            'numOfRows' => $numOfRows,
            'pageNo' => $pageNo,
            'library_area' => $area,
            'library_nm' => $name,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/6260000/BusanLibraryInfoService/getLibraryInfo');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = (object) $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response['response']->body->items->item);
        $data->setTotalCount(count((array) $response['response']->body->totalCount));
        $data->setStatusCode($response['response']->header->resultMsg);
        $data->setData(BusanLibraryInformation::class);

        return $data;
    }

    /**
     * Gets the announcement list of MSS.
     * Retrieves business announcement list from MSS.
     *
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 10).
     * @param string|null $startDate Start date for filtering (optional).
     * @param string|null $endDate End date for filtering (optional).
     * @return PublicDataResponse<MssBusinessAnnouncement> The response containing MSS business announcements.
     */
    public function getMssBusinessAnnouncementList(int $pageNo = 1, int $numOfRows = 10, ?string $startDate = null, ?string $endDate = null)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1421000/mssBizService_v2/getbizList_v2');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();

        $xml = new SimpleXML();
        $xml->parse($response);
        $response = $xml->toObjectData($xml->getData());

        $data = new PublicDataResponse();
        $data->setStack((array) $response->body->items->item);
        $data->setTotalCount(count((array) $response->body->totalCount));
        $data->setStatusCode($response->header->resultMsg);
        $data->setData(MssBusinessAnnouncement::class);

        return $data;
    }

    /**
     * Gets the announcement list of MSIT (Ministry of Science and ICT).
     * Retrieves business announcement list from MSIT.
     *
     * @return PublicDataResponse<MsitBusinessAnnouncement> The response containing MSIT business announcements.
     */
    public function getMsitBusinessAnnouncementList()
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json'
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1721000/msitannouncementinfo/businessAnnouncMentList');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();
        $response = json_decode($response);
        $response->response = (Object) ([...(array) $response->response[0], ...(array) $response->response[1]]);

        $items = [];
        foreach ($response->response->body->items as $item) {
            $items[] = $item->item;
        }

        $data = new PublicDataResponse();
        $data->setStack((array) $items);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setStatusCode($response->response->body->totalCount);
        $data->setData(MsitBusinessAnnouncement::class);

        return $data;
    }

    /**
     * Retrieves mid-temperature forecast.
     * Gets mid-term temperature forecast data.
     *
     * @param string $regId Region ID.
     * @param string $timeForcast Time forecast.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<MidTemperature> The response containing mid-temperature data.
     */
    public function getMidTemperature(string $regId, string $timeForcast, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'regId' => $regId,
            'tmFc' => $timeForcast,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1360000/MidFcstInfoService/getMidTa');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();
        $response = json_decode($response);

        $data = new PublicDataResponse();
        $data->setStack((array) $response->response->body->items->item);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setData(MidTemperature::class);

        return $data;
    }

    /**
     * Retrieves the measurement station list.
     * Gets list of air quality measurement stations.
     *
     * @param string $address Address for filtering.
     * @param string $stationName Station name for filtering.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<MeasurmentStationList> The response containing measurement station data.
     */
    public function getMeasurementStationList(string $address, string $stationName, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
            'addr' => $address,
            'stationName' => $stationName,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/MsrstnInfoInqireSvc/getMsrstnList');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        /**
         * @var ArrayObject $response
         */
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack($response->get('response')->get('body')->get('items'));
        $data->setStatusCode($response->get('response')->get('header')->get('resultMsg'));
        $data->setData(MeasurmentStationList::class);

        return $data;
    }

    /**
     * Retrieves mid-term forecast data.
     * Gets mid-term weather forecast information.
     *
     * @param string $stationId Station ID.
     * @param string $timeForcast Time forecast.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<MidForest> The response containing mid-forecast data.
     */
    public function getMidForecast(string $stationId, string $timeForcast, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'stnId' => $stationId,
            'tmFc' => $timeForcast,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1360000/MidFcstInfoService/getMidFcst');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();

        $response = json_decode($response);
        $data = new PublicDataResponse();
        $data->setStack((array) $response->response->body->items->item);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setData(MidForest::class);

        return $data;
    }

    /**
     * Retrieves the version of forecast data.
     * Gets version information for forecast data.
     *
     * @param string $baseDateTime Base date and time.
     * @param string $ftype Forecast type.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<ForestVersion> The response containing forecast version data.
     */
    public function getForecastVersion(string $baseDateTime, string $ftype, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'basedatetime' => $baseDateTime,
            'ftype' => $ftype,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getFcstVersion');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();

        $response = json_decode($response);
        $data = new PublicDataResponse();
        $data->setStack((array) $response->response->body->items->item);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setData(ForestVersion::class);

        return $data;
    }

    /**
     * Retrieves village forecast data.
     * Gets weather forecast for a specific village location.
     *
     * @param string $baseDate Base date.
     * @param string $baseTime Base time.
     * @param float $nx X coordinate.
     * @param float $ny Y coordinate.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<VilageForest> The response containing village forecast data.
     */
    public function getVilageForecast(string $baseDate, string $baseTime, float $nx, float $ny, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'base_date' => $baseDate,
            'base_time' => $baseTime,
            'nx' => $nx,
            'ny' => $ny,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('https://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getVilageFcst');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->execute();

        $response = json_decode($response);
        $data = new PublicDataResponse();
        $data->setStack((array) $response->response->body->items->item);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setData(VilageForest::class);

        return $data;
    }

    /**
     * Retrieves ultra short-term forecast data.
     * Gets very short-term weather forecast information.
     *
     * @param string $baseDate Base date.
     * @param string $baseTime Base time.
     * @param float $nx X coordinate.
     * @param float $ny Y coordinate.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<VilageForest> The response containing ultra short-term forecast data.
     */
    public function getUltraShorttermForecast(string $baseDate, string $baseTime, float $nx, float $ny, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'base_date' => $baseDate,
            'base_time' => $baseTime,
            'nx' => $nx,
            'ny' => $ny,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getUltraSrtFcst');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();
        $lastHttpCode = $cURL->getLastHttpCode();

        if ($lastHttpCode !== 200) {
            $data = new PublicDataResponse();
            $data->setStatusCode($lastHttpCode);
            $data->setStatusMessage('HTTP request failed with code ' . $lastHttpCode);

            return $data;
        }

        if (!$response->get('response')->get('header')->get('resultCode')->equals('00')) {
            $data = new PublicDataResponse();
            $data->setStatusCode($response->get('response')->get('header')->get('resultCode'));
            $data->setStatusMessage($response->get('response')->get('header')->get('resultMsg'));

            return $data;
        }

        $data = new PublicDataResponse();
        $data->setStack($response->get('response')->get('body')->get('items')->get('item'));
        $data->setStatusCode($response->get('response')->get('header')->get('resultCode'));
        $data->setTotalCount($response->get('response')->get('totalCount'));

        $data->setData(VilageForest::class);

        return $data;
    }

    /**
     * Retrieves ultra short-term nowcast data.
     * Gets very short-term weather nowcast information.
     *
     * @param string $baseDate Base date.
     * @param string $baseTime Base time.
     * @param float $nx X coordinate.
     * @param float $ny Y coordinate.
     * @param int $pageNo Page number for pagination (default: 1).
     * @param int $numOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse<UltraShorttermNowcast> The response containing ultra short-term nowcast data.
     */
    public function getUltraShorttermNowcast(string $baseDate, string $baseTime, float $nx, float $ny, int $pageNo = 1, int $numOfRows = 100): PublicDataResponse
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'dataType' => 'json',
            'base_date' => $baseDate,
            'base_time' => $baseTime,
            'nx' => $nx,
            'ny' => $ny,
            'pageNo' => $pageNo,
            'numOfRows' => $numOfRows,
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/1360000/VilageFcstInfoService_2.0/getUltraSrtNcst');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();
        $lastHttpCode = $cURL->getLastHttpCode();

        if ($lastHttpCode !== 200) {
            $data = new PublicDataResponse();
            $data->setStatusCode($lastHttpCode);
            $data->setStatusMessage('HTTP request failed with code ' . $lastHttpCode);

            return $data;
        }

        if (!$response->get('response')?->get('header')?->get('resultCode')?->equals('00')) {
            $data = new PublicDataResponse();
            $data->setStatusCode($response->get('response')?->get('header')?->get('resultCode'));
            $data->setStatusMessage($response->get('response')?->get('header')?->get('resultMsg'));

            return $data;
        }

        $data = new PublicDataResponse();
        $data->setStack($response->get('response')->get('body')->get('items')->get('item'));
        $data->setStatusCode($response->get('response')->get('header')->get('resultCode'));
        $data->setTotalCount($response->get('response')->get('body')->get('totalCount'));
        $data->setData(UltraShorttermNowcast::class);

        return $data;
    }

    /**
     * Retrieves monthly real-time air pollution data.
     * Gets air pollution statistics for a measurement station over a monthly period.
     *
     * @param string $measurementStationName Name of the measurement station.
     * @param string $inqueryBeginDate Start date for the query.
     * @param string $inqueryEndDate End date for the query.
     * @param int $pageNumber Page number for pagination (default: 1).
     * @param int $numberOfRows Number of rows per page (default: 100).
     * @return mixed The response data from the API.
     */
    public function getMontlyRealtimeAirPollutionData(string $measurementStationName, string $inqueryBeginDate, string $inqueryEndDate, int $pageNumber = 1, int $numberOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'numOfRows' => $numberOfRows,
            'pageNo' => $pageNumber,
            'inqBginMm' => $inqueryBeginDate,
            'inqEndMm' => $inqueryEndDate,
            'msrstnName' => $measurementStationName
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/ArpltnStatsSvc/getMsrstnAcctoRMmrg');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response->response->body->items->item);
        $data->setStatusCode($response->response->header->resultCode);
        $data->setTotalCount($response->response->body->totalCount);
        $data->setData(AirPollutionData::class);

        return $response;
    }

    /**
     * Retrieves daily real-time air pollution data.
     * Gets air pollution statistics for a measurement station over a daily period.
     *
     * @param string $measurementStationName Name of the measurement station.
     * @param string $inqueryBeginDate Start date for the query.
     * @param string $inqueryEndDate End date for the query.
     * @param int $pageNumber Page number for pagination (default: 1).
     * @param int $numberOfRows Number of rows per page (default: 100).
     * @return PublicDataResponse The response containing air pollution data.
     */
    public function getDailyRealtimeAirPollutionData(string $measurementStationName, string $inqueryBeginDate, string $inqueryEndDate, int $pageNumber = 1, int $numberOfRows = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'numOfRows' => $numberOfRows,
            'pageNo' => $pageNumber,
            'inqBginDt' => $inqueryBeginDate,
            'inqEndDt' => $inqueryEndDate,
            'msrstnName' => $measurementStationName
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B552584/ArpltnStatsSvc/getMsrstnAcctoRDyrg');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        $data = new PublicDataResponse();
        $data->setStack((array) $response['response']->body->items);
        $data->setStatusCode($response['response']->header->resultCode);
        $data->setTotalCount($response['response']->body->totalCount);
        $data->setData(AirPollutionData::class);

        return $data;
    }

    /**
     * Validates a business number.
     * Checks if the provided business number is valid.
     *
     * @param string $no Business number.
     * @param string $startDate Start date.
     * @param string $presidentName President's name.
     * @return PublicDataResponse<BusinessNumberValidate> The response containing validation data.
     */
    public function isValidBusinessNumber(string $no, string $startDate, string $presidentName): PublicDataResponse
    {
        $fields = [
            'businesses' => [
                [
                    'b_no' => $no,
                    'start_dt' => $startDate,
                    'p_nm' => $presidentName
                ]
            ]
        ];

        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'JSON'
        ];

        $requestUrl = new URLObject('http://api.odcloud.kr/api/nts-businessman/v1/validate');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostField(json_encode($fields));
        $response = $cURL->execute();

        $response = json_decode($response);
        $data = new PublicDataResponse();
        $data->setStack($response->data);
        $data->setStatusCode($response->status_code);
        $data->setRequestCount($response->request_cnt);
        $data->setData(BusinessNumberValidate::class);

        return $data;
    }

    /**
     * Retrieves the status of a business number.
     * Gets status information for a business number.
     *
     * @param string $number Business number.
     * @return PublicDataResponse<BusinessNumberStatus> The response containing business number status data.
     */
    public function getBusinessNumberStatus(string $number): PublicDataResponse
    {
        $fields = [
            'b_no' => [$number]
        ];

        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'JSON'
        ];

        $requestUrl = new URLObject('http://api.odcloud.kr/api/nts-businessman/v1/status');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setContentTypeApplicationJson()
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setReturnTransfer()
            ->setPostMethod()
            ->setPostField(json_encode($fields));
        $response = $cURL->execute();
        $response = json_decode($response);

        $data = new PublicDataResponse();
        $data->setStack($response->data);
        $data->setStatusCode($response->status_code);
        $data->setRequestCount($response->request_cnt);
        $data->setData(BusinessNumberStatus::class);

        return $data;
    }

    /**
     * Retrieves a list of legal cases.
     * Gets a list of case law from the Korean Law Information Service.
     *
     * @param string $OC User email ID.  (e.g., if email is g4c@korea.kr, then OC = g4c)
     * @param string $target Service target (must be "prec" for case law)
     * @param string $mobileYn Mobile access flag (default: 'Y').
     * @param string $type Output format (default: 'JSON', HTML, XML, or JSON).
     * @param int|null $search Search scope.  (default: 1 = case name, 2 = full text search)
     * @param string|null $query Query string for the selected search scope
     * @param int|null $display Number of results to display (default = 20, max = 100)
     * @param int|null $page Page number of results (default = 1)
     * @param string|null $org Court type (Supreme Court: 400201, Lower Courts: 400202)
     * @param string|null $curt Court name (e.g., Supreme Court, Seoul High Court, Gwangju District Court, Incheon District Court)
     * @param string|null $JO Referenced law name (e.g., Criminal Act, Civil Act)
     * @param string|null $gana Alphabetical search (e.g., ga, na, da, etc.)
     * @param string|null $sort Sorting options:
     *                          - lasc: Case name ascending
     *                          - ldes: Case name descending
     *                          - dasc: Decision date ascending
     *                          - ddes: Decision date descending (default)
     *                          - nasc: Court name ascending
     *                          - ndes: Court name descending
     * @param int|null $date Decision date (single date)
     * @param string|null $prncYd Decision date range (e.g., 20090101~20090130)
     * @param int|null $nb Case number.
     * @param string|null $datSrcNm Data source name (e.g., National Tax Law Info System, Korea Workers' Compensation Case Law, Supreme Court)
     * @return mixed The response data from the API.
     */
    public function getLawList(string $OC, string $target = 'prec', string $mobileYn = 'Y', string $type = 'JSON', ?int $search = null, ?string $query = null, ?int $display = null, ?int $page = null, ?string $org = null, ?string $curt = null, ?string $JO = null, ?string $gana = null, ?string $sort = null, ?int $date = null, ?string $prncYd = null, ?int $nb = null, ?string $datSrcNm = null): mixed
    {
        $queries = [
            'OC' => $OC,
            'target' => $target,
            'mobileYn' => $mobileYn,
            'type' => $type,
            'search' => $search,
            'display' => $display,
            'page' => $page,
            'org' => $org,
            'curt' => $curt,
            'JO' => $JO,
            'gana' => $gana,
            'sort' => $sort,
            'date' => $date,
            'prncYd' => $prncYd,
            'nb' => $nb,
            'datSrcNm' => $datSrcNm,
        ];

        $requestUrl = new URLObject('http://www.law.go.kr/DRF/lawSearch.do?target=prec&mobileYn=Y');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.law.go.kr')
            ->setHeader('Referer', 'https://open.law.go.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves the full text of a legal precedent from the Korean Law Information Service.
     *
     * API Endpoint: http://www.law.go.kr/DRF/lawService.do?target=prec
     *
     * @param string $OC       Required. User email ID (e.g., for g4c@korea.kr, use "g4c").
     * @param string $target   Required. Service target. Must be "prec" for precedent data.
     * @param string $type     Required. Output format. Options: "HTML", "XML", or "JSON".
     *                         Note: Precedents from the National Tax Service are only available in HTML.
     * @param string $ID       Required. Unique identifier for the precedent (판례정보일련번호).
     * @param string $LM       Optional. Name of the precedent (판례명).
     *
     * @return mixed           API response containing the precedent text in the specified format.
     */
    public function getLegalPrecedent(string $OC = 'test', int $ID = 0, string $target = 'prec', string $type = 'JSON', ?string $LM = null): mixed
    {
        $queries = [
            'OC' => $OC,
            'ID' => $ID,
            'target' => $target,
            'type' => $type,
            'LM' => $LM,
        ];

        $requestUrl = new URLObject('https://www.law.go.kr/DRF/lawService.do?OC=test&target=prec&ID=228541&type=JSON');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.law.go.kr')
            ->setHeader('Referer', 'https://open.law.go.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * API Endpoint: https://www.law.go.kr/DRF/lawService.do?target=prec
     * 
     * Searches for legal precedents.
     * Searches for case law from the Korean Law Information Service.
     *
     * @param string $OC User email ID (e.g., for g4c@korea.kr, use "g4c").
     * @param string $target Service target. Must be "prec" for precedent data.
     * @param string $type Output format. Options: "HTML", "XML", or "JSON".
     *                     Note: Precedents from the National Tax Service are only available in HTML.
     * @param int|null $search Search scope.
     * @param string|null $query Query string.
     * @param int|null $display Number of results.
     * @param int|null $page Page number.
     * @param string|null $org Court type.
     * @param string|null $curt Court name.
     * @param string|null $JO Referenced law.
     * @param string|null $gana Alphabetical search.
     * @param string|null $sort Sorting options.
     * @param int|null $date Decision date.
     * @param string|null $prncYd Decision date range.
     * @param string|null $nb Case number.
     * @param string|null $datSrcNm Data source name.
     * @param string|null $popYn Popular case flag.
     * 
     * @return mixed The response data from the API. API response containing the precedent text in the specified format.
     */
    public function getLegalSearch(string $OC = 'test', string $target = 'prec', string $type = 'JSON', ?int $search = null, ?string $query = null, ?int $display = null, ?int $page = null, ?string $org = null, ?string $curt = null, ?string $JO = null, ?string $gana = null, ?string $sort = null, ?int $date = null, ?string $prncYd = null, ?string $nb = null, ?string $datSrcNm = null, ?string $popYn = null): mixed
    {
        $queries = [
            'OC' => $OC,
            'target' => $target,
            'type' => $type,
            'search' => $search,
            'query' => $query,
            'display' => $display,
            'page' => $page,
            'org' => $org,
            'curt' => $curt,
            'JO' => $JO,
            'gana' => $gana,
            'sort' => $sort,
            'date' => $date,
            'prncYd' => $prncYd,
            'nb' => $nb,
            'datSrcNm' => $datSrcNm,
            'popYn' => $popYn,
        ];

        $requestUrl = new URLObject('https://www.law.go.kr/DRF/lawSearch.do?OC=test&target=prec&ID=228541&type=JSON');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.law.go.kr')
            ->setHeader('Referer', 'https://open.law.go.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves the average salary of new employees.
     * Gets data on average salaries for new hires from the Korean government data portal.
     *
     * @param int $ac_year Accounting year (default: 2020).
     * @param int $pageNo Page number (default: 1).
     * @param int $numOfRows Number of rows per page (default: 10).
     * @param string $type Response format (default: 'JSON').
     * @return mixed The response data from the API.
     */
    public function getNewEmployeesAverageSalary(int $ac_year = 2020, int $pageNo = 1, int $numOfRows = 10, string $type = 'JSON')
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'type' => $type,
            'pageNo' => $pageNo,
            'ac_year' => $ac_year
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B551982/openApiNewAverPay/openXmlNewAverPay');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.data.go.kr')
            ->setHeader('Referer', 'https://apis.data.go.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves financial liability information.
     * Gets financial liability data from the Korean government data portal.
     *
     * @param int $ac_year Accounting year (default: 2020).
     * @param int $pageNo Page number (default: 1).
     * @param int $numOfRows Number of rows per page (default: 10).
     * @param string $type Response format (default: 'JSON').
     * @return mixed The response data from the API.
     */
    public function getFinancialLiability(int $ac_year = 2020, int $pageNo = 1, int $numOfRows = 10, string $type = 'JSON')
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'type' => $type,
            'pageNo' => $pageNo,
            'ac_year' => $ac_year
        ];

        $requestUrl = new URLObject('http://apis.data.go.kr/B551982/openApiFinaDebt2/openXmlFinaDebt');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.data.go.kr')
            ->setHeader('Referer', 'https://apis.data.go.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves all consolidated financial statements for a single company.
     * Provides all account items from XBRL financial statements in periodic reports submitted by listed companies (KOSPI, KOSDAQ) and major unlisted companies (business report filers & IFRS adopters).
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @param string $fs_div Financial statement division.
     * @return mixed The response data from the API.
     */
    public function getSingleCompanyAllConsolidatedFinancialStatements(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code, string $fs_div)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code,
            'fs_div' => $fs_div,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/fnlttSinglAcntAll.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Downloads the corporation company unique number list file.
     * Provides a file containing unique numbers, company names, stock codes, and recent change dates for companies registered in DART.
     *
     * @param string $filePath Path to save the downloaded file.
     * @param string $crtfc_key API certification key.
     * @return bool True if download was successful, false otherwise.
     */
    public function downloadCorporationCompanyUniqueNumberListFile(string $filePath, string $crtfc_key): bool
    {
        $file = fopen($filePath, "w+");

        $queries = [
            'crtfc_key' => $crtfc_key
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/corpCode.xml');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'opendart.fss.or.kr')
            ->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0')
            ->setSSLVerifyHost(false)
            ->setFileHandler($file)
            ->setFollowRedirects(false);
        $response = $cURL->execute();

        return $response;
    }

    /**
     * Retrieves consolidated financial statements for multiple companies.
     * Provides major account items (balance sheet, income statement) from XBRL financial statements in periodic reports submitted by listed companies (KOSPI, KOSDAQ) and major unlisted companies (business report filers & IFRS adopters). Supports multiple company queries.
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getMultipleCompaniesConsolidatedFinancialStatements(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/fnlttMultiAcnt.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves consolidated financial statements for a single company.
     * Provides major account items (balance sheet, income statement) from XBRL financial statements in periodic reports submitted by listed companies (KOSPI, KOSDAQ) and major unlisted companies (business report filers & IFRS adopters).
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getSingleCompanyConsolidatedFinancialStatements(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/fnlttSinglAcnt.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves a list of company disclosures.
     * Gets a list of disclosures from the DART system.
     *
     * @param string $crtfc_key API certification key.
     * @param string|null $corp_code Corporation code.
     * @param string|null $bgn_de Start date.
     * @param string|null $end_de End date.
     * @param string|null $last_reprt_at Last report date.
     * @param string|null $pblntf_ty Public notice type.
     * @param string|null $pblntf_detail_ty Public notice detail type.
     * @param string|null $corp_cls Corporation class.
     * @param string|null $sort Sort field.
     * @param string|null $sort_mth Sort method.
     * @param int|null $page_no Page number.
     * @param int|null $page_count Page count.
     * @return mixed The response data from the API.
     */
    public function getCompanyDisclosureList(string $crtfc_key, ?string $corp_code = null, ?string $bgn_de = null, ?string $end_de = null, ?string $last_reprt_at = null, ?string $pblntf_ty = null, ?string $pblntf_detail_ty = null, ?string $corp_cls = null, ?string $sort = null, ?string $sort_mth = null, ?int $page_no = null, ?int $page_count = null)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bgn_de' => $bgn_de,
            'end_de' => $end_de,
            'last_reprt_at' => $last_reprt_at,
            'pblntf_ty' => $pblntf_ty,
            'pblntf_detail_ty' => $pblntf_detail_ty,
            'corp_cls' => $corp_cls,
            'sort' => $sort,
            'sort_mth' => $sort_mth,
            'page_no' => $page_no,
            'page_count' => $page_count,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/list.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves company overview information.
     * Gets basic company information from the DART system.
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @return mixed The response data from the API.
     */
    public function getCompanyOverview(string $crtfc_key, string $corp_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/company.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves original public disclosure documents.
     * Gets the original documents from public disclosures in XML format.
     *
     * @param string $crtfc_key API certification key.
     * @param string $rcept_no Receipt number.
     * @return mixed The response data from the API.
     */
    public function getOriginalPublicDisclosureDocuments(string $crtfc_key, string $rcept_no)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'rcept_no' => $rcept_no
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/document.xml');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves compensation for all directors and auditors approved by shareholders.
     * Gets the total compensation status for all directors and auditors as approved by shareholders' meetings.
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getAllDirectorsAndAuditorsCompensationByShareholdersApproval(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/drctrAdtAllMendngSttusMendngPymntamtTyCl.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves compensation status for all directors and auditors by type.
     * Gets the compensation status for all directors and auditors categorized by type.
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getAllDirectorsAndAuditorsCompensationStatusByType(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/drctrAdtAllMendngSttusMendngPymntamtTyCl.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves compensation status for unregistered officers.
     * Gets the compensation status for officers who are not registered.
     *
     * @param string $crtfc_key API certification key.
     * @param string $corp_code Corporation code.
     * @param string $bsns_year Business year.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getUnregisteredOfficersCompensationStatus(string $crtfc_key, string $corp_code, string $bsns_year, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'corp_code' => $corp_code,
            'bsns_year' => $bsns_year,
            'reprt_code' => $reprt_code,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/unrstExctvMendngSttus.json');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves the original financial statement file.
     * Gets the original financial statement file in XBRL format.
     *
     * @param string $crtfc_key API certification key.
     * @param string $rcept_no Receipt number.
     * @param string $reprt_code Report code.
     * @return mixed The response data from the API.
     */
    public function getOriginalFinancialStatementFile(string $crtfc_key, string $rcept_no, string $reprt_code)
    {
        $queries = [
            'crtfc_key' => $crtfc_key,
            'rcept_no' => $rcept_no,
            'reprt_code' => $reprt_code,
        ];

        $requestUrl = new URLObject('https://opendart.fss.or.kr/api/fnlttXbrl.xml');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    /**
     * Retrieves the national pension enrollment workplace list.
     * Gets a list of workplaces enrolled in the national pension system.
     *
     * @param string $bzowr_rgst_no Business owner registration number.
     * @param string $data_crt_ym Data creation year-month.
     * @param int $page Page number (default: 1).
     * @param int $perPage Number of results per page (default: 100).
     * @return mixed The response data from the API.
     */
    public function getNationalPensionEnrollmentWorkplaceList(string $bzowr_rgst_no, string $data_crt_ym, int $page = 1, int $perPage = 100)
    {
        $queries = [
            'serviceKey' => $this->SERVICE_KEY,
            'returnType' => 'json',
            'cond[BZOWR_RGST_NO::EQ]' => $bzowr_rgst_no,
            'cond[DATA_CRT_YM::EQ]' => $data_crt_ym,
            'page' => $page,
            'perPage' => $perPage,
        ];

        $requestUrl = new URLObject('https://api.odcloud.kr/api/15083277/v1/uddi:af4cc4c2-bd47-4c1c-8aa1-c4e8a632ba2a');
        $requestUrl->setQueryString($queries);

        $cURL = new ClientURL($requestUrl);
        $cURL->option
            ->setURL($requestUrl)
            ->setSSLVerifyPeer(false)
            ->setSSLVerifyHost(false)
            ->setHeader('Accept', 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7')
            ->setHeader('Host', 'www.fss.or.kr')
            ->setHeader('Referer', 'https://opendart.fss.or.kr/')
            ->setReturnTransfer()
            ->setGetMethod();
        $response = $cURL->executeWithDecode();

        return $response;
    }

    public function getBaseDate()
    {
        return date('Ymd');
    }

    public function getBaseTime()
    {
        $t = date('H');

        $ret = $t - ($t + 1) % 3;

        $return = str_pad((string) $ret, 2, "0", STR_PAD_LEFT);

        return str_pad($return, 4, "0", STR_PAD_RIGHT);
    }

    private function getPMStatus($value)
    {
        if ($value < 30) {
            return 1;
        } else if ($value < 80) {
            return 2;
        } else if ($value < 150) {
            return 3;
        } else if ($value >= 150) {
            return 4;
        }
    }

}
