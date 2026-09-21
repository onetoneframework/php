<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\FFI;

use PHPUnit\Framework\TestCase;
use function count;
use function in_array;
use function is_file;

/**
 * Every `$this->{library}->{Function}(...)` call WindowsAPI makes must have a
 * matching C declaration in that library's header, or the call throws
 * "Attempt to call undefined C function" the first time it is reached.
 *
 * Nothing else catches this. The declarations live in gitignored headers, the
 * integration tests are gated behind RUN_WINDOWS_FFI_TESTS, and PHPStan checks
 * the PHP side only - so a wrapper can sit in the class for years looking
 * perfectly correct and fail on first use. An audit at the time of writing found
 * 119 such calls across nine libraries, including the whole service-control
 * family (OpenSCManagerA, StartServiceA, ControlService and the rest).
 *
 * This reads the headers off disk rather than loading FFI, so it runs on every
 * platform.
 */
final class WindowsApiDeclarationTest extends TestCase
{
    private const SOURCE = __DIR__ . '/../../../src/Classes/FFI/WindowsAPI.php';

    private const HEADER_DIRECTORY = __DIR__ . '/../../../src/FFI/Header/';

    /**
     * Property name to header basename, where the two differ.
     *
     * @var array<string, string>
     */
    private const HEADER_FOR = [
        'apimswin' => 'api-ms-win-core-winrt-l1-1-0',
    ];

    /**
     * Libraries whose cdef is built from their own header alone. Every other
     * library is cdef'd as windows.h followed by its own header, so the shared
     * typedefs and structs are in scope; ws2_32 declares its own.
     *
     * @var string[]
     */
    private const SELF_CONTAINED = ['ws2_32'];

    /**
     * FFI's own methods, which are not DLL functions and need no declaration.
     *
     * @var string[]
     */
    private const FFI_BUILTINS = ['new', 'cast', 'addr', 'sizeof', 'type', 'string', 'memcpy', 'free', 'arrayType'];

    /**
     * C's own types, which no header has to declare.
     *
     * @var string[]
     */
    private const C_BUILTIN_TYPES = [
        'char', 'signed char', 'unsigned char', 'short', 'unsigned short',
        'int', 'unsigned', 'unsigned int', 'long', 'unsigned long',
        'long long', 'unsigned long long', 'float', 'double', 'void', 'bool',
        'int8_t', 'uint8_t', 'int16_t', 'uint16_t', 'int32_t', 'uint32_t',
        'int64_t', 'uint64_t', 'size_t', 'intptr_t', 'uintptr_t', 'wchar_t',
    ];

    /**
     * @return array<string, array<string, true>> Function names by library property.
     */
    private function callsByLibrary(): array
    {
        $source = file_get_contents(self::SOURCE);
        $this->assertIsString($source, 'WindowsAPI source must be readable.');

        preg_match_all('/\$this->([a-z0-9_]+)->([A-Za-z_][A-Za-z0-9_]*)\s*\(/', $source, $matches, PREG_SET_ORDER);

        $calls = [];

        foreach ($matches as $match) {
            if (in_array($match[2], self::FFI_BUILTINS, true)) {
                continue;
            }

            $calls[$match[1]][$match[2]] = true;
        }

        return $calls;
    }

    private function declarationsFor(string $library): ?string
    {
        $path = self::HEADER_DIRECTORY . (self::HEADER_FOR[$library] ?? $library) . '.h';

        if (!is_file($path)) {
            return null;
        }

        $declarations = (string) file_get_contents($path);

        if (!in_array($library, self::SELF_CONTAINED, true)) {
            $declarations .= (string) file_get_contents(self::HEADER_DIRECTORY . 'windows.h');
        }

        return $declarations;
    }

    public function testTheAuditFindsCallsAtAll(): void
    {
        $calls = $this->callsByLibrary();

        $this->assertNotEmpty($calls, 'The call pattern must still match; a rename would silently empty this test.');
        $this->assertArrayHasKey('kernel32', $calls);
        $this->assertGreaterThan(100, count($calls['kernel32']), 'kernel32 carries the bulk of the surface.');
    }

    /**
     * Every type name handed to {library}->new(). A literal that ends in "["
     * had its size concatenated on, e.g. new('char[' . $length . ']'), so the
     * element type is what is left once the bracket and anything after it goes.
     *
     * @return array<string, array<string, true>> Type names by library property.
     */
    private function allocationsByLibrary(): array
    {
        $source = (string) file_get_contents(self::SOURCE);

        preg_match_all(
            '/\$this->([a-z0-9_]+)->new\(\s*[\'"]([^\'"]+)[\'"]/',
            $source,
            $matches,
            PREG_SET_ORDER
        );

        $allocations = [];

        foreach ($matches as $match) {
            $type = trim((string) preg_replace('/\[.*$|\*/', '', $match[2]));

            if ($type === '' || in_array(strtolower($type), self::C_BUILTIN_TYPES, true)) {
                continue;
            }

            $allocations[$match[1]][$type] = true;
        }

        return $allocations;
    }

    public function testTheAllocationAuditFindsTypesAtAll(): void
    {
        $allocations = $this->allocationsByLibrary();

        $this->assertNotEmpty($allocations, 'The allocation pattern must still match.');
        $this->assertArrayHasKey('kernel32', $allocations);
    }

    /**
     * The same failure as an undeclared function, one step earlier: FFI rejects
     * an unknown type name, so the wrapper throws before it ever calls anything.
     *
     * This is not hypothetical. getWindowText() allocated "wchar[256]" - not a
     * C type, the real one being wchar_t - and threw on every single call.
     */
    public function testEveryAllocatedTypeIsDeclaredInItsHeader(): void
    {
        if (!is_file(self::HEADER_DIRECTORY . 'windows.h')) {
            $this->markTestSkipped('FFI headers are gitignored and absent from this checkout.');
        }

        $undeclared = [];

        foreach ($this->allocationsByLibrary() as $library => $types) {
            $declarations = $this->declarationsFor($library);

            if ($declarations === null) {
                $undeclared[] = sprintf('%s: no header file for %d allocations', $library, count($types));
                continue;
            }

            foreach (array_keys($types) as $type) {
                if (!preg_match('/\b' . preg_quote($type, '/') . '\b/', $declarations)) {
                    $undeclared[] = $library . '::' . $type;
                }
            }
        }

        sort($undeclared);

        $this->assertSame(
            [],
            $undeclared,
            "These FFI allocations name a type no header declares:\n  " . implode("\n  ", $undeclared)
        );
    }

    public function testEveryCalledFunctionIsDeclaredInItsHeader(): void
    {
        if (!is_file(self::HEADER_DIRECTORY . 'windows.h')) {
            $this->markTestSkipped('FFI headers are gitignored and absent from this checkout.');
        }

        $undeclared = [];

        foreach ($this->callsByLibrary() as $library => $functions) {
            $declarations = $this->declarationsFor($library);

            if ($declarations === null) {
                $undeclared[] = sprintf('%s: no header file for %d calls', $library, count($functions));
                continue;
            }

            foreach (array_keys($functions) as $function) {
                if (!preg_match('/\b' . preg_quote($function, '/') . '\s*\(/', $declarations)) {
                    $undeclared[] = $library . '::' . $function;
                }
            }
        }

        sort($undeclared);

        $this->assertSame(
            [],
            $undeclared,
            "These FFI calls have no C declaration and will throw when reached:\n  " . implode("\n  ", $undeclared)
        );
    }
}
