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
use function is_string;
use function json_encode;
use function json_decode;
use Exception;

/**
 * SRT (Srail) API client for Korean special express trains
 * Handles seat availability, routes, fares, and reservations
 */
class SRT
{
    private string $API_KEY;
    private string $MEMBER_ID;
    private const API_GATEWAY = "https://apigw.srail.kr";
    private const RAIL_TYPE_SRT = "17";

    /**
     * Initialize SRT API client with credentials
     */
    public function __construct(string $apiKey, string $memberId = "")
    {
        $this->API_KEY = $apiKey;
        $this->MEMBER_ID = $memberId;
    }

    /**
     * Make an HTTP request to SRT API
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
                    "Authorization" => "Bearer " . $this->API_KEY,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json"
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
            throw new Exception("SRT API request failed: " . $e->getMessage());
        }
    }

    /**
     * Get available trains between two stations
     *
     * @param string $departureStation Departure station code (e.g., "0015" for Ulsan)
     * @param string $arrivalStation Arrival station code (e.g., "0010" for Busan)
     * @param string $departDate Departure date (YYYYMMDD format)
     * @param string $departTime Departure time (HHMM format, optional)
     * @param int $adultCount Number of adult passengers
     * @param int $childCount Number of child passengers
     * @return array<string, mixed> Available trains with seats and fares
     */
    public function searchTrains(string $departureStation, string $arrivalStation, string $departDate, string $departTime = "", int $adultCount = 1, int $childCount = 0): array
    {
        return $this->request("GET", "/v1/trains/search", [], [
            "depStation" => $departureStation,
            "arrStation" => $arrivalStation,
            "depDate" => $departDate,
            "depTime" => $departTime ?: "000000",
            "trainType" => self::RAIL_TYPE_SRT,
            "adultCount" => $adultCount,
            "childCount" => $childCount
        ]);
    }

    /**
     * Get seat availability for a specific train
     *
     * @param string $trainNumber Train number
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @return array<string, mixed> Seat availability details
     */
    public function getAvailableSeats(string $trainNumber, string $departureStation, string $arrivalStation, string $departDate): array
    {
        return $this->request("GET", "/v1/trains/{$trainNumber}/seats", [], [
            "depStation" => $departureStation,
            "arrStation" => $arrivalStation,
            "depDate" => $departDate
        ]);
    }

    /**
     * Get fare information for a route
     *
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @param string $trainNumber Optional train number
     * @return array<string, mixed> Fare information
     */
    public function getFareInfo(string $departureStation, string $arrivalStation, string $departDate, string $trainNumber = ""): array
    {
        $params = [
            "depStation" => $departureStation,
            "arrStation" => $arrivalStation,
            "depDate" => $departDate
        ];

        if ($trainNumber !== "") {
            $params["trainNumber"] = $trainNumber;
        }

        return $this->request("GET", "/v1/fares", [], $params);
    }

    /**
     * Get station list for autocomplete/selection
     *
     * @param string $keyword Station name or code
     * @return array<string, mixed> Matching stations
     */
    public function getStations(string $keyword): array
    {
        return $this->request("GET", "/v1/stations", [], [
            "keyword" => $keyword
        ]);
    }

    /**
     * Reserve a train seat
     *
     * @param string $trainNumber Train number
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @param string $departDate Travel date (YYYYMMDD)
     * @param array<string, mixed> $passengers Passenger information array
     * @return array<string, mixed> Reservation confirmation
     */
    public function reserveTrain(string $trainNumber, string $departureStation, string $arrivalStation, string $departDate, array $passengers): array
    {
        return $this->request("POST", "/v1/reservations", [
            "trainNumber" => $trainNumber,
            "depStation" => $departureStation,
            "arrStation" => $arrivalStation,
            "depDate" => $departDate,
            "passengers" => $passengers
        ]);
    }

    /**
     * Cancel a train reservation
     *
     * @param string $reservationId Reservation ID
     * @param string $reason Cancellation reason
     * @return array<string, mixed> Cancellation response
     */
    public function cancelReservation(string $reservationId, string $reason = ""): array
    {
        return $this->request("POST", "/v1/reservations/{$reservationId}/cancel", [
            "reason" => $reason
        ]);
    }

    /**
     * Get reservation details
     *
     * @param string $reservationId Reservation ID
     * @return array<string, mixed> Reservation information
     */
    public function getReservation(string $reservationId): array
    {
        return $this->request("GET", "/v1/reservations/{$reservationId}");
    }

    /**
     * Get member's reservation history
     *
     * @param int $pageNum Page number
     * @param int $pageSize Items per page
     * @return array<string, mixed> Reservation history
     */
    public function getReservationHistory(int $pageNum = 1, int $pageSize = 10): array
    {
        return $this->request("GET", "/v1/members/{$this->MEMBER_ID}/reservations", [], [
            "pageNum" => $pageNum,
            "pageSize" => $pageSize
        ]);
    }

    /**
     * Get discount information
     *
     * @param string $departureStation Departure station code
     * @param string $arrivalStation Arrival station code
     * @return array<string, mixed> Available discounts
     */
    public function getDiscounts(string $departureStation, string $arrivalStation): array
    {
        return $this->request("GET", "/v1/discounts", [], [
            "depStation" => $departureStation,
            "arrStation" => $arrivalStation
        ]);
    }

    /**
     * Get seat class information and prices
     *
     * @return array<string, mixed> Seat class details
     */
    public function getSeatClasses(): array
    {
        return $this->request("GET", "/v1/seat-classes");
    }

    /**
     * Get commonly used routes (popular stations)
     *
     * @return array<string, mixed> Popular routes and stations
     */
    public function getPopularRoutes(): array
    {
        return $this->request("GET", "/v1/popular-routes");
    }
}
