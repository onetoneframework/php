<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Framework\Middleware;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Component\TrustedProxies;
use Clover\Framework\Contract\MiddlewareInterface;
use Clover\Framework\Contract\RequestHandlerInterface;
use function explode;
use function filter_var;
use function is_string;
use function strtolower;
use function trim;
use const FILTER_VALIDATE_BOOL;

/**
 * Applies baseline HTTP response headers for production-style deployments.
 *
 * Headers already set on the downstream {@see Response} are left unchanged so controllers can override.
 * Strict CSP and HSTS are opt-in via environment variables to avoid breaking local development and CDN-heavy pages.
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
	private const FORWARDED_PROTO_PART_LIMIT = 2;

	/**
	 * @param list<string>|null $trustedProxies Explicit proxy list; null reads APP_TRUSTED_PROXIES per request.
	 */
	public function __construct(private readonly ?array $trustedProxies = null)
	{
	}

	public function process(Request $request, RequestHandlerInterface $handler): Response
	{
		$response = $handler->handle($request);

		foreach ($this->defaultHeaders($request) as $name => $value) {
			if (!$response->hasHeader($name)) {
				$response->setHeader($name, $value);
			}
		}

		return $response;
	}

	/**
	 * @return array<string, string>
	 */
	private function defaultHeaders(Request $request): array
	{
		$headers = [
			'X-Content-Type-Options' => 'nosniff',
			'X-Frame-Options' => 'SAMEORIGIN',
			'Referrer-Policy' => 'strict-origin-when-cross-origin',
			'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
		];

		$csp = $_ENV['APP_SECURITY_CSP'] ?? '';
		if (is_string($csp) && trim($csp) !== '') {
			$headers['Content-Security-Policy'] = trim($csp);
		}

		if ($this->shouldSendStrictTransportSecurity($request)) {
			$hsts = $_ENV['APP_HSTS_VALUE'] ?? 'max-age=31536000; includeSubDomains';
			if (is_string($hsts) && trim($hsts) !== '') {
				$headers['Strict-Transport-Security'] = trim($hsts);
			}
		}

		return $headers;
	}

	private function shouldSendStrictTransportSecurity(Request $request): bool
	{
		if (filter_var($_ENV['APP_HSTS_ENABLED'] ?? false, FILTER_VALIDATE_BOOL) !== true) {
			return false;
		}

		return $this->isSecureRequest($request);
	}

	private function isSecureRequest(Request $request): bool
	{
		$https = $request->server['HTTPS'] ?? '';
		if (is_string($https) && strtolower($https) === 'on') {
			return true;
		}

		/*
		 * REQUEST_URI is deliberately NOT consulted. It is the request-target copied verbatim from
		 * the request line, so it is client input: origin-form makes it a path, but absolute-form
		 * (`GET https://example.com/ HTTP/1.1`) is legal HTTP/1.1 and arrives with the scheme
		 * attached. Treating that as proof of TLS handed any direct client the same power over
		 * HSTS that trusting X-Forwarded-Proto unconditionally did. A connection that really is
		 * encrypted already sets HTTPS, and a real proxy is covered above, so the branch bought
		 * nothing that was not already established by evidence the client cannot forge.
		 */
		return $this->isForwardedHttps($request);
	}

	/**
	 * Believe X-Forwarded-Proto only when the socket peer is a configured trusted proxy.
	 *
	 * Without that check any client could set the header itself and so decide whether this
	 * application claims to be HTTPS — which decides whether HSTS is pinned into browsers.
	 */
	private function isForwardedHttps(Request $request): bool
	{
		$forwarded = $request->server['HTTP_X_FORWARDED_PROTO'] ?? '';
		if (!is_string($forwarded) || trim($forwarded) === '') {
			return false;
		}

		if (!$this->trustedProxies()->trusts($request->server['REMOTE_ADDR'] ?? null)) {
			return false;
		}

		// A chained deployment appends one hop per proxy; the left-most value is the client's own scheme.
		$clientProto = explode(',', $forwarded, self::FORWARDED_PROTO_PART_LIMIT)[0];

		return strtolower(trim($clientProto)) === 'https';
	}

	/**
	 * Resolved per request on purpose: the existing suite mutates the environment between cases,
	 * and a constructor-cached list would keep trusting proxies after they are removed.
	 */
	private function trustedProxies(): TrustedProxies
	{
		return $this->trustedProxies === null
			? TrustedProxies::fromEnv()
			: TrustedProxies::fromList($this->trustedProxies);
	}
}
