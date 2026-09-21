<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI;

use Clover\Exception\FFI\BindingInitializationException;
use Clover\Exception\FFI\ExtensionNotLoadedException;
use FFI;

/**
 * Class Handler
 *
 * A helper class for PHP FFI operations.
 * This class wraps common FFI functions for easier use.
 */
class ONNX
{

    private $library_file = '/usr/local/lib/libonnx_ffi.so';

    private FFI $ffi;

    public function __construct()
    {
        if (!extension_loaded('ffi')) {
            throw ExtensionNotLoadedException::forBinding(self::class);
        }
        
        $cdef = "
            bool initialize_translator(char* ONNX_MODEL_DIR_PATH, char* ONNX_MODEL_FILE_NAME, char* SP_MODEL_PATH);
            const char* translate_text_ffi(const char* input_c_str);
            void cleanup_translator();
        ";

        try {
            $this->ffi = FFI::cdef($cdef, $this->library_file);
        } catch (FFI\Exception $e) {
            throw BindingInitializationException::forBinding(self::class, $this->library_file, $e);
        }
    }

    public function callModel(string $modelPath, string $modelFileName, string $sentenceModelPath, string $prompt)
    {
        if (!is_dir($modelPath)) {
            throw new \Exception("Model directory not found inside container: ".$modelPath."\nCheck volume mounts in docker-compose.yml and model directory name.\n");
        }
        $load_status = $this->ffi->initialize_translator($modelPath, $modelFileName, $sentenceModelPath);
        if ($load_status !== 0) {
            throw new \Exception("Failed to load model. Status: ".$load_status);
        }

        $translated_c_char_ptr = $this->ffi->translate_text_ffi($prompt);
        if ($translated_c_char_ptr === null) {
            throw new \Exception("Error: Translation returned NULL.");
        }
        
        if (is_string($translated_c_char_ptr)) {
            return $translated_c_char_ptr;
        }

        $this->ffi->cleanup_translator();
    }

}


