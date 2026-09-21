<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\FFI;

use Clover\Classes\FFI\Windows\WindowsAPI;
use Clover\Interface\FFI\FFIWindowsAPIInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * The IP Helper additions: adapters, ICMP echo, the connection tables and ARP.
 *
 * These read the machine's live networking state, so the assertions are about
 * shape and invariants rather than particular addresses - the one exception
 * being the loopback, which answers a ping on any machine that has a stack at
 * all.
 *
 * The two byte-order conversions are pure arithmetic and are tested as such, on
 * any platform. They are the part most likely to be quietly wrong: an address
 * read in the wrong order still looks like an address.
 */
final class WindowsApiNetworkTest extends TestCase
{
    use SharesOneWindowsApi;

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function wrapperProvider(): array
    {
        return [
            'getNetworkAdapters' => ['getNetworkAdapters', 'array|false'],
            'getInterfaceCount' => ['getInterfaceCount', 'int|false'],
            'getBestInterfaceFor' => ['getBestInterfaceFor', 'int|false'],
            'ping' => ['ping', 'array|false'],
            'getTcpConnections' => ['getTcpConnections', 'array|false'],
            'getUdpEndpoints' => ['getUdpEndpoints', 'array|false'],
            'getArpTable' => ['getArpTable', 'array|false'],
            'resolveMacAddress' => ['resolveMacAddress', 'string|false'],
        ];
    }

    /**
     * @dataProvider wrapperProvider
     */
    public function testEachWrapperIsAPublicMethodWithTheExpectedReturnType(string $method, string $returnType): void
    {
        $reflection = new ReflectionClass(WindowsAPI::class);

        $this->assertTrue($reflection->hasMethod($method), $method . '() must exist on WindowsAPI.');

        $reflected = $reflection->getMethod($method);
        $this->assertTrue($reflected->isPublic(), $method . '() must be public.');
        $this->assertSame($returnType, (string) $reflected->getReturnType());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function dllFunctionProvider(): array
    {
        return [
            'GetAdaptersInfo' => ['GetAdaptersInfo'],
            'GetNumberOfInterfaces' => ['GetNumberOfInterfaces'],
            'GetBestInterface' => ['GetBestInterface'],
            'GetExtendedTcpTable' => ['GetExtendedTcpTable'],
            'GetExtendedUdpTable' => ['GetExtendedUdpTable'],
            'GetIpNetTable' => ['GetIpNetTable'],
            'SendARP' => ['SendARP'],
            'IcmpCreateFile' => ['IcmpCreateFile'],
            'IcmpCloseHandle' => ['IcmpCloseHandle'],
            'IcmpSendEcho' => ['IcmpSendEcho'],
        ];
    }

    /**
     * @dataProvider dllFunctionProvider
     */
    public function testTheInterfaceDeclaresEveryDllFunctionTheWrappersCall(string $function): void
    {
        $this->assertTrue(
            (new ReflectionClass(FFIWindowsAPIInterface::class))->hasMethod($function),
            $function . '() must be declared on FFIWindowsAPIInterface so the typed library handle resolves it.'
        );
    }

    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function addressProvider(): array
    {
        return [
            'all zeroes' => ['0.0.0.0', 0],
            'loopback' => ['127.0.0.1', 0x0100007F],
            'a private address' => ['192.168.0.26', 0x1A00A8C0],
            // unpack('V') is unsigned, so all-ones is 2^32-1 and not -1.
            'the broadcast address' => ['255.255.255.255', 4294967295],
            'first octet only' => ['1.0.0.0', 1],
            'last octet only' => ['0.0.0.1', 0x01000000],
        ];
    }

    /**
     * @dataProvider addressProvider
     */
    public function testAnAddressSurvivesTheRoundTripThroughNetworkOrder(string $dotted, int $expected): void
    {
        $toNetwork = new ReflectionMethod(WindowsAPI::class, 'ipv4ToNetworkOrder');
        $toNetwork->setAccessible(true);

        $fromNetwork = new ReflectionMethod(WindowsAPI::class, 'networkOrderToIpv4');
        $fromNetwork->setAccessible(true);

        $instance = (new ReflectionClass(WindowsAPI::class))->newInstanceWithoutConstructor();

        $encoded = $toNetwork->invoke($instance, $dotted);
        $this->assertSame($expected, $encoded, $dotted . ' must encode to the documented word.');
        $this->assertSame($dotted, $fromNetwork->invoke($instance, $encoded));
    }

    public function testSomethingThatIsNotAnAddressIsRejected(): void
    {
        $toNetwork = new ReflectionMethod(WindowsAPI::class, 'ipv4ToNetworkOrder');
        $toNetwork->setAccessible(true);

        $instance = (new ReflectionClass(WindowsAPI::class))->newInstanceWithoutConstructor();

        foreach (['', 'nonsense', '999.1.1.1', '1.2.3', '::1'] as $rejected) {
            $this->assertFalse($toNetwork->invoke($instance, $rejected), $rejected . ' is not an IPv4 address.');
        }
    }

    /**
     * @return array<string, array{0: int, 1: int}>
     */
    public static function portProvider(): array
    {
        return [
            'http' => [0x5000, 80],
            'https' => [0xBB01, 443],
            'dns' => [0x3500, 53],
            'zero' => [0, 0],
            'the highest port' => [0xFFFF, 65535],
        ];
    }

    /**
     * The tables store the port in network order in the low half of a DWORD.
     * Reading it as-is gives a plausible-looking wrong number - 80 becomes
     * 20480 - which is why this is pinned rather than trusted.
     *
     * @dataProvider portProvider
     */
    public function testAPortIsReadOutOfNetworkOrder(int $stored, int $expected): void
    {
        $method = new ReflectionMethod(WindowsAPI::class, 'portFromNetworkOrder');
        $method->setAccessible(true);

        $this->assertSame(
            $expected,
            $method->invoke((new ReflectionClass(WindowsAPI::class))->newInstanceWithoutConstructor(), $stored)
        );
    }

    /**
     * The rows of a table live in a buffer the reading method owns. Handing
     * them out means handing out pointers into memory that is about to go, so
     * the reader takes a mapper and finishes the job while the buffer is alive.
     */
    public function testTheTableReaderMapsRowsRatherThanReturningThem(): void
    {
        $parameters = (new ReflectionMethod(WindowsAPI::class, 'readConnectionTable'))->getParameters();

        $last = end($parameters);
        $this->assertNotFalse($last);
        $this->assertSame('map', $last->getName());
        $this->assertSame('callable', (string) $last->getType());
    }

    #region live calls (Windows only)

    public function testTheAdapterListDescribesAtLeastOneRealAdapter(): void
    {
        $adapters = $this->windowsApiOrSkip()->getNetworkAdapters();

        $this->assertIsArray($adapters);
        $this->assertNotEmpty($adapters);

        foreach ($adapters as $adapter) {
            $this->assertNotSame('', $adapter['name'], 'Every adapter has a GUID name.');
            $this->assertGreaterThan(0, $adapter['index']);
            $this->assertIsArray($adapter['addresses']);

            if ($adapter['mac'] !== '') {
                $this->assertMatchesRegularExpression('/^([0-9A-F]{2}-)*[0-9A-F]{2}$/', $adapter['mac']);
            }

            foreach ($adapter['addresses'] as $address) {
                $this->assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $address['ip']);
            }
        }
    }

    public function testTheInterfaceCountCoversTheAdaptersListed(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $count = $windowsApi->getInterfaceCount();
        $adapters = $windowsApi->getNetworkAdapters();

        $this->assertIsInt($count);
        $this->assertIsArray($adapters);

        // GetAdaptersInfo lists only the IPv4-bound ones, so the stack's own
        // count is never the smaller of the two.
        $this->assertGreaterThanOrEqual(count($adapters), $count);
    }

    public function testTheLoopbackAnswersAPing(): void
    {
        $reply = $this->windowsApiOrSkip()->ping('127.0.0.1');

        $this->assertIsArray($reply);
        $this->assertSame(0, $reply['status'], 'IP_SUCCESS is 0.');
        $this->assertGreaterThanOrEqual(0, $reply['roundTripMillis']);
        $this->assertSame(strlen('onetone'), $reply['bytes'], 'The payload comes back the size it went out.');
    }

    public function testPingingSomethingThatIsNotAnAddressFails(): void
    {
        $this->assertFalse($this->windowsApiOrSkip()->ping('not an address'));
    }

    public function testTheLoopbackRoutesThroughSomeInterface(): void
    {
        $index = $this->windowsApiOrSkip()->getBestInterfaceFor('127.0.0.1');

        $this->assertIsInt($index);
        $this->assertGreaterThan(0, $index);
    }

    public function testTheConnectionTablesReadBackConsistently(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $tcp = $windowsApi->getTcpConnections();
        $this->assertIsArray($tcp);
        $this->assertNotEmpty($tcp, 'A running Windows machine always has listeners.');

        foreach ($tcp as $connection) {
            $this->assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $connection['localAddress']);
            $this->assertGreaterThanOrEqual(0, $connection['localPort']);
            $this->assertLessThanOrEqual(65535, $connection['localPort']);
            $this->assertGreaterThanOrEqual(1, $connection['state']);
            $this->assertLessThanOrEqual(12, $connection['state']);
        }

        $udp = $windowsApi->getUdpEndpoints();
        $this->assertIsArray($udp);

        foreach ($udp as $endpoint) {
            $this->assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $endpoint['localAddress']);
            $this->assertLessThanOrEqual(65535, $endpoint['localPort']);
        }
    }

    /**
     * Reading the same table twice must give the same answer for the entries
     * that did not change. When the rows were pointers into a freed buffer the
     * addresses moved between reads while the ports beside them stayed put.
     */
    public function testTheSameEndpointReadsTheSameWayTwice(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $first = $windowsApi->getUdpEndpoints();
        $second = $windowsApi->getUdpEndpoints();

        $this->assertIsArray($first);
        $this->assertIsArray($second);

        // A port and process can hold several endpoints at once - NetBIOS binds
        // one per interface - so the comparison is between the sets of
        // addresses each port and process is listening on, not between single
        // values.
        $group = static function (array $endpoints): array {
            $grouped = [];

            foreach ($endpoints as $endpoint) {
                $grouped[$endpoint['localPort'] . '/' . $endpoint['processId']][] = $endpoint['localAddress'];
            }

            foreach ($grouped as &$addresses) {
                sort($addresses);
            }

            return $grouped;
        };

        $firstGroups = $group($first);
        $secondGroups = $group($second);

        $compared = 0;

        foreach ($secondGroups as $identity => $addresses) {
            if (!isset($firstGroups[$identity])) {
                continue;
            }

            $this->assertSame($firstGroups[$identity], $addresses, 'Endpoint ' . $identity . ' changed address between reads.');
            $compared++;
        }

        $this->assertGreaterThan(0, $compared, 'Nothing was stable enough to compare.');
    }

    public function testTheArpCacheIsReadable(): void
    {
        $entries = $this->windowsApiOrSkip()->getArpTable();

        $this->assertIsArray($entries);

        foreach ($entries as $entry) {
            $this->assertMatchesRegularExpression('/^\d{1,3}(\.\d{1,3}){3}$/', $entry['address']);
            $this->assertGreaterThan(0, $entry['interfaceIndex']);
            $this->assertContains($entry['type'], [1, 2, 3, 4]);
        }
    }

    public function testTheDefaultGatewayAnswersAnArpRequest(): void
    {
        $windowsApi = $this->windowsApiOrSkip();

        $gateway = null;

        foreach ((array) $windowsApi->getNetworkAdapters() as $adapter) {
            if ($adapter['gateway'] !== '' && $adapter['gateway'] !== '0.0.0.0') {
                $gateway = $adapter['gateway'];
                break;
            }
        }

        if ($gateway === null) {
            $this->markTestSkipped('This machine has no default gateway.');
        }

        $mac = $windowsApi->resolveMacAddress($gateway);

        $this->assertIsString($mac);
        $this->assertMatchesRegularExpression('/^([0-9A-F]{2}-){5}[0-9A-F]{2}$/', $mac);
    }

    #endregion
}
