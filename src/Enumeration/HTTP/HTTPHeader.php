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
 * Enumeration class for HTTP headers.
 */
abstract class HTTPHeader
{
    public const ACCEPT = 'Accept';
    public const AcceptCharset = 'Accept-Charset';
    public const AcceptLanguage = 'Accept-Language';
    public const AcceptPatch = 'Accept-Patch';
    public const AcceptPost = 'Accept-Post';
    public const AcceptRanges = 'Accept-Ranges';
    public const AccessControlAllowCredentials = 'Access-Control-Allow-Credentials';
    public const AccessControlAllowHeaders = 'Access-Control-Allow-Headers';
    public const AccessControlAllowMethods = 'Access-Control-Allow-Methods';
    public const AccessControlAllowOrigin = 'Access-Control-Allow-Origin';
    public const AccessControlExposeHeaders = 'Access-Control-Expose-Headers';
    public const AccessControlMaxAge = 'Access-Control-Max-Age';
    public const AccessControlRequestHeaders = 'Access-Control-Request-Headers';
    public const AccessControlRequestMethod = 'Access-Control-Request-Method';
    public const Age = 'Age';
    public const Allow = 'Allow';
    public const AltSvc = 'Alt-Svc';
    public const Authorization = 'Authorization';
    public const CacheControl = 'Cache-Control';
    public const ClearSiteData = 'Clear-Site-Data';
    public const Connection = 'Connection';
    public const ContentDisposition = 'Content-Disposition';
    public const ContentEncoding = 'Content-Encoding';
    public const ContentLanguage = 'Content-Language';
    public const ContentLength = 'Content-Length';
    public const ContentLocation = 'Content-Location';
    public const ContentRange = 'Content-Range';
    public const ContentSecurityPolicy = 'Content-Security-Policy';
    public const ContentSecurityPolicyReportOnly = 'Content-Security-Policy-Report-Only';
    public const ContentType = 'Content-Type';
    public const Cookie = 'Cookie';
    public const CrossOriginEmbedderPolicy = 'Cross-Origin-Embedder-Policy';
    public const CrossOriginOpenerPolicy = 'Cross-Origin-Opener-Policy';
    public const CrossOriginResourcePolicy = 'Cross-Origin-Resource-Policy';
}
