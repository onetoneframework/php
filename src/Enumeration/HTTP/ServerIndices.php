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
 * Enumeration class for HTTP server indices.
 *
 * Provides named constants for all standard and extended PHP $_SERVER superglobal keys,
 * including CGI variables, HTTP headers, reverse-proxy headers, and server-specific indices.
 */
abstract class ServerIndices
{
    /**
     * The filename of the currently executing script, relative to the document root.
     * e.g. /index.php
     */
    public const CURRENTLY_EXECUTING_SCRIPT_FILENAME = 'PHP_SELF';

    /**
     * The URI used to access the page; includes the path, query string, etc.
     * e.g. /page.php?id=1
     */
    public const REQUEST_URI = 'REQUEST_URI';

    /**
     * The URI of the document as provided by the web server (Nginx-specific).
     * Similar to REQUEST_URI but may differ when rewriting is in use.
     */
    public const DOCUMENT_URI = 'DOCUMENT_URI';

    /**
     * The absolute pathname of the currently executing script on the filesystem.
     * e.g. /var/www/html/index.php
     */
    public const SCRIPT_ABSOLUTE_PATHNAME = 'SCRIPT_FILENAME';

    /**
     * Contains the current script's path.
     * e.g. /index.php
     */
    public const SCRIPT_NAME = 'SCRIPT_NAME';

    /**
     * The full URI to the current page, including scheme, host, and path.
     * e.g. https://example.com/page.php
     */
    public const SCRIPT_URI = 'SCRIPT_URI';

    /**
     * Any client-provided path information following the script filename in the URL.
     * e.g. /extra/path in /script.php/extra/path
     */
    public const PATH_INFO = 'PATH_INFO';

    /**
     * The filesystem-translated version of PATH_INFO.
     * e.g. /var/www/html/extra/path
     */
    public const PATH_TRANSLATED = 'PATH_TRANSLATED';

    /**
     * The document root directory under which the current script is executing,
     * as defined in the server's configuration file.
     */
    public const DOCUMENT_ROOT_DIRECTORY = 'DOCUMENT_ROOT';

    /**
     * Original path info before any URL rewriting (used on some server configurations).
     */
    public const ORIG_PATH_INFO = 'ORIG_PATH_INFO';

    /**
     * The original script name before any internal redirects.
     */
    public const ORIG_SCRIPT_NAME = 'ORIG_SCRIPT_NAME';

    /**
     * Which request method was used to access the page.
     * e.g. GET, POST, PUT, DELETE, PATCH, HEAD, OPTIONS
     */
    public const REQUEST_METHOD = 'REQUEST_METHOD';

    /**
     * The query string, if any, via which the page was accessed.
     * e.g. id=1&name=foo
     */
    public const QUERY_STRING = 'QUERY_STRING';

    /**
     * The Unix timestamp of the start of the request.
     */
    public const REQUEST_TIMESTAMP = 'REQUEST_TIME';

    /**
     * The float timestamp of the start of the request, with microsecond precision.
     */
    public const REQUEST_TIME_FLOAT = 'REQUEST_TIME_FLOAT';

    /**
     * The scheme used for the request.
     * e.g. http or https
     */
    public const REQUEST_SCHEME = 'REQUEST_SCHEME';

    /**
     * Set to a non-empty value if the script was queried through the HTTPS protocol.
     */
    public const HTTPS = 'HTTPS';

    /**
     * The raw length of the request body in bytes (from the Content-Length header).
     */
    public const CONTENT_LENGTH = 'CONTENT_LENGTH';

    /**
     * The MIME type of the request body (from the Content-Type header).
     * e.g. application/json, multipart/form-data
     */
    public const CONTENT_TYPE = 'CONTENT_TYPE';

    /**
     * Allows overriding the HTTP method via a custom header (e.g. from HTML forms).
     * e.g. PUT or DELETE
     */
    public const X_HTTP_METHOD_OVERRIDE = 'X-HTTP-METHOD-OVERRIDE';

    /**
     * Array of arguments passed to the script when run from the command line.
     */
    public const PASSED_ARRAY_ARGUMENTS = 'argv';

    /**
     * Contains the number of command-line parameters passed to the script.
     */
    public const PASSED_ARGUMENTS_COUNT = 'argc';

    /**
     * What revision of the CGI specification the server is using.
     * e.g. CGI/1.1
     */
    public const CGI_REVISION = 'GATEWAY_INTERFACE';

    /**
     * The IP address of the server under which the current script is executing.
     */
    public const SERVER_IP_ADDRESS = 'SERVER_ADDR';

    /**
     * The name of the server host under which the current script is executing.
     * e.g. www.example.com
     */
    public const SERVER_HOST_NAME = 'SERVER_NAME';

    /**
     * Server identification string, given in the headers when responding to requests.
     * e.g. Apache/2.4.41 (Ubuntu)
     */
    public const SERVER_SOFTWARE_NAME = 'SERVER_SOFTWARE';

    /**
     * Name and revision of the information protocol via which the page was requested.
     * e.g. HTTP/1.1 or HTTP/2
     */
    public const SERVER_PROTOCOL = 'SERVER_PROTOCOL';

    /**
     * The port on the server machine being used by the web server for communication.
     * e.g. 80 or 443
     */
    public const SERVER_PORT = 'SERVER_PORT';

    /**
     * The HTML from the server identifying itself, if ServerSignature is enabled.
     */
    public const SERVER_SIGNATURE = 'SERVER_SIGNATURE';

    /**
     * The server administrator's email address, as configured in the web server config.
     */
    public const SERVER_ADMIN = 'SERVER_ADMIN';

    /**
     * The role of the FastCGI process handling the request.
     * e.g. RESPONDER, AUTHORIZER, FILTER
     */
    public const FCGI_ROLE = 'FCGI_ROLE';

    /**
     * The IP address from which the user is viewing the current page.
     */
    public const REMOTE_IP_ADDRESS = 'REMOTE_ADDR';

    /**
     * The hostname from which the user is viewing the current page.
     * Requires HostnameLookups to be enabled; otherwise equals REMOTE_ADDR.
     */
    public const REMOTE_HOST_NAME = 'REMOTE_HOST';

    /**
     * The port being used on the user's machine to communicate with the web server.
     */
    public const REMOTE_PORT = 'REMOTE_PORT';

    /**
     * The authenticated user (set after successful HTTP authentication).
     */
    public const REMOTE_USER = 'REMOTE_USER';

    /**
     * The authenticated user after an internal redirect (e.g. via ErrorDocument).
     */
    public const REDIRECT_REMOTE_USER = 'REDIRECT_REMOTE_USER';

    /**
     * The username provided by the client for HTTP Basic authentication.
     */
    public const PHP_AUTH_USER = 'PHP_AUTH_USER';

    /**
     * The password provided by the client for HTTP Basic authentication.
     */
    public const PHP_AUTH_PW = 'PHP_AUTH_PW';

    /**
     * The raw Digest authentication string sent by the client (HTTP Digest auth only).
     */
    public const PHP_AUTH_DIGEST = 'PHP_AUTH_DIGEST';

    /**
     * The type of HTTP authentication being used.
     * e.g. Basic or Digest
     */
    public const AUTH_TYPE = 'AUTH_TYPE';

    /**
     * The password provided during HTTP authentication (server-specific, e.g. Apache).
     */
    public const AUTH_PASSWORD = 'AUTH_PASSWORD';

    /**
     * The Host header sent by the client, identifying the target domain and optional port.
     * e.g. www.example.com or www.example.com:8080
     */
    public const HTTP_HOST = 'HTTP_HOST';

    /**
     * The User-Agent string identifying the client's browser and operating system.
     */
    public const HTTP_USER_AGENT = 'HTTP_USER_AGENT';

    /**
     * The Accept header indicating media types the client can process.
     * e.g. text/html,application/xhtml+xml,application/json
     */
    public const HTTP_ACCEPT = 'HTTP_ACCEPT';

    /**
     * The Accept-Language header indicating the client's preferred language(s).
     * e.g. en-US,en;q=0.9,ko;q=0.8
     */
    public const HTTP_ACCEPT_LANGUAGE = 'HTTP_ACCEPT_LANGUAGE';

    /**
     * The Accept-Encoding header indicating compression algorithms the client supports.
     * e.g. gzip, deflate, br
     */
    public const HTTP_ACCEPT_ENCODING = 'HTTP_ACCEPT_ENCODING';

    /**
     * The Accept-Charset header indicating character sets the client can handle.
     * e.g. utf-8, iso-8859-1
     */
    public const HTTP_ACCEPT_CHARSET = 'HTTP_ACCEPT_CHARSET';

    /**
     * The Content-Type header of the request body sent by the client.
     * e.g. application/json, multipart/form-data
     */
    public const HTTP_CONTENT_TYPE = 'HTTP_CONTENT_TYPE';

    /**
     * The Referer header containing the URL of the page that linked to this request.
     */
    public const HTTP_REFERER = 'HTTP_REFERER';

    /**
     * The Origin header used for CORS, indicating where the request originated.
     * e.g. https://example.com
     */
    public const HTTP_ORIGIN = 'HTTP_ORIGIN';

    /**
     * The Connection header indicating whether the connection should stay alive.
     * e.g. keep-alive or close
     */
    public const HTTP_CONNECTION = 'HTTP_CONNECTION';

    /**
     * The Cache-Control header specifying caching directives.
     * e.g. no-cache, max-age=0
     */
    public const HTTP_CACHE_CONTROL = 'HTTP_CACHE_CONTROL';

    /**
     * The Cookie header containing all cookies sent by the client as a raw string.
     */
    public const HTTP_COOKIE = 'HTTP_COOKIE';

    /**
     * The Pragma header, commonly used for backward-compatible cache control.
     * e.g. no-cache
     */
    public const HTTP_PRAGMA = 'HTTP_PRAGMA';

    /**
     * The Authorization header containing credentials for HTTP authentication.
     * e.g. Bearer <token> or Basic <base64>
     */
    public const HTTP_AUTHORIZATION = 'HTTP_AUTHORIZATION';

    /**
     * The If-Modified-Since header; allows conditional requests based on modification date.
     */
    public const HTTP_IF_MODIFIED_SINCE = 'HTTP_IF_MODIFIED_SINCE';

    /**
     * The If-None-Match header; allows conditional requests using ETags.
     */
    public const HTTP_IF_NONE_MATCH = 'HTTP_IF_NONE_MATCH';

    /**
     * The Range header indicating the byte range(s) the client is requesting.
     * e.g. bytes=0-1023
     */
    public const HTTP_RANGE = 'HTTP_RANGE';

    /**
     * The Transfer-Encoding header specifying the encoding applied to the request body.
     * e.g. chunked
     */
    public const HTTP_TRANSFER_ENCODING = 'HTTP_TRANSFER_ENCODING';

    /**
     * The TE header specifying the transfer encodings the client is willing to accept.
     */
    public const HTTP_TE = 'HTTP_TE';

    /**
     * The DNT (Do Not Track) header sent by browsers respecting user privacy preferences.
     * Value is "1" when tracking should be disabled, "0" when allowed.
     */
    public const HTTP_DNT = 'HTTP_DNT';

    /**
     * The Upgrade-Insecure-Requests header indicating the client prefers a secure response.
     * Value is "1" when the client supports upgrading to HTTPS.
     */
    public const HTTP_UPGRADE_INSECURE_REQUESTS = 'HTTP_UPGRADE_INSECURE_REQUESTS';

    /**
     * The Sec-Fetch-Site header indicating the relationship between request origin and target.
     * e.g. same-origin, same-site, cross-site, none
     */
    public const HTTP_SEC_FETCH_SITE = 'HTTP_SEC_FETCH_SITE';

    /**
     * The Sec-Fetch-Mode header indicating the request mode.
     * e.g. navigate, cors, no-cors, same-origin, websocket
     */
    public const HTTP_SEC_FETCH_MODE = 'HTTP_SEC_FETCH_MODE';

    /**
     * The Sec-Fetch-User header indicating whether the request was triggered by user action.
     * Value is "?1" when triggered by a user gesture.
     */
    public const HTTP_SEC_FETCH_USER = 'HTTP_SEC_FETCH_USER';

    /**
     * The Sec-Fetch-Dest header indicating the intended destination of the request.
     * e.g. document, script, style, image, font, worker
     */
    public const HTTP_SEC_FETCH_DEST = 'HTTP_SEC_FETCH_DEST';

    /**
     * The Sec-CH-UA header providing the client's browser brand and version information.
     * e.g. "Chromium";v="112", "Google Chrome";v="112"
     */
    public const HTTP_SEC_CH_UA = 'HTTP_SEC_CH_UA';

    /**
     * The Sec-CH-UA-Mobile client hint indicating whether the browser is on a mobile device.
     * Value is "?1" for mobile, "?0" for non-mobile.
     */
    public const HTTP_SEC_CH_UA_MOBILE = 'HTTP_SEC_CH_UA_MOBILE';

    /**
     * The Sec-CH-UA-Platform client hint indicating the client's operating system platform.
     * e.g. "Windows", "macOS", "Android"
     */
    public const HTTP_SEC_CH_UA_PLATFORM = 'HTTP_SEC_CH_UA_PLATFORM';

    /**
     * The X-Requested-With header, commonly set to "XMLHttpRequest" by Ajax libraries
     * to distinguish Ajax requests from regular page loads.
     */
    public const HTTP_X_REQUESTED_WITH = 'HTTP_X_REQUESTED_WITH';

    /**
     * A custom X-CSRF-Token header used for CSRF protection in API requests.
     */
    public const HTTP_X_CSRF_TOKEN = 'HTTP_X_CSRF_TOKEN';

    /**
     * The X-Forwarded-For header containing the originating IP address(es) of the client
     * through one or more proxies or load balancers.
     * e.g. 203.0.113.1, 70.41.3.18
     */
    public const HTTP_X_FORWARDED_FOR = 'HTTP_X_FORWARDED_FOR';

    /**
     * The X-Forwarded-Host header indicating the original Host requested by the client.
     */
    public const HTTP_X_FORWARDED_HOST = 'HTTP_X_FORWARDED_HOST';

    /**
     * The X-Forwarded-Proto header indicating the protocol used by the client (http/https).
     */
    public const HTTP_X_FORWARDED_PROTO = 'HTTP_X_FORWARDED_PROTO';

    /**
     * The X-Forwarded-Prefix header indicating the URL prefix stripped by a reverse proxy.
     */
    public const HTTP_X_FORWARDED_PREFIX = 'HTTP_X_FORWARDED_PREFIX';

    /**
     * The X-Real-IP header set by reverse proxies (e.g. Nginx) with the real client IP.
     */
    public const HTTP_X_REAL_IP = 'HTTP_X_REAL_IP';

    /**
     * The X-Cluster-Client-IP header used in clustered environments to pass the real client IP.
     */
    public const HTTP_X_CLUSTER_CLIENT_IP = 'HTTP_X_CLUSTER_CLIENT_IP';

    /**
     * The Client-IP header sometimes set by proxies to pass along the originating client IP.
     */
    public const HTTP_CLIENT_IP = 'HTTP_CLIENT_IP';

    /**
     * The Forwarded header (RFC 7239) as an alternative to X-Forwarded-For.
     * e.g. for=203.0.113.1;proto=https;host=example.com
     */
    public const HTTP_FORWARDED = 'HTTP_FORWARDED';

    /**
     * The X-Forwarded-For header as set by AWS Elastic Load Balancer.
     */
    public const HEADER_X_FORWARDED_AWS_ELB = 'HEADER_X_FORWARDED_AWS_ELB';

    /**
     * The X-Forwarded-For header as set by Traefik reverse proxy.
     */
    public const HEADER_X_FORWARDED_TRAEFIK = 'HEADER_X_FORWARDED_TRAEFIK';

    /**
     * The X-Forwarded-Proto header as added by a reverse proxy (non-HTTP_ prefixed form).
     */
    public const HEADER_X_FORWARDED_PROTO = 'HEADER_X_FORWARDED_PROTO';

    /**
     * The X-Forwarded-Port header indicating the original port used by the client.
     */
    public const HEADER_X_FORWARDED_PORT = 'HEADER_X_FORWARDED_PORT';

    /**
     * The X-Forwarded-Host header in its non-HTTP_ prefixed form.
     */
    public const HEADER_X_FORWARDED_HOST = 'HEADER_X_FORWARDED_HOST';

    /**
     * The X-Forwarded-For header in its non-HTTP_ prefixed form.
     */
    public const HEADER_X_FORWARDED_FOR = 'HEADER_X_FORWARDED_FOR';

    /**
     * The Cloudflare CF-Connecting-IP header containing the real visitor IP address
     * as determined by Cloudflare's edge network.
     */
    public const HTTP_CF_CONNECTING_IP = 'HTTP-CF-CONNECTING-IP';

    /**
     * The SSL_HTTPS header used by some proxies to indicate a secure HTTPS connection.
     */
    public const HTTP_SSL_HTTPS = 'HTTP_SSL_HTTPS';

    /**
     * The X-Wap-Profile header sent by some mobile browsers pointing to a device profile URL.
     */
    public const HTTP_X_WAP_PROFILE = 'HTTP_X_WAP_PROFILE';

    /**
     * The Device-Stock-UA header sent by some proxy browsers with the original device UA.
     */
    public const HTTP_DEVICE_STOCK_UA = 'HTTP_DEVICE_STOCK_UA';

    /**
     * The X-UCBrowser-Device-UA header sent by UC Browser with the underlying device UA.
     */
    public const HTTP_X_UCBROWSER_DEVICE_UA = 'HTTP_X_UCBROWSER_DEVICE_UA';

    /**
     * The X-Bolt-Phone-UA header sent by the Bolt browser with device identification info.
     */
    public const HTTP_X_BOLT_PHONE_UA = 'HTTP_X_BOLT_PHONE_UA';

    /**
     * The X-Skyfire-Phone header sent by the Skyfire browser with device UA information.
     */
    public const HTTP_X_SKYFIRE_PHONE = 'HTTP_X_SKYFIRE_PHONE';

    /**
     * The X-OperaMini-Phone-UA header sent by Opera Mini with the original device UA.
     */
    public const HTTP_X_OPERAMINI_PHONE_UA = 'HTTP_X_OPERAMINI_PHONE_UA';

    /**
     * The X-Rewrite-URL header used by IIS to pass the original URL before rewriting.
     */
    public const HTTP_X_REWRITE_URL = 'HTTP_X_REWRITE_URL';

    /**
     * The X-Original-URL header set by IIS URL Rewrite module with the pre-rewrite URL.
     */
    public const HTTP_X_ORIGINAL_URL = 'HTTP_X_ORIGINAL_URL';

    /**
     * Set to "1" by IIS URL Rewrite module when the URL has been rewritten.
     */
    public const IIS_WAS_URL_REWRITTEN = 'IIS_WasUrlRewritten';

    /**
     * The unencoded form of the original URL (IIS-specific), before percent-decoding.
     */
    public const UNENCODED_URL = 'UNENCODED_URL';

    /**
     * The If-None-Match header value used for ETag-based cache validation.
     * Note: Named "IF_NONE_MATH" to preserve backward compatibility.
     */
    public const IF_NONE_MATH = 'If-None-Match';

    /**
     * The Pragma request header, typically "no-cache" for backward-compatible cache control.
     */
    public const PRAGMA = 'Pragma';

    /**
     * The HTTP status code set after an internal redirect (e.g. via ErrorDocument).
     * e.g. 200, 404
     */
    public const REDIRECT_STATUS = 'REDIRECT_STATUS';

    /**
     * The URL that was internally redirected to (set by Apache after an ErrorDocument redirect).
     */
    public const REDIRECT_URL = 'REDIRECT_URL';

    /**
     * The local IP address of the server interface handling the request (IIS-specific).
     */
    public const LOCAL_ADDR = 'LOCAL_ADDR';

    /**
     * The physical filesystem path to the root of the IIS application.
     */
    public const APPL_PHYSICAL_PATH = 'APPL_PHYSICAL_PATH';

    /**
     * The metabase path to the IIS application configuration.
     */
    public const APPL_MD_PATH = 'APPL_MD_PATH';

    /**
     * The path to the IIS application pool configuration file.
     */
    public const APP_POOL_CONFIG = 'APP_POOL_CONFIG';

    /**
     * The identifier of the IIS application pool handling the request.
     */
    public const APP_POOL_ID = 'APP_POOL_ID';

    /**
     * The unique ID of the IIS server instance handling the request.
     */
    public const INSTANCE_ID = 'INSTANCE_ID';

    /**
     * The metabase path for the IIS server instance handling the request.
     */
    public const INSTANCE_META_PATH = 'INSTANCE_META_PATH';
}
