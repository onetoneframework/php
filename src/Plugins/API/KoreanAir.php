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
use function sprintf;
use function time;
use function hash_hmac;
use function base64_encode;
use function json_encode;
use function json_decode;
use function is_string;
use Exception;

/**
 * Korean Air (KAL) API client for flight reservations and seat management
 * Handles flight searches, seat availability, fares, and booking operations
 */
class KoreanAir
{
    private string $API_KEY;
    private string $AGENCY_CODE;
    private const API_GATEWAY = "https://api.koreanair.com";
    public const CABIN_ECONOMY = "Y";
    public const CABIN_BUSINESS = "J";
    public const CABIN_FIRST = "F";

    /**
     * Initialize Korean Air API client with credentials
     */
    public function __construct(string $apiKey, string $agencyCode = "")
    {
        $this->API_KEY = $apiKey;
        $this->AGENCY_CODE = $agencyCode;
    }

    /**
     * Make an HTTP request to Korean Air API
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE)
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
            $requestURL->setParameters($queryParams);

            $timestamp = (string) time();
            $signature = $this->generateSignature($method, $endpoint, $timestamp);

            $cURL = new ClientURL($requestURL);
            $cURL->option->setURL($requestURL)
                ->setMethod($method)
                ->setHeaders([
                    "Authorization" => "Bearer " . $this->API_KEY,
                    "X-API-Signature" => $signature,
                    "X-Request-Timestamp" => $timestamp,
                    "Content-Type" => "application/json",
                    "Accept" => "application/json",
                    "X-Agency-Code" => $this->AGENCY_CODE
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
            throw new Exception("Korean Air API request failed: " . $e->getMessage());
        }
    }

    /**
     * Generate API signature for request authentication
     *
     * @param string $method HTTP method
     * @param string $endpoint Endpoint path
     * @param string $timestamp Request timestamp
     * @return string Base64 encoded signature
     */
    private function generateSignature(string $method, string $endpoint, string $timestamp): string
    {
        $stringToSign = sprintf("%s\n%s\n%s", $method, $endpoint, $timestamp);
        $signature = hash_hmac('sha256', $stringToSign, $this->API_KEY, true);
        return base64_encode($signature);
    }

    /**
     * Search available flights between two airports
     *
     * @param string $departureCode Departure airport code (e.g., "PUS" for Busan)
     * @param string $arrivalCode Arrival airport code (e.g., "ICN" for Incheon)
     * @param string $departDate Departure date (YYYY-MM-DD)
     * @param string $returnDate Return date for round trips (YYYY-MM-DD, optional)
     * @param int $adultCount Number of adult passengers
     * @param int $childCount Number of child passengers (ages 2-11)
     * @param int $infantCount Number of infant passengers (under 2)
     * @param string $cabin Cabin class (Y=Economy, J=Business, F=First)
     * @return array<string, mixed> Available flights with prices and seat availability
     */
    public function searchFlights(string $departureCode, string $arrivalCode, string $departDate, string $returnDate = "", int $adultCount = 1, int $childCount = 0, int $infantCount = 0, string $cabin = self::CABIN_ECONOMY): array
    {
        $params = [
            "depAirport" => $departureCode,
            "arrAirport" => $arrivalCode,
            "depDate" => $departDate,
            "adultCount" => $adultCount,
            "childCount" => $childCount,
            "infantCount" => $infantCount,
            "cabin" => $cabin
        ];

        if ($returnDate !== "") {
            $params["returnDate"] = $returnDate;
            $params["tripType"] = "roundTrip";
        } else {
            $params["tripType"] = "oneWay";
        }

        return $this->request("GET", "/v2/flights/search", [], $params);
    }

    /**
     * Get available seats for a specific flight
     *
     * @param string $flightNumber Flight number
     * @param string $departDate Flight departure date (YYYY-MM-DD)
     * @param string $cabin Cabin class (Y=Economy, J=Business, F=First)
     * @return array<string, mixed> Seat map with availability status
     */
    public function getAvailableSeats(string $flightNumber, string $departDate, string $cabin = self::CABIN_ECONOMY): array
    {
        return $this->request("GET", "/v2/flights/{$flightNumber}/seats", [], [
            "depDate" => $departDate,
            "cabin" => $cabin
        ]);
    }

    /**
     * Get fare information for a flight
     *
     * @param string $flightNumber Flight number
     * @param string $departDate Flight departure date (YYYY-MM-DD)
     * @param string $cabin Cabin class
     * @param int $adultCount Number of adults
     * @param int $childCount Number of children
     * @return array<string, mixed> Detailed fare breakdown and taxes
     */
    public function getFareInfo(string $flightNumber, string $departDate, string $cabin, int $adultCount = 1, int $childCount = 0): array
    {
        return $this->request("GET", "/v2/flights/{$flightNumber}/fares", [], [
            "depDate" => $departDate,
            "cabin" => $cabin,
            "adultCount" => $adultCount,
            "childCount" => $childCount
        ]);
    }

    /**
     * Get airport information
     *
     * @param string $keyword Airport name or code
     * @return array<string, mixed> Matching airports with codes
     */
    public function getAirports(string $keyword): array
    {
        return $this->request("GET", "/v2/airports", [], [
            "keyword" => $keyword
        ]);
    }

    /**
     * Get airline routes
     *
     * @param string $departureCode Departure airport code
     * @param string $arrivalCode Arrival airport code
     * @return array<string, mixed> Available routes and frequencies
     */
    public function getRoutes(string $departureCode, string $arrivalCode): array
    {
        return $this->request("GET", "/v2/routes", [], [
            "depAirport" => $departureCode,
            "arrAirport" => $arrivalCode
        ]);
    }

    /**
     * Make a flight reservation
     *
     * @param array<string, mixed> $bookingData Booking information
     * @return array<string, mixed> Booking confirmation with reference number (PNR)
     */
    public function bookFlight(array $bookingData): array
    {
        return $this->request("POST", "/v2/bookings", $bookingData);
    }

    /**
     * Get booking details
     *
     * @param string $bookingReference Booking reference number (PNR)
     * @return array<string, mixed> Complete booking information and itinerary
     */
    public function getBooking(string $bookingReference): array
    {
        return $this->request("GET", "/v2/bookings/{$bookingReference}");
    }

    /**
     * Modify/Reissue a booking (change date, seats, etc.)
     *
     * @param string $bookingReference Booking reference number (PNR)
     * @param array<string, mixed> $modificationData Changes to apply
     * @return array<string, mixed> Updated booking confirmation
     */
    public function modifyBooking(string $bookingReference, array $modificationData): array
    {
        return $this->request("PUT", "/v2/bookings/{$bookingReference}", $modificationData);
    }

    /**
     * Cancel a booking
     *
     * @param string $bookingReference Booking reference number (PNR)
     * @param string $reason Cancellation reason
     * @return array<string, mixed> Cancellation confirmation with refund information
     */
    public function cancelBooking(string $bookingReference, string $reason = ""): array
    {
        return $this->request("POST", "/v2/bookings/{$bookingReference}/cancel", [
            "reason" => $reason
        ]);
    }

    /**
     * Select specific seats for a booking
     *
     * @param string $bookingReference Booking reference number
     * @param array<string, string> $seatSelections Array of passenger ID to seat number mapping
     * @return array<string, mixed> Seat selection confirmation
     */
    public function selectSeats(string $bookingReference, array $seatSelections): array
    {
        return $this->request("POST", "/v2/bookings/{$bookingReference}/seat-selection", [
            "seats" => $seatSelections
        ]);
    }

    /**
     * Get special services available (meals, seats, baggage, etc.)
     *
     * @param string $flightNumber Flight number
     * @param string $departDate Flight departure date
     * @return array<string, mixed> Available ancillary services with pricing
     */
    public function getSpecialServices(string $flightNumber, string $departDate): array
    {
        return $this->request("GET", "/v2/flights/{$flightNumber}/services", [], [
            "depDate" => $departDate
        ]);
    }

    /**
     * Add special services to a booking (ancillaries)
     *
     * @param string $bookingReference Booking reference number
     * @param array<string, mixed> $services Services to add
     * @return array<string, mixed> Updated booking with services
     */
    public function addServices(string $bookingReference, array $services): array
    {
        return $this->request("POST", "/v2/bookings/{$bookingReference}/services", [
            "services" => $services
        ]);
    }

    /**
     * Check member frequent flyer status and miles
     *
     * @param string $memberNumber Frequent flyer membership number
     * @return array<string, mixed> Member status and accumulated miles
     */
    public function getMemberStatus(string $memberNumber): array
    {
        return $this->request("GET", "/v2/members/{$memberNumber}");
    }

    /**
     * Apply frequent flyer miles to booking
     *
     * @param string $bookingReference Booking reference number
     * @param string $memberNumber Frequent flyer membership number
     * @param int $milesToUse Miles to apply
     * @return array<string, mixed> Updated booking with miles discount
     */
    public function applyFrequentFlyerMiles(string $bookingReference, string $memberNumber, int $milesToUse): array
    {
        return $this->request("POST", "/v2/bookings/{$bookingReference}/apply-miles", [
            "memberNumber" => $memberNumber,
            "miles" => $milesToUse
        ]);
    }

    /**
     * Get baggage allowance information
     *
     * @param string $flightNumber Flight number
     * @param string $cabin Cabin class
     * @return array<string, mixed> Baggage allowance by fare type
     */
    public function getBaggageAllowance(string $flightNumber, string $cabin): array
    {
        return $this->request("GET", "/v2/flights/{$flightNumber}/baggage", [], [
            "cabin" => $cabin
        ]);
    }

    /**
     * Get check-in information and options
     *
     * @param string $bookingReference Booking reference number
     * @return array<string, mixed> Check-in details and available options
     */
    public function getCheckInInfo(string $bookingReference): array
    {
        return $this->request("GET", "/v2/bookings/{$bookingReference}/check-in");
    }

    /**
     * Get special promotions and fares
     *
     * @param string $departureCode Departure airport code
     * @param string $arrivalCode Arrival airport code
     * @return array<string, mixed> Current promotions and special fares
     */
    public function getPromotions(string $departureCode, string $arrivalCode): array
    {
        return $this->request("GET", "/v2/promotions", [], [
            "depAirport" => $departureCode,
            "arrAirport" => $arrivalCode
        ]);
    }

    /**
     * Get popular/trending routes
     *
     * @return array<string, mixed> Most searched and booked routes
     */
    public function getTrendingRoutes(): array
    {
        return $this->request("GET", "/v2/trending-routes");
    }
}
