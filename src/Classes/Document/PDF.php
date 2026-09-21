<?php

declare(strict_types=1);

/**
 * License - Onetone Framework
 *
 * @copyright  Copyright (C) Onetoneframework (tactics6655@gmail.com)
 * @license    AGPL 3.0
 */

namespace Clover\Classes\Document;

use RuntimeException;
use InvalidArgumentException;
use function chr;
use function strlen;
use function count;
use function is_readable;
use function is_dir;
use function mkdir;
use function file_get_contents;
use function file_put_contents;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function dirname;
use function copy;
use function uniqid;
use function gzuncompress;
use function gzcompress;
use function substr;
use function trim;
use function rtrim;
use function strtolower;
use function stripos;
use function strpos;
use function strncmp;
use function preg_replace;
use function preg_replace_callback;
use function mb_convert_encoding;
use function pack;
use function crc32;
use function octdec;
use function abs;
use function array_unique;
use function array_values;
use function array_map;
use function array_filter;
use function array_merge;
use function array_count_values;
use function arsort;
use function explode;
use function implode;
use function str_word_count;
use function filesize;
use function filemtime;
use function md5_file;
use function sha1_file;
use function sprintf;
use function round;
use function max;
use function min;
use function in_array;
use function str_contains;

/**
 * PDF Document Utility Class
 *
 * Provides advanced utilities for extracting and manipulating PDF data
 * including text, images, metadata, structural information, validation,
 * and security analysis — all without external library dependencies.
 */
class PDF
{
    /** @var string PDF magic bytes */
    private const PDF_MAGIC = '%PDF-';

    /** @var float Points-to-millimeters conversion factor */
    private const POINTS_TO_MM = 0.352778;

    /** @var float Points-to-inches conversion factor */
    private const POINTS_TO_INCHES = 1.0 / 72.0;

    // ── Validation & Identification ─────────────────────────────────────

    /**
     * Validate whether a file is a structurally sound PDF.
     *
     * Checks the header magic bytes and verifies the presence of an
     * %%EOF trailer marker, which is required by the PDF specification.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if the file has valid PDF structure.
     */
    public function isValidPdf(string $pdfFile): bool
    {
        if (!is_readable($pdfFile)) {
            return false;
        }

        $header = file_get_contents($pdfFile, false, null, 0, 10);
        if ($header === false || strncmp($header, self::PDF_MAGIC, 5) !== 0) {
            return false;
        }

        // Check for %%EOF marker in the last 1KB
        $size = filesize($pdfFile);
        $tailLen = min($size, 1024);
        $tail = file_get_contents($pdfFile, false, null, $size - $tailLen, $tailLen);

        return $tail !== false && str_contains($tail, '%%EOF');
    }

    /**
     * Get the PDF version string (e.g. "1.4", "1.7", "2.0").
     *
     * Reads the header comment which always starts with %PDF-X.Y.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Version string, or null if not found.
     */
    public function getVersion(string $pdfFile): ?string
    {
        $data = $this->getFileContent($pdfFile, 1024);
        if (preg_match('/%PDF-(\d+\.\d+)/i', $data, $m)) {
            return $m[1];
        }
        return null;
    }

    /**
     * Get the file size in bytes.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int File size in bytes.
     * @throws RuntimeException If the file is not readable.
     */
    public function getFileSize(string $pdfFile): int
    {
        if (!is_readable($pdfFile)) {
            throw new RuntimeException("PDF not readable: $pdfFile");
        }
        return filesize($pdfFile);
    }

    /**
     * Get a human-readable file size string (e.g. "2.4 MB").
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string Formatted size string with appropriate unit.
     * @throws RuntimeException If the file is not readable.
     */
    public function getFileSizeFormatted(string $pdfFile): string
    {
        $bytes = $this->getFileSize($pdfFile);
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
     * Compute a hash digest for the PDF file.
     *
     * Useful for integrity verification and deduplication.
     *
     * @param string $pdfFile  Path to the PDF file.
     * @param string $algo     Hash algorithm: 'md5' or 'sha1'.
     * @return string Hexadecimal hash digest.
     * @throws InvalidArgumentException If an unsupported algorithm is specified.
     * @throws RuntimeException If the file is not readable.
     */
    public function getFileHash(string $pdfFile, string $algo = 'md5'): string
    {
        if (!is_readable($pdfFile)) {
            throw new RuntimeException("PDF not readable: $pdfFile");
        }
        return match ($algo) {
            'md5' => md5_file($pdfFile),
            'sha1' => sha1_file($pdfFile),
            default => throw new InvalidArgumentException("Unsupported hash algorithm: $algo (use 'md5' or 'sha1')"),
        };
    }

    /**
     * Get the last-modified timestamp of the PDF file on disk.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Unix timestamp of the last modification.
     * @throws RuntimeException If the file is not readable.
     */
    public function getFileModifiedTime(string $pdfFile): int
    {
        if (!is_readable($pdfFile)) {
            throw new RuntimeException("PDF not readable: $pdfFile");
        }
        return filemtime($pdfFile);
    }

    // ── Security & Encryption ───────────────────────────────────────────

    /**
     * Check if the PDF is encrypted (password-protected).
     *
     * Looks for the /Encrypt dictionary reference in the trailer.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if the document is encrypted.
     */
    public function isEncrypted(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        return preg_match('/\/Encrypt\s+\d+\s+\d+\s+R/i', $data) === 1;
    }

    /**
     * Check if the PDF contains embedded JavaScript.
     *
     * JavaScript in PDFs can pose security risks, as it may execute
     * arbitrary code when the document is opened.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if JavaScript actions are present.
     */
    public function hasJavaScript(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        return preg_match('/\/JS\s+|\/JavaScript\s+/i', $data) === 1;
    }

    /**
     * Check if the PDF contains a digital signature.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if a /Sig field type is present.
     */
    public function hasDigitalSignature(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        return preg_match('/\/Type\s*\/Sig\b|\/FT\s*\/Sig\b/i', $data) === 1;
    }

    /**
     * Determine the PDF permission flags from the /Encrypt dictionary.
     *
     * Returns the raw integer /P value which encodes printing, copying,
     * modification, and annotation permissions as a bitmask.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int|null The permission flags integer, or null if not encrypted.
     */
    public function getPermissionFlags(string $pdfFile): ?int
    {
        $data = $this->getFileContent($pdfFile);
        if (preg_match('/\/P\s+(-?\d+)/i', $data, $m)) {
            return (int) $m[1];
        }
        return null;
    }

    /**
     * Decode the permission bitmask into human-readable boolean flags.
     *
     * The flags follow the PDF specification Table 22 (PDF 1.7).
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array{
     *     print: bool,
     *     modify: bool,
     *     copy: bool,
     *     annotate: bool,
     *     fillForms: bool,
     *     extractAccessibility: bool,
     *     assemble: bool,
     *     printHighQuality: bool
     * }|null Decoded permissions, or null if not encrypted.
     */
    public function getPermissions(string $pdfFile): ?array
    {
        $p = $this->getPermissionFlags($pdfFile);
        if ($p === null) {
            return null;
        }

        return [
            'print' => (bool) ($p & (1 << 2)),
            'modify' => (bool) ($p & (1 << 3)),
            'copy' => (bool) ($p & (1 << 4)),
            'annotate' => (bool) ($p & (1 << 5)),
            'fillForms' => (bool) ($p & (1 << 8)),
            'extractAccessibility' => (bool) ($p & (1 << 9)),
            'assemble' => (bool) ($p & (1 << 10)),
            'printHighQuality' => (bool) ($p & (1 << 11)),
        ];
    }

    /**
     * Run a basic security audit on the PDF.
     *
     * Aggregates several checks into a single summary that can be used
     * for automated screening of untrusted documents.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array{
     *     isValid: bool,
     *     isEncrypted: bool,
     *     hasJavaScript: bool,
     *     hasDigitalSignature: bool,
     *     hasFormFields: bool,
     *     hasEmbeddedFiles: bool,
     *     hasURIActions: bool
     * }
     */
    public function securityAudit(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        return [
            'isValid' => $this->isValidPdf($pdfFile),
            'isEncrypted' => preg_match('/\/Encrypt\s+\d+\s+\d+\s+R/i', $data) === 1,
            'hasJavaScript' => preg_match('/\/JS\s+|\/JavaScript\s+/i', $data) === 1,
            'hasDigitalSignature' => preg_match('/\/Type\s*\/Sig\b|\/FT\s*\/Sig\b/i', $data) === 1,
            'hasFormFields' => preg_match('/\/AcroForm\s*<<|\/AcroForm\s+\d+\s+\d+\s+R/i', $data) === 1,
            'hasEmbeddedFiles' => preg_match('/\/Type\s*\/Filespec\b|\/Type\s*\/EmbeddedFile\b/i', $data) === 1,
            'hasURIActions' => preg_match('/\/URI\s*\(/i', $data) === 1,
        ];
    }

    // ── Page & Structure ────────────────────────────────────────────────

    /**
     * Return the approximate page count by examining /Type /Page objects.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Number of pages found.
     */
    public function getPageCount(string $pdfFile): int
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/\/Type\s*\/Page\b/i', $data, $m);
        return count($m[0]);
    }

    /**
     * Return array of page object identifiers found in the PDF.
     *
     * Each entry is an indirect reference string like "5 0 R" or a
     * synthetic "page_N" label when individual objects cannot be matched.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string> List of page identifiers.
     */
    public function getPages(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $pages = [];

        if (preg_match_all('/(\d+)\s+(\d+)\s+obj\s*(<<.*?\/Type\s*\/Page\b.*?>>)\s*endobj/s', $data, $m, PREG_SET_ORDER)) {
            foreach ($m as $entry) {
                $pages[] = (int) $entry[1] . ' ' . (int) $entry[2] . ' R';
            }
        } elseif (preg_match('/\/Count\s+(\d+)/i', $data, $c)) {
            $pagesCount = (int) $c[1];
            for ($i = 0; $i < $pagesCount; $i++) {
                $pages[] = 'page_' . ($i + 1);
            }
        }

        return $pages;
    }

    /**
     * Get the first MediaBox dimensions (represents default page size).
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array{x0: float, y0: float, x1: float, y1: float, width: float, height: float}|null
     */
    public function getMediaBox(string $pdfFile): ?array
    {
        $data = $this->getFileContent($pdfFile);
        if (preg_match('/\/MediaBox\s*\[\s*([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s+([\d\.\-]+)\s*\]/i', $data, $m)) {
            return [
                'x0' => (float) $m[1],
                'y0' => (float) $m[2],
                'x1' => (float) $m[3],
                'y1' => (float) $m[4],
                'width' => abs((float) $m[3] - (float) $m[1]),
                'height' => abs((float) $m[4] - (float) $m[2]),
            ];
        }
        return null;
    }

    /**
     * Retrieve per-page MediaBox dimensions for all pages.
     *
     * Falls back to the global MediaBox when a page-level box is absent.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, array{page: int, width: float, height: float}>
     */
    public function getPageSizes(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $sizes = [];

        preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        $pageIdx = 0;
        foreach ($objs as $o) {
            $obj = $o[1];
            if (!preg_match('/\/Type\s*\/Page\b/i', $obj)) {
                continue;
            }
            $pageIdx++;

            if (preg_match('/\/MediaBox\s*\[\s*([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)\s+([\d.\-]+)/i', $obj, $m)) {
                $sizes[] = [
                    'page' => $pageIdx,
                    'width' => abs((float) $m[3] - (float) $m[1]),
                    'height' => abs((float) $m[4] - (float) $m[2]),
                ];
            }
        }

        // Fallback to global MediaBox
        if (empty($sizes)) {
            $global = $this->getMediaBox($pdfFile);
            if ($global !== null) {
                $count = $this->getPageCount($pdfFile);
                for ($i = 1; $i <= $count; $i++) {
                    $sizes[] = [
                        'page' => $i,
                        'width' => $global['width'],
                        'height' => $global['height'],
                    ];
                }
            }
        }

        return $sizes;
    }

    /**
     * Get page size in millimeters using the first MediaBox.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array{width_mm: float, height_mm: float}|null
     */
    public function getPageSizeMm(string $pdfFile): ?array
    {
        $box = $this->getMediaBox($pdfFile);
        if ($box === null) {
            return null;
        }
        return [
            'width_mm' => round($box['width'] * self::POINTS_TO_MM, 2),
            'height_mm' => round($box['height'] * self::POINTS_TO_MM, 2),
        ];
    }

    /**
     * Get page size in inches using the first MediaBox.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array{width_in: float, height_in: float}|null
     */
    public function getPageSizeInches(string $pdfFile): ?array
    {
        $box = $this->getMediaBox($pdfFile);
        if ($box === null) {
            return null;
        }
        return [
            'width_in' => round($box['width'] * self::POINTS_TO_INCHES, 2),
            'height_in' => round($box['height'] * self::POINTS_TO_INCHES, 2),
        ];
    }

    /**
     * Detect the page rotation angle for each page.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, int> Map of page number => rotation degrees (0, 90, 180, 270).
     */
    public function getPageRotations(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $rotations = [];

        preg_match_all('/\d+\s+\d+\s+obj(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        $pageIdx = 0;
        foreach ($objs as $o) {
            $obj = $o[1];
            if (!preg_match('/\/Type\s*\/Page\b/i', $obj)) {
                continue;
            }
            $pageIdx++;
            $rotation = 0;
            if (preg_match('/\/Rotate\s+(\d+)/i', $obj, $m)) {
                $rotation = (int) $m[1];
            }
            $rotations[$pageIdx] = $rotation;
        }

        return $rotations;
    }

    /**
     * Check if the PDF is linearized (optimized for fast web viewing).
     *
     * Linearized PDFs can display the first page before the entire
     * file is downloaded, improving perceived performance.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if the linearization dictionary is present.
     */
    public function isLinearized(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile, 4096);
        return preg_match('/\/Linearized\s+/i', $data) === 1;
    }

    /**
     * Check if the PDF is a tagged PDF (structured for accessibility).
     *
     * Tagged PDFs include a logical structure tree that enables screen
     * readers and text reflow on mobile devices.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if the /MarkInfo dictionary with /Marked true is found.
     */
    public function isTagged(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        return preg_match('/\/MarkInfo\s*<<[^>]*\/Marked\s+true/i', $data) === 1;
    }

    /**
     * Detect PDF/A conformance level if declared in the XMP metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Conformance string (e.g. "1b", "2a"), or null.
     */
    public function getPdfAConformance(string $pdfFile): ?string
    {
        $xmp = $this->getXmpMetadata($pdfFile);
        if ($xmp === null) {
            return null;
        }

        $part = null;
        $conformance = null;

        if (preg_match('/pdfaid:part[>\s]*(\d+)/i', $xmp, $m)) {
            $part = $m[1];
        }
        if (preg_match('/pdfaid:conformance[>\s]*([a-zA-Z]+)/i', $xmp, $m)) {
            $conformance = strtolower($m[1]);
        }

        if ($part !== null) {
            return $part . ($conformance ?? '');
        }
        return null;
    }

    // ── Metadata ────────────────────────────────────────────────────────

    /**
     * Extract basic metadata from the PDF Info dictionary.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<string, string> Associative array of metadata fields.
     */
    public function getMetadata(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $meta = [];

        if (preg_match('/\/Info\s+(\d+\s+\d+\s+R)/i', $data, $m)) {
            $ref = preg_quote($m[1], '/');
            if (preg_match('/' . $ref . '\s*(<<.*?>>)\s*endobj/s', $data, $obj)) {
                $meta = $this->parseDictionaryString($obj[1]);
            }
        } else {
            if (preg_match('/(\d+\s+\d+\s+obj)\s*<<[^>]*\/Title\s*\(.*?\).*?>>/is', $data, $obj)) {
                $meta = $this->parseDictionaryString($obj[0]);
            }
        }

        return $meta;
    }

    /**
     * Get the document title from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Title string, or null if not set.
     */
    public function getTitle(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Title'] ?? null;
    }

    /**
     * Get the document author from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Author string, or null if not set.
     */
    public function getAuthor(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Author'] ?? null;
    }

    /**
     * Get the document subject from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Subject string, or null if not set.
     */
    public function getSubject(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Subject'] ?? null;
    }

    /**
     * Get the document keywords from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Keywords string, or null if not set.
     */
    public function getKeywords(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Keywords'] ?? null;
    }

    /**
     * Get the producer application from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Producer string, or null if not set.
     */
    public function getProducer(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Producer'] ?? null;
    }

    /**
     * Get the creator application from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Creator string, or null if not set.
     */
    public function getCreator(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['Creator'] ?? null;
    }

    /**
     * Retrieve the creation date string from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Raw date string (e.g. "D:20230101120000+09'00'"), or null.
     */
    public function getCreationDate(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['CreationDate'] ?? null;
    }

    /**
     * Retrieve the last modification date string from PDF metadata.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Raw date string, or null.
     */
    public function getModificationDate(string $pdfFile): ?string
    {
        return $this->getMetadata($pdfFile)['ModDate'] ?? null;
    }

    /**
     * Extract the raw XMP metadata XML block from the PDF.
     *
     * XMP (Extensible Metadata Platform) carries richer metadata than
     * the Info dictionary, including Dublin Core and PDF/A conformance.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string|null Raw XMP XML string, or null if not found.
     */
    public function getXmpMetadata(string $pdfFile): ?string
    {
        $data = $this->getFileContent($pdfFile);
        if (preg_match('/<\?xpacket\s[^>]*?>(.*?)<\?xpacket\s+end/is', $data, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Produce a comprehensive summary of the document in a single call.
     *
     * Combines metadata, page info, font listing, image count, security
     * flags, and structural properties into one associative array.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<string, mixed> Summary information.
     */
    public function getSummary(string $pdfFile): array
    {
        $meta = $this->getMetadata($pdfFile);
        $box = $this->getMediaBox($pdfFile);

        return [
            'version' => $this->getVersion($pdfFile),
            'pageCount' => $this->getPageCount($pdfFile),
            'fileSize' => $this->getFileSizeFormatted($pdfFile),
            'fileSizeBytes' => $this->getFileSize($pdfFile),
            'metadata' => $meta,
            'mediaBox' => $box,
            'pageSizeMm' => $this->getPageSizeMm($pdfFile),
            'isEncrypted' => $this->isEncrypted($pdfFile),
            'isLinearized' => $this->isLinearized($pdfFile),
            'isTagged' => $this->isTagged($pdfFile),
            'hasJavaScript' => $this->hasJavaScript($pdfFile),
            'hasFormFields' => $this->hasFormFields($pdfFile),
            'hasSignature' => $this->hasDigitalSignature($pdfFile),
            'imageCount' => $this->getImageCount($pdfFile),
            'fontCount' => count($this->listFonts($pdfFile)),
            'pdfaConformance' => $this->getPdfAConformance($pdfFile),
        ];
    }

    // ── Text Extraction & Analysis ──────────────────────────────────────

    /**
     * Simple text extraction from BT/ET text blocks.
     *
     * Handles both literal string "(text)" and hex string "<AABB>" formats.
     * Not suitable for complex PDFs with CID/ToUnicode font mappings.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return string Extracted plain text.
     */
    public function extractText(string $pdfFile): string
    {
        $data = $this->getFileContent($pdfFile);
        $text = '';

        if (preg_match_all('/BT(.*?)ET/s', $data, $blocks)) {
            foreach ($blocks[1] as $blk) {
                if (preg_match_all('/\((.*?)\)/s', $blk, $strs)) {
                    foreach ($strs[1] as $s) {
                        $text .= $this->decodePdfString($s) . ' ';
                    }
                }
                if (preg_match_all('/<([0-9A-Fa-f]+)>/', $blk, $hexs)) {
                    foreach ($hexs[1] as $h) {
                        $text .= $this->hexToString($h) . ' ';
                    }
                }
            }
        }

        return preg_replace('/\s+/', ' ', trim($text));
    }

    /**
     * Search extracted text for a keyword (case-insensitive).
     *
     * @param string $pdfFile Path to the PDF file.
     * @param string $keyword Search term.
     * @return bool True if found.
     */
    public function containsText(string $pdfFile, string $keyword): bool
    {
        return stripos($this->extractText($pdfFile), $keyword) !== false;
    }

    /**
     * Count occurrences of a keyword in the extracted text (case-insensitive).
     *
     * @param string $pdfFile Path to the PDF file.
     * @param string $keyword Search term.
     * @return int Number of occurrences.
     */
    public function countTextOccurrences(string $pdfFile, string $keyword): int
    {
        $text = strtolower($this->extractText($pdfFile));
        $keyword = strtolower($keyword);
        if ($keyword === '') {
            return 0;
        }
        return substr_count($text, $keyword);
    }

    /**
     * Get the approximate word count from extracted text.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Word count.
     */
    public function getWordCount(string $pdfFile): int
    {
        $text = $this->extractText($pdfFile);
        if (trim($text) === '') {
            return 0;
        }
        return str_word_count($text);
    }

    /**
     * Get the character count (excluding spaces) from extracted text.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Character count.
     */
    public function getCharacterCount(string $pdfFile): int
    {
        $text = $this->extractText($pdfFile);
        return strlen(preg_replace('/\s/', '', $text));
    }

    /**
     * Extract all unique words from the document and count their frequency.
     *
     * @param string $pdfFile   Path to the PDF file.
     * @param int    $minLength Minimum word length to include.
     * @return array<string, int> Word => frequency, sorted descending.
     */
    public function getWordFrequency(string $pdfFile, int $minLength = 3): array
    {
        $text = strtolower($this->extractText($pdfFile));
        $words = preg_split('/[^a-z0-9\'-]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn(string $w) => strlen($w) >= $minLength);

        $freq = array_count_values($words);
        arsort($freq);
        return $freq;
    }

    // ── Fonts ───────────────────────────────────────────────────────────

    /**
     * List fonts declared in the PDF resources.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<string, string> Font name => reference or definition.
     */
    public function listFonts(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $fonts = [];

        if (preg_match_all('/\/Font\s*<<(.+?)>>/is', $data, $matches)) {
            foreach ($matches[1] as $block) {
                if (preg_match_all('/\/([A-Za-z0-9]+)\s+((\d+\s+\d+\s+R)|<<.*?>>)/s', $block, $fmatches, PREG_SET_ORDER)) {
                    foreach ($fmatches as $fm) {
                        $fonts[$fm[1]] = trim($fm[2]);
                    }
                }
            }
        }

        return $fonts;
    }

    /**
     * Check whether all fonts used in the PDF are embedded.
     *
     * Returns false if any font references an external resource
     * without a FontDescriptor stream.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if all referenced fonts are embedded.
     */
    public function areFontsEmbedded(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/(\d+\s+\d+\s+obj)(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        $hasFonts = false;
        $allEmbedded = true;

        foreach ($objs as $o) {
            $obj = $o[2];
            if (!preg_match('/\/Type\s*\/Font\b/i', $obj)) {
                continue;
            }
            if (preg_match('/\/Subtype\s*\/(Type0|Type3)\b/i', $obj)) {
                $hasFonts = true;
                continue;
            }

            $hasFonts = true;
            if (!preg_match('/\/FontDescriptor\s+\d+\s+\d+\s+R/i', $obj)) {
                $allEmbedded = false;
            }
        }

        return $hasFonts && $allEmbedded;
    }

    /**
     * Extract the base font names used in the document.
     *
     * Returns a deduplicated list of /BaseFont values.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string> List of base font names.
     */
    public function getBaseFontNames(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/\/BaseFont\s*\/([^\s\/\]>]+)/i', $data, $m);
        return array_values(array_unique($m[1] ?? []));
    }

    // ── Images ──────────────────────────────────────────────────────────

    /**
     * Return the number of embedded images in the PDF.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Image count.
     */
    public function getImageCount(string $pdfFile): int
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/\/Subtype\s*\/Image\b/i', $data, $m);
        return count($m[0]);
    }

    /**
     * Get metadata about each embedded image (dimensions, filter, color space).
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, array{width: int|null, height: int|null, bpc: int, colorspace: string, filter: string}>
     */
    public function getImageInfo(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/(\d+\s+\d+\s+obj)(.*?endobj)/is', $data, $objs, PREG_SET_ORDER);
        $images = [];

        foreach ($objs as $o) {
            $objText = $o[2];
            if (!preg_match('/\/Subtype\s*\/Image/i', $objText)) {
                continue;
            }
            $images[] = [
                'width' => $this->getInt($objText, '/Width'),
                'height' => $this->getInt($objText, '/Height'),
                'bpc' => $this->getInt($objText, '/BitsPerComponent') ?: 8,
                'colorspace' => $this->getName($objText, '/ColorSpace') ?: 'Unknown',
                'filter' => $this->getFilter($objText),
            ];
        }

        return $images;
    }

    /**
     * Extract images from a PDF file into the specified output directory.
     *
     * Supports DCT (JPEG), JPX (JPEG 2000), and Flate-compressed streams.
     * Flate streams are converted to minimal PNG format when possible.
     *
     * @param string $pdfFile Path to the PDF file.
     * @param string $outDir  Directory to write extracted images.
     * @return array<int, string> Array of saved image file paths.
     */
    public function extractImages(string $pdfFile, string $outDir): array
    {
        $data = $this->getFileContent($pdfFile);
        $this->ensureDirectory($outDir);

        preg_match_all('/(\d+\s+\d+\s+obj)(.*?endobj)/is', $data, $objs, PREG_SET_ORDER);
        $saved = [];

        foreach ($objs as $o) {
            $objText = $o[2];
            if (!preg_match('/\/Subtype\s*\/Image/i', $objText)) {
                continue;
            }

            $width = $this->getInt($objText, '/Width');
            $height = $this->getInt($objText, '/Height');
            $bpc = $this->getInt($objText, '/BitsPerComponent') ?: 8;
            $colorspace = $this->getName($objText, '/ColorSpace') ?: 'DeviceRGB';
            $filter = $this->getFilter($objText);

            if (!preg_match('/stream\s*\r?\n(.*?)\r?\nendstream/s', $objText, $m)) {
                continue;
            }

            $stream = $this->trimStream($m[1]);
            $id = uniqid('img_', true);
            $filterLower = strtolower($filter);

            if (stripos($filterLower, 'dctdecode') !== false || stripos($filterLower, 'dct') !== false) {
                $file = "$outDir/{$id}.jpg";
                file_put_contents($file, $stream);
                $saved[] = $file;
            } elseif (stripos($filterLower, 'jpxdecode') !== false || stripos($filterLower, 'jpx') !== false) {
                $file = "$outDir/{$id}.jp2";
                file_put_contents($file, $stream);
                $saved[] = $file;
            } elseif (stripos($filterLower, 'flatedecode') !== false || stripos($filterLower, 'flate') !== false) {
                $png = $this->flateToPng($stream, $width, $height, $bpc, $colorspace);
                $ext = $png !== false ? '.png' : '.bin';
                $file = "$outDir/{$id}" . $ext;
                file_put_contents($file, $png !== false ? $png : $stream);
                $saved[] = $file;
            } else {
                $file = "$outDir/{$id}.bin";
                file_put_contents($file, $stream);
                $saved[] = $file;
            }
        }

        return $saved;
    }

    // ── Colors ──────────────────────────────────────────────────────────

    /**
     * Return all unique color space names referenced in the document.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string>
     */
    public function getColorSpaces(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/\/ColorSpace\s*\/([A-Za-z][A-Za-z0-9]*)/i', $data, $m);
        return array_values(array_unique($m[1]));
    }

    // ── Forms ───────────────────────────────────────────────────────────

    /**
     * Check if the PDF contains AcroForm fields.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return bool True if an AcroForm is defined.
     */
    public function hasFormFields(string $pdfFile): bool
    {
        $data = $this->getFileContent($pdfFile);
        return preg_match('/\/AcroForm\s*<<|\/AcroForm\s+\d+\s+\d+\s+R/i', $data) === 1;
    }

    /**
     * Extract AcroForm field names and their current values.
     *
     * Supports literal strings, hex strings, and name values.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<string, string> Field name => current value.
     */
    public function getFormFields(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $fields = [];

        preg_match_all('/(\d+\s+\d+\s+obj)(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        foreach ($objs as $o) {
            $obj = $o[2];
            if (!preg_match('/\/FT\s*\//i', $obj)) {
                continue;
            }

            $name = null;
            $value = '';

            if (preg_match('/\/T\s*\((.*?)\)/s', $obj, $m)) {
                $name = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/T\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $name = $this->hexToString($m[1]);
            }

            if ($name === null) {
                continue;
            }

            if (preg_match('/\/V\s*\((.*?)\)/s', $obj, $m)) {
                $value = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/V\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $value = $this->hexToString($m[1]);
            } elseif (preg_match('/\/V\s*\/([^\s\/\]>]+)/i', $obj, $m)) {
                $value = $m[1]; // Boolean/Name value e.g. /Yes, /Off
            }

            $fields[$name] = $value;
        }

        return $fields;
    }

    /**
     * Get a list of form field names only (without values).
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string> List of field names.
     */
    public function getFormFieldNames(string $pdfFile): array
    {
        return array_keys($this->getFormFields($pdfFile));
    }

    // ── Bookmarks & Navigation ──────────────────────────────────────────

    /**
     * Extract PDF bookmarks (outline / table of contents).
     *
     * Returns a flat list of outline entries with titles and optional
     * destination page references.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, array{title: string, page: string|null}>
     */
    public function getBookmarks(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $bookmarks = [];

        preg_match_all('/(\d+\s+\d+\s+obj)(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        foreach ($objs as $o) {
            $obj = $o[2];
            if (!preg_match('/\/Title\s*[\(<]/i', $obj)) {
                continue;
            }
            if (!preg_match('/\/Parent\s+\d+\s+\d+\s+R/i', $obj)) {
                continue;
            }

            $title = null;
            if (preg_match('/\/Title\s*\((.*?)\)/s', $obj, $m)) {
                $title = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/Title\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $title = $this->hexToString($m[1]);
            }

            if ($title === null) {
                continue;
            }

            $page = null;
            if (preg_match('/\/Dest\s*\[(\d+\s+\d+\s+R)/i', $obj, $m)) {
                $page = $m[1];
            }

            $bookmarks[] = ['title' => $title, 'page' => $page];
        }

        return $bookmarks;
    }

    /**
     * Extract all hyperlinks (URI actions) from PDF annotations.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string> Deduplicated list of URLs.
     */
    public function getLinks(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $links = [];

        preg_match_all('/\/URI\s*\((.*?)\)/is', $data, $m);
        foreach ($m[1] as $uri) {
            $decoded = $this->decodePdfString($uri);
            if ($decoded !== '') {
                $links[] = $decoded;
            }
        }

        return array_values(array_unique($links));
    }

    // ── Annotations ─────────────────────────────────────────────────────

    /**
     * Extract text annotations (comments, sticky notes, highlights, etc.).
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, array{subtype: string, contents: string, author: string|null}>
     */
    public function getAnnotations(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $annotations = [];

        preg_match_all('/(\d+\s+\d+\s+obj)(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        foreach ($objs as $o) {
            $obj = $o[2];
            if (!preg_match('/\/Type\s*\/Annot\b/i', $obj)) {
                continue;
            }

            $subtype = 'Unknown';
            $contents = '';
            $author = null;

            if (preg_match('/\/Subtype\s*\/([A-Za-z]+)/i', $obj, $m)) {
                $subtype = $m[1];
            }
            if (preg_match('/\/Contents\s*\((.*?)\)/s', $obj, $m)) {
                $contents = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/Contents\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $contents = $this->hexToString($m[1]);
            }
            if (preg_match('/\/T\s*\((.*?)\)/s', $obj, $m)) {
                $author = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/T\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $author = $this->hexToString($m[1]);
            }

            $annotations[] = [
                'subtype' => $subtype,
                'contents' => $contents,
                'author' => $author,
            ];
        }

        return $annotations;
    }

    /**
     * Count annotations by subtype.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<string, int> Subtype => count map.
     */
    public function countAnnotationsByType(string $pdfFile): array
    {
        $annotations = $this->getAnnotations($pdfFile);
        $counts = [];
        foreach ($annotations as $a) {
            $type = $a['subtype'];
            $counts[$type] = ($counts[$type] ?? 0) + 1;
        }
        return $counts;
    }

    // ── Attachments ─────────────────────────────────────────────────────

    /**
     * List embedded file attachments in the PDF.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, array{name: string, description: string|null}>
     */
    public function getFileAttachments(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $attachments = [];

        preg_match_all('/(\d+\s+\d+\s+obj)(.*?)endobj/is', $data, $objs, PREG_SET_ORDER);

        foreach ($objs as $o) {
            $obj = $o[2];
            if (!preg_match('/\/Type\s*\/Filespec\b/i', $obj) && !preg_match('/\/Type\s*\/EmbeddedFile\b/i', $obj)) {
                continue;
            }

            $name = null;
            $desc = null;

            if (preg_match('/\/F\s*\((.*?)\)/s', $obj, $m)) {
                $name = $this->decodePdfString($m[1]);
            } elseif (preg_match('/\/F\s*<([0-9A-Fa-f]+)>/i', $obj, $m)) {
                $name = $this->hexToString($m[1]);
            }

            if ($name === null) {
                continue;
            }

            if (preg_match('/\/Desc\s*\((.*?)\)/s', $obj, $m)) {
                $desc = $this->decodePdfString($m[1]);
            }

            $attachments[] = ['name' => $name, 'description' => $desc];
        }

        return $attachments;
    }

    // ── Object Analysis ─────────────────────────────────────────────────

    /**
     * Count all indirect objects in the PDF.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return int Total object count.
     */
    public function getObjectCount(string $pdfFile): int
    {
        $data = $this->getFileContent($pdfFile);
        preg_match_all('/\d+\s+\d+\s+obj\b/i', $data, $m);
        return count($m[0]);
    }

    /**
     * List all stream filters used in the document.
     *
     * Useful for understanding the compression schemes in use.
     *
     * @param string $pdfFile Path to the PDF file.
     * @return array<int, string> Deduplicated list of filter names.
     */
    public function getStreamFilters(string $pdfFile): array
    {
        $data = $this->getFileContent($pdfFile);
        $filters = [];

        preg_match_all('/\/Filter\s*\/([A-Za-z0-9]+)/i', $data, $m);
        $filters = array_merge($filters, $m[1] ?? []);

        preg_match_all('/\/Filter\s*\[([^\]]+)\]/i', $data, $m);
        foreach ($m[1] ?? [] as $arr) {
            preg_match_all('/\/([A-Za-z0-9]+)/', $arr, $sub);
            $filters = array_merge($filters, $sub[1] ?? []);
        }

        return array_values(array_unique($filters));
    }

    // ── File Operations ─────────────────────────────────────────────────

    /**
     * Save a copy of the PDF to another path.
     *
     * @param string $pdfFile Source PDF path.
     * @param string $dest    Destination path.
     * @return bool True on success.
     * @throws RuntimeException If the source is not readable.
     */
    public function saveAs(string $pdfFile, string $dest): bool
    {
        if (!is_readable($pdfFile)) {
            throw new RuntimeException("PDF not readable: $pdfFile");
        }
        $this->ensureDirectory(dirname($dest));
        return copy($pdfFile, $dest);
    }

    // ── Private Helpers ─────────────────────────────────────────────────

    /**
     * Read file content with readability guard.
     *
     * @param string   $pdfFile   Path to the PDF file.
     * @param int|null $maxLength Maximum bytes to read, or null for entire file.
     * @return string Raw file content.
     * @throws RuntimeException If the file cannot be read.
     */
    private function getFileContent(string $pdfFile, ?int $maxLength = null): string
    {
        if (!is_readable($pdfFile)) {
            throw new RuntimeException("PDF not readable: $pdfFile");
        }
        $data = $maxLength
            ? file_get_contents($pdfFile, false, null, 0, $maxLength)
            : file_get_contents($pdfFile);
        if ($data === false) {
            throw new RuntimeException("Failed to read PDF payload: $pdfFile");
        }
        return $data;
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

    /**
     * Parse common metadata keys from a PDF dictionary string.
     *
     * @param string $dict Raw dictionary text.
     * @return array<string, string> Extracted key-value pairs.
     */
    private function parseDictionaryString(string $dict): array
    {
        $meta = [];
        $keys = ['Title', 'Author', 'Subject', 'Keywords', 'Creator', 'Producer', 'CreationDate', 'ModDate'];

        foreach ($keys as $k) {
            $qk = preg_quote($k, '/');
            if (preg_match('/\/' . $qk . '\s*\((.*?)\)/s', $dict, $val)) {
                $meta[$k] = $this->decodePdfString($val[1]);
            } elseif (preg_match('/\/' . $qk . '\s*<([0-9A-Fa-f]+)>/s', $dict, $val2)) {
                $meta[$k] = $this->hexToString($val2[1]);
            }
        }

        return $meta;
    }

    /**
     * Extract an integer value for a PDF dictionary key.
     *
     * @param string $text Raw object text.
     * @param string $key  Key name (e.g. "/Width").
     * @return int|null
     */
    private function getInt(string $text, string $key): ?int
    {
        $q = preg_quote($key, '/');
        return preg_match('/' . $q . '\s+(\d+)/i', $text, $m) ? (int) $m[1] : null;
    }

    /**
     * Extract a name value for a PDF dictionary key.
     *
     * @param string $text Raw object text.
     * @param string $key  Key name (e.g. "/ColorSpace").
     * @return string|null
     */
    private function getName(string $text, string $key): ?string
    {
        $q = preg_quote($key, '/');
        return preg_match('/' . $q . '\s*\/([A-Za-z0-9]+)/i', $text, $m) ? $m[1] : null;
    }

    /**
     * Extract the /Filter value (supports both name and array syntax).
     *
     * @param string $text Raw object text.
     * @return string Filter string, or empty if none.
     */
    private function getFilter(string $text): string
    {
        if (preg_match('/\/Filter\s*\[([^\]]+)\]/i', $text, $m)) {
            return $m[1];
        }
        if (preg_match('/\/Filter\s*\/([A-Za-z0-9]+)/i', $text, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Trim leading/trailing newlines from a raw stream body.
     *
     * @param string $s Stream content.
     * @return string Trimmed content.
     */
    private function trimStream(string $s): string
    {
        if (strncmp($s, "\r\n", 2) === 0) {
            $s = substr($s, 2);
        } elseif (strncmp($s, "\n", 1) === 0 || strncmp($s, "\r", 1) === 0) {
            $s = substr($s, 1);
        }
        return rtrim($s, "\r\n");
    }

    /**
     * Convert a Flate-compressed image stream into a minimal valid PNG.
     *
     * @param string      $stream     Raw or compressed stream data.
     * @param int|null    $w          Image width in pixels.
     * @param int|null    $h          Image height in pixels.
     * @param int         $bpc        Bits per component.
     * @param string      $colorspace PDF color space name.
     * @return string|false PNG binary string, or false on failure.
     */
    private function flateToPng(string $stream, ?int $w, ?int $h, int $bpc, string $colorspace): string|false
    {
        if (!$w || !$h || $bpc !== 8) {
            return false;
        }

        $raw = @gzuncompress($stream);
        if ($raw === false) {
            $raw = $stream;
        }

        $colorType = null;
        $channels = null;
        if (stripos($colorspace, 'DeviceRGB') !== false) {
            $colorType = 2;
            $channels = 3;
        } elseif (stripos($colorspace, 'DeviceGray') !== false) {
            $colorType = 0;
            $channels = 1;
        } else {
            return false;
        }

        $scanlineLen = $channels * $w;
        if (strlen($raw) < $scanlineLen * $h) {
            return false;
        }

        $img = '';
        for ($y = 0; $y < $h; $y++) {
            $img .= chr(0) . substr($raw, $y * $scanlineLen, $scanlineLen);
        }

        $idat = gzcompress($img);

        $png = "\x89PNG\r\n\x1a\n";
        $png .= $this->pngChunk('IHDR', pack('N2C5', $w, $h, $bpc, $colorType, 0, 0, 0));
        $png .= $this->pngChunk('IDAT', $idat);
        $png .= $this->pngChunk('IEND', '');
        return $png;
    }

    /**
     * Build a single PNG chunk (length + type + data + CRC32).
     *
     * @param string $type 4-character chunk type.
     * @param string $data Chunk payload.
     * @return string Binary PNG chunk.
     */
    private function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)) . $type . $data . pack('N', crc32($type . $data));
    }

    /**
     * Decode a PDF literal string with escape sequences.
     *
     * @param string $s Escaped string content.
     * @return string Decoded string.
     */
    private function decodePdfString(string $s): string
    {
        $s = preg_replace_callback('/\\\\([nrtbf\\\\()])/', function ($m) {
            $map = ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0C", '\\' => '\\', '(' => '(', ')' => ')'];
            return $map[$m[1]] ?? $m[1];
        }, $s);

        return preg_replace_callback('/\\\\([0-7]{1,3})/', function ($m) {
            return chr(octdec($m[1]));
        }, $s);
    }

    /**
     * Convert a PDF hex string to text, handling UTF-16BE BOM detection.
     *
     * @param string $hex Hexadecimal string (without angle brackets).
     * @return string Decoded text.
     */
    private function hexToString(string $hex): string
    {
        $hex = preg_replace('/\s+/', '', $hex);
        if (strlen($hex) % 2 === 1) {
            $hex .= '0';
        }

        $bin = pack('H*', $hex);
        if (strncmp($bin, "\xFE\xFF", 2) === 0) {
            return mb_convert_encoding(substr($bin, 2), 'UTF-8', 'UTF-16BE');
        }

        return $bin;
    }
}
