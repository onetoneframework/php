<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */


namespace Clover\Classes\FFI;

use FFI;
use FFI\CData;
use RuntimeException;
use Clover\Classes\File\Handler as FileHandler;

class LibBert
{
    private FFI $ffi;

    /**
     * @throws \FFI\Exception If OpenCV is not available
     */
    public function __construct()
    {
        $bertHeader = FileHandler::read(sprintf("%s%s", BASE_PATH, "/../src/FFI/Header/bert.h"));
        if (!$bertHeader) {
            throw new RuntimeException('Bert header file is not exists');
        }
        $this->ffi = FFI::cdef($bertHeader, "libbert_shared.so");
    }

    public function loadFromFile(string $modelPath): CData
    {
        return $this->ffi->bert_load_from_file($modelPath);
    }

    public function encode(CData $context, int $threads, string $text, CData $embeddings): CData
    {
        return $this->ffi->bert_encode($context, $threads, $text, $embeddings);
    }

    public function getDimensions(CData $context): int
    {
        return $this->ffi->bert_n_embd($context);
    }

    public function getMaxTokens(CData $context): int
    {
        return (int) $this->ffi->bert_n_max_tokens($context);
    }

    public function freeContext(CData $context): CData
    {
        return $this->ffi->bert_free($context);
    }
}
