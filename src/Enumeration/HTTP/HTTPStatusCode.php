<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Enumeration;

/**
 * Enumeration class for HTTP status codes.
 */
enum HTTPStatusCode: int
{
    public const A_TIMEOUT_OCCURRED = 524;
    public const ACCEPTED = 202;
    public const ALREADY_REPORTED = 208;
    public const BAD_GATEWAY = 502;
    public const BAD_REQUEST = 400;
    public const BANDWIDTH_LIMIT_EXCEEDED = 509;
    public const CLIENT_CLOSED_REQUEST = 499;
    public const CONFLICT = 409;
    public const CONNECTION_CLOSED_WITHOUT_RESPONSE = 444;
    public const CONNECTION_TIMEOUT = 522;
    public const CONTINUE = 100;
    public const CREATED = 201;
    public const EARLY_HINTS = 103;
    public const EXPECTATION_FAILED = 417;
    public const FAILED_DEPENDENCY = 424;
    public const FORBIDDEN = 403;
    public const FOUND = 302;
    public const GATEWAY_TIMEOUT = 504;
    public const GONE = 410;
    public const HTTP_VERSION_NOT_SUPPORTED = 505;
    public const IM_A_TEAPOT = 418;
    public const IM_USED = 226;
    public const INSUFFICIENT_STORAGE = 507;
    public const INTERNAL_SERVER_ERROR = 500;
    public const LENGTH_REQUIRED = 411;
    public const LOCKED = 423;
    public const LOOP_DETECTED = 508;
    public const METHOD_NOT_ALLOWED = 405;
    public const MISDIRECTED_REQUEST = 421;
    public const MOVED_PERMANENTLY = 301;
    public const MULTI_STATUS = 207;
    public const MULTIPLE_CHOICES = 300;
    public const NETWORK_AUTHENTICATION_REQUIRED = 511;
    public const NETWORK_CONNECT_TIMEOUT_ERROR = 599;
    public const NO_CONTENT = 204;
    public const NON_AUTHORITATIVE_INFORMATION = 203;
    public const NOT_ACCEPTABLE = 406;
    public const NOT_EXTENDED = 510;
    public const NOT_FOUND = 404;
    public const NOT_IMPLEMENTED = 501;
    public const NOT_MODIFIED = 304;
    public const OK = 200;
    public const PARTIAL_CONTENT = 206;
    public const PAYMENT_REQUIRED = 402;
    public const PERMANENT_REDIRECT = 308;
    public const PRECONDITION_FAILED = 412;
    public const PRECONDITION_REQUIRED = 428;
    public const PROCESSING = 102;
    public const PROXY_AUTHENTICATION_REQUIRED = 407;
    public const REQUEST_ENTITY_TOO_LARGE = 413;
    public const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    public const REQUEST_TIMEOUT = 408;
    public const REQUEST_URI_TOO_LONG = 414;
    public const REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    public const RESET_CONTENT = 205;
    public const SEE_OTHER = 303;
    public const SERVICE_UNAVAILABLE = 503;
    public const SWITCHING_PROTOCOLS = 101;
    public const TEMPORARY_REDIRECT = 307;
    public const TOO_EARLY = 425;
    public const TOO_MANY_REQUESTS = 429;
    public const UNAUTHORIZED = 401;
    public const UNAVAILABLE_FOR_LEGAL_REASONS = 451;
    public const UNPROCESSABLE_ENTITY = 422;
    public const UNSUPPORTED_MEDIA_TYPE = 415;
    public const UNUSED = 306;
    public const UPGRADE_REQUIRED = 426;
    public const USE_PROXY = 305;
    public const VARIANT_ALSO_NEGOTIATES = 506;
    public const WEBSERVER_IS_RETURNING_AN_UNKNOWN_ERROR = 520;

    public function getMessage(): string
    {
        return match ($this->value) {
            HTTPStatusCode::CONTINUE => 'Continue',
            HTTPStatusCode::SWITCHING_PROTOCOLS => 'Switching Protocols',
            HTTPStatusCode::PROCESSING => 'Processing',
            HTTPStatusCode::EARLY_HINTS => 'Early Hints',
            HTTPStatusCode::OK => 'OK',
            HTTPStatusCode::CREATED => 'Created',
            HTTPStatusCode::ACCEPTED => 'Accepted',
            HTTPStatusCode::NON_AUTHORITATIVE_INFORMATION => 'Non-Authoritative Information',
            HTTPStatusCode::NO_CONTENT => 'No Content',
            HTTPStatusCode::RESET_CONTENT => 'Reset Content',
            HTTPStatusCode::PARTIAL_CONTENT => 'Partial Content',
            HTTPStatusCode::MULTI_STATUS => 'Multi-Status',
            HTTPStatusCode::ALREADY_REPORTED => 'Already Reported',
            HTTPStatusCode::IM_USED => 'IM Used',
            HTTPStatusCode::MULTIPLE_CHOICES => 'Multiple Choices',
            HTTPStatusCode::MOVED_PERMANENTLY => 'Moved Permanently',
            HTTPStatusCode::FOUND => 'Found',
            HTTPStatusCode::SEE_OTHER => 'See Other',
            HTTPStatusCode::NOT_MODIFIED => 'Not Modified',
            HTTPStatusCode::USE_PROXY => 'Use Proxy',
            HTTPStatusCode::TEMPORARY_REDIRECT => 'Temporary Redirect',
            HTTPStatusCode::PERMANENT_REDIRECT => 'Permanent Redirect',
            HTTPStatusCode::BAD_REQUEST => 'Bad Request',
            HTTPStatusCode::UNAUTHORIZED => 'Unauthorized',
            HTTPStatusCode::PAYMENT_REQUIRED => 'Payment Required',
            HTTPStatusCode::FORBIDDEN => 'Forbidden',
            HTTPStatusCode::NOT_FOUND => 'Not Found',
            HTTPStatusCode::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            HTTPStatusCode::NOT_ACCEPTABLE => 'Not Acceptable',
            HTTPStatusCode::PROXY_AUTHENTICATION_REQUIRED => 'Proxy Authentication Required',
            HTTPStatusCode::REQUEST_TIMEOUT => 'Request Timeout',
            HTTPStatusCode::CONFLICT => 'Conflict',
            HTTPStatusCode::GONE => 'Gone',
            HTTPStatusCode::LENGTH_REQUIRED => 'Length Required',
            HTTPStatusCode::PRECONDITION_FAILED => 'Precondition Failed',
            HTTPStatusCode::REQUEST_ENTITY_TOO_LARGE => 'Request Entity Too Large',
            HTTPStatusCode::REQUEST_URI_TOO_LONG => 'Request-URI Too Long',
            HTTPStatusCode::UNSUPPORTED_MEDIA_TYPE => 'Unsupported Media Type',
            HTTPStatusCode::REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
            HTTPStatusCode::EXPECTATION_FAILED => 'Expectation Failed',
            HTTPStatusCode::IM_A_TEAPOT => 'I\'m a teapot',
            HTTPStatusCode::MISDIRECTED_REQUEST => 'Misdirected Request',
            HTTPStatusCode::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',
            HTTPStatusCode::LOCKED => 'Locked',
            HTTPStatusCode::FAILED_DEPENDENCY => 'Failed Dependency',
            HTTPStatusCode::TOO_EARLY => 'Too Early',
            HTTPStatusCode::UPGRADE_REQUIRED => 'Upgrade Required',
            HTTPStatusCode::PRECONDITION_REQUIRED => 'Precondition Required',
            HTTPStatusCode::TOO_MANY_REQUESTS => 'Too Many Requests',
            HTTPStatusCode::REQUEST_HEADER_FIELDS_TOO_LARGE => 'Request Header Fields Too Large',
            HTTPStatusCode::CONNECTION_CLOSED_WITHOUT_RESPONSE => 'Connection Closed Without Response',
            HTTPStatusCode::UNAVAILABLE_FOR_LEGAL_REASONS => 'Unavailable For Legal Reasons',
            HTTPStatusCode::CLIENT_CLOSED_REQUEST => 'Client Closed Request',
            HTTPStatusCode::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            HTTPStatusCode::NOT_IMPLEMENTED => 'Not Implemented',
            HTTPStatusCode::BAD_GATEWAY => 'Bad Gateway',
            HTTPStatusCode::SERVICE_UNAVAILABLE => 'Service Unavailable',
            HTTPStatusCode::GATEWAY_TIMEOUT => 'Gateway Timeout',
            HTTPStatusCode::HTTP_VERSION_NOT_SUPPORTED => 'HTTP Version Not Supported',
            HTTPStatusCode::VARIANT_ALSO_NEGOTIATES => 'Variant Also Negotiates',
            HTTPStatusCode::INSUFFICIENT_STORAGE => 'Insufficient Storage',
            HTTPStatusCode::LOOP_DETECTED => 'Loop Detected',
            HTTPStatusCode::BANDWIDTH_LIMIT_EXCEEDED => 'Bandwidth Limit Exceeded',
            HTTPStatusCode::NOT_EXTENDED => 'Not Extended',
            HTTPStatusCode::NETWORK_AUTHENTICATION_REQUIRED => 'Network Authentication Required',
            HTTPStatusCode::NETWORK_CONNECT_TIMEOUT_ERROR => 'Network Connect Timeout Error',
            HTTPStatusCode::WEBSERVER_IS_RETURNING_AN_UNKNOWN_ERROR => 'Webserver is Returning an Unknown Error',
            HTTPStatusCode::CONNECTION_TIMEOUT => 'Connection Timeout',
            HTTPStatusCode::A_TIMEOUT_OCCURRED => 'A Timeout Occurred',
        };
    }
}
