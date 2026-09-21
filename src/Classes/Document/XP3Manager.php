<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Archive;

use RuntimeException;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use function is_readable;
use function is_dir;
use function mkdir;
use function dirname;
use function fopen;
use function fclose;
use function fread;
use function fwrite;
use function fseek;
use function ftell;
use function feof;
use function filesize;
use function filemtime;
use function str_replace;
use function realpath;
use function strlen;
use function substr;
use function pack;
use function unpack;
use function mb_convert_encoding;
use function gzcompress;
use function gzuncompress;
use function md5_file;
use function sha1_file;
use function in_array;
use function array_filter;
use function array_map;
use function array_sum;
use function array_column;
use function count;
use function strtolower;
use function pathinfo;
use function round;
use function min;
use function usort;
use function fnmatch;
use function hash;
use function hash_init;
use function hash_update;
use function hash_final;
use function str_pad;

/**
 * XP3 Archive Utility Class (Native Binary Implementation)
 *
 * Implements the Kirikiri XP3 v2 binary specification including chunk tree
 * structures (File, info, segm, adlr), UTF-16LE filename encoding, 64-bit
 * offsets, and zlib index compression.
 *
 * Supports packing, unpacking, listing, searching, validation, and
 * single-file extraction from XP3 archives.
 */
class XP3Manager
{
    /** @var string 11-byte XP3 file signature */
    private const XP3_SIGNATURE = "XP3\x0D\x0A\x20\x0A\x1A\x8B\x67\x01";

    /** @var int Length of the XP3 signature in bytes */
    private const SIGNATURE_LENGTH = 11;

    /** @var int Length of the 64-bit index offset field */
    private const INDEX_OFFSET_LENGTH = 8;

    /** @var int Default read/write buffer size */
    private const BUFFER_SIZE = 8192;

    // ── Packing ─────────────────────────────────────────────────────────

    /**
     * Pack files from a directory into an XP3 archive.
     *
     * Recursively scans the input directory, writes each file's raw data
     * sequentially, then builds a compressed chunk-tree index at the end.
     *
     * @param string $inputDir  Source directory to pack.
     * @param string $outXp3    Output XP3 file path.
     * @param int    $compLevel Zlib compression level for the index (0-9).
     * @return bool True on success.
     * @throws RuntimeException On I/O failures or invalid input.
     */
    public function pack(string $inputDir, string $outXp3, int $compLevel = 9): bool
    {
        if (!is_dir($inputDir)) {
            throw new RuntimeException("Input directory does not exist: $inputDir");
        }
        if ($compLevel < 0 || $compLevel > 9) {
            throw new InvalidArgumentException("Compression level must be between 0 and 9.");
        }

        $this->ensureDirectory(dirname($outXp3));

        $outStream = fopen($outXp3, 'wb');
        if ($outStream === false) {
            throw new RuntimeException("Failed to open output file for writing: $outXp3");
        }

        try {
            // 1. Write the 11-byte header signature
            fwrite($outStream, self::XP3_SIGNATURE);

            // 2. Reserve 8 bytes for the index offset (will be overwritten later)
            $indexOffsetPos = self::SIGNATURE_LENGTH;
            fwrite($outStream, pack('P', 0));

            // 3. Write file data sequentially and collect index entries
            $fileEntries = [];
            $currentOffset = self::SIGNATURE_LENGTH + self::INDEX_OFFSET_LENGTH;

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($inputDir, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            $rootDir = realpath($inputDir);

            /** @var SplFileInfo $fileInfo */
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || !is_readable($fileInfo->getRealPath())) {
                    continue;
                }

                $filePath = $fileInfo->getRealPath();
                $size = filesize($filePath);

                $relativePath = str_replace($rootDir . DIRECTORY_SEPARATOR, '', $filePath);
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                // Stream-copy file data into the archive
                $inStream = fopen($filePath, 'rb');
                if ($inStream === false) {
                    continue;
                }
                while (!feof($inStream)) {
                    $chunk = fread($inStream, self::BUFFER_SIZE);
                    if ($chunk !== false && $chunk !== '') {
                        fwrite($outStream, $chunk);
                    }
                }
                fclose($inStream);

                // Compute Adler-32 hash for the adlr chunk
                $adler32 = $this->computeAdler32($filePath);

                $fileEntries[] = [
                    'name' => $relativePath,
                    'offset' => $currentOffset,
                    'size' => $size,
                    'adler32' => $adler32,
                ];

                $currentOffset += $size;
            }

            // 4. Build the chunk-tree index for all entries
            $indexData = '';
            foreach ($fileEntries as $entry) {
                $indexData .= $this->buildFileChunk(
                    $entry['name'],
                    $entry['offset'],
                    $entry['size'],
                    $entry['adler32']
                );
            }

            // 5. Compress the index with zlib
            $compressedIndex = gzcompress($indexData, $compLevel);
            $uncompressedSize = strlen($indexData);

            // Payload: flag(1) + originalSize(8) + compressedData
            $finalIndexPayload = chr(1) . pack('P', $uncompressedSize) . $compressedIndex;

            // 6. Append index payload at the end of the data section
            $indexStartOffset = $currentOffset;
            fwrite($outStream, $finalIndexPayload);

            // 7. Seek back and overwrite the reserved index offset field
            fseek($outStream, $indexOffsetPos);
            fwrite($outStream, pack('P', $indexStartOffset));

            return true;
        } finally {
            fclose($outStream);
        }
    }

    /**
     * Pack files from an explicit list of paths into an XP3 archive.
     *
     * Useful when only specific files need to be archived rather than
     * an entire directory tree.
     *
     * @param array<int, string> $filePaths  Absolute paths of files to pack.
     * @param string             $outXp3     Output XP3 file path.
     * @param string             $baseDir    Base directory for computing relative names.
     * @param int                $compLevel  Zlib compression level for the index (0-9).
     * @return bool True on success.
     * @throws RuntimeException On I/O failures.
     */
    public function packFiles(array $filePaths, string $outXp3, string $baseDir = '', int $compLevel = 9): bool
    {
        $this->ensureDirectory(dirname($outXp3));
        $baseDir = $baseDir !== '' ? realpath($baseDir) : '';

        $outStream = fopen($outXp3, 'wb');
        if ($outStream === false) {
            throw new RuntimeException("Failed to open output file for writing: $outXp3");
        }

        try {
            fwrite($outStream, self::XP3_SIGNATURE);
            $indexOffsetPos = self::SIGNATURE_LENGTH;
            fwrite($outStream, pack('P', 0));

            $fileEntries = [];
            $currentOffset = self::SIGNATURE_LENGTH + self::INDEX_OFFSET_LENGTH;

            foreach ($filePaths as $filePath) {
                if (!is_readable($filePath) || is_dir($filePath)) {
                    continue;
                }

                $size = filesize($filePath);
                $realPath = realpath($filePath);

                // Compute relative name
                if ($baseDir !== '' && $realPath !== false) {
                    $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $realPath);
                } else {
                    $relativePath = basename($filePath);
                }
                $relativePath = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);

                $inStream = fopen($filePath, 'rb');
                if ($inStream === false) {
                    continue;
                }
                while (!feof($inStream)) {
                    $chunk = fread($inStream, self::BUFFER_SIZE);
                    if ($chunk !== false && $chunk !== '') {
                        fwrite($outStream, $chunk);
                    }
                }
                fclose($inStream);

                $adler32 = $this->computeAdler32($filePath);

                $fileEntries[] = [
                    'name' => $relativePath,
                    'offset' => $currentOffset,
                    'size' => $size,
                    'adler32' => $adler32,
                ];

                $currentOffset += $size;
            }

            $indexData = '';
            foreach ($fileEntries as $entry) {
                $indexData .= $this->buildFileChunk($entry['name'], $entry['offset'], $entry['size'], $entry['adler32']);
            }

            $compressedIndex = gzcompress($indexData, $compLevel);
            $uncompressedSize = strlen($indexData);
            $finalIndexPayload = chr(1) . pack('P', $uncompressedSize) . $compressedIndex;

            $indexStartOffset = $currentOffset;
            fwrite($outStream, $finalIndexPayload);

            fseek($outStream, $indexOffsetPos);
            fwrite($outStream, pack('P', $indexStartOffset));

            return true;
        } finally {
            fclose($outStream);
        }
    }

    // ── Unpacking ───────────────────────────────────────────────────────

    /**
     * Unpack all files from an XP3 archive to the output directory.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $outDir   Destination directory.
     * @return bool True on success.
     * @throws RuntimeException On signature mismatch or I/O errors.
     */
    public function unpack(string $inputXp3, string $outDir): bool
    {
        $inStream = $this->openArchiveStream($inputXp3);

        try {
            $this->validateSignature($inStream);
            $indexOffset = $this->readIndexOffset($inStream);
            $indexData = $this->readIndexPayload($inStream, $indexOffset);
            $entries = $this->parseIndexData($indexData);

            foreach ($entries as $entry) {
                $targetPath = $outDir . '/' . $entry['name'];
                $this->ensureDirectory(dirname($targetPath));

                $outStream = fopen($targetPath, 'wb');
                if ($outStream === false) {
                    continue;
                }

                fseek($inStream, $entry['offset']);
                $remaining = $entry['size'];

                while ($remaining > 0 && !feof($inStream)) {
                    $chunkSize = (int) min($remaining, self::BUFFER_SIZE);
                    $data = fread($inStream, $chunkSize);
                    if ($data === false) {
                        break;
                    }
                    fwrite($outStream, $data);
                    $remaining -= strlen($data);
                }
                fclose($outStream);
            }

            return true;
        } finally {
            fclose($inStream);
        }
    }

    /**
     * Extract a single file from the archive by its relative path name.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $fileName Relative file name inside the archive (e.g. "images/bg.png").
     * @param string $outPath  Destination file path on disk.
     * @return bool True if the file was found and extracted.
     * @throws RuntimeException On I/O errors.
     */
    public function extractFile(string $inputXp3, string $fileName, string $outPath): bool
    {
        $inStream = $this->openArchiveStream($inputXp3);

        try {
            $this->validateSignature($inStream);
            $indexOffset = $this->readIndexOffset($inStream);
            $indexData = $this->readIndexPayload($inStream, $indexOffset);
            $entries = $this->parseIndexData($indexData);

            foreach ($entries as $entry) {
                if ($entry['name'] !== $fileName) {
                    continue;
                }

                $this->ensureDirectory(dirname($outPath));
                $outStream = fopen($outPath, 'wb');
                if ($outStream === false) {
                    throw new RuntimeException("Failed to open output file: $outPath");
                }

                fseek($inStream, $entry['offset']);
                $remaining = $entry['size'];

                while ($remaining > 0 && !feof($inStream)) {
                    $chunkSize = (int) min($remaining, self::BUFFER_SIZE);
                    $data = fread($inStream, $chunkSize);
                    if ($data === false) {
                        break;
                    }
                    fwrite($outStream, $data);
                    $remaining -= strlen($data);
                }
                fclose($outStream);
                return true;
            }

            return false;
        } finally {
            fclose($inStream);
        }
    }

    /**
     * Extract multiple files matching a glob pattern.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $pattern  Glob pattern (e.g. "*.png", "scripts/*.ks").
     * @param string $outDir   Destination directory.
     * @return array<int, string> List of extracted file paths.
     * @throws RuntimeException On I/O errors.
     */
    public function extractByPattern(string $inputXp3, string $pattern, string $outDir): array
    {
        $inStream = $this->openArchiveStream($inputXp3);
        $extracted = [];

        try {
            $this->validateSignature($inStream);
            $indexOffset = $this->readIndexOffset($inStream);
            $indexData = $this->readIndexPayload($inStream, $indexOffset);
            $entries = $this->parseIndexData($indexData);

            foreach ($entries as $entry) {
                if (!fnmatch($pattern, $entry['name'])) {
                    continue;
                }

                $targetPath = $outDir . '/' . $entry['name'];
                $this->ensureDirectory(dirname($targetPath));

                $outStream = fopen($targetPath, 'wb');
                if ($outStream === false) {
                    continue;
                }

                fseek($inStream, $entry['offset']);
                $remaining = $entry['size'];

                while ($remaining > 0 && !feof($inStream)) {
                    $chunkSize = (int) min($remaining, self::BUFFER_SIZE);
                    $data = fread($inStream, $chunkSize);
                    if ($data === false) {
                        break;
                    }
                    fwrite($outStream, $data);
                    $remaining -= strlen($data);
                }
                fclose($outStream);
                $extracted[] = $targetPath;
            }

            return $extracted;
        } finally {
            fclose($inStream);
        }
    }

    // ── Listing & Inspection ────────────────────────────────────────────

    /**
     * List all files in the archive without extracting them.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return array<int, array{name: string, offset: int, size: int}> Index entries.
     * @throws RuntimeException On signature mismatch or I/O errors.
     */
    public function listFiles(string $inputXp3): array
    {
        $inStream = $this->openArchiveStream($inputXp3);

        try {
            $this->validateSignature($inStream);
            $indexOffset = $this->readIndexOffset($inStream);
            $indexData = $this->readIndexPayload($inStream, $indexOffset);
            return $this->parseIndexData($indexData);
        } finally {
            fclose($inStream);
        }
    }

    /**
     * Get the total number of files in the archive.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return int File count.
     */
    public function getFileCount(string $inputXp3): int
    {
        return count($this->listFiles($inputXp3));
    }

    /**
     * Get the combined (uncompressed) size of all archived files in bytes.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return int Total size in bytes.
     */
    public function getTotalUncompressedSize(string $inputXp3): int
    {
        $entries = $this->listFiles($inputXp3);
        return (int) array_sum(array_column($entries, 'size'));
    }

    /**
     * Get the formatted total uncompressed size (e.g. "12.5 MB").
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return string Human-readable size.
     */
    public function getTotalUncompressedSizeFormatted(string $inputXp3): string
    {
        return $this->formatBytes($this->getTotalUncompressedSize($inputXp3));
    }

    /**
     * Check whether a specific file exists inside the archive.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $fileName Relative file name to look up.
     * @return bool True if the file is in the archive index.
     */
    public function fileExists(string $inputXp3, string $fileName): bool
    {
        $entries = $this->listFiles($inputXp3);
        foreach ($entries as $entry) {
            if ($entry['name'] === $fileName) {
                return true;
            }
        }
        return false;
    }

    /**
     * Search for files by name pattern using glob-style matching.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $pattern  Glob pattern (e.g. "*.png", "scripts/*.ks").
     * @return array<int, array{name: string, offset: int, size: int}> Matching entries.
     */
    public function searchFiles(string $inputXp3, string $pattern): array
    {
        $entries = $this->listFiles($inputXp3);
        return array_values(array_filter($entries, fn(array $e) => fnmatch($pattern, $e['name'])));
    }

    /**
     * Get information about a single file inside the archive.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $fileName Relative file name.
     * @return array{name: string, offset: int, size: int}|null Entry data, or null if not found.
     */
    public function getFileInfo(string $inputXp3, string $fileName): ?array
    {
        $entries = $this->listFiles($inputXp3);
        foreach ($entries as $entry) {
            if ($entry['name'] === $fileName) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Collect a breakdown of file counts grouped by extension.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return array<string, int> Extension => count map (extensions lowercased).
     */
    public function getExtensionBreakdown(string $inputXp3): array
    {
        $entries = $this->listFiles($inputXp3);
        $breakdown = [];
        foreach ($entries as $entry) {
            $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
            if ($ext === '') {
                $ext = '(no extension)';
            }
            $breakdown[$ext] = ($breakdown[$ext] ?? 0) + 1;
        }
        return $breakdown;
    }

    /**
     * Get the N largest files in the archive, sorted descending by size.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param int    $limit    Maximum number of results.
     * @return array<int, array{name: string, offset: int, size: int}>
     */
    public function getLargestFiles(string $inputXp3, int $limit = 10): array
    {
        $entries = $this->listFiles($inputXp3);
        usort($entries, fn(array $a, array $b) => $b['size'] <=> $a['size']);
        return array_slice($entries, 0, $limit);
    }

    /**
     * Produce a comprehensive summary of the archive contents.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return array{
     *     fileCount: int,
     *     totalSize: int,
     *     totalSizeFormatted: string,
     *     archiveSize: int,
     *     archiveSizeFormatted: string,
     *     extensions: array<string, int>
     * }
     */
    public function getSummary(string $inputXp3): array
    {
        $archiveSize = filesize($inputXp3);
        $totalUncompressed = $this->getTotalUncompressedSize($inputXp3);

        return [
            'fileCount' => $this->getFileCount($inputXp3),
            'totalSize' => $totalUncompressed,
            'totalSizeFormatted' => $this->formatBytes($totalUncompressed),
            'archiveSize' => $archiveSize,
            'archiveSizeFormatted' => $this->formatBytes($archiveSize),
            'extensions' => $this->getExtensionBreakdown($inputXp3),
        ];
    }

    // ── Validation ──────────────────────────────────────────────────────

    /**
     * Validate whether a file is a valid XP3 archive.
     *
     * Checks the header signature and attempts to read the index.
     *
     * @param string $inputXp3 Path to the file.
     * @return bool True if the file is a valid XP3 archive.
     */
    public function isValidArchive(string $inputXp3): bool
    {
        if (!is_readable($inputXp3)) {
            return false;
        }

        $inStream = @fopen($inputXp3, 'rb');
        if ($inStream === false) {
            return false;
        }

        try {
            $header = fread($inStream, self::SIGNATURE_LENGTH);
            if ($header !== self::XP3_SIGNATURE) {
                return false;
            }

            $indexOffsetData = fread($inStream, self::INDEX_OFFSET_LENGTH);
            if ($indexOffsetData === false || strlen($indexOffsetData) < self::INDEX_OFFSET_LENGTH) {
                return false;
            }

            $indexOffset = unpack('P', $indexOffsetData)[1];
            if ($indexOffset < self::SIGNATURE_LENGTH + self::INDEX_OFFSET_LENGTH) {
                return false;
            }

            // Attempt to read the index payload
            fseek($inStream, $indexOffset);
            $flagByte = fread($inStream, 1);
            return $flagByte !== false && strlen($flagByte) === 1;
        } catch (\Throwable) {
            return false;
        } finally {
            fclose($inStream);
        }
    }

    /**
     * Verify the integrity of the archive by reading every file's data.
     *
     * Returns a list of any files that could not be fully read.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @return array{valid: bool, errors: array<int, string>}
     */
    public function verifyIntegrity(string $inputXp3): array
    {
        $inStream = $this->openArchiveStream($inputXp3);
        $errors = [];

        try {
            $this->validateSignature($inStream);
            $indexOffset = $this->readIndexOffset($inStream);
            $indexData = $this->readIndexPayload($inStream, $indexOffset);
            $entries = $this->parseIndexData($indexData);

            foreach ($entries as $entry) {
                fseek($inStream, $entry['offset']);
                $remaining = $entry['size'];
                $readTotal = 0;

                while ($remaining > 0 && !feof($inStream)) {
                    $chunkSize = (int) min($remaining, self::BUFFER_SIZE);
                    $data = fread($inStream, $chunkSize);
                    if ($data === false) {
                        break;
                    }
                    $readTotal += strlen($data);
                    $remaining -= strlen($data);
                }

                if ($readTotal !== $entry['size']) {
                    $errors[] = sprintf(
                        "%s: expected %d bytes, read %d bytes",
                        $entry['name'],
                        $entry['size'],
                        $readTotal
                    );
                }
            }

            return ['valid' => empty($errors), 'errors' => $errors];
        } finally {
            fclose($inStream);
        }
    }

    /**
     * Compute the hash of the entire archive file.
     *
     * @param string $inputXp3 Path to the XP3 archive.
     * @param string $algo     Hash algorithm: 'md5' or 'sha1'.
     * @return string Hexadecimal hash.
     */
    public function getArchiveHash(string $inputXp3, string $algo = 'md5'): string
    {
        if (!is_readable($inputXp3)) {
            throw new RuntimeException("Cannot read archive: $inputXp3");
        }
        return match ($algo) {
            'md5' => md5_file($inputXp3),
            'sha1' => sha1_file($inputXp3),
            default => throw new InvalidArgumentException("Unsupported hash algorithm: $algo"),
        };
    }

    // ── Binary Chunk Builder (Packing) ──────────────────────────────────

    /**
     * Build a complete File chunk containing info, segm, and adlr sub-chunks.
     *
     * @param string $filename Relative file name (UTF-8).
     * @param int    $offset   Byte offset of the file data in the archive.
     * @param int    $size     File size in bytes.
     * @param int    $adler32  Adler-32 checksum of the file content.
     * @return string Binary chunk data.
     */
    private function buildFileChunk(string $filename, int $offset, int $size, int $adler32 = 0): string
    {
        // Encode filename to UTF-16LE per Kirikiri specification
        $nameUtf16 = mb_convert_encoding($filename, 'UTF-16LE', 'UTF-8');
        $nameLen = (int) (strlen($nameUtf16) / 2);

        // info chunk: flags(4) + orgSize(8) + arcSize(8) + nameLen(2) + nameUtf16
        $infoData = pack('V', 0) . pack('P', $size) . pack('P', $size) . pack('v', $nameLen) . $nameUtf16;
        $infoChunk = $this->wrapChunk('info', $infoData);

        // segm chunk: flags(4) + offset(8) + orgSize(8) + arcSize(8)
        $segmData = pack('V', 0) . pack('P', $offset) . pack('P', $size) . pack('P', $size);
        $segmChunk = $this->wrapChunk('segm', $segmData);

        // adlr chunk: adler32(4)
        $adlrData = pack('V', $adler32);
        $adlrChunk = $this->wrapChunk('adlr', $adlrData);

        return $this->wrapChunk('File', $infoChunk . $segmChunk . $adlrChunk);
    }

    /**
     * Wrap binary data into a named chunk (4-byte name + 8-byte size + data).
     *
     * @param string $name Chunk name (max 4 characters, null-padded).
     * @param string $data Chunk payload.
     * @return string Binary chunk.
     */
    private function wrapChunk(string $name, string $data): string
    {
        return str_pad($name, 4, "\0") . pack('P', strlen($data)) . $data;
    }

    // ── Binary Chunk Parser (Unpacking) ─────────────────────────────────

    /**
     * Parse the decompressed index data into an array of file entries.
     *
     * @param string $indexData Raw decompressed index bytes.
     * @return array<int, array{name: string, offset: int, size: int}>
     */
    private function parseIndexData(string $indexData): array
    {
        $files = [];
        $offset = 0;
        $len = strlen($indexData);

        while ($offset < $len) {
            if ($offset + 12 > $len) {
                break;
            }

            $chunkName = substr($indexData, $offset, 4);
            $offset += 4;
            $chunkSize = unpack('P', substr($indexData, $offset, 8))[1];
            $offset += 8;

            if ($offset + $chunkSize > $len) {
                break;
            }

            $chunkData = substr($indexData, $offset, $chunkSize);
            $offset += $chunkSize;

            if ($chunkName === 'File') {
                $files[] = $this->parseFileChunk($chunkData);
            }
        }

        return $files;
    }

    /**
     * Parse a single File chunk into name, offset, and size fields.
     *
     * @param string $fileData Raw File chunk payload.
     * @return array{name: string, offset: int, size: int}
     */
    private function parseFileChunk(string $fileData): array
    {
        $offset = 0;
        $len = strlen($fileData);
        $fileEntry = ['name' => '', 'offset' => 0, 'size' => 0];

        while ($offset < $len) {
            if ($offset + 12 > $len) {
                break;
            }

            $chunkName = substr($fileData, $offset, 4);
            $offset += 4;
            $chunkSize = unpack('P', substr($fileData, $offset, 8))[1];
            $offset += 8;

            if ($offset + $chunkSize > $len) {
                break;
            }

            $chunkData = substr($fileData, $offset, $chunkSize);
            $offset += $chunkSize;

            if ($chunkName === 'info') {
                if (strlen($chunkData) >= 22) {
                    $nameLen = unpack('v', substr($chunkData, 20, 2))[1];
                    $nameUtf16 = substr($chunkData, 22, $nameLen * 2);
                    $fileEntry['name'] = mb_convert_encoding($nameUtf16, 'UTF-8', 'UTF-16LE');
                }
            } elseif ($chunkName === 'segm') {
                if (strlen($chunkData) >= 20) {
                    $fileEntry['offset'] = unpack('P', substr($chunkData, 4, 8))[1];
                    $fileEntry['size'] = unpack('P', substr($chunkData, 12, 8))[1];
                }
            }
        }

        return $fileEntry;
    }

    // ── Stream Helpers ──────────────────────────────────────────────────

    /**
     * Open an XP3 archive for binary reading.
     *
     * @param string $inputXp3 Path to the archive.
     * @return resource File stream handle.
     * @throws RuntimeException If the file cannot be opened.
     */
    private function openArchiveStream(string $inputXp3)
    {
        if (!is_readable($inputXp3)) {
            throw new RuntimeException("Cannot open XP3 file: $inputXp3");
        }

        $stream = fopen($inputXp3, 'rb');
        if ($stream === false) {
            throw new RuntimeException("Failed to open XP3 archive stream: $inputXp3");
        }

        return $stream;
    }

    /**
     * Read and validate the XP3 header signature from a stream.
     *
     * @param resource $stream Open file stream positioned at the start.
     * @throws RuntimeException If the signature does not match.
     */
    private function validateSignature($stream): void
    {
        $header = fread($stream, self::SIGNATURE_LENGTH);
        if ($header !== self::XP3_SIGNATURE) {
            throw new RuntimeException("Invalid XP3 file signature.");
        }
    }

    /**
     * Read the 64-bit index offset value from the stream.
     *
     * @param resource $stream Open file stream positioned after the signature.
     * @return int Index byte offset.
     */
    private function readIndexOffset($stream): int
    {
        $data = fread($stream, self::INDEX_OFFSET_LENGTH);
        return unpack('P', $data)[1];
    }

    /**
     * Read and decompress (if needed) the index payload from the stream.
     *
     * @param resource $stream      Open file stream.
     * @param int      $indexOffset  Byte position of the index.
     * @return string Decompressed index data.
     * @throws RuntimeException On decompression failures.
     */
    private function readIndexPayload($stream, int $indexOffset): string
    {
        fseek($stream, $indexOffset);
        $isCompressed = ord(fread($stream, 1)) === 1;

        if ($isCompressed) {
            $uncompressedSize = unpack('P', fread($stream, 8))[1];
            $compressedData = '';
            while (!feof($stream)) {
                $compressedData .= fread($stream, self::BUFFER_SIZE);
            }
            $indexData = gzuncompress($compressedData);
            if ($indexData === false) {
                throw new RuntimeException("Index decompression failed.");
            }
            if (strlen($indexData) !== $uncompressedSize) {
                throw new RuntimeException("Index decompression size mismatch.");
            }
            return $indexData;
        }

        $indexData = '';
        while (!feof($stream)) {
            $indexData .= fread($stream, self::BUFFER_SIZE);
        }
        return $indexData;
    }

    // ── Utility Helpers ─────────────────────────────────────────────────

    /**
     * Compute the Adler-32 checksum for a file.
     *
     * @param string $filePath Path to the file.
     * @return int Adler-32 checksum as unsigned 32-bit integer.
     */
    private function computeAdler32(string $filePath): int
    {
        $ctx = hash_init('adler32');
        $stream = fopen($filePath, 'rb');
        if ($stream === false) {
            return 0;
        }
        while (!feof($stream)) {
            $chunk = fread($stream, self::BUFFER_SIZE);
            if ($chunk !== false && $chunk !== '') {
                hash_update($ctx, $chunk);
            }
        }
        fclose($stream);

        $hex = hash_final($ctx);
        return (int) hexdec($hex);
    }

    /**
     * Format a byte count into a human-readable string.
     *
     * @param int $bytes Byte count.
     * @return string Formatted string (e.g. "3.14 MB").
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $idx = 0;
        $size = (float) $bytes;
        while ($size >= 1024 && $idx < count($units) - 1) {
            $size /= 1024;
            $idx++;
        }
        return round($size, 2) . ' ' . $units[$idx];
    }

    /**
     * Ensure a directory exists, creating it recursively if needed.
     *
     * @param string $dir Directory path.
     * @throws RuntimeException If directory creation fails.
     */
    private function ensureDirectory(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException("Failed to create directory: $dir");
        }
    }
}
