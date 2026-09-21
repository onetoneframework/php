<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI;

use Clover\Classes\File\Handler as FileHandler;
use RuntimeException;
use Clover\Exception\FFI\BindingInitializationException;
use Clover\Exception\FFI\ExtensionNotLoadedException;
use FFI;
use function sprintf;

class ONNXRuntime
{

    private $library_file = '/usr/local/lib/libonnxruntime.so';

    private FFI $ffi;

    public function __construct(?string $libraryFilePath = null)
    {
        if (!extension_loaded('ffi')) {
            throw ExtensionNotLoadedException::forBinding(self::class);
        }
    
        $coreml = "";
        if (PHP_OS_FAMILY == 'Darwin' && php_uname('m') != 'x86_64') {
            $coreml = 'OrtStatus* OrtSessionOptionsAppendExecutionProvider_CoreML(OrtSessionOptions* options, uint32_t coreml_flags);';
        }

        $onnxRuntimeHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/onnxruntime.h"));
        if (!$onnxRuntimeHeader) {
            throw new RuntimeException('Onnxruntime header file is not exists');
        }
        $cdef = $onnxRuntimeHeader.$coreml;

        try {
            $this->ffi = FFI::cdef($cdef, $libraryFilePath ?? $this->library_file);
        } catch (FFI\Exception $e) {
            throw BindingInitializationException::forBinding(self::class, $libraryFilePath ?? $this->library_file, $e);
        }
    }

    public function getAPI(): string
    {
        $version = $this->ffi->OrtGetApiBase()[0]->GetApi(24)[0];
        return FFI::string($version);
    }

    public function getVersionString(): string
    {
        $version = $this->ffi->OrtGetApiBase()[0]->GetVersionString();
        return FFI::string($version);
    }

}
