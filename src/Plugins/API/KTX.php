<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Plugin;

use Clover\Classes\Data\URLObject;
use Clover\Classes\ClientURL;
use function json_encode;
use function json_decode;
use function is_string;
use Exception;

/**
 * KTX API client for Korean Railways high-speed trains
 * Handles seat availability, routes, fares, and reservations for KTX services
 */
class KTX
{
    private string $MEMBER_ID;
    private string $PASSWORD;
    private string $API_KEY;
    private const API_GATEWAY = "https://api.korail.com";
    private const TRAIN_TYPE_KTX = "1";
    private const TRAIN_TYPE_SEMI_KTX = "2";

    /**
     * Initialize KTX API client with credentials
     */
    public function __construct(string $memberId, string $password, string $apiKey = "")
    {
        $this->MEMBER_ID = $memberId;
        $this->PASSWORD = $password;
        $this->API_KEY = $apiKey;
    }

    /**
     * Make an HTTP request to KTX API
     *
     * @param string $method HTTP method (GET, POST)
     * @param string $endpoint API endpoint path
     * @param array<string, mixed> $data Request payload
     * @param array<string, string> $queryParams Query parameters
     * @return array<string, mixed> API response
     * @throws Exception
     */
    public function request(string $method, string $endpoint, array $data = [], array $queryParams = []): array
    {
        try {
            $requestURL = new URLObject(self::API_GATEWAY);
            $requestURL->appendPath($endpoint);

            if (!empty($queryParams)) {
                foreach ($queryParams as $key => $value) {
                    $requestURL->setParameter($key, (string) $value);
                }
            }

            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setMethod($method)
                ->setHeaders([
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                    "X-API-KEY" => $this->API_KEY
                ]);

            if (!empty($data)) {
                $cURL->option->setPostField(json_encode($data, JSON_UNESCAPED_UNICODE));
            }

            $response = $cURL->execute();
            $cURL->close();

            if (is_string($response)) {
                return json_decode($response, true, 512, JSON_THROW_ON_ERROR) ?? [];
            }

            return (array) $response;
        } catch (Exception $e) {
            throw new Exception("KTX API request failed: " . $e->getMessage());
        }
    }

    /**
     * Search available KTX trains between two stations
     *
     * @param string $departureStation Departure station code (e.g., "0015" for Ulsan)
     * @param string $arrivalStation Arrival station code (e.g., "0010" for Busan)
     * @param string $departDate Departure date (YYYYMMDD format)
     * @param string $departTime Departure time (HHMM format, optional)
     * @param int $adultCount Number of adult passengers
     * @param int $childCount Number of child passengers
     * @return array<string, mixed> Available KTX trains with seats and fares
     */
    public function searchTrains(string $departureStation, string $arrivalStation, string $departDate, string $departTime = "", int $adultCount = 1, int $childCount = 0): array
    {
        return $this->request("GET", "/v1/trains", [], [
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation,
            "depDate" => $departDate,
            "depTime" => $departTime ?: "000000",
            "trainType" => self::TRAIN_TYPE_KTX . "," . self::TRAIN_TYPE_SEMI_KTX,
            "adultCnt" => $adultCount,
            "childCnt" => $childCount
        ]);
    }

    /**
     * Get real-time seat availability for a specific train
     *
     * @param string $trainNumber Train number
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @return array<string, mixed> Seat class availability with remaining seats
     */
    public function getAvailableSeats(string $trainNumber, string $departureStation, string $arrivalStation, string $departDate): array
    {
        return $this->request("GET", "/v1/trains/{$trainNumber}/availability", [], [
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation,
            "depDate" => $departDate
        ]);
    }

    /**
     * Get detailed fare information for a route
     *
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @param string $trainNumber Optional specific train number
     * @return array<string, mixed> Comprehensive fare data by seat class
     */
    public function getFareInfo(string $departureStation, string $arrivalStation, string $departDate, string $trainNumber = ""): array
    {
        $params = [
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation,
            "depDate" => $departDate
        ];

        if ($trainNumber !== "") {
            $params["trainNo"] = $trainNumber;
        }

        return $this->request("GET", "/v1/fares", [], $params);
    }

    /**
     * Get station list and codes
     *
     * @param string $keyword Station name or partial code
     * @return array<string, mixed> Matching stations with codes
     */
    public function getStations(string $keyword): array
    {
        return $this->request("GET", "/v1/stations", [], [
            "keyword" => $keyword
        ]);
    }

    /**
     * Reserve a KTX train seat
     *
     * @param string $trainNumber Train number
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @param array<string, mixed> $passengers Passenger information
     * @param string $seatType Seat type (1=special, 2=first-class, 3=standard)
     * @return array<string, mixed> Reservation confirmation with reference number
     */
    public function reserveTrain(string $trainNumber, string $departureStation, string $arrivalStation, string $departDate, array $passengers, string $seatType = "3"): array
    {
        return $this->request("POST", "/v1/reservations", [
            "trainNo" => $trainNumber,
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation,
            "depDate" => $departDate,
            "passengers" => $passengers,
            "seatType" => $seatType
        ]);
    }

    /**
     * Cancel a KTX train reservation
     *
     * @param string $reservationId Reservation ID or reference number
     * @param string $reason Cancellation reason
     * @return array<string, mixed> Cancellation confirmation and refund info
     */
    public function cancelReservation(string $reservationId, string $reason = ""): array
    {
        return $this->request("POST", "/v1/reservations/{$reservationId}/cancel", [
            "reason" => $reason
        ]);
    }

    /**
     * Get reservation details by ID
     *
     * @param string $reservationId Reservation ID
     * @return array<string, mixed> Detailed reservation information
     */
    public function getReservation(string $reservationId): array
    {
        return $this->request("GET", "/v1/reservations/{$reservationId}");
    }

    /**
     * Get member's reservation history
     *
     * @param int $pageNum Page number (starts from 1)
     * @param int $pageSize Number of items per page
     * @return array<string, mixed> Paginated reservation history
     */
    public function getReservationHistory(int $pageNum = 1, int $pageSize = 10): array
    {
        return $this->request("GET", "/v1/members/{$this->MEMBER_ID}/reservations", [], [
            "page" => $pageNum,
            "size" => $pageSize
        ]);
    }

    /**
     * Get special promotions and discounts
     *
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @return array<string, mixed> Available promotions with discount rates
     */
    public function getPromotions(string $departureStation, string $arrivalStation): array
    {
        return $this->request("GET", "/v1/promotions", [], [
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation
        ]);
    }

    /**
     * Get seat class information with pricing
     *
     * @return array<string, mixed> Details about all available seat classes
     */
    public function getSeatClasses(): array
    {
        return $this->request("GET", "/v1/seat-classes");
    }

    /**
     * Check member loyalty points
     *
     * @return array<string, mixed> Point balance and redemption options
     */
    public function getMemberPoints(): array
    {
        return $this->request("GET", "/v1/members/{$this->MEMBER_ID}/points");
    }

    /**
     * Apply loyalty points to a reservation
     *
     * @param string $reservationId Reservation ID
     * @param int $pointsToUse Points to apply
     * @return array<string, mixed> Updated reservation with point discount
     */
    public function applyPointsToReservation(string $reservationId, int $pointsToUse): array
    {
        return $this->request("POST", "/v1/reservations/{$reservationId}/apply-points", [
            "points" => $pointsToUse
        ]);
    }

    /**
     * Get route information between stations
     *
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @return array<string, mixed> Route details including travel time and distance
     */
    public function getRouteInfo(string $departureStation, string $arrivalStation): array
    {
        return $this->request("GET", "/v1/routes", [], [
            "depStn" => $departureStation,
            "arrStn" => $arrivalStation
        ]);
    }

    /**
     * Get trending/popular routes
     *
     * @return array<string, mixed> Most searched and booked routes
     */
    public function getPopularRoutes(): array
    {
        return $this->request("GET", "/v1/popular-routes");
    }
}
