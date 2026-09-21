<?php

declare(strict_types=1);

namespace Clover\Tests\Framework\Middleware;

use Clover\Framework\Component\Request;
use Clover\Framework\Component\Response;
use Clover\Framework\Contract\RequestHandlerInterface;
use Clover\Framework\Middleware\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['APP_HSTS_ENABLED'], $_ENV['APP_SECURITY_CSP'], $_ENV['APP_TRUSTED_PROXIES']);
    }

    private function okHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response(['ok' => true], [], 'json', 200);
            }
        };
    }

    /**
     * @param array<string, string> $server
     */
    private function hstsHeaderFor(array $server): ?string
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request([], [], [], [], $server, '');

        return $middleware->process($request, $this->okHandler())->getHeader('Strict-Transport-Security');
    }

    /**
     * An absolute-form request line is legal HTTP/1.1 and reaches PHP with the scheme still
     * attached to REQUEST_URI, so believing it would let any direct client pin HSTS in browsers —
     * the same hole as trusting X-Forwarded-Proto from an unverified peer.
     */
    public function testAbsoluteFormRequestUriCannotForgeASecureRequest(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => 'https://example.com/admin',
            'REMOTE_ADDR' => '203.0.113.9',
        ]));
    }

    public function testAbsoluteFormRequestUriIsIgnoredEvenFromATrustedProxy(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        // The proxy is trusted, but it forwarded no scheme; the URI alone must not stand in for one.
        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => 'https://example.com/admin',
            'REMOTE_ADDR' => '10.0.0.1',
        ]));
    }

    public function testAppliesBaselineHeadersWhenNotAlreadySet(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/'], '');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response(['ok' => true], [], 'json', 200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame('nosniff', $response->getHeader('X-Content-Type-Options'));
        $this->assertSame('SAMEORIGIN', $response->getHeader('X-Frame-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->getHeader('Referrer-Policy'));
    }

    public function testDoesNotOverrideExistingHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request([], [], [], [], ['REQUEST_URI' => '/'], '');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                $r = new Response(['ok' => true], [], 'json', 200);
                $r->setHeader('X-Frame-Options', 'ALLOWALL');

                return $r;
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertSame('ALLOWALL', $response->getHeader('X-Frame-Options'));
    }

    public function testAddsHstsWhenEnabledAndHttps(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
            'HTTPS' => 'on',
        ], '');

        $handler = new class () implements RequestHandlerInterface {
            public function handle(Request $request): Response
            {
                return new Response(['ok' => true], [], 'json', 200);
            }
        };

        $response = $middleware->process($request, $handler);

        $this->assertNotNull($response->getHeader('Strict-Transport-Security'));
        unset($_ENV['APP_HSTS_ENABLED']);
    }

    public function testIgnoresForwardedProtoFromUntrustedPeer(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '203.0.113.7',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testHonoursForwardedProtoFromTrustedPeer(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testIgnoresForwardedProtoWhenNoProxiesConfigured(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testIgnoresForwardedProtoWhenProxyListIsEmptyString(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '  ,  ';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testDirectHttpsStillWinsWithoutAnyTrustedProxy(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'HTTPS' => 'on',
            'REMOTE_ADDR' => '203.0.113.7',
        ]));
    }

    public function testTrustedProxyCidrRangeMatchesPeer(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '192.168.0.0/16, 10.0.0.0/8';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.4.5.6',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testPeerJustOutsideTrustedCidrRangeIsRejected(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.0/24';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.1.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testTrustedIpv6PeerIsMatchedRegardlessOfSpelling(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '::1';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '0:0:0:0:0:0:0:1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testTrustedIpv6CidrRangeMatchesPeer(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = 'fd00::/8';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => 'fd12:3456::1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testIpv4PeerIsNotMatchedByIpv6Range(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '::/0';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }

    public function testTrustedProxyForwardingPlainHttpDoesNotSendHsts(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'http',
        ]));
    }

    public function testForwardedProtoChainUsesLeftmostClientScheme(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertNotNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https, http',
        ]));

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'http, https',
        ]));
    }

    public function testExplicitConstructorListOverridesEnvironment(): void
    {
        $_ENV['APP_HSTS_ENABLED'] = 'true';
        $_ENV['APP_TRUSTED_PROXIES'] = '203.0.113.7';

        $middleware = new SecurityHeadersMiddleware(['10.0.0.1']);
        $request = new Request([], [], [], [], [
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ], '');

        $this->assertNotNull(
            $middleware->process($request, $this->okHandler())->getHeader('Strict-Transport-Security')
        );
    }

    public function testForwardedProtoIsIgnoredWhenHstsIsDisabled(): void
    {
        $_ENV['APP_TRUSTED_PROXIES'] = '10.0.0.1';

        $this->assertNull($this->hstsHeaderFor([
            'REQUEST_URI' => '/',
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));
    }
}
