<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Tests\Classes\FFI;

use Clover\Classes\FFI\HuggingFace;
use Clover\Classes\FFI\ONNX;
use Clover\Exception\FFI\BindingInitializationException;
use Clover\Exception\FFI\ExtensionNotLoadedException;
use Clover\Exception\FFI\FFIException;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The FFI wrappers used to call `exit()` when ext-ffi was missing and `die()`
 * when `FFI::cdef()` failed. Both killed the process, so a caller could not
 * fall back to a non-native path — and no test could observe the failure at
 * all. These tests exist to keep the failures catchable.
 *
 * They run isolated because a failed FFI::cdef() leaves PHP's heap corrupted on
 * this build. The failure is not raised here — the next FFI work in the same
 * process dies with "zend_mm_heap corrupted", whichever test that happens to
 * be, so provoking binding failures in the shared process took the rest of the
 * FFI suite down with it.
 */
#[RunTestsInSeparateProcesses]
final class FFIBindingFailureTest extends TestCase
{
	protected function setUp(): void
	{
		if (!extension_loaded('ffi')) {
			$this->markTestSkipped('ext-ffi is required to reach the cdef failure path.');
		}
	}

	public function testAFailedBindingThrowsInsteadOfTerminatingTheProcess(): void
	{
		$this->expectException(BindingInitializationException::class);
		new ONNX();
	}

	public function testTheFailureNamesTheBindingAndTheLibrary(): void
	{
		try {
			new HuggingFace();
			$this->fail('Expected the missing shared library to be reported.');
		} catch (BindingInitializationException $exception) {
			$this->assertStringContainsString(HuggingFace::class, $exception->getMessage());
			$this->assertStringContainsString('libctranslate_ffi.so', $exception->getMessage());
			$this->assertNotNull($exception->getPrevious());
		}
	}

	public function testTheBindingFailureIsCatchableAsAnFFIException(): void
	{
		try {
			new ONNX();
			$this->fail('Expected a binding failure.');
		} catch (FFIException $exception) {
			$this->assertInstanceOf(RuntimeException::class, $exception);
		}
	}

	public function testTheExtensionFailureIsAlsoAnFFIException(): void
	{
		$exception = ExtensionNotLoadedException::forBinding(ONNX::class);

		$this->assertInstanceOf(FFIException::class, $exception);
		$this->assertStringContainsString(ONNX::class, $exception->getMessage());
		$this->assertStringContainsString('ext-ffi', $exception->getMessage());
	}
}
