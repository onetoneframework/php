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
class HuggingFace
{

    private $library_file = '/usr/local/lib/libctranslate_ffi.so';

    private FFI $ffi;

    public function __construct()
    {
        if (!extension_loaded('ffi')) {
            throw ExtensionNotLoadedException::forBinding(self::class);
        }
        
        $cdef = "
            int load_model_ct2(const char *ct2_model_path, const char *source_sp_model_path, const char *target_sp_model_path, const char *device_str, int num_threads_per_replica, int num_replicas);
            const char *translate_text_ct2(const char* input_text_c_str);
            void free_translated_string_ct2(const char* str_to_free);
            void unload_model_ct2();
        ";

        try {
            $this->ffi = FFI::cdef($cdef, $this->library_file);
        } catch (FFI\Exception $e) {
            throw BindingInitializationException::forBinding(self::class, $this->library_file, $e);
        }
    }

    public function callModel(string $modelPath, string $sourceSentencePieceModelPath, string $targetSentencePieceModelPath, string $prompt, string $device = "cpu", int $threadsCount = 6, int $replicasCount = 1)
    {
        if (!is_dir($modelPath)) {
            throw new \Exception("Model directory not found inside container: ".$modelPath."\nCheck volume mounts in docker-compose.yml and model directory name.\n");
        }
        $load_status = $this->ffi->load_model_ct2($modelPath, $sourceSentencePieceModelPath, $targetSentencePieceModelPath, $device, $threadsCount, $replicasCount);
        if ($load_status !== 0) {
            throw new \Exception("Failed to load model. Status: ".$load_status);
        }

        $translated_c_char_ptr = $this->ffi->translate_text_ct2($prompt);
        if ($translated_c_char_ptr === null) {
            throw new \Exception("Error: Translation returned NULL.");
        }
        
        if (is_string($translated_c_char_ptr)) {
            return $translated_c_char_ptr;
        }

        $translated_text = FFI::string($translated_c_char_ptr);
        $this->ffi->free_translated_string_ct2($translated_c_char_ptr); 

        $error_data = json_decode($translated_text, true);
        $this->ffi->unload_model_ct2();
        if (json_last_error() === JSON_ERROR_NONE && isset($error_data['error'])) {
            throw new \Exception($error_data['error']);
        } else {
            return $translated_text;
        }
    }

}


